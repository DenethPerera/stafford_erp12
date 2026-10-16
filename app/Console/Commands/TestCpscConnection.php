<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CpscService;
use Exception;

class TestCpscConnection extends Command
{
    protected $signature = 'cpsc:test-connection';
    protected $description = 'Test connectivity to CPSC eFiling API via read-only GET /getCollections endpoint.';

    public function handle(CpscService $cpscService)
    {
        $endpoint = 'getCollections';
        $this->info("Target Endpoint: GET /efiling/api/{$endpoint}");
        $this->info("Initiating read-only test call using native PHP cURL...");

        try {
            $response = $cpscService->getCollections();
            
            $this->info("HTTP Request Result: SUCCESS");
            $this->info("Response Status Code: " . ($response['statusCode'] ?? '200'));
            $this->info("Response Data:");
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        } catch (Exception $e) {
            $this->error("HTTP Request Result: FAILURE");
            $this->error("Error Message: " . $e->getMessage());
            return 1;
        }
    }
}
