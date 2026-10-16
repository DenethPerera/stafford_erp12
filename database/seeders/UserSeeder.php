<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('stafford');

        $lab = User::updateOrCreate(['email' => 'lab@example.com'], [
            'name' => 'Lab User',
            'password' => Hash::make('password'),
        ]);
        $lab->assignRole('lab');
    }
}
