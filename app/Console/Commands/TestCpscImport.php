<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StaffordQualification;
use App\Http\Resources\EFilingCertificateResource;
use App\Services\CpscService;
use Exception;

class TestCpscImport extends Command
{
    protected $signature = 'cpsc:test-import {id}';
    protected $description = 'Execute a single-certificate test POST /import call to CPSC test collection without modifying database records.';

    public function handle(CpscService $cpscService)
    {
        $id = $this->argument('id');
        
        $cert = StaffordQualification::find($id);
        if (!$cert) {
            $this->error("Record not found: No StaffordQualification record exists with ID {$id}.");
            return 1;
        }

        // 1. Run Pre-flight Validation Rules (matching SyncCertificatesToCPSC job)
        $validTypes = ['GTIN', 'UPC', 'SKU', 'Model #', 'Serial #', 'Registered #', 'Alternate ID'];
        $errors = [];

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
                $this->error("Pre-Flight Validation Failed for Record ID {$id}:");
                foreach ($errors as $err) {
                    $this->line(" - {$err}");
                }
                return 1;
            }

        // 2. Generate Payload using EFilingCertificateResource
        try {
            $resourceData = (new EFilingCertificateResource($cert))->resolve();
        } catch (Exception $e) {
            $this->error("Payload Generation Failed: " . $e->getMessage());
            return 1;
        }

        // 3. Display Short Summary
        $this->info("==========================================");
        $this->info("CPSC Single-Certificate Import Test");
        $this->info("==========================================");
        $this->line("Database ID        : " . $cert->id);
        $this->line("Version ID         : " . ($cert->version_id ?? 'N/A'));
        $this->line("Primary Product ID : " . ($cert->primary_product_id ?? 'N/A'));
        $this->line("Certificate Type   : " . ($cert->cert_type ?? 'N/A'));
        $this->line("Product Name       : " . ($cert->product_name ?? 'N/A'));
        $this->info("------------------------------------------");
        $this->line("Certifier ID       : StaffordERP");
        $this->line("Collection ID      : 22effa9e-02ab-49d7-a576-1efd50a8c7cb");
        $doCertify = config('services.cpsc.do_certify', true);
        $this->line("doCertify          : " . ($doCertify ? 'true' : 'false'));
        $this->info("==========================================");

        // 4. Call CPSC POST /import API endpoint for this single certificate
        $certifierId = 'StaffordERP';
        $collectionId = '22effa9e-02ab-49d7-a576-1efd50a8c7cb';

        $this->info("Dispatching POST /import request to CPSC...");

        try {
            $response = $cpscService->importProducts([$resourceData], $doCertify, $certifierId, $collectionId);

            $this->info("\nCPSC API Response Received:");
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $importId = $response['importId'] ?? null;
            $importStatus = $response['importStatus'] ?? $response['statusMessage'] ?? null;

            $this->info("\n------------------------------------------");
            if ($importId) {
                $this->info("Status     : ACCEPTED FOR IMPORT");
                $this->info("Import ID  : " . $importId);
                $this->line("CPSC Message: " . $importStatus);
            } else {
                $this->warn("Status     : REJECTED / NO IMPORT ID");
                $this->line("CPSC Message: " . ($importStatus ?? 'No importId returned.'));
            }
            $this->info("------------------------------------------");

            return 0;
        } catch (Exception $e) {
            $this->error("\nHTTP / API Request Failed:");
            $this->error($e->getMessage());
            return 1;
        }
    }
}
