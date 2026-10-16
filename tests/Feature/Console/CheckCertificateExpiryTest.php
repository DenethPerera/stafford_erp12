<?php

namespace Tests\Feature\Console;

use App\Models\Certificate;
use App\Models\Factory as CompanyFactory;
use App\Models\Standard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckCertificateExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_near_expiry_certificates_as_expiring_soon(): void
    {
        $certificate = $this->createCertificate(now()->addDays(10)->toDateString(), Certificate::STATUS_VALID);

        $this->artisan('certificates:check-expiry', ['--threshold' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => Certificate::STATUS_EXPIRING_SOON,
        ]);
    }

    public function test_it_marks_past_due_certificates_as_expired(): void
    {
        $certificate = $this->createCertificate(now()->subDay()->toDateString(), Certificate::STATUS_VALID);

        $this->artisan('certificates:check-expiry', ['--threshold' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => Certificate::STATUS_EXPIRED,
        ]);
    }

    public function test_it_leaves_long_valid_certificates_unchanged(): void
    {
        $certificate = $this->createCertificate(now()->addDays(180)->toDateString(), Certificate::STATUS_VALID);

        $this->artisan('certificates:check-expiry', ['--threshold' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => Certificate::STATUS_VALID,
        ]);
    }

    private function createCertificate(string $expiryDate, string $status): Certificate
    {
        $factory = CompanyFactory::create([
            'name' => 'Test Factory',
            'location' => 'Test Location',
            'registration_no' => 'REG-'.uniqid(),
            'contact_person' => 'Test Person',
            'contact_phone' => '1234567890',
            'contact_email' => 'factory'.uniqid().'@example.com',
            'status' => 'active',
        ]);

        $standard = Standard::create([
            'name' => 'Test Standard '.uniqid(),
            'code' => 'STD-'.uniqid(),
            'issuing_body' => 'Test Body',
            'description' => 'Test standard',
            'validity_period_months' => 12,
            'status' => 'active',
        ]);

        return Certificate::create([
            'factory_id' => $factory->id,
            'standard_id' => $standard->id,
            'certificate_number' => 'CERT-'.uniqid(),
            'issue_date' => now()->subMonth()->toDateString(),
            'expiry_date' => $expiryDate,
            'status' => $status,
            'notes' => 'Created for testing',
        ]);
    }
}