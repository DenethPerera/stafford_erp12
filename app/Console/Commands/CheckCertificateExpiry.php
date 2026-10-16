<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Certificate;
use Carbon\Carbon;

class CheckCertificateExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:check-expiry {--threshold=90 : Days before expiry to warn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring or expired certificates and update their status.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thresholdDays = $this->option('threshold');
        $thresholdDate = Carbon::now()->addDays($thresholdDays)->startOfDay();
        $today = Carbon::now()->startOfDay();

        // Expiring Soon (less than threshold days, but not yet expired, and currently marked as valid)
        $expiringCertificates = Certificate::where('status', Certificate::STATUS_VALID)
            ->where('expiry_date', '<=', $thresholdDate)
            ->where('expiry_date', '>', $today)
            ->get();

        foreach ($expiringCertificates as $cert) {
            $cert->update(['status' => Certificate::STATUS_EXPIRING_SOON]);
            $this->info("Certificate #{$cert->id} marked as expiring soon.");
        }

        // Expired (expiry date passed, and not already marked expired or under renewal)
        $expiredCertificates = Certificate::whereIn('status', [Certificate::STATUS_VALID, Certificate::STATUS_EXPIRING_SOON])
            ->where('expiry_date', '<=', $today)
            ->get();

        foreach ($expiredCertificates as $cert) {
            $cert->update(['status' => Certificate::STATUS_EXPIRED]);
            $this->info("Certificate #{$cert->id} marked as expired.");
        }

        $this->info("Certificate expiry check completed. Expiring soon: {$expiringCertificates->count()}, Expired: {$expiredCertificates->count()}");
    }
}
