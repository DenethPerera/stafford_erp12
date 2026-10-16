<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FactorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $factories = [
            [
                'name' => 'Colombo Main Plant',
                'location' => 'Colombo, Sri Lanka',
                'registration_no' => 'REG-1001',
                'contact_person' => 'John Doe',
                'contact_phone' => '+94 112 345 678',
                'contact_email' => 'colombo@example.com',
                'status' => 'active',
            ],
            [
                'name' => 'Kandy Branch',
                'location' => 'Kandy, Sri Lanka',
                'registration_no' => 'REG-1002',
                'contact_person' => 'Jane Smith',
                'contact_phone' => '+94 812 345 678',
                'contact_email' => 'kandy@example.com',
                'status' => 'active',
            ],
        ];

        foreach ($factories as $factory) {
            \App\Models\Factory::updateOrCreate(
                ['name' => $factory['name']],
                $factory
            );
        }
    }
}
