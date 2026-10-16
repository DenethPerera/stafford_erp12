<?php

namespace Tests\Feature\Console;

use App\Models\AuditStage;
use App\Models\Certificate;
use App\Models\Factory as CompanyFactory;
use App\Models\RenewalProcess;
use App\Models\Standard;
use App\Models\StandardStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RenewalProcessEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prevents_duplicate_renewals_for_the_same_certificate(): void
    {
        [$certificate] = $this->createRenewalFixture(Certificate::STATUS_EXPIRING_SOON, now()->addDays(10)->toDateString(), 1, false);

        $this->actingAs($this->makeAdminUser());

        $this->post(route('renewals.store', $certificate))->assertSessionHas('error');

        $this->assertDatabaseCount('renewal_processes', 1);
    }

    public function test_it_blocks_out_of_order_stage_updates(): void
    {
        [$certificate, $renewal, $firstAuditStage, $secondAuditStage] = $this->createRenewalFixture(Certificate::STATUS_EXPIRING_SOON, now()->addDays(10)->toDateString(), 2);

        $this->actingAs($this->makeAdminUser());

        $this->post(route('renewals.update_stage', $renewal), [
            'audit_stage_id' => $secondAuditStage->id,
            'result' => 'passed',
            'auditor_name' => 'Auditor',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('renewal_processes', [
            'id' => $renewal->id,
            'current_standard_stage_id' => $firstAuditStage->standard_stage_id,
            'status' => RenewalProcess::STATUS_IN_PROGRESS,
        ]);

        $this->assertDatabaseHas('audit_stages', [
            'id' => $secondAuditStage->id,
            'result' => AuditStage::RESULT_PENDING,
        ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => Certificate::STATUS_UNDER_RENEWAL,
        ]);
    }

    public function test_it_restores_certificate_status_when_renewal_is_rejected(): void
    {
        [$certificate, $renewal, $firstAuditStage] = $this->createRenewalFixture(Certificate::STATUS_EXPIRING_SOON, now()->addDays(10)->toDateString());

        $this->actingAs($this->makeAdminUser());

        $this->post(route('renewals.update_stage', $renewal), [
            'audit_stage_id' => $firstAuditStage->id,
            'result' => 'failed',
            'auditor_name' => 'Auditor',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('renewal_processes', [
            'id' => $renewal->id,
            'status' => RenewalProcess::STATUS_REJECTED,
        ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => Certificate::STATUS_EXPIRING_SOON,
        ]);
    }

    private function createRenewalFixture(string $certificateStatus, string $expiryDate, int $stageCount = 1, bool $markUnderRenewal = true): array
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

        $standardStages = collect();
        for ($index = 1; $index <= $stageCount; $index++) {
            $standardStages->push(StandardStage::create([
                'standard_id' => $standard->id,
                'name' => 'Stage '.$index,
                'order_sequence' => $index,
                'description' => 'Stage '.$index,
            ]));
        }

        $certificate = Certificate::create([
            'factory_id' => $factory->id,
            'standard_id' => $standard->id,
            'certificate_number' => 'CERT-'.uniqid(),
            'issue_date' => now()->subMonth()->toDateString(),
            'expiry_date' => $expiryDate,
            'status' => $certificateStatus,
            'notes' => 'Created for testing',
        ]);

        $renewal = RenewalProcess::create([
            'certificate_id' => $certificate->id,
            'current_standard_stage_id' => $standardStages->first()->id,
            'status' => RenewalProcess::STATUS_IN_PROGRESS,
            'started_at' => now()->toDateString(),
        ]);

        foreach ($standardStages as $standardStage) {
            AuditStage::create([
                'renewal_process_id' => $renewal->id,
                'standard_stage_id' => $standardStage->id,
                'result' => AuditStage::RESULT_PENDING,
            ]);
        }

        if ($markUnderRenewal) {
            $certificate->update(['status' => Certificate::STATUS_UNDER_RENEWAL]);
        }

        $auditStages = $renewal->auditStages()->orderBy('id')->get();

        return [$certificate, $renewal, $auditStages->first(), $auditStages->count() > 1 ? $auditStages->get(1) : null];
    }

    private function makeAdminUser()
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}