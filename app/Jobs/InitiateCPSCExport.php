<?php

namespace App\Jobs;

use App\Services\EFilingApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InitiateCPSCExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function handle(EFilingApiService $apiService): void
    {
        try {
            Log::info('Initiating CPSC async export...', ['filters' => $this->filters]);
            
            $response = $apiService->exportAsync($this->filters);
            
            if (isset($response['exportId'])) {
                $exportId = $response['exportId'];
                Log::info('CPSC export initiated successfully', ['exportId' => $exportId]);
                
                // Dispatch the polling job to check on this export's status
                PollCPSCExportStatus::dispatch($exportId);
            } else {
                Log::error('Failed to initiate CPSC export', ['response' => $response]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during CPSC export initiation', ['error' => $e->getMessage()]);
        }
    }
}
