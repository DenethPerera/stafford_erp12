<?php

namespace App\Jobs;

use App\Models\StaffordQualification;
use App\Services\EFilingApiService;
use App\Http\Resources\EFilingCertificateResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCertificatesToCPSC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $certificateIds;

    public function __construct(array $certificateIds)
    {
        $this->certificateIds = $certificateIds;
    }

    public function handle(EFilingApiService $apiService): void
    {
        $certificates = StaffordQualification::whereIn('id', $this->certificateIds)->get();
        
        if ($certificates->isEmpty()) {
            return;
        }

        // Pre-flight check: validate mandatory fields before attempting CPSC import
        $validTypes = ['GTIN', 'UPC', 'SKU', 'Model #', 'Serial #', 'Registered #', 'Alternate ID'];
        foreach ($certificates as $cert) {
            $errors = [];

            // Structural Pre-flight Checks
            if (empty(trim((string)$cert->version_id))) {
                $errors[] = 'Version ID is missing.';
            }
            if (empty(trim((string)$cert->primary_product_id))) {
                $errors[] = 'Primary Product ID is missing.';
            }
            if (empty($cert->product_id_type) || !in_array($cert->product_id_type, $validTypes)) {
                $errors[] = 'Primary Product ID Type is invalid or missing.';
            }
            if (empty($cert->cert_type) || !in_array(strtoupper($cert->cert_type), ['GCC', 'CPC'])) {
                $errors[] = 'Certificate Type must be GCC or CPC.';
            }
            if (empty(trim((string)$cert->product_name))) {
                $errors[] = 'Product Name is missing.';
            }
            if (empty($cert->manufacture_date)) {
                $errors[] = 'Manufacture Date is missing.';
            }
            if (empty($cert->last_test_date)) {
                $errors[] = 'Last Test Date is missing.';
            }

            // Point of Contact (POC) Pre-flight Check per CPSC Spec Page 23
            $pocType = $cert->poc_type ?? $cert->point_of_contact ?? 'Importer';
            if ($pocType === 'Other') {
                $missingPocFields = [];
                if (empty(trim((string)$cert->poc_address_line1))) $missingPocFields[] = 'Address Line 1';
                if (empty(trim((string)$cert->poc_city))) $missingPocFields[] = 'City';
                if (empty(trim((string)$cert->poc_country))) $missingPocFields[] = 'Country';
                if (empty(trim((string)$cert->poc_phone))) $missingPocFields[] = 'Phone';
                if (empty(trim((string)$cert->poc_email))) $missingPocFields[] = 'Email';

                if (!empty($missingPocFields)) {
                    $errors[] = "Point of Contact is set to 'Other' but is missing required contact fields: " . implode(', ', $missingPocFields) . ".";
                }
            }

            // Laboratories Structural Check
            $masterLab = $cert->lab;
            $tests = $cert->tests ?? [];
            if (empty($tests)) {
                $errors[] = 'No Lab Tests attached. Please assign and complete at least one Lab Test.';
            } else {
                foreach ($tests as $index => $lt) {
                    $labName = $masterLab?->name ?? $lt['labName'] ?? ('Lab #' . ($index + 1));
                    $citations = $lt['citations'] ?? [];
                    if (empty($citations)) {
                        $errors[] = "No Applicable Citations added for laboratory '{$labName}'.";
                    }
                }
            }

            if (!empty($errors)) {
                $cert->update([
                    'api_sync_status' => 'failed',
                    'api_sync_message' => 'Sync Error: ' . implode(' ', $errors)
                ]);
                continue;
            }

            $validCertificateIds[] = $cert->id;
        }

        if (empty($validCertificateIds)) {
            return;
        }

        $validCertificates = $certificates->whereIn('id', $validCertificateIds);

        // Set status to syncing initially for valid certificates
        StaffordQualification::whereIn('id', $validCertificateIds)
            ->update([
                'api_sync_status' => 'syncing',
                'api_sync_message' => 'Preparing payload for CPSC...'
            ]);

        // Map to JSON resource array
        $productLists = EFilingCertificateResource::collection($validCertificates)->resolve();

        // Log the payload being sent
        Log::info('CPSC Sync Payload', ['payload' => json_encode($productLists)]);

        try {
            // Attempt to import with doCertify (defaulting to true per CPSC v4.0 spec)
            $doCertify = config('services.cpsc.do_certify', true);
            $response = $apiService->import($productLists, $doCertify);
            
            // Log response
            Log::info('CPSC Sync Initiated', ['response' => $response]);

            // Save importId or handle error response
            if (isset($response['importId'])) {
                $importId = $response['importId'];
                StaffordQualification::whereIn('id', $validCertificateIds)
                    ->update([
                        'api_import_id' => $importId,
                        'api_sync_status' => 'syncing',
                        'api_sync_message' => 'Upload initiated with CPSC. Import ID: ' . $importId
                    ]);

                // Finite polling loop: maximum 10 attempts with 3 seconds delay
                $maxAttempts = 10;
                $attempt = 0;
                $terminalStateReached = false;

                while ($attempt < $maxAttempts && !$terminalStateReached) {
                    $attempt++;
                    sleep(3);

                    try {
                        $statusResp = $apiService->getImportStatus($importId);
                        $importStatus = strtolower($statusResp['importStatus'] ?? $statusResp['status'] ?? '');

                        if (in_array($importStatus, ['complete', 'done'])) {
                            $terminalStateReached = true;
                            $logResp = $apiService->getImportLog($importId, true);
                            $hasErrors = false;
                            $errorMsg = 'Successfully synced to CPSC.';

                            if (isset($logResp['statusMessage']) && str_starts_with(strtolower($logResp['statusMessage']), 'failed')) {
                                $hasErrors = true;
                                $errorMsg = $logResp['statusMessage'];
                            } elseif (!empty($logResp['productList'])) {
                                foreach ($logResp['productList'] as $item) {
                                    $rawErrors = $item['validations']['errors'] ?? (is_array($item['validations'] ?? null) ? $item['validations'] : []);
                                    if (!empty($rawErrors)) {
                                        $hasErrors = true;
                                        $validationErrors = array_map(function($v) {
                                            return ($v['errorMessage'] ?? 'Validation Error') . (!empty($v['errorField']) ? ' (' . $v['errorField'] . ')' : '');
                                        }, $rawErrors);
                                        $errorMsg = implode('; ', $validationErrors);
                                        break;
                                    }
                                }
                            }

                            StaffordQualification::whereIn('id', $validCertificateIds)
                                ->update([
                                    'api_sync_status' => $hasErrors ? 'failed' : 'synced',
                                    'api_sync_message' => $errorMsg
                                ]);

                        } elseif (in_array(strtolower($importStatus), ['failed', 'error', 'upload failed']) || str_starts_with(strtolower($importStatus), 'failed')) {
                            $terminalStateReached = true;
                            $errorMsg = $statusResp['statusMessage'] ?? $statusResp['message'] ?? 'Import failed on CPSC side.';
                            StaffordQualification::whereIn('id', $validCertificateIds)
                                ->update([
                                    'api_sync_status' => 'failed',
                                    'api_sync_message' => $errorMsg
                                ]);
                        } else {
                            StaffordQualification::whereIn('id', $validCertificateIds)
                                ->update([
                                    'api_sync_status' => 'syncing',
                                    'api_sync_message' => "Processing on CPSC side... (Attempt {$attempt}/{$maxAttempts})"
                                ]);
                        }
                    } catch (\Exception $statusEx) {
                        Log::warning('CPSC Import Status Check Warning', ['warning' => $statusEx->getMessage()]);
                    }
                }

                if (!$terminalStateReached) {
                    StaffordQualification::whereIn('id', $validCertificateIds)
                        ->update([
                            'api_sync_status' => 'syncing',
                            'api_sync_message' => "Upload initiated with CPSC (Import ID: {$importId}). Async processing pending background check."
                        ]);
                }

            } else {
                // Determine the best error message from the response
                $errorMessage = 'API did not return an importId.';
                
                if (isset($response['statusMessage'])) {
                    $errorMessage = $response['statusMessage'];
                    if (isset($response['importStatus'])) {
                        $errorMessage = $response['importStatus'] . ' (' . $errorMessage . ')';
                    }
                } elseif (isset($response['message'])) {
                    $errorMessage = $response['message'];
                }

                StaffordQualification::whereIn('id', $this->certificateIds)
                    ->update([
                        'api_sync_status' => 'failed',
                        'api_sync_message' => $errorMessage
                    ]);
            }
            
        } catch (\Exception $e) {
            Log::error('CPSC Sync Failed', ['error' => $e->getMessage()]);
            
            StaffordQualification::whereIn('id', $this->certificateIds)
                ->update([
                    'api_sync_status' => 'failed',
                    'api_sync_message' => $e->getMessage()
                ]);
        }
    }
}
