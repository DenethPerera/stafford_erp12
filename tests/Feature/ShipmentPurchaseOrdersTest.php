<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
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
            $table->string('country')->nullable();
        });
        DB::table('tbl_country')->insert(['country_code' => 'AF', 'country' => 'Afghanistan']);

        Schema::connection('erav')->create('salescustomerlist', function (Blueprint $table) {
            $table->integer('customer_id')->primary();
            $table->string('customer_name')->nullable();
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
            $table->string('br_code')->nullable();
            $table->boolean('isoffline')->default(false);
        });
        Schema::connection('erav')->create('prodorder_list', function (Blueprint $table) {
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->boolean('isoffline')->default(false);
        });

        DB::connection('erav')->table('salescustomerlist')->insert(['customer_id' => 5, 'customer_name' => 'Test Buyer']);
        DB::connection('erav')->table('porder_pdf_list')->insert([
            ['id' => 763, 'customer_id' => 5, 'pdf_po_refno' => 'PO-763'],
            ['id' => 1394, 'customer_id' => 5, 'pdf_po_refno' => 'PO-1394'],
        ]);
        DB::connection('erav')->table('mbranchlist')->insert([
            ['id' => 3, 'br_name' => 'Factory 3', 'br_code' => 'F03'],
            ['id' => 29, 'br_name' => 'Factory 29', 'br_code' => 'F29'],
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

    public function test_created_shipments_search_matches_ids_without_changing_the_next_generated_number(): void
    {
        $first = $this->createSearchShipment('AF-000007');
        $second = $this->createSearchShipment('AF-000008');
        $this->createSearchShipment('CUSTOM-RED');
        $this->signInAdmin();

        $searches = [
            ['000007', [$first->id]],
            ['  AF-000007  ', [$first->id]],
            ['AF-', [$first->id, $second->id]],
            ['0', [$first->id, $second->id]],
        ];
        foreach ($searches as [$term, $expectedIds]) {
            $response = $this->get(route('shipment-management.create', ['shipment_search' => $term]));
            $response->assertOk()->assertSee('placeholder="Search shipments..."', false);
            $this->assertEqualsCanonicalizing($expectedIds, $response->viewData('shipments')->pluck('id')->all());
            $this->assertSame(trim($term), $response->viewData('shipmentSearch'));
            $this->assertSame(9, $response->viewData('nextShipmentNumbers')['AF']);
        }
    }

    public function test_created_shipments_search_handles_no_matches_and_blank_searches(): void
    {
        $this->createSearchShipment('AF-000001');
        $this->signInAdmin();

        $notFound = $this->get(route('shipment-management.create', ['shipment_search' => 'NoMatch']));
        $notFound->assertOk()->assertSee('No shipments match your search.');
        $this->assertSame(0, $notFound->viewData('shipments')->total());

        foreach ([[], ['shipment_search' => '   ']] as $parameters) {
            $response = $this->get(route('shipment-management.create', $parameters))->assertOk();
            $this->assertSame(1, $response->viewData('shipments')->total());
            $this->assertSame('', $response->viewData('shipmentSearch'));
        }
    }

    public function test_created_shipments_search_is_kept_when_changing_pages(): void
    {
        for ($number = 1; $number <= 12; $number++) {
            $this->createSearchShipment(sprintf('AF-%06d', $number));
        }
        $this->createSearchShipment('CUSTOM-RED');
        $this->signInAdmin();

        $response = $this->get(route('shipment-management.create', [
            'shipment_search' => 'AF-',
            'page' => 2,
        ]));
        $response->assertOk()->assertDontSee('<td>CUSTOM-RED</td>', false);
        $shipments = $response->viewData('shipments');
        $this->assertSame(12, $shipments->total());
        $this->assertSame(2, $shipments->currentPage());
        $this->assertCount(2, $shipments);
        parse_str(parse_url($shipments->previousPageUrl(), PHP_URL_QUERY), $parameters);
        $this->assertSame('AF-', $parameters['shipment_search']);
    }

    public function test_created_shipments_search_rejects_invalid_input(): void
    {
        $this->signInAdmin();

        foreach ([['AF-'], str_repeat('A', 256)] as $term) {
            $this->from(route('shipment-management.create'))
                ->get(route('shipment-management.create', ['shipment_search' => $term]))
                ->assertRedirect(route('shipment-management.create'))
                ->assertSessionHasErrors('shipment_search');
        }
    }

    public function test_created_shipments_show_only_saved_purchase_orders_under_each_factory(): void
    {
        $first = $this->createSearchShipment('AF-000001');
        $first->update(['shipment_type' => 'consolidated', 'factory_ids' => [3, 29]]);
        $second = $this->createSearchShipment('AF-000002');

        DB::table('shipment_order_factories')->insert([
            ['shipment_id' => $first->id, 'purchase_order_id' => 763, 'factory_id' => 3],
            ['shipment_id' => $first->id, 'purchase_order_id' => 1394, 'factory_id' => 29],
            ['shipment_id' => $second->id, 'purchase_order_id' => 1394, 'factory_id' => 3],
        ]);
        $this->signInAdmin();

        $response = $this->get(route('shipment-management.create'))->assertOk();
        $this->assertSame([
            $first->id => [3 => ['PO-763'], 29 => ['PO-1394']],
            $second->id => [3 => ['PO-1394']],
        ], $response->viewData('factoryPurchaseOrders'));
        $response->assertSee('Factories / POs')
            ->assertSee('<details class="mb-2">', false)
            ->assertSee('<li>PO-763</li>', false)
            ->assertSee('<li>PO-1394</li>', false);

        // Searching must not load assignments from shipments outside the results.
        $filtered = $this->get(route('shipment-management.create', ['shipment_search' => 'AF-000001']))
            ->assertOk();
        $this->assertSame([
            $first->id => [3 => ['PO-763'], 29 => ['PO-1394']],
        ], $filtered->viewData('factoryPurchaseOrders'));
    }

    public function test_created_shipments_handle_missing_purchase_orders_and_empty_assignments(): void
    {
        $shipment = $this->createSearchShipment('AF-000001');
        $shipment->update(['shipment_type' => 'consolidated', 'factory_ids' => [3, 29]]);
        DB::table('shipment_order_factories')->insert([
            'shipment_id' => $shipment->id,
            'purchase_order_id' => 9999,
            'factory_id' => 3,
        ]);
        $this->signInAdmin();

        $this->get(route('shipment-management.create'))
            ->assertOk()
            ->assertSee('<li>PO #9999</li>', false)
            ->assertSee('No purchase orders assigned.');
    }

    public function test_factory_shipment_form_only_lists_its_own_assigned_purchase_orders(): void
    {
        $shipment = $this->createSearchShipment('AF-000001');
        DB::table('shipment_order_factories')->insert([
            ['shipment_id' => $shipment->id, 'purchase_order_id' => 763, 'factory_id' => 3],
            ['shipment_id' => $shipment->id, 'purchase_order_id' => 1394, 'factory_id' => 29],
        ]);
        $this->withMiddleware();

        foreach ([3 => ['PO-763', 'PO-1394'], 29 => ['PO-1394', 'PO-763']] as $factoryId => $poNumbers) {
            $user = new User(['name' => 'Factory User', 'mbranchlist_id' => $factoryId]);
            $user->id = 1;
            $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
            $this->actingAs($user);

            // A factory ID in the URL must not change which factory's POs are shown.
            $this->get(route('create-shipment.index', ['factory_id' => 999]))
                ->assertOk()
                ->assertSee('Order Delivery')
                ->assertSee('value="Factory ' . $factoryId . '"', false)
                ->assertSee($poNumbers[0])
                ->assertDontSee($poNumbers[1]);
        }
    }

    public function test_factory_shipment_form_requires_an_assigned_factory(): void
    {
        $user = new User(['name' => 'Unassigned Factory User']);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
        $this->withMiddleware()->actingAs($user);

        $this->get(route('create-shipment.index'))->assertForbidden();
    }

    private function signInAdmin(): void
    {
        $this->withMiddleware();
        $user = new User(['name' => 'Test Admin']);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'super_admin', 'guard_name' => 'web'])]));
        $this->actingAs($user);
    }

    private function createSearchShipment(string $shipmentId): Shipment
    {
        return Shipment::create([
            'country_of_origin' => 'AF',
            'buyer_id' => '5',
            'shipment_id' => $shipmentId,
            'shipment_id_mode' => 'manual',
            'purchase_order_id' => null,
            'shipment_type' => 'individual',
            'factory_ids' => [3],
        ]);
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
