<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StaffordQualification;
use App\Http\Resources\EFilingCertificateResource;
use Exception;

class TestCpscPayload extends Command
{
    protected $signature = 'cpsc:test-payload {id}';
    protected $description = 'Generate and output offline CPSC eFiling API v4.0 JSON payload for a StaffordQualification record.';

    public function handle()
    {
        $id = $this->argument('id');
        
        $record = StaffordQualification::find($id);
        if (!$record) {
            $this->error("Record not found: No StaffordQualification record exists with ID {$id}.");
            return 1;
        }

        try {
            $resourceData = (new EFilingCertificateResource($record))->resolve();
            $payload = [
                'productList' => [$resourceData]
            ];

            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        } catch (Exception $e) {
            $this->error("Transformation Error: " . $e->getMessage());
            return 1;
        }
    }
}
