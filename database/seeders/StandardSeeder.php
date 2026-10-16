<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StandardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $standards = [
            [
                'name' => 'ISO 9001:2015',
                'code' => 'ISO9001',
                'description' => 'Quality Management System',
                'validity_period_months' => 36,
            ],
            [
                'name' => 'ISO 14001:2015',
                'code' => 'ISO14001',
                'description' => 'Environmental Management System',
                'validity_period_months' => 36,
            ],
            [
                'name' => 'ISO 45001:2018',
                'code' => 'ISO45001',
                'description' => 'Occupational Health and Safety',
                'validity_period_months' => 36,
            ],
            [
                'name' => 'SLS 1330',
                'code' => 'SLS1330',
                'description' => 'Sri Lanka Standard',
                'validity_period_months' => 12,
            ],
        ];

        foreach ($standards as $standardData) {
            $standard = \App\Models\Standard::updateOrCreate(
                ['code' => $standardData['code']],
                $standardData
            );
            
            // Create default stages for testing depending on the standard if it doesn't have any
            if ($standard->wasRecentlyCreated || $standard->standardStages()->count() === 0) {
                if (str_contains($standard->code, 'ISO')) {
                    \App\Models\StandardStage::insert([
                        ['standard_id' => $standard->id, 'name' => 'Pre Audit', 'order_sequence' => 1, 'description' => 'Initial document check.'],
                        ['standard_id' => $standard->id, 'name' => 'Secondary Audit', 'order_sequence' => 2, 'description' => 'On-site secondary review.'],
                        ['standard_id' => $standard->id, 'name' => 'Final Audit', 'order_sequence' => 3, 'description' => 'Final certification audit.'],
                    ]);
                } else {
                    \App\Models\StandardStage::insert([
                        ['standard_id' => $standard->id, 'name' => 'Document Review', 'order_sequence' => 1, 'description' => 'Review of application documents.'],
                        ['standard_id' => $standard->id, 'name' => 'Final Audit', 'order_sequence' => 2, 'description' => 'On-site final check.'],
                    ]);
                }
            }
        }
    }
}
