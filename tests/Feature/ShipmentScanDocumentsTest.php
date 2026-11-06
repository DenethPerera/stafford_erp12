<?php

namespace Tests\Feature;

use App\Models\ShipmentDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentScanDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Share an isolated database to support the scan's qualified table joins.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.erav' => [
                'driver' => 'sqlite',
                'database' => 'main',
                'prefix' => '',
            ],
        ]);
        DB::purge('sqlite');
        DB::purge('erav');
        $pdo = DB::connection('sqlite')->getPdo();
        DB::connection('erav')->setPdo($pdo)->setReadPdo($pdo);

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id')->unique();
            $table->unsignedBigInteger('import_id')->nullable();
        });
        Schema::create('shipment_order_factories', function (Blueprint $table) {
            $table->unsignedBigInteger('shipment_id');
            $table->integer('purchase_order_id');
            $table->integer('factory_id')->nullable();
        });
        (require database_path('migrations/2026_10_30_000001_create_shipment_documents_table.php'))->up();
        (require database_path('migrations/2026_11_02_070000_add_shipment_id_to_shipment_documents_table.php'))->up();

        Schema::connection('erav')->create('porder_pdf_list', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('pdf_po_refno');
        });
        Schema::connection('erav')->create('mbranchlist', function (Blueprint $table) {
            $table->id();
            $table->string('br_name');
        });
        Schema::connection('erav')->create('porder_pdf_info', function (Blueprint $table) {
            $table->id();
            $table->integer('porder_pdf_list_id');
            $table->string('item_styleno')->nullable();
            $table->string('item_description')->nullable();
            $table->decimal('item_order_qty')->nullable();
            $table->decimal('revised_unit_price')->nullable();
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('prodshipment_list', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_refno', 255);
            $table->integer('import_id');
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->integer('prodorder_list_prodorder_id');
            $table->integer('isoffline')->default(0);
            $table->string('ship_container_no')->nullable();
        });
        Schema::connection('erav')->create('prodshipment_info', function (Blueprint $table) {
            $table->id();
            $table->integer('prodshipment_list_id');
            $table->integer('porder_pdf_info_id');
            $table->decimal('ship_qty')->nullable();
            $table->integer('full_carton_nos')->nullable();
            $table->integer('odd_carton_nos')->nullable();
            $table->decimal('cbm_per_carton')->nullable();
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('prodorder_list', function (Blueprint $table) {
            $table->integer('prodorder_id')->primary();
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->integer('currency_type');
        });
        Schema::connection('erav')->create('tbl_currency', function (Blueprint $table) {
            $table->integer('idtbl_currency')->primary();
            $table->string('currency_name');
        });
        Schema::connection('erav')->create('imports', function (Blueprint $table) {
            $table->id();
            $table->boolean('deleted')->default(false);
            $table->integer('delivery_status')->nullable();
            $table->string('house_bl_no')->nullable();
            $table->string('vsl_name')->nullable();
            $table->string('key_val')->nullable();
            $table->string('loading_port_name')->nullable();
            $table->string('discharge_port_name')->nullable();
            $table->string('port_edt_date')->nullable();
            $table->string('port_eta_date')->nullable();
        });
        Schema::connection('erav')->create('imports_po_nos', function (Blueprint $table) {
            $table->unsignedBigInteger('import_id');
            $table->string('po_no');
        });
        Schema::connection('erav')->create('tbl_delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::connection('erav')->create('imports_container_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->string('container_no');
        });
        Storage::fake('shipment_documents');
    }

    public function test_scan_shows_matching_factory_uploads_and_excludes_other_shipments(): void
    {
        $this->createShipment('SHIP-0007');
        $this->actingAs($this->userWithRole('factory', 3));
        $this->post(route('shipment-documents.store'), [
            'shipment_id' => 'SHIP-0007',
            'master_bl' => $this->pdf('master-bl.pdf'),
            'booking_confirmation' => $this->pdf('booking.pdf'),
            'container_loading_list' => $this->pdf('loading.pdf'),
        ])->assertSessionHasNoErrors();

        // Several factories may upload documents for the same shipment.
        $otherFactory = $this->createDocument('SHIP-0007', 'factory-29.pdf', 29);
        $this->createDocument('SHIP-00070', 'other-shipment.pdf');
        $this->createDocument('ship-0007', 'different-case.pdf');
        $this->createDocument(null, 'legacy-unassigned.pdf');
        $this->actingAs($this->userWithRole('super_admin'));

        $response = $this->get(route('shipment-management.scan', ['shipment_id' => 'SHIP-0007']));
        $response->assertOk()
            ->assertSee('Uploaded Shipment Documents')
            ->assertSee('Shipment Details')
            ->assertSee('master-bl.pdf')
            ->assertSee('booking.pdf')
            ->assertSee('loading.pdf')
            ->assertSee('factory-29.pdf')
            ->assertDontSee('other-shipment.pdf')
            ->assertDontSee('different-case.pdf')
            ->assertDontSee('legacy-unassigned.pdf')
            ->assertSee(route('shipment-management.documents.show', $otherFactory->id))
            ->assertViewHas('shipmentDocuments', function ($documents) {
                return count($documents) === 4;
            });
    }

    public function test_existing_shipment_without_uploads_shows_the_empty_documents_message(): void
    {
        $this->createShipment('SHIP-0007');
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('shipment-management.scan', ['shipment_id' => 'SHIP-0007']))
            ->assertOk()
            ->assertSee('Uploaded Shipment Documents')
            ->assertSee('No documents have been uploaded for this shipment yet.')
            ->assertSee('Shipment Details')
            ->assertViewHas('shipmentDocuments', []);
    }

    public function test_initial_invalid_and_unknown_searches_do_not_show_documents(): void
    {
        $this->createShipment('SHIP-0007');
        $this->createDocument('UNKNOWN', 'unknown-shipment.pdf');
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('shipment-management.scan'))
            ->assertOk()->assertDontSee('Uploaded Shipment Documents');

        foreach (['UNKNOWN', 'ship-0007', '', ['SHIP-0007']] as $shipmentId) {
            $this->get(route('shipment-management.scan', ['shipment_id' => $shipmentId]))
                ->assertOk()
                ->assertDontSee('Uploaded Shipment Documents')
                ->assertDontSee('unknown-shipment.pdf')
                ->assertViewHas('shipmentDocuments', []);
        }
    }

    public function test_linked_shipping_details_remain_visible_beside_documents(): void
    {
        $importId = DB::connection('erav')->table('imports')->insertGetId([
            'house_bl_no' => 'BL-123',
            'vsl_name' => 'Test Vessel',
            'loading_port_name' => 'Colombo',
            'key_val' => '1',
        ]);
        $this->createShipment('SHIP-0007', $importId);
        $this->createDocument('SHIP-0007', 'master-bl.pdf');
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('shipment-management.scan', ['shipment_id' => 'SHIP-0007']))
            ->assertOk()
            ->assertSee('BL-123')
            ->assertSee('Test Vessel')
            ->assertSee('Colombo')
            ->assertSee('master-bl.pdf');
    }

    public function test_documents_remain_visible_when_the_saved_shipping_record_is_missing(): void
    {
        $this->createShipment('SHIP-0007', 999);
        $this->createDocument('SHIP-0007', 'master-bl.pdf');
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('shipment-management.scan', ['shipment_id' => 'SHIP-0007']))
            ->assertOk()
            ->assertSee('The saved shipping record is missing or deleted.')
            ->assertSee('master-bl.pdf');
    }

    public function test_admin_can_view_and_download_documents_using_the_scan_links(): void
    {
        $document = $this->createDocument('SHIP-0007', 'master-bl.pdf');
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get(route('shipment-management.documents.show', $document->id))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $response = $this->get(route('shipment-management.documents.show', [
            'shipmentDocument' => $document->id,
            'download' => 1,
        ]));
        $response->assertOk()->assertDownload('master-bl.pdf');
        $this->assertSame("%PDF-1.4\nScan document\n%%EOF", $response->streamedContent());

        Storage::disk('shipment_documents')->delete($document->file_path);
        $this->get(route('shipment-management.documents.show', $document->id))->assertNotFound();
        $this->get(route('shipment-management.documents.show', 999))->assertNotFound();
    }

    public function test_scan_and_its_document_links_require_an_administrator(): void
    {
        $document = $this->createDocument('SHIP-0007', 'master-bl.pdf');
        $this->get(route('shipment-management.scan'))->assertRedirect(route('login'));
        $this->get(route('shipment-management.documents.show', $document->id))
            ->assertRedirect(route('login'));

        foreach (['factory', 'lab', 'forwarder'] as $role) {
            $this->actingAs($this->userWithRole($role, 3));
            $this->get(route('shipment-management.scan'))->assertForbidden();
            $this->get(route('shipment-management.documents.show', $document->id))->assertForbidden();
            $this->get(route('shipment-management.documents.show', [
                'shipmentDocument' => $document->id,
                'download' => 1,
            ]))->assertForbidden();
        }
    }

    private function createShipment(string $shipmentId, ?int $importId = null): void
    {
        DB::table('shipments')->insert([
            'shipment_id' => $shipmentId,
            'import_id' => $importId,
        ]);
    }

    private function createDocument(?string $shipmentId, string $name, int $branchId = 3): ShipmentDocument
    {
        $document = ShipmentDocument::create([
            'mbranchlist_id' => $branchId,
            'shipment_id' => $shipmentId,
            'document_type' => 'Master BL',
            'original_name' => $name,
            'file_path' => $branchId . '/' . $name,
        ]);
        Storage::disk('shipment_documents')->put($document->file_path, "%PDF-1.4\nScan document\n%%EOF");

        return $document;
    }

    private function userWithRole(string $role, ?int $branchId = null): User
    {
        $user = new User(['name' => 'Test User', 'mbranchlist_id' => $branchId]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));

        return $user;
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\nScan document\n%%EOF");
    }
}
