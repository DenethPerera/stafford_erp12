<?php

namespace Tests\Feature;

use App\Models\ShipmentDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only create the new table in an isolated test database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_10_30_000001_create_shipment_documents_table.php'))->up();
        (require database_path('migrations/2026_11_02_070000_add_shipment_id_to_shipment_documents_table.php'))->up();
        Storage::fake('shipment_documents');
    }

    public function test_factory_can_upload_list_view_and_download_a_document(): void
    {
        $this->actingAs($this->factoryUser(3));
        $contents = "%PDF-1.4\nShipment document\n%%EOF";

        $this->post(route('shipment-documents.store'), [
            'shipment_id' => 'SHIP-0007',
            'master_bl' => UploadedFile::fake()->createWithContent('master-bl.pdf', $contents),
        ])->assertSessionHasNoErrors()->assertRedirect(route('shipment-documents.index'));

        $document = ShipmentDocument::sole();
        $this->assertEquals(3, $document->mbranchlist_id);
        $this->assertSame('SHIP-0007', $document->shipment_id);
        Storage::disk('shipment_documents')->assertExists($document->file_path);

        $this->get(route('shipment-documents.index'))
            ->assertOk()->assertSee('SHIP-0007')->assertSee('master-bl.pdf')->assertSee('Upload Shipment Documents')
            ->assertSee('name="master_bl"', false)
            ->assertSee('name="booking_confirmation"', false)
            ->assertSee('name="container_loading_list"', false)
            ->assertDontSee('name="document_type"', false);

        $this->get(route('shipment-documents.document', $document->id))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $response = $this->get(route('shipment-documents.document', [
            'shipmentDocument' => $document->id,
            'download' => 1,
        ]));
        $response->assertOk()->assertDownload('master-bl.pdf');
        $this->assertSame($contents, $response->streamedContent());
    }

    public function test_factory_cannot_list_view_or_download_another_factory_document(): void
    {
        $document = ShipmentDocument::create([
            'mbranchlist_id' => 29,
            'document_type' => 'Booking Confirmation',
            'original_name' => 'other-factory.pdf',
            'file_path' => '29/other-factory.pdf',
        ]);
        $this->actingAs($this->factoryUser(3));

        $this->get(route('shipment-documents.index'))->assertOk()->assertDontSee('other-factory.pdf');
        $this->get(route('shipment-documents.document', $document->id))->assertForbidden();
        $this->get(route('shipment-documents.document', [
            'shipmentDocument' => $document->id,
            'download' => 1,
        ]))->assertForbidden();
    }

    public function test_new_uploads_require_a_shipment_id(): void
    {
        $this->actingAs($this->factoryUser(3));

        foreach ([null, '', '   '] as $shipmentId) {
            $this->post(route('shipment-documents.store'), [
                'shipment_id' => $shipmentId,
                'master_bl' => UploadedFile::fake()->createWithContent('master-bl.pdf', "%PDF-1.4\nTest\n%%EOF"),
            ])->assertSessionHasErrors('shipment_id');
        }

        $this->assertDatabaseCount('shipment_documents', 0);
        $this->assertSame([], Storage::disk('shipment_documents')->allFiles());
    }

    public function test_factory_can_upload_all_three_documents_in_one_request(): void
    {
        $this->actingAs($this->factoryUser(3));

        $this->post(route('shipment-documents.store'), [
            'shipment_id' => 'SHIP-0007',
            'mbranchlist_id' => 29,
            'master_bl' => UploadedFile::fake()->createWithContent('master-bl.pdf', "%PDF-1.4\nMaster BL\n%%EOF"),
            'booking_confirmation' => UploadedFile::fake()->createWithContent('booking.pdf', "%PDF-1.4\nBooking\n%%EOF"),
            'container_loading_list' => UploadedFile::fake()->createWithContent('loading.pdf', "%PDF-1.4\nLoading\n%%EOF"),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('shipment-documents.index'))
            ->assertSessionHas('success', 'Shipment documents uploaded successfully.');

        $this->assertDatabaseCount('shipment_documents', 3);
        foreach (['Master BL', 'Booking Confirmation', 'Container Loading List'] as $type) {
            $document = ShipmentDocument::where('document_type', $type)->sole();
            $this->assertSame('SHIP-0007', $document->shipment_id);
            $this->assertEquals(3, $document->mbranchlist_id);
            Storage::disk('shipment_documents')->assertExists($document->file_path);
        }
    }

    public function test_factory_can_add_more_documents_to_the_same_shipment(): void
    {
        $this->actingAs($this->factoryUser(3));

        foreach (['master_bl', 'booking_confirmation'] as $field) {
            $this->post(route('shipment-documents.store'), [
                'shipment_id' => 'SHIP-0007',
                $field => UploadedFile::fake()->createWithContent('document.pdf', "%PDF-1.4\nTest\n%%EOF"),
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(2, ShipmentDocument::where('shipment_id', 'SHIP-0007')->count());
    }

    public function test_invalid_shipment_ids_are_rejected_before_saving_the_file(): void
    {
        $this->actingAs($this->factoryUser(3));

        foreach ([str_repeat('A', 256), ['SHIP-0007']] as $shipmentId) {
            $this->post(route('shipment-documents.store'), [
                'shipment_id' => $shipmentId,
                'master_bl' => UploadedFile::fake()->createWithContent('document.pdf', "%PDF-1.4\nTest\n%%EOF"),
            ])->assertSessionHasErrors('shipment_id');
        }

        $this->assertDatabaseCount('shipment_documents', 0);
        $this->assertSame([], Storage::disk('shipment_documents')->allFiles());
    }

    public function test_document_list_shows_one_row_per_shipment_with_a_column_for_each_type(): void
    {
        $master = $this->listedDocument('SHIP-0007', 'Master BL', 'master-bl.pdf');
        $this->listedDocument('SHIP-0007', 'Booking Confirmation', 'booking.pdf');
        $this->listedDocument('SHIP-0007', 'Container Loading List', 'loading.pdf');
        $this->listedDocument('SHIP-0007', 'Master BL', 'updated-master-bl.pdf');
        $this->listedDocument('SHIP-0007', 'Master BL', 'other-factory.pdf', 29);
        $this->listedDocument('SHIP-0008', 'Master BL', 'next-shipment.pdf');
        $this->actingAs($this->factoryUser(3));

        $response = $this->get(route('shipment-documents.index'));
        $response->assertOk()
            ->assertSee(route('shipment-documents.document', $master->id))
            ->assertSee(route('shipment-documents.destroy', $master->id))
            ->assertDontSee('other-factory.pdf')
            ->assertViewHas('shipments', function ($shipments) {
                return $shipments->total() === 2;
            });

        $rows = $this->documentTableRows($response->getContent());
        $this->assertCount(2, $rows);
        $newerCells = $rows->item(0)->getElementsByTagName('td');
        $this->assertCount(4, $newerCells);
        $this->assertSame('SHIP-0008', trim($newerCells->item(0)->textContent));
        $this->assertStringContainsString('Not uploaded', $newerCells->item(2)->textContent);

        $cells = $rows->item(1)->getElementsByTagName('td');
        $this->assertCount(4, $cells);
        $this->assertSame('SHIP-0007', trim($cells->item(0)->textContent));
        $this->assertStringContainsString('master-bl.pdf', $cells->item(1)->textContent);
        $this->assertStringContainsString('updated-master-bl.pdf', $cells->item(1)->textContent);
        $this->assertStringContainsString('booking.pdf', $cells->item(2)->textContent);
        $this->assertStringContainsString('loading.pdf', $cells->item(3)->textContent);
    }

    public function test_pagination_keeps_all_files_for_a_shipment_on_the_same_page(): void
    {
        for ($number = 1; $number <= 11; $number++) {
            $shipmentId = sprintf('SHIP-%04d', $number);
            $this->listedDocument($shipmentId, 'Master BL', $shipmentId . '-master.pdf');
            $this->listedDocument($shipmentId, 'Booking Confirmation', $shipmentId . '-booking.pdf');
            $this->listedDocument($shipmentId, 'Container Loading List', $shipmentId . '-loading.pdf');
        }
        $this->actingAs($this->factoryUser(3));

        $firstPage = $this->get(route('shipment-documents.index'));
        $firstPage->assertOk()
            ->assertDontSee('SHIP-0001')
            ->assertViewHas('shipments', function ($shipments) {
                return $shipments->total() === 11 && $shipments->lastPage() === 2;
            });
        $this->assertCount(10, $this->documentTableRows($firstPage->getContent()));

        $secondPage = $this->get(route('shipment-documents.index', ['page' => 2]));
        $secondPage->assertOk()
            ->assertSee('SHIP-0001-master.pdf')
            ->assertSee('SHIP-0001-booking.pdf')
            ->assertSee('SHIP-0001-loading.pdf')
            ->assertDontSee('SHIP-0002');
        $this->assertCount(1, $this->documentTableRows($secondPage->getContent()));
    }

    public function test_legacy_files_without_a_shipment_id_remain_available_in_one_row(): void
    {
        $this->listedDocument(null, 'Master BL', 'legacy-master.pdf');
        $this->listedDocument(null, 'Booking Confirmation', 'legacy-booking.pdf');
        $this->actingAs($this->factoryUser(3));

        $response = $this->get(route('shipment-documents.index'));
        $response->assertOk()
            ->assertSee('Not assigned')
            ->assertSee('legacy-master.pdf')
            ->assertSee('legacy-booking.pdf');
        $this->assertCount(1, $this->documentTableRows($response->getContent()));
    }

    public function test_all_selected_files_are_validated_before_any_are_saved(): void
    {
        $this->actingAs($this->factoryUser(3));

        $this->post(route('shipment-documents.store'), [
            'shipment_id' => 'SHIP-0007',
            'master_bl' => UploadedFile::fake()->createWithContent('valid.pdf', "%PDF-1.4\nTest\n%%EOF"),
            'booking_confirmation' => UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
        ])->assertSessionHasErrors('booking_confirmation');

        $this->post(route('shipment-documents.store'), [
            'shipment_id' => 'SHIP-0007',
            'container_loading_list' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('container_loading_list');

        $this->assertDatabaseCount('shipment_documents', 0);
        $this->assertSame([], Storage::disk('shipment_documents')->allFiles());
    }

    public function test_factory_must_select_at_least_one_file(): void
    {
        $this->actingAs($this->factoryUser(3));

        $this->from(route('shipment-documents.index'))
            ->post(route('shipment-documents.store'), ['shipment_id' => 'SHIP-0007'])
            ->assertRedirect(route('shipment-documents.index'))
            ->assertSessionHasErrors('documents')
            ->assertSessionHasInput('shipment_id', 'SHIP-0007');

        $this->assertDatabaseCount('shipment_documents', 0);
        $this->assertSame([], Storage::disk('shipment_documents')->allFiles());
    }

    public function test_failed_batch_removes_its_files_and_records_but_keeps_existing_documents(): void
    {
        $existing = ShipmentDocument::create([
            'mbranchlist_id' => 3,
            'shipment_id' => 'EXISTING',
            'document_type' => 'Master BL',
            'original_name' => 'existing.pdf',
            'file_path' => '3/existing.pdf',
        ]);
        Storage::disk('shipment_documents')->put($existing->file_path, "%PDF-1.4\nExisting\n%%EOF");
        $this->actingAs($this->factoryUser(3));

        $saveCount = 0;
        Event::listen('eloquent.creating: ' . ShipmentDocument::class, function () use (&$saveCount) {
            $saveCount++;
            if ($saveCount === 2) {
                throw new \RuntimeException('Simulated second document save failure.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->post(route('shipment-documents.store'), [
                'shipment_id' => 'SHIP-0007',
                'master_bl' => UploadedFile::fake()->createWithContent('master-bl.pdf', "%PDF-1.4\nMaster BL\n%%EOF"),
                'booking_confirmation' => UploadedFile::fake()->createWithContent('booking.pdf', "%PDF-1.4\nBooking\n%%EOF"),
            ]);
            $this->fail('The simulated save error should have been raised.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated second document save failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('shipment_documents', 1);
        $this->assertDatabaseHas('shipment_documents', ['id' => $existing->id]);
        $this->assertSame([$existing->file_path], Storage::disk('shipment_documents')->allFiles());
    }

    public function test_factory_without_a_branch_cannot_access_or_upload_documents(): void
    {
        $this->actingAs($this->factoryUser(null));

        $this->get(route('shipment-documents.index'))->assertForbidden();
        $this->post(route('shipment-documents.store'))->assertForbidden();
    }

    public function test_missing_file_returns_not_found(): void
    {
        $this->actingAs($this->factoryUser(3));
        $document = ShipmentDocument::create([
            'mbranchlist_id' => 3,
            'document_type' => 'Master BL',
            'original_name' => 'missing.pdf',
            'file_path' => '3/missing.pdf',
        ]);

        $this->get(route('shipment-documents.document', $document->id))->assertNotFound();
    }

    public function test_guests_and_non_factory_users_cannot_access_document_routes(): void
    {
        $this->get(route('shipment-documents.index'))->assertRedirect(route('login'));
        $this->post(route('shipment-documents.store'))->assertRedirect(route('login'));
        $this->get(route('shipment-documents.document', 1))->assertRedirect(route('login'));
        $this->delete(route('shipment-documents.destroy', 1))->assertRedirect(route('login'));

        $document = ShipmentDocument::create([
            'mbranchlist_id' => 3,
            'document_type' => 'Master BL',
            'original_name' => 'master-bl.pdf',
            'file_path' => '3/master-bl.pdf',
        ]);
        $user = $this->factoryUser(3);
        $user->setRelation('roles', collect([new Role(['name' => 'lab', 'guard_name' => 'web'])]));
        $this->actingAs($user);

        $this->get(route('shipment-documents.index'))->assertForbidden();
        $this->post(route('shipment-documents.store'))->assertForbidden();
        $this->get(route('shipment-documents.document', $document->id))->assertForbidden();
        $this->delete(route('shipment-documents.destroy', $document->id))->assertForbidden();
    }

    public function test_factory_can_delete_its_document_and_stored_file(): void
    {
        $this->actingAs($this->factoryUser(3));
        $this->post(route('shipment-documents.store'), [
            'shipment_id' => 'SHIP-0007',
            'master_bl' => UploadedFile::fake()->createWithContent('master-bl.pdf', "%PDF-1.4\nTest\n%%EOF"),
        ])->assertSessionHasNoErrors();
        $document = ShipmentDocument::sole();

        $this->get(route('shipment-documents.index'))
            ->assertOk()->assertSee(route('shipment-documents.destroy', $document->id))->assertSee('Delete');

        $this->delete(route('shipment-documents.destroy', $document->id))
            ->assertRedirect(route('shipment-documents.index'))
            ->assertSessionHas('success', 'Shipment document deleted successfully.');

        $this->assertDatabaseMissing('shipment_documents', ['id' => $document->id]);
        Storage::disk('shipment_documents')->assertMissing($document->file_path);
        $this->get(route('shipment-documents.document', $document->id))->assertNotFound();
    }

    public function test_other_factories_and_unassigned_users_cannot_delete_a_document(): void
    {
        $document = ShipmentDocument::create([
            'mbranchlist_id' => 29,
            'document_type' => 'Master BL',
            'original_name' => 'other-factory.pdf',
            'file_path' => '29/other-factory.pdf',
        ]);
        Storage::disk('shipment_documents')->put($document->file_path, 'Test document');

        $this->actingAs($this->factoryUser(3));
        $this->delete(route('shipment-documents.destroy', $document->id))->assertForbidden();

        $this->actingAs($this->factoryUser(null));
        $this->delete(route('shipment-documents.destroy', $document->id))->assertForbidden();

        $this->assertDatabaseHas('shipment_documents', ['id' => $document->id]);
        Storage::disk('shipment_documents')->assertExists($document->file_path);
    }

    public function test_factory_can_delete_a_record_when_the_file_is_already_missing(): void
    {
        $document = ShipmentDocument::create([
            'mbranchlist_id' => 3,
            'document_type' => 'Master BL',
            'original_name' => 'missing.pdf',
            'file_path' => '3/missing.pdf',
        ]);
        $this->actingAs($this->factoryUser(3));

        $this->delete(route('shipment-documents.destroy', $document->id))
            ->assertRedirect(route('shipment-documents.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('shipment_documents', ['id' => $document->id]);
    }

    private function listedDocument(?string $shipmentId, string $type, string $name, int $branchId = 3): ShipmentDocument
    {
        return ShipmentDocument::create([
            'mbranchlist_id' => $branchId,
            'shipment_id' => $shipmentId,
            'document_type' => $type,
            'original_name' => $name,
            'file_path' => $branchId . '/' . $name,
        ]);
    }

    private function documentTableRows(string $html): \DOMNodeList
    {
        $document = new \DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return (new \DOMXPath($document))->query('//table/tbody/tr');
    }

    private function factoryUser(?int $branchId): User
    {
        $user = new User(['name' => 'Factory User', 'mbranchlist_id' => $branchId]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));

        return $user;
    }
}
