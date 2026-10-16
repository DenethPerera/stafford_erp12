<?php

namespace App\Jobs;

use App\Services\EFilingApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollCPSCExportStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exportId;

    // Retry configuration
    public $tries = 50;
    public $backoff = 60; // Wait 60 seconds before retrying

    public function __construct(string $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle(EFilingApiService $apiService): void
    {
        try {
            Log::info('Checking CPSC async export status...', ['exportId' => $this->exportId]);
            
            $response = $apiService->exportAsyncStatus($this->exportId);
            
            if (isset($response['exportStatus'])) {
                $status = strtolower($response['exportStatus']);
                Log::info('CPSC export status retrieved', ['exportId' => $this->exportId, 'status' => $status]);
                
                if ($status === 'completed') {
                    // Export is ready, dispatch job to process data
                    ProcessCPSCExportData::dispatch($this->exportId);
                } elseif (str_contains($status, 'failed')) {
                    // Export failed at CPSC end
                    Log::error('CPSC export failed', ['exportId' => $this->exportId, 'response' => $response]);
                } else {
                    // Still processing (e.g. "started", "downloading"), put job back on queue with delay
                    $this->release($this->backoff);
                }
            } else {
                Log::error('Failed to retrieve valid export status from CPSC', ['response' => $response]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during CPSC export status check', ['error' => $e->getMessage()]);
            // Re-throw so Laravel handles the retry logic if it's a network issue
            throw $e;
        }
    }
}
