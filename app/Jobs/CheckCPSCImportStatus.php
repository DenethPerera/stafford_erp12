<?php

namespace App\Jobs;

use App\Models\StaffordQualification;
use App\Services\EFilingApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckCPSCImportStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(EFilingApiService $apiService): void
    {
        // Get all unique import IDs that are currently syncing
        $importIds = StaffordQualification::where('api_sync_status', 'syncing')
            ->whereNotNull('api_import_id')
            ->distinct()
            ->pluck('api_import_id');

        foreach ($importIds as $importId) {
            try {
                // Safety check: if import has been syncing for > 24 hours, mark as timed out
                $oldestRecord = StaffordQualification::where('api_import_id', $importId)
                    ->where('api_sync_status', 'syncing')
                    ->oldest('updated_at')
                    ->first();

                if ($oldestRecord && $oldestRecord->updated_at && $oldestRecord->updated_at->lt(now()->subHours(24))) {
                    Log::warning('CPSC Import Status Check Timed Out after 24 Hours', ['importId' => $importId]);
                    StaffordQualification::where('api_import_id', $importId)
                        ->update([
                            'api_sync_status' => 'failed',
                            'api_sync_message' => 'CPSC import status check timed out after 24 hours in pending state.'
                        ]);
                    continue;
                }

                $statusResponse = $apiService->getImportStatus($importId);
                
                // CPSC V4 returns "importStatus": "Complete" / "Uploading…" / "Failed"
                $status = $statusResponse['importStatus'] ?? $statusResponse['status'] ?? 'pending';

                if (strtolower($status) === 'complete' || strtolower($status) === 'done') {
                    // It's complete, retrieve log to check validations
                    $logResponse = $apiService->getImportLog($importId, true);
                    
                    $hasErrors = false;
                    $errorMessages = [];

                    if (isset($logResponse['statusMessage']) && str_starts_with(strtolower($logResponse['statusMessage']), 'failed')) {
                        $hasErrors = true;
                        $errorMessages[] = $logResponse['statusMessage'];
                    } elseif (!empty($logResponse['productList'])) {
                        foreach ($logResponse['productList'] as $item) {
                            $rawErrors = $item['validations']['errors'] ?? (is_array($item['validations'] ?? null) ? $item['validations'] : []);
                            if (!empty($rawErrors)) {
                                $hasErrors = true;
                                foreach ($rawErrors as $val) {
                                    $errorMessages[] = ($val['errorMessage'] ?? 'Validation Error') . (!empty($val['errorField']) ? ' (' . $val['errorField'] . ')' : '');
                                }
                            }
                        }
                    }

                    if ($hasErrors) {
                        $failureMsg = implode('; ', array_unique($errorMessages));
                        Log::error('CPSC Import Completed with Item Validation Errors', [
                            'importId' => $importId,
                            'errors' => $failureMsg
                        ]);
                        StaffordQualification::where('api_import_id', $importId)
                            ->update([
                                'api_sync_status' => 'failed',
                                'api_sync_message' => $failureMsg
                            ]);
                    } else {
                        Log::info('CPSC Import Completed Successfully', ['importId' => $importId]);
                        StaffordQualification::where('api_import_id', $importId)
                            ->update([
                                'api_sync_status' => 'synced',
                                'api_sync_message' => 'Successfully synced and certified with CPSC.'
                            ]);
                    }
                } elseif (in_array(strtolower($status), ['failed', 'error', 'upload failed']) || str_starts_with(strtolower($status), 'failed')) {
                    $failureMsg = $statusResponse['statusMessage'] ?? $statusResponse['message'] ?? 'Import failed on CPSC side.';
                    Log::error('CPSC Import Failed on CPSC side', [
                        'importId' => $importId,
                        'status' => $status,
                        'response' => $statusResponse
                    ]);
                    StaffordQualification::where('api_import_id', $importId)
                        ->update([
                            'api_sync_status' => 'failed',
                            'api_sync_message' => $failureMsg
                        ]);
                }

            } catch (\Exception $e) {
                Log::error('CPSC Import Status Check Failed', [
                    'importId' => $importId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
