<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CpscService;
use Exception;

class TestCpsc extends Command
{
    protected $signature = 'cpsc:test';
    protected $description = 'Test the CPSC getCollections API';

    public function handle(CpscService $cpscService)
    {
        $this->info("Checking credentials and testing CPSC API...");

        try {
            $result = $cpscService->getCollections();
            $this->info("SUCCESS! Response received:");
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {
            $this->error("FAILED: " . $e->getMessage());
        }
    }
}