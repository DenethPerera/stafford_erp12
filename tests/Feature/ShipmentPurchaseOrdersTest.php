<?php

namespace Tests\Feature;

use App\Models\Shipment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShipmentPurchaseOrdersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use isolated databases for both connections, never the configured ERAV database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.erav' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sqlite');
        DB::purge('erav');
        $this->withoutMiddleware();

        (require database_path('migrations/2026_10_26_090433_create_shipments_table.php'))->up();
        (require database_path('migrations/2026_10_27_000001_create_shipment_order_factories_table.php'))->up();

        Schema::create('tbl_country', function (Blueprint $table) {
            $table->string('country_code');
        });
        DB::table('tbl_country')->insert(['country_code' => 'AF']);

        Schema::connection('erav')->create('salescustomerlist', function (Blueprint $table) {
            $table->integer('customer_id')->primary();
            $table->boolean('isoffline')->default(false);
        });
        Schema::connection('erav')->create('porder_pdf_list', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('customer_id');
            $table->string('pdf_po_refno');
            $table->boolean('isoffline')->default(false);
        });
        Schema::connection('erav')->create('mbranchlist', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('br_name');
            $table->boolean('isoffline')->default(false);
        });
        Schema::connection('erav')->create('prodorder_list', function (Blueprint $table) {
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->boolean('isoffline')->default(false);
        });

        DB::connection('erav')->table('salescustomerlist')->insert(['customer_id' => 5]);
        DB::connection('erav')->table('porder_pdf_list')->insert([
            ['id' => 763, 'customer_id' => 5, 'pdf_po_refno' => 'PO-763'],
            ['id' => 1394, 'customer_id' => 5, 'pdf_po_refno' => 'PO-1394'],
        ]);
        DB::connection('erav')->table('mbranchlist')->insert([
            ['id' => 3, 'br_name' => 'Factory 3'],
            ['id' => 29, 'br_name' => 'Factory 29'],
        ]);
        DB::connection('erav')->table('prodorder_list')->insert([
            ['porder_pdf_list_id' => 763, 'mbranchlist_id' => 3],
            ['porder_pdf_list_id' => 1394, 'mbranchlist_id' => 3],
            ['porder_pdf_list_id' => 1394, 'mbranchlist_id' => 29],
        ]);
    }

    public static function shipmentSelections(): array
    {
        return [
            'one PO' => [[763], 'individual', [3], 1],
            'multiple POs at one factory' => [[1394, 763], 'individual', [3], 2],
            'multiple POs and factories' => [[763, 1394], 'consolidated', [3, 29], 3],
        ];
    }

    #[DataProvider('shipmentSelections')]
    public function test_it_saves_every_selected_purchase_order(
        array $purchaseOrderIds,
        string $type,
        array $factoryIds,
        int $linkCount
    ): void {
        $this->purchaseOrderMigration()->up();

        $this->post(route('shipment-management.store'), [
            'country_of_origin' => 'AF',
            'buyer_id' => 5,
            'shipment_id_mode' => 'manual',
            'shipment_id' => 'AF-000001',
            // Browser form values arrive as strings.
            'purchase_order_ids' => array_map('strval', $purchaseOrderIds),
            'shipment_type' => $type,
            'factory_id' => $factoryIds[0],
            'factory_ids' => $factoryIds,
        ])->assertSessionHasNoErrors()->assertRedirect(route('shipment-management.create'));

        $shipment = Shipment::sole();
        $this->assertSame($purchaseOrderIds, $shipment->purchase_order_id);
        $this->assertSame($purchaseOrderIds, json_decode($shipment->getRawOriginal('purchase_order_id'), true));
        $this->assertSame($factoryIds, $shipment->factory_ids);
        $this->assertDatabaseCount('shipment_order_factories', $linkCount);
        foreach ($purchaseOrderIds as $id) {
            $this->assertDatabaseHas('shipment_order_factories', [
                'shipment_id' => $shipment->id,
                'purchase_order_id' => $id,
            ]);
        }
    }

    public function test_migration_recovers_old_selections_and_preserves_legacy_ids(): void
    {
        $legacyId = $this->insertLegacyShipment('AF-000001', 763);
        $multiId = $this->insertLegacyShipment('AF-000002', null);
        $singleId = $this->insertLegacyShipment('AF-000003', 1394);
        $unknownId = $this->insertLegacyShipment('AF-000004', null);
        DB::table('shipment_order_factories')->insert([
            ['shipment_id' => $multiId, 'purchase_order_id' => 763, 'factory_id' => 3],
            ['shipment_id' => $multiId, 'purchase_order_id' => 1394, 'factory_id' => 3],
            ['shipment_id' => $multiId, 'purchase_order_id' => 1394, 'factory_id' => 29],
            ['shipment_id' => $singleId, 'purchase_order_id' => 1394, 'factory_id' => 3],
        ]);

        $migration = $this->purchaseOrderMigration();
        $migration->up();

        $this->assertSame([763], Shipment::findOrFail($legacyId)->purchase_order_id);
        $this->assertSame([763, 1394], Shipment::findOrFail($multiId)->purchase_order_id);
        $this->assertSame([1394], Shipment::findOrFail($singleId)->purchase_order_id);
        $this->assertNull(Shipment::findOrFail($unknownId)->purchase_order_id);
        $this->assertFalse(Schema::hasColumn('shipments', 'purchase_order_ids_copy'));

        $migration->down();

        $this->assertDatabaseHas('shipments', ['id' => $legacyId, 'purchase_order_id' => 763]);
        $this->assertDatabaseHas('shipments', ['id' => $multiId, 'purchase_order_id' => null]);
        $this->assertDatabaseCount('shipment_order_factories', 4);

        $migration->up();
        $this->assertSame([763, 1394], Shipment::findOrFail($multiId)->purchase_order_id);
    }

    private function insertLegacyShipment(string $shipmentId, ?int $purchaseOrderId): int
    {
        return DB::table('shipments')->insertGetId([
            'country_of_origin' => 'AF',
            'buyer_id' => '5',
            'shipment_id' => $shipmentId,
            'shipment_id_mode' => 'manual',
            'purchase_order_id' => $purchaseOrderId,
            'shipment_type' => 'consolidated',
            'factory_ids' => '[3,29]',
        ]);
    }

    private function purchaseOrderMigration(): Migration
    {
        return require database_path('migrations/2026_10_28_000001_store_shipment_purchase_orders_as_json.php');
    }
}
