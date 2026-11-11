<?php

namespace Tests\Feature;

use App\Models\Erav\EravProdShipment;
use App\Models\Erav\EravProdShipmentItem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FactoryShipmentSaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Both databases are isolated in memory. Never insert test data into the live ERP.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.erav' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
        ]);
        DB::purge('sqlite');
        DB::purge('erav');

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id');
        });
        Schema::create('shipment_order_factories', function (Blueprint $table) {
            $table->id();
            $table->integer('shipment_id');
            $table->integer('purchase_order_id');
            $table->integer('factory_id');
        });
        Schema::connection('erav')->create('prodorder_list', function (Blueprint $table) {
            $table->integer('prodorder_id')->primary();
            $table->string('prodorder_ref', 50);
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->integer('isoffline')->default(0);
        });
        Schema::connection('erav')->create('porder_pdf_list', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('customer_id');
            $table->string('pdf_po_refno');
            $table->integer('isoffline')->default(0);
        });
        Schema::connection('erav')->create('porder_pdf_info', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('porder_pdf_list_id');
            $table->string('item_styleno');
            $table->string('item_colorcode');
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('prodstyle_list', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('styleid');
            $table->integer('isoffline')->default(0);
        });
        Schema::connection('erav')->create('prodorder_info', function (Blueprint $table) {
            $table->integer('pack_no')->primary();
            $table->integer('prodorder_id');
            $table->integer('porder_pdf_info_id');
            $table->integer('color_code');
            $table->string('tbl_style_color_code');
            $table->string('tbl_style_color_desc');
            $table->integer('size_type');
            $table->string('tbl_style_size_desc');
            $table->integer('prod_qty');
            $table->integer('qrycancel')->default(0);
        });
        Schema::connection('erav')->create('tbl_carton_dimension', function (Blueprint $table) {
            $table->integer('idtbl_carton_dimension')->primary();
            $table->integer('prodstyle_list_id');
            $table->string('styleid');
            $table->string('tbl_style_size_desc');
            $table->integer('pcs_per_carton');
            $table->double('carton_length');
            $table->double('carton_width');
            $table->double('carton_height');
            $table->double('item_weight');
            $table->double('carton_weight');
            $table->integer('status')->default(1);
        });
        Schema::connection('erav')->create('porderctndeliaddrlist', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('porder_pdf_list_id');
            $table->string('item_styleno');
            $table->string('item_colorcode');
            $table->string('item_colordesc');
            $table->integer('salesotheraddr_custdest_id');
            $table->integer('prodstyle_list_id')->nullable();
            $table->integer('prodcarton_from');
            $table->integer('prodcarton_to');
            $table->integer('pack_by_ratio')->default(1);
            $table->string('ctnpo_num', 50);
            $table->string('ctndoc_id', 30);
            $table->date('delivery_schdate')->nullable();
            $table->integer('qrycancel')->default(0);
            $table->string('created_by', 20);
            $table->dateTime('created_at');
        });
        Schema::connection('erav')->create('salesotheraddrlist', function (Blueprint $table) {
            $table->increments('custdest_id');
            $table->integer('customer_id');
            $table->string('custdest_name', 50);
            $table->string('custdest_addr', 200);
            $table->string('custdest_regno', 100);
            $table->integer('qrycancel')->default(0);
            $table->string('created_by', 20);
            $table->dateTime('created_at');
        });
        Schema::connection('erav')->create('mbranchlist', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('br_name');
        });
        Schema::connection('erav')->create('salescustomerlist', function (Blueprint $table) {
            $table->integer('customer_id')->primary();
            $table->string('customer_name');
        });

        // Match the required legacy columns and defaults, without Laravel timestamps.
        Schema::connection('erav')->create('prodshipment_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('prodorder_list_prodorder_id');
            $table->string('prodorder_ref', 50);
            $table->integer('porder_pdf_list_id');
            $table->integer('mbranchlist_id');
            $table->date('shiporder_docdate');
            $table->string('shipment_invno', 40);
            $table->string('stafford_invno', 40)->nullable();
            $table->string('shipment_moreinfo', 100);
            $table->string('shipment_refno', 15);
            $table->string('shipment_htsno', 15);
            $table->string('ship_container_no', 50)->nullable();
            $table->string('vessel_code', 25);
            $table->integer('import_id')->default(0);
            $table->integer('imports_container_number_id')->default(0);
            $table->date('plan_etd')->nullable();
            $table->string('ship_method', 20)->default('SEA');
            $table->string('pl_labels', 30)->nullable();
            $table->integer('ship_tot_cartons')->default(0);
            $table->double('ship_net_weight')->default(0);
            $table->double('ship_gross_weight')->default(0);
            $table->string('prepare_by', 20);
            $table->dateTime('prep_time');
            $table->string('updated_by', 20)->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('isoffline')->default(0);
            $table->integer('qryapproved')->default(0);
            $table->string('approve_by', 20)->nullable();
            $table->dateTime('approve_on')->nullable();
            $table->integer('qryfinalized')->default(0);
            $table->string('finalize_by', 20)->nullable();
            $table->dateTime('finalize_time')->nullable();
        });
        Schema::connection('erav')->create('prodshipment_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('prodshipment_list_id');
            $table->integer('prodorder_info_packno');
            $table->integer('porder_pdf_info_id');
            $table->integer('porderctndeliaddrlist_id')->default(0);
            $table->integer('prodstyle_list_id');
            $table->integer('color_code');
            $table->string('tbl_style_color_desc', 100);
            $table->string('tbl_style_color_code', 50);
            $table->integer('size_type');
            $table->string('tbl_style_size_desc', 50);
            $table->integer('ship_qty');
            $table->integer('full_carton_nos');
            $table->integer('odd_carton_nos');
            $table->double('pack_netwgt')->default(0);
            $table->double('pack_grossweight')->default(0);
            $table->double('odditems_netwgt')->default(0);
            $table->double('oddpack_grossweight')->default(0);
            $table->integer('idtbl_carton_dimension')->default(0);
            $table->double('carton_length')->default(0);
            $table->double('carton_width')->default(0);
            $table->double('carton_height')->default(0);
            $table->integer('pcs_per_carton');
            $table->integer('packrate_itemsize');
            $table->integer('baserate_itemcolor');
            $table->double('cbm_per_carton');
            $table->double('item_weight');
            $table->double('carton_weight');
            $table->integer('qrycancel')->default(0);
            $table->string('created_by', 20);
            $table->dateTime('created_at');
            $table->string('updated_by', 20)->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('shipments')->insert(['id' => 1, 'shipment_id' => 'AF-000015']);
        DB::table('shipment_order_factories')->insert([
            ['id' => 1, 'shipment_id' => 1, 'purchase_order_id' => 100, 'factory_id' => 3],
            ['id' => 2, 'shipment_id' => 1, 'purchase_order_id' => 100, 'factory_id' => 29],
        ]);
        DB::connection('erav')->table('prodorder_list')->insert([
            ['prodorder_id' => 10, 'prodorder_ref' => 'VPO-3003259', 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 3, 'isoffline' => 0],
            ['prodorder_id' => 11, 'prodorder_ref' => 'OTHER-FACTORY', 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 29, 'isoffline' => 0],
            ['prodorder_id' => 12, 'prodorder_ref' => 'OTHER-PO', 'porder_pdf_list_id' => 200, 'mbranchlist_id' => 3, 'isoffline' => 0],
            ['prodorder_id' => 13, 'prodorder_ref' => 'OFFLINE', 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 3, 'isoffline' => 1],
            ['prodorder_id' => 14, 'prodorder_ref' => '', 'porder_pdf_list_id' => 100, 'mbranchlist_id' => 3, 'isoffline' => 0],
        ]);
        DB::connection('erav')->table('mbranchlist')->insert(['id' => 3, 'br_name' => 'My Factory']);
        DB::connection('erav')->table('salescustomerlist')->insert(['customer_id' => 5, 'customer_name' => 'My Customer']);
        DB::connection('erav')->table('porder_pdf_list')->insert([
            ['id' => 100, 'customer_id' => 5, 'pdf_po_refno' => 'PO-100'],
            ['id' => 200, 'customer_id' => 6, 'pdf_po_refno' => 'PO-200'],
        ]);
        DB::connection('erav')->table('prodstyle_list')->insert([
            ['id' => 30, 'styleid' => 'STYLE-1', 'isoffline' => 0],
            ['id' => 31, 'styleid' => 'STYLE-2', 'isoffline' => 0],
            ['id' => 32, 'styleid' => 'OFFLINE-STYLE', 'isoffline' => 1],
        ]);
        DB::connection('erav')->table('porder_pdf_info')->insert([
            ['id' => 500, 'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-1', 'item_colorcode' => 'BLUE', 'qrycancel' => 0],
            ['id' => 501, 'porder_pdf_list_id' => 200, 'item_styleno' => 'STYLE-2', 'item_colorcode' => 'BLUE', 'qrycancel' => 0],
            ['id' => 502, 'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-1', 'item_colorcode' => 'BLUE', 'qrycancel' => 1],
            ['id' => 503, 'porder_pdf_list_id' => 100, 'item_styleno' => 'OFFLINE-STYLE', 'item_colorcode' => 'BLUE', 'qrycancel' => 0],
        ]);
        $baseItem = [
            'prodorder_id' => 10, 'porder_pdf_info_id' => 500, 'color_code' => 3,
            'tbl_style_color_code' => 'BLUE', 'tbl_style_color_desc' => 'Blue',
            'size_type' => 2, 'tbl_style_size_desc' => 'M', 'prod_qty' => 125, 'qrycancel' => 0,
        ];
        DB::connection('erav')->table('prodorder_info')->insert([
            array_merge($baseItem, ['pack_no' => 1000]),
            array_merge($baseItem, ['pack_no' => 1001, 'prodorder_id' => 11]),
            array_merge($baseItem, ['pack_no' => 1002, 'porder_pdf_info_id' => 501]),
            array_merge($baseItem, ['pack_no' => 1003, 'qrycancel' => 1]),
            array_merge($baseItem, ['pack_no' => 1004, 'porder_pdf_info_id' => 502]),
            array_merge($baseItem, ['pack_no' => 1005, 'porder_pdf_info_id' => 503]),
        ]);
        $baseCarton = [
            'prodstyle_list_id' => 30, 'styleid' => 'STYLE-1', 'tbl_style_size_desc' => 'M',
            'pcs_per_carton' => 12, 'carton_length' => 60, 'carton_width' => 40, 'carton_height' => 30,
            'item_weight' => 0.2, 'carton_weight' => 0.5, 'status' => 1,
        ];
        DB::connection('erav')->table('tbl_carton_dimension')->insert([
            array_merge($baseCarton, ['idtbl_carton_dimension' => 70]),
            array_merge($baseCarton, ['idtbl_carton_dimension' => 71, 'prodstyle_list_id' => 31, 'styleid' => 'STYLE-2']),
            array_merge($baseCarton, ['idtbl_carton_dimension' => 72, 'tbl_style_size_desc' => 'L']),
            array_merge($baseCarton, ['idtbl_carton_dimension' => 73, 'status' => 0]),
        ]);
        DB::connection('erav')->table('salesotheraddrlist')->insert([
            ['custdest_id' => 90, 'customer_id' => 5, 'custdest_name' => 'Main Warehouse', 'custdest_addr' => '10 Main Street', 'qrycancel' => 0, 'custdest_regno' => '', 'created_by' => '7', 'created_at' => '2026-11-09 08:00:00'],
            ['custdest_id' => 91, 'customer_id' => 6, 'custdest_name' => 'Other Customer', 'custdest_addr' => 'Hidden Address', 'qrycancel' => 0, 'custdest_regno' => '', 'created_by' => '7', 'created_at' => '2026-11-09 08:00:00'],
            ['custdest_id' => 92, 'customer_id' => 5, 'custdest_name' => 'Cancelled Address', 'custdest_addr' => 'Closed Address', 'qrycancel' => 1, 'custdest_regno' => '', 'created_by' => '7', 'created_at' => '2026-11-09 08:00:00'],
        ]);
        $baseAddress = [
            'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-1', 'item_colorcode' => 'BLUE',
            'item_colordesc' => 'Blue', 'salesotheraddr_custdest_id' => 90, 'qrycancel' => 0,
            'prodstyle_list_id' => 30, 'prodcarton_from' => 1, 'prodcarton_to' => 11,
            'pack_by_ratio' => 0, 'ctnpo_num' => 'PO-100', 'ctndoc_id' => 'EXISTING-1',
            'created_by' => '7', 'created_at' => '2026-11-09 08:00:00',
        ];
        DB::connection('erav')->table('porderctndeliaddrlist')->insert([
            array_merge($baseAddress, ['id' => 80]),
            array_merge($baseAddress, ['id' => 81, 'porder_pdf_list_id' => 200]),
            array_merge($baseAddress, ['id' => 82, 'item_styleno' => 'STYLE-2']),
            array_merge($baseAddress, ['id' => 83, 'item_colorcode' => 'RED']),
            array_merge($baseAddress, ['id' => 84, 'qrycancel' => 1]),
            array_merge($baseAddress, ['id' => 85, 'salesotheraddr_custdest_id' => 91]),
            array_merge($baseAddress, ['id' => 86, 'salesotheraddr_custdest_id' => 92]),
        ]);
    }

    public function test_factory_saves_the_mapped_header_with_trusted_links_and_approval_details(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'remarks' => 'Handle with care',
            'container_number' => 'CONT-100',
            'vessel' => 'VESSEL-1',
            'stafford_invoice_number' => 'STAFF-100',
            'etd' => '2026-11-15',
            'pl_label' => 'PL-100',
            'approve_shipment' => '1',
            'odd_net_weight' => 1,
            'odd_gross_weight' => 1.5,
            // These submitted values must never override the checked assignment.
            'shipment_number' => 'FORGED',
            'order_reference' => 'FORGED',
            'purchase_order_id' => 999,
            'mbranchlist_id' => 29,
            'full_cartons' => 999,
            'cbm_per_carton' => 999,
            'weight_per_piece' => 999,
        ]))->assertSessionHasNoErrors()
            ->assertRedirect(route('shipment-details.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('prodshipment_list', 1, 'erav');
        $this->assertDatabaseHas('prodshipment_list', [
            'prodorder_list_prodorder_id' => 10,
            'prodorder_ref' => 'VPO-3003259',
            'porder_pdf_list_id' => 100,
            'mbranchlist_id' => 3,
            'shiporder_docdate' => '2026-11-09',
            'shipment_invno' => 'INV-100',
            'stafford_invno' => 'STAFF-100',
            'shipment_moreinfo' => 'Handle with care',
            'shipment_refno' => 'AF-000015',
            'shipment_htsno' => '',
            'ship_container_no' => 'CONT-100',
            'vessel_code' => 'VESSEL-1',
            'ship_method' => 'SEA',
            'plan_etd' => '2026-11-15',
            'pl_labels' => 'PL-100',
            'prepare_by' => '7',
            'isoffline' => 0,
            'qryapproved' => 1,
            'approve_by' => '7',
            'qryfinalized' => 0,
            'ship_tot_cartons' => 11,
            'ship_net_weight' => 25,
            'ship_gross_weight' => 30,
        ], 'erav');
        $delivery = EravProdShipment::sole();
        $this->assertNotNull($delivery->prep_time);
        $this->assertSame($delivery->prep_time, $delivery->approve_on);
        $this->assertDatabaseCount('prodshipment_info', 1, 'erav');
        $this->assertDatabaseHas('prodshipment_info', [
            'prodshipment_list_id' => $delivery->id, 'prodorder_info_packno' => 1000,
            'porder_pdf_info_id' => 500, 'prodstyle_list_id' => 30,
            'color_code' => 3, 'tbl_style_color_code' => 'BLUE', 'tbl_style_color_desc' => 'Blue',
            'size_type' => 2, 'tbl_style_size_desc' => 'M', 'ship_qty' => 125,
            'full_carton_nos' => 10, 'odd_carton_nos' => 1, 'pcs_per_carton' => 12,
            'pack_netwgt' => 25, 'pack_grossweight' => 30,
            'odditems_netwgt' => 1, 'oddpack_grossweight' => 1.5,
            'idtbl_carton_dimension' => 70, 'carton_length' => 60, 'carton_width' => 40, 'carton_height' => 30,
            'item_weight' => 0.2, 'carton_weight' => 0.5, 'cbm_per_carton' => 0.072,
            'porderctndeliaddrlist_id' => 80, 'qrycancel' => 0, 'created_by' => '7',
        ], 'erav');
        $this->assertSame($delivery->id, $delivery->items()->sole()->shipment->id);
    }

    public function test_optional_fields_and_unchecked_approval_save_without_null_errors(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())
            ->assertSessionHasNoErrors()->assertRedirect(route('shipment-details.index'));

        $this->assertDatabaseHas('prodshipment_list', [
            'shipment_moreinfo' => '', 'vessel_code' => '', 'shipment_htsno' => '',
            'ship_container_no' => null, 'stafford_invno' => null,
            'plan_etd' => null, 'pl_labels' => null,
            'qryapproved' => 0, 'approve_by' => null, 'approve_on' => null,
            'ship_tot_cartons' => 11, 'ship_net_weight' => 25, 'ship_gross_weight' => 30,
        ], 'erav');
        $this->assertDatabaseHas('prodshipment_info', [
            'odditems_netwgt' => 0, 'oddpack_grossweight' => 0,
        ], 'erav');
    }

    public function test_invalid_fields_return_errors_and_do_not_create_a_header(): void
    {
        $this->actingAs($this->factoryUser());
        $invalid = [
            'prepared_date' => 'invalid', 'vendor_invoice_number' => '',
            'remarks' => str_repeat('A', 101), 'container_number' => str_repeat('A', 51),
            'vessel' => str_repeat('A', 26), 'stafford_invoice_number' => str_repeat('A', 41),
            'ship_via' => 'invalid', 'etd' => 'invalid',
            'pl_label' => str_repeat('A', 31), 'approve_shipment' => 'invalid',
            'ship_quantity' => 0, 'pieces_per_carton' => 0,
            'carton_length' => 0, 'carton_width' => -1, 'carton_height' => 0,
            'total_net_weight' => 10, 'total_gross_weight' => 9,
            'odd_net_weight' => 11, 'odd_gross_weight' => 12,
        ];
        $this->from(route('create-shipment.index', ['assignment_id' => 1]))
            ->post(route('create-shipment.store'), array_merge($this->validData(), $invalid))
            ->assertSessionHasErrors([
                'prepared_date', 'vendor_invoice_number', 'remarks', 'container_number',
                'vessel', 'stafford_invoice_number', 'ship_via', 'etd', 'pl_label', 'approve_shipment',
                'ship_quantity', 'pieces_per_carton', 'carton_length', 'carton_width', 'carton_height',
                'total_gross_weight', 'odd_net_weight', 'odd_gross_weight',
            ])
            ->assertSessionHasInput('remarks', $invalid['remarks']);
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
    }

    public function test_other_factory_missing_and_mismatched_orders_cannot_be_saved(): void
    {
        $this->actingAs($this->factoryUser());
        foreach ([['assignment_id' => 2], ['assignment_id' => 999],
            ['production_order_id' => 11], ['production_order_id' => 12],
            ['production_order_id' => 13], ['production_order_id' => 14],
            ['production_order_id' => 999]] as $changes) {
            $this->post(route('create-shipment.store'), array_merge($this->validData(), $changes))
                ->assertNotFound();
        }
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
    }

    public function test_overlong_stored_shipment_numbers_are_rejected_instead_of_truncated(): void
    {
        DB::table('shipments')->where('id', 1)->update(['shipment_id' => str_repeat('A', 16)]);
        $this->actingAs($this->factoryUser());
        $this->from(route('create-shipment.index', ['assignment_id' => 1]))
            ->post(route('create-shipment.store'), $this->validData())
            ->assertSessionHasErrors('shipment_number');
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
    }

    public function test_items_cartons_and_addresses_must_match_the_checked_order(): void
    {
        $this->actingAs($this->factoryUser());
        foreach ([1001, 1002, 1003, 1004, 1005, 9999] as $itemId) {
            $this->post(route('create-shipment.store'), array_merge($this->validData(), ['production_item_id' => $itemId]))
                ->assertNotFound();
        }
        foreach ([71, 72, 73, 9999] as $cartonId) {
            $this->post(route('create-shipment.store'), array_merge($this->validData(), ['carton_dimension_id' => $cartonId]))
                ->assertNotFound();
        }
        foreach ([81, 82, 83, 84, 85, 86, 9999] as $addressId) {
            $this->post(route('create-shipment.store'), array_merge($this->validData(), ['delivery_address_id' => $addressId]))
                ->assertNotFound();
        }
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
        $this->assertDatabaseCount('prodshipment_info', 0, 'erav');
    }

    public function test_remaining_quantity_accounts_for_previous_deliveries(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 100]))
            ->assertSessionHasNoErrors();
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 26]))
            ->assertSessionHasErrors('ship_quantity');
        $this->assertDatabaseCount('prodshipment_list', 1, 'erav');

        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 25]))
            ->assertSessionHasNoErrors();
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 1]))
            ->assertSessionHasErrors('ship_quantity');
        $this->assertDatabaseCount('prodshipment_list', 2, 'erav');
        $this->assertEquals(125, EravProdShipmentItem::sum('ship_qty'));
    }

    public function test_cancelled_items_and_inactive_headers_do_not_consume_remaining_quantity(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        EravProdShipmentItem::query()->update(['qrycancel' => 1]);
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        EravProdShipment::query()->update(['isoffline' => 1]);
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('prodshipment_list', 3, 'erav');
    }

    public function test_item_failure_rolls_back_the_header_and_allows_retry(): void
    {
        $this->actingAs($this->factoryUser());
        $eventName = 'eloquent.creating: ' . EravProdShipmentItem::class;
        Event::listen($eventName, function () {
            throw new \RuntimeException('Simulated item failure');
        });
        try {
            $this->post(route('create-shipment.store'), $this->validData())
                ->assertSessionHasErrors('shipment');
        } finally {
            Event::forget($eventName);
        }
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
        $this->assertDatabaseCount('prodshipment_info', 0, 'erav');
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('prodshipment_list', 1, 'erav');
    }

    public function test_carton_counts_and_volume_use_actual_packing_inputs(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'ship_quantity' => 120, 'carton_length' => 70,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prodshipment_info', [
            'full_carton_nos' => 10, 'odd_carton_nos' => 0,
            'carton_length' => 70, 'cbm_per_carton' => 0.084,
        ], 'erav');
    }

    public function test_odd_weights_require_an_odd_carton(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'ship_quantity' => 120, 'odd_net_weight' => 1, 'odd_gross_weight' => 2,
        ]))->assertSessionHasErrors('odd_net_weight');
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
    }

    public function test_odd_gross_weight_cannot_be_missing_when_odd_net_weight_is_entered(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'odd_net_weight' => 1, 'odd_gross_weight' => '',
        ]))->assertSessionHasErrors('odd_gross_weight');
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
    }

    public function test_form_loads_only_valid_items_and_customer_addresses(): void
    {
        $this->actingAs($this->factoryUser());
        $response = $this->get(route('create-shipment.index', ['assignment_id' => 1]))->assertOk();
        $this->assertSame([1000], $response->viewData('orderItems')->pluck('pack_no')->all());
        $this->assertEqualsCanonicalizing([70, 72], $response->viewData('cartonDimensions')->pluck('idtbl_carton_dimension')->all());
        $response->assertSee('Main Warehouse')->assertDontSee('Hidden Address')->assertDontSee('Closed Address');
        $response->assertSee('name="production_item_id"', false)
            ->assertSee('name="carton_dimension_id"', false)
            ->assertSee('name="delivery_address_id"', false);
    }

    public function test_manual_address_is_saved_and_linked_without_changing_existing_addresses(): void
    {
        $this->actingAs($this->factoryUser());
        $originalAddress = DB::connection('erav')->table('salesotheraddrlist')->where('custdest_id', 90)->first();
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'address_mode' => 'manual',
            'manual_address_name' => 'New Warehouse',
            'manual_address' => "20 Factory Road\nColombo",
            'delivery_address_id' => null,
            // The customer and PO links must come from the checked assignment.
            'customer_id' => 6, 'salesotheraddr_custdest_id' => 91,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('shipment-details.index'));

        $delivery = EravProdShipment::sole();
        $item = EravProdShipmentItem::sole();
        $address = DB::connection('erav')->table('porderctndeliaddrlist')
            ->where('id', $item->porderctndeliaddrlist_id)->first();
        $this->assertDatabaseHas('porderctndeliaddrlist', [
            'id' => $address->id, 'porder_pdf_list_id' => 100, 'item_styleno' => 'STYLE-1',
            'prodstyle_list_id' => 30, 'item_colorcode' => 'BLUE', 'item_colordesc' => 'Blue',
            'prodcarton_from' => 1, 'prodcarton_to' => 11, 'pack_by_ratio' => 0,
            'ctnpo_num' => 'PO-100', 'ctndoc_id' => 'SHIP-' . $delivery->id,
            'qrycancel' => 0, 'created_by' => '7',
        ], 'erav');
        $this->assertDatabaseHas('salesotheraddrlist', [
            'custdest_id' => $address->salesotheraddr_custdest_id,
            'customer_id' => 5, 'custdest_name' => 'New Warehouse',
            'custdest_addr' => "20 Factory Road\nColombo", 'custdest_regno' => '',
            'qrycancel' => 0, 'created_by' => '7',
        ], 'erav');
        $this->assertEquals($originalAddress, DB::connection('erav')->table('salesotheraddrlist')->where('custdest_id', 90)->first());
        $this->assertDatabaseCount('salesotheraddrlist', 4, 'erav');
        $this->assertDatabaseCount('porderctndeliaddrlist', 8, 'erav');
    }

    public function test_manual_address_works_without_saved_addresses_and_with_an_optional_name(): void
    {
        DB::connection('erav')->table('porderctndeliaddrlist')->delete();
        DB::connection('erav')->table('salesotheraddrlist')->delete();
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'address_mode' => 'manual', 'manual_address' => '20 Factory Road',
            'delivery_address_id' => 'unused',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('salesotheraddrlist', [
            'custdest_name' => 'Shipment AF-000015', 'custdest_addr' => '20 Factory Road', 'customer_id' => 5,
        ], 'erav');
    }

    public function test_address_mode_and_manual_address_limits_are_validated(): void
    {
        $this->actingAs($this->factoryUser());
        foreach ([null, '   ', str_repeat('A', 201)] as $address) {
            $this->post(route('create-shipment.store'), array_merge($this->validData(), [
                'address_mode' => 'manual', 'manual_address' => $address,
            ]))->assertSessionHasErrors('manual_address');
        }
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'address_mode' => 'manual', 'manual_address' => 'Valid address',
            'manual_address_name' => str_repeat('A', 51),
        ]))->assertSessionHasErrors('manual_address_name');
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'address_mode' => 'invalid',
        ]))->assertSessionHasErrors('address_mode');
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'address_mode' => 'existing', 'delivery_address_id' => null, 'manual_address' => 'Typed address',
        ]))->assertSessionHasErrors('delivery_address_id');
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
        $this->assertDatabaseCount('salesotheraddrlist', 3, 'erav');
    }

    public function test_manual_address_records_roll_back_when_the_item_cannot_be_saved(): void
    {
        $this->actingAs($this->factoryUser());
        $eventName = 'eloquent.creating: ' . EravProdShipmentItem::class;
        Event::listen($eventName, function () {
            throw new \RuntimeException('Simulated item failure');
        });
        try {
            $this->post(route('create-shipment.store'), array_merge($this->validData(), [
                'address_mode' => 'manual', 'manual_address' => 'New address',
            ]))->assertSessionHasErrors('shipment');
        } finally {
            Event::forget($eventName);
        }
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
        $this->assertDatabaseCount('prodshipment_info', 0, 'erav');
        $this->assertDatabaseCount('salesotheraddrlist', 3, 'erav');
        $this->assertDatabaseCount('porderctndeliaddrlist', 7, 'erav');
    }

    public function test_manual_address_and_name_are_kept_after_validation_errors(): void
    {
        $this->actingAs($this->factoryUser());
        $this->from(route('create-shipment.index', ['assignment_id' => 1]))
            ->post(route('create-shipment.store'), array_merge($this->validData(), [
                'address_mode' => 'manual', 'manual_address' => 'Building 2, Factory Road',
                'manual_address_name' => 'Warehouse A', 'vendor_invoice_number' => '',
            ]))->assertSessionHasErrors('vendor_invoice_number');
        $this->get(route('create-shipment.index', ['assignment_id' => 1]))
            ->assertOk()->assertSee('Building 2, Factory Road')->assertSee('Warehouse A');
    }

    public function test_saving_requires_a_factory_login_with_an_assigned_factory(): void
    {
        $this->post(route('create-shipment.store'), $this->validData())
            ->assertRedirect(route('login'));
        $this->actingAs($this->factoryUser(null));
        $this->post(route('create-shipment.store'), $this->validData())->assertForbidden();
        $user = $this->factoryUser();
        $user->setRelation('roles', collect([new Role(['name' => 'super_admin', 'guard_name' => 'web'])]));
        $this->actingAs($user);
        $this->post(route('create-shipment.store'), $this->validData())->assertForbidden();
        $this->assertDatabaseCount('prodshipment_list', 0, 'erav');
    }

    public function test_form_has_a_history_popup_in_place_of_the_new_shipment_button(): void
    {
        $this->actingAs($this->factoryUser());
        $this->get(route('create-shipment.index', ['assignment_id' => 1]))->assertOk()
            ->assertSee('data-bs-target="#shipment-history-modal"', false)
            ->assertSee('aria-label="View created shipment history"', false)
            ->assertSee('modal-dialog-scrollable')
            ->assertSee(json_encode(route('create-shipment.history')), false)
            ->assertDontSee('New Shipment');

        $this->get(route('create-shipment.history'))->assertOk()
            ->assertSee('No shipments created yet.')
            ->assertViewHas('historyShipments', function ($shipments) {
                return $shipments->isEmpty();
            });
    }

    public function test_history_shows_all_own_deliveries_and_details_without_exposing_another_factory(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'ship_quantity' => 60, 'remarks' => 'Handle with care',
            'container_number' => 'CONT-100', 'vessel' => 'VESSEL-1',
            'stafford_invoice_number' => 'STAFF-100', 'pl_label' => 'PL-100',
            'etd' => '2026-11-15', 'approve_shipment' => 1,
        ]))->assertSessionHasNoErrors();
        $first = EravProdShipment::sole();
        $firstItem = $first->items()->sole();

        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'ship_quantity' => 65, 'vendor_invoice_number' => 'INV-SECOND',
        ]))->assertSessionHasNoErrors();
        $second = EravProdShipment::orderByDesc('id')->first();
        // History includes old deliveries even if the current assignment has changed.
        $second->shipment_refno = 'OLD-SHIPMENT';
        $second->isoffline = 1;
        $second->save();
        $second->items()->update(['qrycancel' => 1]);

        $other = $first->replicate();
        $other->mbranchlist_id = 29;
        $other->shipment_refno = 'PRIVATE-SHIP';
        $other->shipment_invno = 'PRIVATE-INVOICE';
        $other->save();
        $otherItem = $firstItem->replicate();
        $otherItem->created_at = $firstItem->getRawOriginal('created_at');
        $otherItem->prodshipment_list_id = $other->id;
        $otherItem->tbl_style_color_desc = 'PRIVATE-COLOR';
        $otherItem->save();

        $response = $this->get(route('create-shipment.history', [
            'factory_id' => 29, 'assignment_id' => 2,
        ]))->assertOk()->assertSee('My Factory')->assertSee('My Customer')
            ->assertSee('AF-000015')->assertSee('OLD-SHIPMENT')->assertSee('PO-100')
            ->assertSee('VPO-3003259')->assertSee('INV-100')->assertSee('INV-SECOND')
            ->assertSee('CONT-100')->assertSee('VESSEL-1')->assertSee('STAFF-100')
            ->assertSee('PL-100')->assertSee('2026-11-15')->assertSee('Handle with care')
            ->assertSee('STYLE-1')->assertSee('Blue')->assertSee('Main Warehouse')
            ->assertSee('CBM / Ctn')->assertSee('Total Gross Weight (kg)')
            ->assertSee('Approved')->assertSee('Inactive')->assertSee('Cancelled')
            ->assertDontSee('PRIVATE-SHIP')->assertDontSee('PRIVATE-INVOICE')->assertDontSee('PRIVATE-COLOR');

        $this->assertSame([$second->id, $first->id], $response->viewData('historyShipments')->pluck('id')->all());
        $items = $response->viewData('historyItems');
        $this->assertCount(2, $items);
        $this->assertSame($firstItem->id, $items[$first->id]->sole()->id);
        $this->assertSame(65, $items[$second->id]->sole()->ship_qty);
    }

    public function test_history_keeps_saved_records_when_lookup_data_is_missing(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        foreach (['prodstyle_list', 'prodorder_info', 'porderctndeliaddrlist',
            'salesotheraddrlist', 'porder_pdf_list', 'salescustomerlist'] as $table) {
            DB::connection('erav')->table($table)->delete();
        }

        $this->get(route('create-shipment.history'))->assertOk()
            ->assertSee('AF-000015')->assertSee('INV-100')->assertSee('125 pcs')
            ->assertSee('Style unavailable')->assertSee('Address unavailable');
    }

    public function test_history_escapes_saved_text_and_shows_manual_addresses(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), [
            'remarks' => '<script>alert(1)</script>',
            'address_mode' => 'manual', 'manual_address_name' => 'Warehouse A',
            'manual_address' => "20 Factory Road\nColombo",
        ]))->assertSessionHasNoErrors();

        $this->get(route('create-shipment.history'))->assertOk()
            ->assertSee('Warehouse A')->assertSee("20 Factory Road\nColombo")
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_history_requires_a_factory_login_with_an_assigned_factory(): void
    {
        $this->get(route('create-shipment.history'))->assertRedirect(route('login'));
        $this->actingAs($this->factoryUser(null));
        $this->get(route('create-shipment.history'))->assertForbidden();
        $user = $this->factoryUser();
        $user->setRelation('roles', collect([new Role(['name' => 'super_admin', 'guard_name' => 'web'])]));
        $this->actingAs($user);
        $this->get(route('create-shipment.history'))->assertForbidden();
    }

    public function test_history_edit_link_opens_the_form_with_existing_values(): void
    {
        $this->actingAs($this->factoryUser());
        $data = array_merge($this->validData(), [
            'remarks' => 'Existing remarks', 'container_number' => 'CONTAINER-A',
            'vessel' => 'VESSEL-A', 'stafford_invoice_number' => 'STAFF-A',
            'etd' => '2026-11-15', 'pl_label' => 'LABEL-A', 'approve_shipment' => 1,
            'carton_length' => 75, 'odd_net_weight' => 1, 'odd_gross_weight' => 2,
            'address_mode' => 'manual', 'manual_address_name' => 'Saved Warehouse',
            'manual_address' => "10 Factory Road\nColombo",
        ]);
        $this->post(route('create-shipment.store'), $data)->assertSessionHasNoErrors();
        $delivery = EravProdShipment::sole();

        $this->get(route('create-shipment.history'))->assertOk()
            ->assertSee(route('create-shipment.edit', $delivery->id), false)
            ->assertSee(route('create-shipment.destroy', $delivery->id), false)
            ->assertSee('name="_method" value="DELETE"', false);

        $response = $this->get(route('create-shipment.edit', $delivery->id))->assertOk()
            ->assertSee('Update Shipment')->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Existing remarks')->assertSee('CONTAINER-A')->assertSee('VESSEL-A')
            ->assertSee('STAFF-A')->assertSee('LABEL-A')->assertSee('Saved Warehouse')
            ->assertSee("10 Factory Road\nColombo")
            ->assertSee('name="carton_length" class="form-control" value="75"', false);
        $form = $response->viewData('formData');
        foreach (['production_order_id', 'production_item_id', 'carton_dimension_id', 'ship_quantity',
            'pieces_per_carton', 'carton_length', 'carton_width', 'carton_height',
            'total_net_weight', 'total_gross_weight', 'odd_net_weight', 'odd_gross_weight',
            'prepared_date', 'remarks', 'container_number', 'vessel', 'vendor_invoice_number',
            'stafford_invoice_number', 'ship_via', 'etd', 'pl_label', 'approve_shipment'] as $field) {
            $this->assertEquals($data[$field], $form[$field], $field);
        }
        $this->assertSame($delivery->items()->sole()->porderctndeliaddrlist_id, $form['delivery_address_id']);
    }

    public function test_update_changes_the_existing_header_and_item_without_creating_duplicates(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        $delivery = EravProdShipment::sole();
        $item = $delivery->items()->sole();
        $original = $delivery->getAttributes();

        // Forged assignment and shipment values must not move an existing delivery.
        $data = array_merge($this->validData(), [
            'assignment_id' => 2, 'shipment_number' => 'FORGED-NUMBER',
            'vendor_invoice_number' => 'INV-UPDATED', 'remarks' => 'Updated remarks',
            'ship_quantity' => 120, 'carton_length' => 70, 'approve_shipment' => 1,
        ]);
        $this->put(route('create-shipment.update', $delivery->id), $data)
            ->assertSessionHasNoErrors()->assertSessionHas('success')
            ->assertRedirect(route('create-shipment.index', ['history' => 1]));

        $this->assertDatabaseCount('prodshipment_list', 1, 'erav');
        $this->assertDatabaseCount('prodshipment_info', 1, 'erav');
        $this->assertDatabaseHas('prodshipment_list', [
            'id' => $delivery->id, 'shipment_invno' => 'INV-UPDATED', 'shipment_moreinfo' => 'Updated remarks',
            'shipment_refno' => 'AF-000015', 'mbranchlist_id' => 3, 'porder_pdf_list_id' => 100,
            'ship_tot_cartons' => 10, 'qryapproved' => 1, 'updated_by' => '7',
            'prepare_by' => $original['prepare_by'], 'prep_time' => $original['prep_time'],
        ], 'erav');
        $this->assertDatabaseHas('prodshipment_info', [
            'id' => $item->id, 'prodshipment_list_id' => $delivery->id, 'ship_qty' => 120,
            'full_carton_nos' => 10, 'odd_carton_nos' => 0, 'carton_length' => 70,
            'cbm_per_carton' => 0.084, 'updated_by' => '7',
        ], 'erav');
    }

    public function test_update_excludes_itself_but_checks_the_quantity_of_other_deliveries(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 100]))
            ->assertSessionHasNoErrors();
        $first = EravProdShipment::sole();
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 25]))
            ->assertSessionHasNoErrors();

        $this->put(route('create-shipment.update', $first->id), array_merge($this->validData(), ['ship_quantity' => 100]))
            ->assertSessionHasNoErrors();
        $before = $first->fresh()->getAttributes();
        $this->from(route('create-shipment.edit', $first->id))
            ->put(route('create-shipment.update', $first->id), array_merge($this->validData(), ['ship_quantity' => 101]))
            ->assertSessionHasErrors('ship_quantity');
        $this->assertSame($before, $first->fresh()->getAttributes());
        $this->assertEquals(100, $first->items()->sole()->ship_qty);
    }

    public function test_failed_update_keeps_the_saved_delivery_and_restores_submitted_form_values(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        $delivery = EravProdShipment::sole();
        $this->from(route('create-shipment.edit', $delivery->id))
            ->put(route('create-shipment.update', $delivery->id), array_merge($this->validData(), [
                'vendor_invoice_number' => '', 'remarks' => 'My unsaved change',
            ]))->assertSessionHasErrors('vendor_invoice_number');
        $this->get(route('create-shipment.edit', $delivery->id))->assertOk()
            ->assertSee('My unsaved change');
        $this->assertSame('INV-100', $delivery->fresh()->shipment_invno);

        Event::listen('eloquent.updating: ' . EravProdShipmentItem::class, function () {
            throw new \RuntimeException('Simulated update failure');
        });
        try {
            $this->put(route('create-shipment.update', $delivery->id), array_merge($this->validData(), [
                'vendor_invoice_number' => 'SHOULD-ROLL-BACK', 'ship_quantity' => 100,
            ]))->assertSessionHasErrors('shipment');
        } finally {
            Event::forget('eloquent.updating: ' . EravProdShipmentItem::class);
        }
        $this->assertSame('INV-100', $delivery->fresh()->shipment_invno);
        $this->assertEquals(125, $delivery->items()->sole()->ship_qty);
    }

    public function test_delete_removes_only_the_selected_delivery_and_items_and_releases_quantity(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 100]))
            ->assertSessionHasNoErrors();
        $first = EravProdShipment::sole();
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 25]))
            ->assertSessionHasNoErrors();
        $second = EravProdShipment::orderByDesc('id')->first();
        $this->delete(route('create-shipment.destroy', $first->id))
            ->assertSessionHas('success')->assertRedirect(route('create-shipment.index', ['history' => 1]));

        $this->assertDatabaseMissing('prodshipment_list', ['id' => $first->id], 'erav');
        $this->assertDatabaseMissing('prodshipment_info', ['prodshipment_list_id' => $first->id], 'erav');
        $this->assertDatabaseHas('prodshipment_list', ['id' => $second->id], 'erav');
        $this->assertDatabaseHas('prodshipment_info', ['prodshipment_list_id' => $second->id], 'erav');
        $this->assertDatabaseCount('porderctndeliaddrlist', 7, 'erav');
        $this->assertDatabaseCount('salesotheraddrlist', 3, 'erav');
        $this->post(route('create-shipment.store'), array_merge($this->validData(), ['ship_quantity' => 100]))
            ->assertSessionHasNoErrors();
    }

    public function test_other_factories_and_unassigned_users_cannot_edit_update_or_delete_deliveries(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        $delivery = EravProdShipment::sole();
        $this->actingAs($this->factoryUser(29));
        $this->get(route('create-shipment.edit', $delivery->id))->assertNotFound();
        $this->put(route('create-shipment.update', $delivery->id), $this->validData())->assertNotFound();
        $this->delete(route('create-shipment.destroy', $delivery->id))->assertNotFound();
        $this->actingAs($this->factoryUser(null));
        $this->get(route('create-shipment.edit', $delivery->id))->assertForbidden();
        $this->put(route('create-shipment.update', $delivery->id), $this->validData())->assertForbidden();
        $this->delete(route('create-shipment.destroy', $delivery->id))->assertForbidden();
        $this->assertDatabaseCount('prodshipment_list', 1, 'erav');
    }

    public function test_finalized_or_linked_deliveries_are_protected_and_multiple_items_are_preserved(): void
    {
        $this->actingAs($this->factoryUser());
        $this->post(route('create-shipment.store'), $this->validData())->assertSessionHasNoErrors();
        $delivery = EravProdShipment::sole();
        foreach (['qryfinalized', 'import_id', 'imports_container_number_id'] as $column) {
            $delivery->$column = 1;
            $delivery->save();
            $this->get(route('create-shipment.edit', $delivery->id))->assertSessionHas('error');
            $this->put(route('create-shipment.update', $delivery->id), $this->validData())->assertSessionHas('error');
            $this->delete(route('create-shipment.destroy', $delivery->id))->assertSessionHas('error');
            $delivery->$column = 0;
            $delivery->save();
        }

        $item = $delivery->items()->sole();
        $copy = $item->replicate();
        $copy->created_at = $item->getRawOriginal('created_at');
        $copy->save();
        $this->get(route('create-shipment.edit', $delivery->id))->assertSessionHas('error');
        $this->put(route('create-shipment.update', $delivery->id), $this->validData())->assertSessionHas('error');
        $this->assertDatabaseCount('prodshipment_info', 2, 'erav');
    }

    private function validData(): array
    {
        return [
            'assignment_id' => 1,
            'production_order_id' => 10,
            'production_item_id' => 1000,
            'carton_dimension_id' => 70,
            'delivery_address_id' => 80,
            'ship_quantity' => 125,
            'pieces_per_carton' => 12,
            'carton_length' => 60,
            'carton_width' => 40,
            'carton_height' => 30,
            'total_net_weight' => 25,
            'total_gross_weight' => 30,
            'prepared_date' => '2026-11-09',
            'vendor_invoice_number' => 'INV-100',
            'ship_via' => 'sea',
        ];
    }

    private function factoryUser(?int $factoryId = 3): User
    {
        $user = new User(['name' => 'Factory User', 'mbranchlist_id' => $factoryId]);
        $user->id = 7;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));

        return $user;
    }
}
