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
            $table->increments('prodorder_id');
            $table->string('prodorder_ref')->nullable();
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->boolean('isoffline')->default(false);
        });
        Schema::connection('erav')->create('prodorder_info', function (Blueprint $table) {
            $table->integer('pack_no')->primary();
            $table->integer('prodorder_id');
            $table->integer('porder_pdf_info_id');
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('porder_pdf_info', function (Blueprint $table) {
            $table->id();
            $table->integer('porder_pdf_list_id');
            $table->string('item_styleno');
            $table->string('item_colorcode');
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('prodstyle_list', function (Blueprint $table) {
            $table->id();
            $table->string('styleid');
            $table->integer('isoffline')->default(0);
        });
        Schema::connection('erav')->create('tbl_carton_dimension', function (Blueprint $table) {
            $table->integer('idtbl_carton_dimension')->primary();
            $table->integer('prodstyle_list_id');
            $table->string('styleid');
            $table->string('tbl_style_size_desc');
            $table->integer('status')->default(1);
        });
        Schema::connection('erav')->create('porderctndeliaddrlist', function (Blueprint $table) {
            $table->id();
            $table->integer('porder_pdf_list_id');
            $table->string('item_styleno');
            $table->string('item_colorcode');
            $table->string('item_colordesc');
            $table->integer('salesotheraddr_custdest_id');
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('salesotheraddrlist', function (Blueprint $table) {
            $table->integer('custdest_id')->primary();
            $table->integer('customer_id');
            $table->string('custdest_name');
            $table->string('custdest_addr');
            $table->integer('qrycancel')->default(0);
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
                ->assertViewHas('customerName', '')
                ->assertViewHas('productionOrders', function ($orders) {
                    return $orders->isEmpty();
                })
                ->assertViewHas('shipmentNumber', '')
                ->assertSee('value="Factory ' . $factoryId . '"', false)
                ->assertSee($poNumbers[0])
                ->assertDontSee($poNumbers[1]);
        }
    }

    public function test_create_shipment_loads_customer_order_and_shipment_from_the_selected_assignment(): void
    {
        $shipment = $this->createSearchShipment('AF-000015');
        $assignmentId = DB::table('shipment_order_factories')->insertGetId([
            'shipment_id' => $shipment->id, 'purchase_order_id' => 763, 'factory_id' => 3,
        ]);
        DB::connection('erav')->table('prodorder_list')
            ->where('porder_pdf_list_id', 763)->where('mbranchlist_id', 3)
            ->update(['prodorder_ref' => 'VPO-3003259']);
        DB::connection('erav')->table('prodorder_list')->insert([
            ['porder_pdf_list_id' => 763, 'mbranchlist_id' => 3, 'prodorder_ref' => 'VPO-3003259', 'isoffline' => 0],
            ['porder_pdf_list_id' => 763, 'mbranchlist_id' => 3, 'prodorder_ref' => 'VPO-3003260', 'isoffline' => 0],
            ['porder_pdf_list_id' => 763, 'mbranchlist_id' => 3, 'prodorder_ref' => 'OFFLINE-ORDER', 'isoffline' => 1],
            ['porder_pdf_list_id' => 763, 'mbranchlist_id' => 29, 'prodorder_ref' => 'OTHER-FACTORY', 'isoffline' => 0],
            ['porder_pdf_list_id' => 1394, 'mbranchlist_id' => 3, 'prodorder_ref' => 'OTHER-PO', 'isoffline' => 0],
        ]);

        $user = new User(['name' => 'Factory User', 'mbranchlist_id' => 3]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
        $this->withMiddleware()->actingAs($user);

        $this->get(route('create-shipment.index', ['assignment_id' => $assignmentId]))
            ->assertOk()
            ->assertViewHas('selectedPurchaseOrderId', 763)
            ->assertSee('value="Test Buyer"', false)
            ->assertSee('value="AF-000015"', false)
            ->assertSee('VPO-3003259')
            ->assertSee('VPO-3003260')
            ->assertViewHas('selectedProductionOrderId', null)
            ->assertDontSee('OFFLINE-ORDER')
            ->assertDontSee('OTHER-FACTORY')
            ->assertDontSee('OTHER-PO');
    }

    public function test_create_shipment_rejects_missing_or_other_factory_assignments(): void
    {
        $shipment = $this->createSearchShipment('AF-000015');
        $otherAssignmentId = DB::table('shipment_order_factories')->insertGetId([
            'shipment_id' => $shipment->id, 'purchase_order_id' => 763, 'factory_id' => 29,
        ]);
        $user = new User(['name' => 'Factory User', 'mbranchlist_id' => 3]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
        $this->withMiddleware()->actingAs($user);

        foreach ([$otherAssignmentId, 99999] as $assignmentId) {
            $this->get(route('create-shipment.index', ['assignment_id' => $assignmentId, 'factory_id' => 29]))
                ->assertNotFound();
        }

        foreach ([['1'], 'invalid', 0] as $assignmentId) {
            $this->from(route('create-shipment.index'))
                ->get(route('create-shipment.index', ['assignment_id' => $assignmentId]))
                ->assertRedirect(route('create-shipment.index'))
                ->assertSessionHasErrors('assignment_id');
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

    public function test_created_shipments_have_edit_and_delete_actions_and_edit_loads_saved_values(): void
    {
        $this->prepareShipmentActions();
        $shipment = $this->createSearchShipment('AF-000001');
        $shipment->update([
            'purchase_order_id' => [763, 1394],
            'shipment_type' => 'consolidated',
            'factory_ids' => [3, 29],
        ]);

        $this->get(route('shipment-management.create'))->assertOk()
            ->assertSee(route('shipment-management.edit', $shipment->id), false)
            ->assertSee(route('shipment-management.destroy', $shipment->id), false)
            ->assertSee('name="_method" value="DELETE"', false);

        $response = $this->get(route('shipment-management.edit', $shipment->id))->assertOk()
            ->assertSee('Update Shipment')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="AF-000001"', false);
        $this->assertTrue($shipment->is($response->viewData('editingShipment')));
        $response->assertSee('loadPurchaseOrders([763,1394], [3,29]);', false);
    }

    public function test_update_keeps_its_own_id_and_replaces_only_its_assignments(): void
    {
        $this->prepareShipmentActions();
        $shipment = $this->createSearchShipment('AF-000001');
        $other = $this->createSearchShipment('AF-000002');
        foreach ([$shipment, $other] as $record) {
            DB::table('shipment_order_factories')->insert([
                'shipment_id' => $record->id, 'purchase_order_id' => 763, 'factory_id' => 3,
            ]);
        }

        $this->put(route('shipment-management.update', $shipment->id), $this->shipmentEditData())
            ->assertSessionHasNoErrors()->assertSessionHas('success')
            ->assertRedirect(route('shipment-management.create'));

        $shipment->refresh();
        $this->assertSame([1394], $shipment->purchase_order_id);
        $this->assertSame([29], $shipment->factory_ids);
        $this->assertDatabaseCount('shipments', 2);
        $this->assertDatabaseCount('shipment_order_factories', 2);
        $this->assertDatabaseHas('shipment_order_factories', [
            'shipment_id' => $shipment->id, 'purchase_order_id' => 1394, 'factory_id' => 29,
        ]);
        $this->assertDatabaseHas('shipment_order_factories', [
            'shipment_id' => $other->id, 'purchase_order_id' => 763, 'factory_id' => 3,
        ]);

        // An unused shipment can also be renamed.
        $data = $this->shipmentEditData();
        $data['shipment_id'] = 'CUSTOM-UPDATED';
        $this->put(route('shipment-management.update', $shipment->id), $data)->assertSessionHasNoErrors();
        $this->assertSame('CUSTOM-UPDATED', $shipment->fresh()->shipment_id);
    }

    public function test_invalid_edits_keep_the_original_shipment_and_assignments(): void
    {
        $this->prepareShipmentActions();
        $shipment = $this->createSearchShipment('AF-000001');
        $this->createSearchShipment('AF-000002');
        DB::table('shipment_order_factories')->insert([
            'shipment_id' => $shipment->id, 'purchase_order_id' => 763, 'factory_id' => 3,
        ]);
        $before = $shipment->fresh()->getAttributes();

        $data = $this->shipmentEditData();
        $data['shipment_id'] = 'AF-000002';
        $this->from(route('shipment-management.edit', $shipment->id))
            ->put(route('shipment-management.update', $shipment->id), $data)
            ->assertSessionHasErrors('shipment_id');

        $data = $this->shipmentEditData();
        $data['purchase_order_ids'] = [763]; // PO-763 does not belong to Factory 29.
        $this->put(route('shipment-management.update', $shipment->id), $data)
            ->assertSessionHasErrors('purchase_order_ids');

        $this->assertSame($before, $shipment->fresh()->getAttributes());
        $this->assertDatabaseHas('shipment_order_factories', [
            'shipment_id' => $shipment->id, 'purchase_order_id' => 763, 'factory_id' => 3,
        ]);
    }

    public function test_delete_removes_only_the_selected_shipment_and_its_assignments(): void
    {
        $this->prepareShipmentActions();
        $shipment = $this->createSearchShipment('AF-000001');
        $other = $this->createSearchShipment('AF-000002');
        foreach ([$shipment, $other] as $record) {
            DB::table('shipment_order_factories')->insert([
                'shipment_id' => $record->id, 'purchase_order_id' => 763, 'factory_id' => 3,
            ]);
        }

        $this->delete(route('shipment-management.destroy', $shipment->id))
            ->assertRedirect(route('shipment-management.create'))->assertSessionHas('success');

        $this->assertDatabaseMissing('shipments', ['id' => $shipment->id]);
        $this->assertDatabaseMissing('shipment_order_factories', ['shipment_id' => $shipment->id]);
        $this->assertDatabaseHas('shipments', ['id' => $other->id]);
        $this->assertDatabaseHas('shipment_order_factories', ['shipment_id' => $other->id]);
    }

    public static function linkedShipmentRecords(): array
    {
        return [
            'delivery' => ['erav', 'prodshipment_list', 'shipment_refno'],
            'factory document' => ['sqlite', 'shipment_documents', 'shipment_id'],
            'forwarder document' => ['sqlite', 'forwarder_documents', 'shipment_id'],
        ];
    }

    #[DataProvider('linkedShipmentRecords')]
    public function test_linked_records_prevent_edit_and_delete(string $connection, string $table, string $column): void
    {
        $this->prepareShipmentActions();
        $shipment = $this->createSearchShipment('AF-000001');
        DB::connection($connection)->table($table)->insert([$column => $shipment->shipment_id]);

        $this->get(route('shipment-management.edit', $shipment->id))
            ->assertRedirect(route('shipment-management.create'))->assertSessionHas('error');
        $this->put(route('shipment-management.update', $shipment->id), $this->shipmentEditData())
            ->assertRedirect(route('shipment-management.create'))->assertSessionHas('error');
        $this->delete(route('shipment-management.destroy', $shipment->id))
            ->assertRedirect(route('shipment-management.create'))->assertSessionHas('error');

        $this->assertSame([3], $shipment->fresh()->factory_ids);
        $this->assertSame(1, DB::connection($connection)->table($table)->count());
    }

    public function test_shipment_actions_require_an_admin_and_return_404_for_unknown_shipments(): void
    {
        $this->prepareShipmentActions();
        $this->get(route('shipment-management.edit', 99999))->assertNotFound();
        $this->put(route('shipment-management.update', 99999), [])->assertNotFound();
        $this->delete(route('shipment-management.destroy', 99999))->assertNotFound();

        $shipment = $this->createSearchShipment('AF-000001');
        $user = new User(['name' => 'Factory User', 'mbranchlist_id' => 3]);
        $user->id = 2;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
        $this->actingAs($user);
        $this->get(route('shipment-management.edit', $shipment->id))->assertForbidden();
        $this->put(route('shipment-management.update', $shipment->id), $this->shipmentEditData())->assertForbidden();
        $this->delete(route('shipment-management.destroy', $shipment->id))->assertForbidden();
        $this->assertDatabaseHas('shipments', ['id' => $shipment->id]);
    }

    private function shipmentEditData(): array
    {
        return [
            'country_of_origin' => 'AF', 'buyer_id' => 5,
            'shipment_id_mode' => 'manual', 'shipment_id' => 'AF-000001',
            'purchase_order_ids' => [1394], 'shipment_type' => 'individual', 'factory_id' => 29,
        ];
    }

    private function prepareShipmentActions(): void
    {
        $this->purchaseOrderMigration()->up();
        Schema::connection('erav')->create('prodshipment_list', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_refno');
        });
        foreach (['shipment_documents', 'forwarder_documents'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('shipment_id')->nullable();
            });
        }
        $this->signInAdmin();
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
