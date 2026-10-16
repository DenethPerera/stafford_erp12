<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CpscToken;
use Carbon\Carbon;

class CpscTokenSeeder extends Seeder
{
    public function run(): void
    {
        $token = env('CPSC_API_TOKEN');
        $secret = env('CPSC_API_SECRET');
        $expiresAt = env('CPSC_TOKEN_EXPIRES_AT');

        if (!$token || !$secret || !$expiresAt) {
            $this->command->error("Missing CPSC values in your .env file! Check CPSC_API_TOKEN, CPSC_API_SECRET, and CPSC_TOKEN_EXPIRES_AT.");
            return;
        }

        CpscToken::create([
            'token'      => $token,
            'api_secret' => $secret,
            'expires_at' => Carbon::parse($expiresAt),
        ]);

        $this->command->info("CPSC Token successfully saved to database!");
    }
}