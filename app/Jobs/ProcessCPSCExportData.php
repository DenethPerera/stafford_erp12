<?php

namespace App\Jobs;

use App\Services\EFilingApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCPSCExportData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exportId;

    public function __construct(string $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle(EFilingApiService $apiService): void
    {
        try {
            Log::info('Retrieving CPSC async export data...', ['exportId' => $this->exportId]);
            
            $response = $apiService->exportAsyncData($this->exportId);
            
            if (isset($response['productList'])) {
                $productList = $response['productList'];
                $count = count($productList);
                Log::info("CPSC export data retrieved successfully. $count certificates found.", ['exportId' => $this->exportId]);
                
                // Save the payload to the local disk safely for manual review before DB integration
                $fileName = "cpsc_exports/export_{$this->exportId}_" . time() . ".json";
                Storage::disk('local')->put($fileName, json_encode($response, JSON_PRETTY_PRINT));
                
                Log::info("CPSC export data saved to storage: $fileName");
                
                // Note: To automatically map and save to the database in the future,
                // you would iterate over $productList here and create/update StaffordQualification records.
                // foreach ($productList as $item) {
                //     $coreProduct = $item['coreProduct'] ?? [];
                //     $productMetadata = $item['productMetadata'] ?? [];
                //     // Save to DB...
                // }
                
            } else {
                Log::error('Failed to retrieve productList from CPSC export data', ['response' => $response]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during CPSC export data processing', ['error' => $e->getMessage()]);
        }
    }
}
