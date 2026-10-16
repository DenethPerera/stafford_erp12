<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckCPSCImportStatus;

class CheckCpscStatus extends Command
{
    protected $signature = 'cpsc:check-import-status';
    protected $description = 'Poll CPSC eFiling API for all pending syncing import IDs and update local certificate statuses.';

    public function handle()
    {
        $this->info("Checking CPSC import status for all pending records...");

        try {
            CheckCPSCImportStatus::dispatchSync();
            $this->info("CPSC import status check completed successfully.");
            return 0;
        } catch (\Exception $e) {
            $this->error("CPSC import status check failed: " . $e->getMessage());
            return 1;
        }
    }
}
