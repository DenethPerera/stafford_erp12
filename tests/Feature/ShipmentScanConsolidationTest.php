<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentScanConsolidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Share one isolated SQLite database so main.table joins can be tested.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.erav' => [
                'driver' => 'sqlite', 'database' => 'main', 'prefix' => '',
            ],
        ]);
        DB::purge('sqlite');
        DB::purge('erav');
        $pdo = DB::connection('sqlite')->getPdo();
        DB::connection('erav')->setPdo($pdo)->setReadPdo($pdo);

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id');
            $table->integer('import_id')->nullable();
        });
        Schema::create('shipment_order_factories', function (Blueprint $table) {
            $table->id();
            $table->integer('shipment_id');
            $table->integer('purchase_order_id');
            $table->integer('factory_id');
        });
        Schema::create('shipment_documents', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id');
        });
        Schema::create('mbranchlist', function (Blueprint $table) {
            $table->id();
            $table->string('br_name');
        });
        Schema::create('porder_pdf_list', function (Blueprint $table) {
            $table->id();
            $table->string('pdf_po_refno');
        });
        Schema::create('porder_pdf_info', function (Blueprint $table) {
            $table->id();
            $table->integer('porder_pdf_list_id');
            $table->string('item_styleno')->nullable();
            $table->string('item_description')->nullable();
            $table->decimal('item_order_qty')->nullable();
            $table->decimal('revised_unit_price')->nullable();
            $table->integer('qrycancel')->default(0);
        });
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->integer('deleted')->default(0);
            $table->integer('delivery_status')->nullable();
            $table->string('house_bl_no')->nullable();
            $table->string('vsl_name')->nullable();
            $table->string('key_val')->nullable();
            $table->string('container_type')->nullable();
            $table->string('loading_port_name')->nullable();
            $table->string('discharge_port_name')->nullable();
            $table->string('port_edt_date')->nullable();
            $table->string('port_eta_date')->nullable();
        });
        Schema::create('imports_po_nos', function (Blueprint $table) {
            $table->integer('import_id');
            $table->string('po_no');
        });
        Schema::create('tbl_delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('imports_container_numbers', function (Blueprint $table) {
            $table->id();
            $table->integer('import_id');
            $table->string('container_no');
        });
        Schema::create('prodshipment_list', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_refno', 255);
            $table->integer('import_id')->nullable()->default(0);
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->integer('prodorder_list_prodorder_id');
            $table->integer('isoffline')->default(0);
            $table->string('ship_container_no')->nullable();
        });
        Schema::create('prodshipment_info', function (Blueprint $table) {
            $table->id();
            $table->integer('prodshipment_list_id');
            $table->integer('porder_pdf_info_id');
            $table->decimal('ship_qty')->nullable();
            $table->integer('full_carton_nos')->nullable();
            $table->integer('odd_carton_nos')->nullable();
            $table->decimal('cbm_per_carton', 12, 6)->nullable();
            $table->integer('qrycancel')->default(0);
        });
        Schema::create('prodorder_list', function (Blueprint $table) {
            $table->integer('prodorder_id')->primary();
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->integer('currency_type');
        });
        Schema::create('tbl_currency', function (Blueprint $table) {
            $table->integer('idtbl_currency')->primary();
            $table->string('currency_name');
        });

        DB::table('shipments')->insert(['id' => 1, 'shipment_id' => 'SHIP-TEST', 'import_id' => 1]);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 1, 'purchase_order_id' => 100, 'factory_id' => 10]);
        DB::table('imports')->insert(['id' => 1]);
        DB::table('mbranchlist')->insert(['id' => 10, 'br_name' => 'Factory One']);
        DB::table('porder_pdf_list')->insert(['id' => 100, 'pdf_po_refno' => 'PO-100']);
        DB::table('porder_pdf_info')->insert([
            'id' => 1000, 'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-1',
            'item_order_qty' => 100, 'revised_unit_price' => 2,
        ]);
        DB::table('prodorder_list')->insert([
            'prodorder_id' => 50, 'porder_pdf_list_id' => 100,
            'mbranchlist_id' => 10, 'currency_type' => 1,
        ]);
        DB::table('tbl_currency')->insert([
            ['idtbl_currency' => 1, 'currency_name' => 'USD'],
            ['idtbl_currency' => 2, 'currency_name' => 'EURO'],
        ]);

        $user = new User(['name' => 'Scan Test']);
        $user->id = 1;
        $user->setRelation('roles', collect([
            new Role(['name' => 'super_admin', 'guard_name' => 'web']),
        ]));
        $this->actingAs($user);
    }

    public function test_multiple_deliveries_and_duplicate_links_do_not_multiply_totals(): void
    {
        $this->addDelivery(1);
        $this->addDelivery(2, [], ['ship_qty' => 20, 'full_carton_nos' => 2, 'odd_carton_nos' => 1, 'cbm_per_carton' => 0.2]);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 1, 'purchase_order_id' => 100, 'factory_id' => 10]);

        $response = $this->scan();
        $totals = $response->viewData('consolidationTotals');
        $this->assertEquals(100, $totals['ordered_qty']);
        $this->assertEquals(30, $totals['shipped_qty']);
        $this->assertEquals(4, $totals['cartons']);
        $this->assertEqualsWithDelta(0.7, $totals['cbm'], 0.000001);
        $this->assertEquals(['USD' => 60], $totals['shipped_values']);
        $factory = $response->viewData('consolidationFactories')[0];
        $this->assertCount(1, $factory['rows']);
        $this->assertEquals(4, $factory['rows']->first()->cartons);
        $response->assertSee('Shipped Qty')->assertSee('USD 60.00');
    }

    public function test_only_matching_active_shipment_po_factory_and_item_records_are_counted(): void
    {
        $this->addDelivery(1);
        // Even the same PO, factory, and import cannot link another shipment.
        $this->addDelivery(2, ['shipment_refno' => 'OTHER-SHIPMENT']);
        $this->addDelivery(3, ['mbranchlist_id' => 11]);
        $this->addDelivery(4, ['porder_pdf_list_id' => 200]);
        $this->addDelivery(5, [], ['qrycancel' => 1]);
        $this->addDelivery(6, ['isoffline' => 1]);
        DB::table('porder_pdf_info')->insert([
            ['id' => 1001, 'porder_pdf_list_id' => 100, 'qrycancel' => 1],
            ['id' => 2000, 'porder_pdf_list_id' => 200, 'qrycancel' => 0],
        ]);
        $this->addDelivery(7, [], ['porder_pdf_info_id' => 1001]);
        $this->addDelivery(8, [], ['porder_pdf_info_id' => 2000]);

        $totals = $this->scan()->viewData('consolidationTotals');
        $this->assertEquals(10, $totals['shipped_qty']);
        $this->assertEquals(1, $totals['cartons']);
        $this->assertEquals(['USD' => 20], $totals['shipped_values']);
    }

    public function test_size_and_color_items_with_the_same_po_and_style_share_one_row(): void
    {
        DB::table('porder_pdf_info')->where('id', 1000)->update(['item_description' => 'T-shirt']);
        DB::table('porder_pdf_info')->insert([
            'id' => 1001, 'porder_pdf_list_id' => 100, 'item_styleno' => ' STYLE-1 ',
            'item_description' => 'T-shirt', 'item_order_qty' => 50, 'revised_unit_price' => 3,
        ]);
        $this->addDelivery(1);
        $this->addDelivery(2, [], ['ship_qty' => 20, 'full_carton_nos' => 2, 'cbm_per_carton' => 0.2]);
        $this->addDelivery(3, [], ['porder_pdf_info_id' => 1001, 'ship_qty' => 5, 'cbm_per_carton' => 0.3]);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 1, 'purchase_order_id' => 100, 'factory_id' => 10]);

        $response = $this->scan();
        $factory = $response->viewData('consolidationFactories')[0];
        $this->assertCount(1, $factory['rows']);
        $row = $factory['rows']->first();
        $this->assertTrue($row->has_items);
        $this->assertSame('STYLE-1', $row->item_styleno);
        $this->assertSame('T-shirt', $row->item_description);
        $this->assertEquals(150, $row->item_order_qty);
        $this->assertEquals(35, $row->shipped_qty);
        $this->assertEquals(4, $row->cartons);
        $this->assertEqualsWithDelta(0.8, $row->cbm, 0.000001);
        $this->assertEquals(['USD' => 75], $row->shipped_values);
        $this->assertEquals(150, $factory['ordered_qty']);
        $this->assertEquals(150, $response->viewData('consolidationTotals')['ordered_qty']);
        $this->assertEquals(35, $response->viewData('consolidationTotals')['shipped_qty']);
        $response->assertSee('150.00')->assertSee('USD 75.00');
    }

    public function test_different_styles_and_purchase_orders_remain_separate(): void
    {
        DB::table('porder_pdf_list')->insert(['id' => 200, 'pdf_po_refno' => 'PO-200']);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 1, 'purchase_order_id' => 200, 'factory_id' => 10]);
        DB::table('porder_pdf_info')->insert([
            ['id' => 1001, 'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-2', 'item_order_qty' => 20],
            ['id' => 2000, 'porder_pdf_list_id' => 200, 'item_styleno' => 'STYLE-1', 'item_order_qty' => 30],
        ]);
        $this->addDelivery(1);

        $factory = $this->scan()->viewData('consolidationFactories')[0];
        $this->assertCount(3, $factory['rows']);
        $rows = $factory['rows']->values();
        $this->assertSame('PO-100', $rows[0]->pdf_po_refno);
        $this->assertSame('STYLE-1', $rows[0]->item_styleno);
        $this->assertEquals(100, $rows[0]->item_order_qty);
        $this->assertEquals(10, $rows[0]->shipped_qty);
        $this->assertSame('STYLE-2', $rows[1]->item_styleno);
        $this->assertEquals(20, $rows[1]->item_order_qty);
        $this->assertNull($rows[1]->shipped_qty);
        $this->assertSame('PO-200', $rows[2]->pdf_po_refno);
        $this->assertSame('STYLE-1', $rows[2]->item_styleno);
        $this->assertEquals(30, $rows[2]->item_order_qty);
        $this->assertNull($rows[2]->shipped_qty);
    }

    public function test_grouped_rows_preserve_missing_quantities_and_empty_purchase_orders(): void
    {
        DB::table('porder_pdf_info')->insert([
            'id' => 1001, 'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-1',
            'item_order_qty' => null, 'item_description' => 'Another description',
        ]);
        $factory = $this->scan()->viewData('consolidationFactories')[0];
        $this->assertCount(1, $factory['rows']);
        $this->assertNull($factory['rows']->first()->item_order_qty);
        $this->assertNull($factory['rows']->first()->cartons);
        $this->assertSame('Another description', $factory['rows']->first()->item_description);

        DB::table('porder_pdf_info')->update(['qrycancel' => 1]);
        $response = $this->scan();
        $rows = $response->viewData('consolidationFactories')[0]['rows'];
        $this->assertCount(1, $rows);
        $this->assertFalse($rows->first()->has_items);
        $response->assertSee('PO-100')->assertSee('No active PO items found');
    }

    public function test_shared_po_is_counted_once_for_ordered_quantity_and_each_actual_delivery_is_counted(): void
    {
        DB::table('mbranchlist')->insert(['id' => 11, 'br_name' => 'Factory Two']);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 1, 'purchase_order_id' => 100, 'factory_id' => 11]);
        DB::table('prodorder_list')->insert(['prodorder_id' => 51, 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 11, 'currency_type' => 1]);
        $this->addDelivery(1);
        $this->addDelivery(2, ['mbranchlist_id' => 11, 'prodorder_list_prodorder_id' => 51], ['ship_qty' => 20]);

        $response = $this->scan();
        $totals = $response->viewData('consolidationTotals');
        $this->assertEquals(100, $totals['ordered_qty']);
        $this->assertEquals(30, $totals['shipped_qty']);
        $factories = $response->viewData('consolidationFactories');
        $this->assertCount(2, $factories);
        $this->assertEquals(10, $factories[0]['shipped_qty']);
        $this->assertEquals(20, $factories[1]['shipped_qty']);
    }

    public function test_different_currencies_are_displayed_separately_and_unknown_currency_is_not_assumed(): void
    {
        DB::table('prodorder_list')->insert(['prodorder_id' => 51, 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 10, 'currency_type' => 2]);
        $this->addDelivery(1);
        $this->addDelivery(2, ['prodorder_list_prodorder_id' => 51], ['ship_qty' => 20]);
        $response = $this->scan();
        $this->assertEquals(['USD' => 20, 'EURO' => 40], $response->viewData('consolidationTotals')['shipped_values']);
        $response->assertSee('USD 20.00')->assertSee('EURO 40.00');

        // A production record for another factory cannot supply the currency.
        DB::table('prodorder_list')->where('prodorder_id', 51)->update(['mbranchlist_id' => 99]);
        $this->assertNull($this->scan()->viewData('consolidationTotals')['shipped_values']);
    }

    public function test_missing_values_stay_unavailable_but_zero_is_valid_without_a_manual_import(): void
    {
        $this->assertNull($this->scan()->viewData('consolidationTotals')['cartons']);
        $this->addDelivery(1, [], ['ship_qty' => 0, 'full_carton_nos' => 0]);
        $totals = $this->scan()->viewData('consolidationTotals');
        $this->assertSame(0.0, $totals['shipped_qty']);
        $this->assertSame(0.0, $totals['cartons']);
        $this->assertSame(0.0, $totals['cbm']);
        $this->assertEquals(['USD' => 0], $totals['shipped_values']);

        $this->addDelivery(2, [], ['ship_qty' => null, 'odd_carton_nos' => null, 'cbm_per_carton' => null]);
        $totals = $this->scan()->viewData('consolidationTotals');
        foreach (['shipped_qty', 'cartons', 'cbm', 'shipped_values'] as $field) {
            $this->assertNull($totals[$field]);
        }

        DB::table('shipments')->where('id', 1)->update(['import_id' => null]);
        $totals = $this->scan()->viewData('consolidationTotals');
        $this->assertEquals(100, $totals['ordered_qty']);
        $this->assertNull($totals['shipped_qty']);
    }

    public function test_quantities_do_not_require_an_import_and_never_use_a_stale_manual_import(): void
    {
        DB::table('shipments')->where('id', 1)->update(['import_id' => 999]);
        $this->addDelivery(1, ['import_id' => 0]);
        $this->addDelivery(2, ['import_id' => null], ['ship_qty' => 20]);

        $response = $this->scan();
        $totals = $response->viewData('consolidationTotals');
        $this->assertEquals(30, $totals['shipped_qty']);
        $this->assertEquals(2, $totals['cartons']);
        $this->assertEqualsWithDelta(0.2, $totals['cbm'], 0.000001);
        $this->assertNull($response->viewData('shippingDetails')[0]['bl_number']);
        $response->assertSee('without a linked Import ID')
            ->assertDontSee('The saved shipping record is missing or deleted.')
            ->assertDontSee('Matching Shipping Record')
            ->assertDontSee('No shipping records match this shipment');
    }

    public function test_multiple_imports_have_separate_details_and_containers_without_multiplying_quantities(): void
    {
        DB::table('shipments')->where('id', 1)->update(['import_id' => 999]);
        DB::table('tbl_delivery_statuses')->insert([
            ['id' => 1, 'name' => 'At sea'],
            ['id' => 2, 'name' => 'Arrived'],
        ]);
        DB::table('imports')->where('id', 1)->update([
            'house_bl_no' => 'BL-A', 'vsl_name' => 'Vessel A',
            'container_type' => '40HC', 'delivery_status' => 1,
            'key_val' => '0', 'port_edt_date' => '2026-11-01',
            'port_eta_date' => '2026-11-11',
        ]);
        DB::table('imports')->insert([
            'id' => 2, 'house_bl_no' => 'BL-B', 'container_type' => '20GP',
            'delivery_status' => 2, 'key_val' => '1',
        ]);
        DB::table('imports_container_numbers')->insert([
            ['import_id' => 1, 'container_no' => ' container-a '],
            ['import_id' => 1, 'container_no' => 'CONTAINER-A'],
            ['import_id' => 1, 'container_no' => 'CONTAINER-B'],
            ['import_id' => 2, 'container_no' => 'CONTAINER-C'],
            ['import_id' => 999, 'container_no' => 'UNRELATED-CONTAINER'],
        ]);
        $this->addDelivery(1);
        $this->addDelivery(2, ['import_id' => 1]);
        $this->addDelivery(3, ['import_id' => 2]);
        $this->addDelivery(4, ['shipment_refno' => 'OTHER-SHIPMENT', 'import_id' => 2]);

        $response = $this->scan();
        $details = $response->viewData('shippingDetails');
        $this->assertCount(2, $details);
        $this->assertSame(1, $details[0]['import_id']);
        $this->assertSame('BL-A', $details[0]['bl_number']);
        $this->assertSame('Import', $details[0]['export_import']);
        $this->assertSame('10 days', $details[0]['transit_time']);
        $this->assertSame('CONTAINER-A, CONTAINER-B', $details[0]['container_number']);
        $this->assertSame(2, $details[1]['import_id']);
        $this->assertSame('BL-B', $details[1]['bl_number']);
        $this->assertSame('Export', $details[1]['export_import']);
        $this->assertNull($details[1]['vessel_name']);
        $this->assertNull($details[1]['etd']);
        $this->assertNull($details[1]['transit_time']);
        $containers = $response->viewData('containerDetails');
        $this->assertCount(3, $containers);
        $this->assertSame('40HC', $containers[0]['size_type']);
        $this->assertSame(2, $containers[2]['import_id']);
        $this->assertSame('20GP', $containers[2]['size_type']);
        $this->assertEquals(30, $response->viewData('consolidationTotals')['shipped_qty']);
        $response->assertDontSee('UNRELATED-CONTAINER')
            ->assertDontSee('badge rounded-pill bg-primary bg-opacity-10 text-primary', false);
    }

    public function test_missing_and_deleted_imports_do_not_remove_production_quantities(): void
    {
        DB::table('imports')->insert(['id' => 2, 'deleted' => 1, 'house_bl_no' => 'DELETED-BL']);
        DB::table('imports_container_numbers')->insert([
            ['import_id' => 2, 'container_no' => 'DELETED-CONTAINER'],
            ['import_id' => 99, 'container_no' => 'ORPHAN-CONTAINER'],
        ]);
        $this->addDelivery(1);
        $this->addDelivery(2, ['import_id' => 2]);
        $this->addDelivery(3, ['import_id' => 99]);

        $response = $this->scan();
        $this->assertCount(1, $response->viewData('shippingDetails'));
        $this->assertEquals(30, $response->viewData('consolidationTotals')['shipped_qty']);
        $response->assertSee('One or more linked shipping records are missing or deleted.')
            ->assertDontSee('DELETED-BL')->assertDontSee('DELETED-CONTAINER')
            ->assertDontSee('ORPHAN-CONTAINER');
    }

    public function test_unassigned_deliveries_cannot_supply_import_information(): void
    {
        DB::table('shipments')->where('id', 1)->update(['import_id' => 1]);
        DB::table('imports')->where('id', 1)->update(['house_bl_no' => 'UNASSIGNED-BL']);
        $this->addDelivery(1, ['mbranchlist_id' => 99]);

        $response = $this->scan();
        $response->assertSee('not assigned to this shipment')->assertDontSee('UNASSIGNED-BL');
        $this->assertNull($response->viewData('consolidationTotals')['shipped_qty']);
    }

    public function test_container_fallback_only_reads_this_shipment_and_this_import(): void
    {
        $this->addDelivery(1, ['ship_container_no' => ' valid-container ']);
        $this->addDelivery(2, ['ship_container_no' => 'VALID-CONTAINER']);
        $this->addDelivery(3, ['ship_container_no' => 'N/A']);
        $this->addDelivery(4, ['shipment_refno' => 'OTHER-SHIPMENT', 'ship_container_no' => 'WRONG-SHIPMENT']);
        $this->addDelivery(5, ['isoffline' => 1, 'ship_container_no' => 'OFFLINE-CONTAINER']);
        $this->addDelivery(6, ['import_id' => 99, 'ship_container_no' => 'MISSING-IMPORT']);

        $response = $this->scan();
        $containers = $response->viewData('containerDetails');
        $this->assertCount(1, $containers);
        $this->assertSame('VALID-CONTAINER', $containers[0]['container_no']);
        $this->assertSame('VALID-CONTAINER', $response->viewData('shippingDetails')[0]['container_number']);
    }

    public function test_saved_historical_import_is_preserved_without_guessing_production_ownership(): void
    {
        DB::table('imports')->where('id', 1)->update(['house_bl_no' => 'HISTORICAL-BL']);
        $this->addDelivery(1, ['shipment_refno' => 'OLD-REFERENCE']);

        $response = $this->scan();
        $response->assertSee('HISTORICAL-BL')->assertSee('Historical shipping information is shown.');
        $this->assertNull($response->viewData('consolidationTotals')['shipped_qty']);
        $this->assertSame('OLD-REFERENCE', DB::table('prodshipment_list')->value('shipment_refno'));
        $this->assertEquals(1, DB::table('shipments')->value('import_id'));
    }

    public function test_po_matching_cannot_supply_an_import_without_a_shipment_reference(): void
    {
        DB::table('shipments')->where('id', 1)->update(['import_id' => null]);
        DB::table('imports')->where('id', 1)->update(['house_bl_no' => 'PO-ONLY-BL']);
        DB::table('imports_po_nos')->insert(['import_id' => 1, 'po_no' => 'PO-100']);
        $this->addDelivery(1, ['shipment_refno' => 'OTHER-SHIPMENT']);

        $response = $this->scan();
        $response->assertSee('No production shipment records are linked to this Shipment ID.')
            ->assertDontSee('PO-ONLY-BL')->assertDontSee('Choose a shipping record');
        $this->assertNull($response->viewData('consolidationTotals')['shipped_qty']);
    }

    public function test_offline_references_do_not_reactivate_a_stale_manual_import(): void
    {
        DB::table('imports')->where('id', 1)->update(['house_bl_no' => 'STALE-BL']);
        $this->addDelivery(1, ['isoffline' => 1]);

        $response = $this->scan();
        $response->assertDontSee('STALE-BL');
        $this->assertNull($response->viewData('consolidationTotals')['shipped_qty']);
    }

    public function test_full_shipment_number_is_used_without_truncation_or_case_folding(): void
    {
        $number = 'LK-TEST-000000000123456789';
        DB::table('shipments')->where('id', 1)->update(['shipment_id' => $number, 'import_id' => null]);
        $this->addDelivery(1, ['shipment_refno' => $number, 'import_id' => 0]);
        $this->addDelivery(2, ['shipment_refno' => substr($number, 0, 15)]);
        $this->addDelivery(3, ['shipment_refno' => strtolower($number)]);
        $this->addDelivery(4, ['shipment_refno' => $number . ' ']);

        $response = $this->get(route('shipment-management.scan', ['shipment_id' => $number]))->assertOk();
        $this->assertEquals(10, $response->viewData('consolidationTotals')['shipped_qty']);
    }

    public function test_a_shared_po_remains_isolated_between_two_searchable_shipments(): void
    {
        DB::table('shipments')->insert(['id' => 2, 'shipment_id' => 'SHIP-SECOND']);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 2, 'purchase_order_id' => 100, 'factory_id' => 10]);
        $this->addDelivery(1);
        $this->addDelivery(2, ['shipment_refno' => 'SHIP-SECOND'], ['ship_qty' => 25]);

        $this->assertEquals(10, $this->scan()->viewData('consolidationTotals')['shipped_qty']);
        $second = $this->get(route('shipment-management.scan', ['shipment_id' => 'SHIP-SECOND']))->assertOk();
        $this->assertEquals(25, $second->viewData('consolidationTotals')['shipped_qty']);
        $this->assertEquals(100, $second->viewData('consolidationTotals')['ordered_qty']);
    }

    public function test_manual_import_endpoint_no_longer_updates_shipments(): void
    {
        DB::table('shipments')->where('id', 1)->update(['import_id' => null]);
        $this->post(route('shipment-management.save-import'), [
            'shipment_id' => 'SHIP-TEST', 'import_id' => 1,
        ])->assertStatus(410);

        $this->assertNull(DB::table('shipments')->value('import_id'));
    }

    public function test_one_shipment_totals_multiple_pos_production_orders_and_imports(): void
    {
        DB::table('shipments')->where('id', 1)->update(['import_id' => null]);
        DB::table('imports')->insert(['id' => 2, 'house_bl_no' => 'SECOND-BL']);
        DB::table('porder_pdf_list')->insert(['id' => 200, 'pdf_po_refno' => 'PO-200']);
        DB::table('shipment_order_factories')->insert(['shipment_id' => 1, 'purchase_order_id' => 200, 'factory_id' => 10]);
        DB::table('porder_pdf_info')->insert([
            'id' => 2000, 'porder_pdf_list_id' => 200, 'item_styleno' => 'STYLE-2',
            'item_order_qty' => 200, 'revised_unit_price' => 2,
        ]);
        DB::table('prodorder_list')->insert([
            ['prodorder_id' => 51, 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 10, 'currency_type' => 1],
            ['prodorder_id' => 52, 'porder_pdf_list_id' => 200, 'mbranchlist_id' => 10, 'currency_type' => 1],
        ]);
        $this->addDelivery(1, ['import_id' => 0], ['ship_qty' => 100, 'full_carton_nos' => 10]);
        $this->addDelivery(2, ['prodorder_list_prodorder_id' => 51], [
            'ship_qty' => 50, 'full_carton_nos' => 5, 'cbm_per_carton' => 0.2,
        ]);
        $this->addDelivery(3, [
            'porder_pdf_list_id' => 200, 'prodorder_list_prodorder_id' => 52, 'import_id' => 2,
        ], ['porder_pdf_info_id' => 2000, 'ship_qty' => 30, 'full_carton_nos' => 3]);

        $response = $this->scan();
        $totals = $response->viewData('consolidationTotals');
        $this->assertEquals(300, $totals['ordered_qty']);
        $this->assertEquals(180, $totals['shipped_qty']);
        $this->assertEquals(18, $totals['cartons']);
        $this->assertEqualsWithDelta(2.3, $totals['cbm'], 0.000001);
        $this->assertEquals(['USD' => 360], $totals['shipped_values']);
        $this->assertCount(2, $response->viewData('shippingDetails'));
        $this->assertCount(2, $response->viewData('consolidationFactories')[0]['rows']);
    }

    private function addDelivery(int $id, array $delivery = [], array $item = []): void
    {
        DB::table('prodshipment_list')->insert(array_merge([
            'id' => $id, 'shipment_refno' => 'SHIP-TEST', 'import_id' => 1, 'porder_pdf_list_id' => 100,
            'mbranchlist_id' => 10, 'prodorder_list_prodorder_id' => 50,
        ], $delivery));
        DB::table('prodshipment_info')->insert(array_merge([
            'id' => $id, 'prodshipment_list_id' => $id, 'porder_pdf_info_id' => 1000,
            'ship_qty' => 10, 'full_carton_nos' => 1, 'odd_carton_nos' => 0,
            'cbm_per_carton' => 0.1,
        ], $item));
    }

    private function scan()
    {
        return $this->get(route('shipment-management.scan', ['shipment_id' => 'SHIP-TEST']))
            ->assertOk();
    }
}
