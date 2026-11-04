<?php

namespace Tests\Feature;

use App\Models\Forwarder;
use App\Models\ForwarderDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ForwarderDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        // Isolate these tests from the application's existing database.
        Schema::create('forwarders', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone');
            $table->string('cargo_security_certificate')->nullable();
            $table->string('container_inspection_checklist')->nullable();
            $table->string('consolidation_file')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_02_060000_create_forwarder_documents_table.php'))->up();
        (require database_path('migrations/2026_11_02_080000_add_shipment_id_to_forwarder_documents_table.php'))->up();
        Storage::fake('forwarders');
    }

    public function test_super_admin_can_delete_a_forwarder_and_its_documents(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));
        $forwarder = $this->createForwarder([
            'cargo_security_certificate' => 'cargo_security_certificate/certificate.pdf',
            'container_inspection_checklist' => 'container_inspection_checklist/checklist.pdf',
            'consolidation_file' => 'consolidation_file/consolidation.pdf',
        ]);
        $other = $this->createForwarder([
            'cargo_security_certificate' => 'cargo_security_certificate/other.pdf',
        ]);
        $newDocument = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'cargo_security_certificate' => $forwarder->id . '/cargo_security_certificate/new.pdf',
        ]);
        $anotherShipment = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0008',
            'consolidation_file' => $forwarder->id . '/consolidation_file/another-shipment.pdf',
        ]);
        $paths = [
            $forwarder->cargo_security_certificate,
            $forwarder->container_inspection_checklist,
            $forwarder->consolidation_file,
            $newDocument->cargo_security_certificate,
            $anotherShipment->consolidation_file,
        ];
        foreach (array_merge($paths, [$other->cargo_security_certificate]) as $path) {
            Storage::disk('forwarders')->put($path, 'Test document');
        }

        $this->get(route('forwarders.index'))
            ->assertOk()
            ->assertSee(route('forwarders.destroy', $forwarder))
            ->assertSee('Delete this forwarder and all its uploaded documents?');

        $this->delete(route('forwarders.destroy', $forwarder))
            ->assertRedirect(route('forwarders.index'))
            ->assertSessionHas('success', 'Forwarder deleted successfully.');

        $this->assertDatabaseMissing('forwarders', ['id' => $forwarder->id]);
        $this->assertDatabaseMissing('forwarder_documents', ['id' => $newDocument->id]);
        $this->assertDatabaseMissing('forwarder_documents', ['id' => $anotherShipment->id]);
        Storage::disk('forwarders')->assertMissing($paths);
        $this->assertDatabaseHas('forwarders', ['id' => $other->id]);
        Storage::disk('forwarders')->assertExists($other->cargo_security_certificate);
    }

    public function test_super_admin_can_delete_forwarders_with_no_documents_or_missing_files(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));

        foreach ([[], ['cargo_security_certificate' => 'cargo_security_certificate/missing.pdf']] as $attributes) {
            $forwarder = $this->createForwarder($attributes);

            $this->delete(route('forwarders.destroy', $forwarder))
                ->assertRedirect(route('forwarders.index'))
                ->assertSessionHas('success');

            $this->assertDatabaseMissing('forwarders', ['id' => $forwarder->id]);
        }
    }

    public function test_guests_and_other_roles_cannot_delete_forwarders(): void
    {
        $forwarder = $this->createForwarder([
            'cargo_security_certificate' => 'cargo_security_certificate/keep.pdf',
        ]);
        Storage::disk('forwarders')->put($forwarder->cargo_security_certificate, 'Keep this file');

        $this->delete(route('forwarders.destroy', $forwarder))
            ->assertRedirect(route('login'));

        foreach (['factory', 'lab', 'forwarder'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->delete(route('forwarders.destroy', $forwarder))->assertForbidden();
        }

        $this->assertDatabaseHas('forwarders', ['id' => $forwarder->id]);
        Storage::disk('forwarders')->assertExists($forwarder->cargo_security_certificate);
    }

    public function test_deleting_a_missing_forwarder_returns_not_found(): void
    {
        $this->actingAs($this->userWithRole('super_admin'));

        $this->delete(route('forwarders.destroy', 999))->assertNotFound();
    }

    public function test_admin_can_delete_one_shipment_document_row_without_deleting_the_company(): void
    {
        $forwarder = $this->createForwarder([
            'cargo_security_certificate' => 'legacy/company-cargo.pdf',
        ]);
        $otherForwarder = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'shipment-7/cargo.pdf',
            'container_inspection_checklist' => 'shipment-7/inspection.pdf',
            'consolidation_file' => 'shipment-7/consolidation.pdf',
        ]);
        $otherShipment = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0008',
            'cargo_security_certificate' => 'shipment-8/cargo.pdf',
        ]);
        $otherCompanyDocument = ForwarderDocument::create([
            'forwarder_id' => $otherForwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'other-company/cargo.pdf',
        ]);
        $deletedPaths = [
            $document->cargo_security_certificate,
            $document->container_inspection_checklist,
            $document->consolidation_file,
        ];
        $keptPaths = [
            $forwarder->cargo_security_certificate,
            $otherShipment->cargo_security_certificate,
            $otherCompanyDocument->cargo_security_certificate,
        ];
        foreach (array_merge($deletedPaths, $keptPaths) as $path) {
            Storage::disk('forwarders')->put($path, 'Test document');
        }
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('forwarders.index'))
            ->assertOk()
            ->assertSee('data-action="' . route('forwarder-documents.destroy', $document->id) . '"', false)
            ->assertSee('data-bs-target="#confirmModal"', false);

        $this->delete(route('forwarder-documents.destroy', $document->id))
            ->assertRedirect(route('forwarders.index'))
            ->assertSessionHas('success', 'Forwarder shipment documents deleted successfully.');

        $this->assertDatabaseMissing('forwarder_documents', ['id' => $document->id]);
        $this->assertDatabaseHas('forwarders', ['id' => $forwarder->id]);
        $this->assertDatabaseHas('forwarder_documents', ['id' => $otherShipment->id]);
        $this->assertDatabaseHas('forwarder_documents', ['id' => $otherCompanyDocument->id]);
        Storage::disk('forwarders')->assertMissing($deletedPaths);
        Storage::disk('forwarders')->assertExists($keptPaths);
    }

    public function test_document_rows_with_missing_or_no_files_can_be_deleted(): void
    {
        $forwarder = $this->createForwarder();
        $this->actingAs($this->userWithRole('super_admin'));

        foreach (['missing.pdf', null] as $index => $path) {
            $document = ForwarderDocument::create([
                'forwarder_id' => $forwarder->id,
                'shipment_id' => 'SHIP-' . $index,
                'cargo_security_certificate' => $path,
            ]);

            $this->delete(route('forwarder-documents.destroy', $document->id))
                ->assertRedirect(route('forwarders.index'))
                ->assertSessionHas('success');
            $this->assertDatabaseMissing('forwarder_documents', ['id' => $document->id]);
        }

        $this->assertDatabaseHas('forwarders', ['id' => $forwarder->id]);
        $this->delete(route('forwarder-documents.destroy', 999))->assertNotFound();
    }

    public function test_guests_and_other_roles_cannot_delete_shipment_document_rows(): void
    {
        $forwarder = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'cargo.pdf',
        ]);
        Storage::disk('forwarders')->put('cargo.pdf', 'Test document');

        $this->delete(route('forwarder-documents.destroy', $document->id))
            ->assertRedirect(route('login'));

        foreach (['factory', 'lab', 'forwarder'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->delete(route('forwarder-documents.destroy', $document->id))->assertForbidden();
        }

        $this->assertDatabaseHas('forwarder_documents', ['id' => $document->id]);
        Storage::disk('forwarders')->assertExists('cargo.pdf');
    }

    public function test_document_record_is_kept_if_a_file_cannot_be_deleted(): void
    {
        $forwarder = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'cargo.pdf',
        ]);
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('cargo.pdf')->andReturn(true);
        $disk->shouldReceive('delete')->once()->with('cargo.pdf')->andReturn(false);
        Storage::shouldReceive('disk')->once()->with('forwarders')->andReturn($disk);
        $this->actingAs($this->userWithRole('super_admin'));

        $this->delete(route('forwarder-documents.destroy', $document->id))
            ->assertRedirect(route('forwarders.index'))
            ->assertSessionHas('error', 'A forwarder document could not be deleted. Please try again.');

        $this->assertDatabaseHas('forwarder_documents', ['id' => $document->id]);
    }

    public function test_forwarder_can_delete_its_shipment_files_from_the_upload_page(): void
    {
        $forwarder = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'shipment-7/cargo.pdf',
            'container_inspection_checklist' => 'shipment-7/inspection.pdf',
            'consolidation_file' => 'shipment-7/consolidation.pdf',
        ]);
        $otherShipment = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0008',
            'cargo_security_certificate' => 'shipment-8/cargo.pdf',
        ]);
        $deletedPaths = [
            $document->cargo_security_certificate,
            $document->container_inspection_checklist,
            $document->consolidation_file,
        ];
        foreach (array_merge($deletedPaths, [$otherShipment->cargo_security_certificate]) as $path) {
            Storage::disk('forwarders')->put($path, 'Test document');
        }
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->get(route('forwarder.file-upload'))
            ->assertOk()
            ->assertSee('data-action="' . route('forwarder.documents.destroy', $document->id) . '"', false)
            ->assertSee('data-bs-target="#confirmModal"', false);

        $this->delete(route('forwarder.documents.destroy', $document->id))
            ->assertRedirect(route('forwarder.file-upload'))
            ->assertSessionHas('success', 'Forwarder shipment documents deleted successfully.');

        $this->assertDatabaseMissing('forwarder_documents', ['id' => $document->id]);
        $this->assertDatabaseHas('forwarders', ['id' => $forwarder->id]);
        $this->assertDatabaseHas('forwarder_documents', ['id' => $otherShipment->id]);
        Storage::disk('forwarders')->assertMissing($deletedPaths);
        Storage::disk('forwarders')->assertExists($otherShipment->cargo_security_certificate);
        $this->get(route('forwarder.file-upload'))
            ->assertOk()->assertDontSee('SHIP-0007')->assertSee('SHIP-0008');
    }

    public function test_forwarder_cannot_delete_another_company_document_or_delete_without_an_assignment(): void
    {
        $forwarder = $this->createForwarder();
        $other = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $other->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'other-company.pdf',
        ]);
        Storage::disk('forwarders')->put('other-company.pdf', 'Test document');

        foreach ([$forwarder->id, null, 999] as $assignment) {
            $this->actingAs($this->userWithRole('forwarder', $assignment));
            $this->delete(route('forwarder.documents.destroy', $document->id), [
                'forwarder_id' => $other->id,
            ])->assertForbidden();
        }

        $this->assertDatabaseHas('forwarder_documents', ['id' => $document->id]);
        Storage::disk('forwarders')->assertExists('other-company.pdf');
    }

    public function test_forwarder_delete_route_requires_a_forwarder_login(): void
    {
        $forwarder = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
        ]);

        $this->delete(route('forwarder.documents.destroy', $document->id))
            ->assertRedirect(route('forwarder.login'));

        foreach (['factory', 'lab', 'super_admin'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->delete(route('forwarder.documents.destroy', $document->id))->assertForbidden();
        }

        $this->assertDatabaseHas('forwarder_documents', ['id' => $document->id]);
    }

    public function test_forwarder_returns_to_its_upload_page_if_file_deletion_fails(): void
    {
        $forwarder = $this->createForwarder();
        $document = ForwarderDocument::create([
            'forwarder_id' => $forwarder->id,
            'shipment_id' => 'SHIP-0007',
            'cargo_security_certificate' => 'cargo.pdf',
        ]);
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('cargo.pdf')->andReturn(true);
        $disk->shouldReceive('delete')->once()->with('cargo.pdf')->andReturn(false);
        Storage::shouldReceive('disk')->once()->with('forwarders')->andReturn($disk);
        $this->actingAs($this->userWithRole('forwarder', $forwarder->id));

        $this->delete(route('forwarder.documents.destroy', $document->id))
            ->assertRedirect(route('forwarder.file-upload'))
            ->assertSessionHas('error', 'A forwarder document could not be deleted. Please try again.');

        $this->assertDatabaseHas('forwarder_documents', ['id' => $document->id]);
    }

    private function createForwarder(array $attributes = []): Forwarder
    {
        return Forwarder::create(array_merge([
            'company_name' => 'Test Forwarder',
            'contact_person' => 'Test Person',
            'email' => 'forwarder@example.com',
            'phone' => '0779080706',
        ], $attributes));
    }

    private function userWithRole(string $role, ?int $forwarderId = null): User
    {
        $user = new User(['name' => 'Test User', 'forwarder_id' => $forwarderId]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));

        return $user;
    }
}
