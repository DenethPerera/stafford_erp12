<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateSalesContractPricesRequest;
use App\Models\Erav\EravPurchaseOrder;
use App\Models\Erav\EravPurchaseOrderItem;
use App\Models\User;
use App\Services\SalesContractService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.erav' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::purge('erav');
        $schema = Schema::connection('erav');
        $schema->create('porder_pdf_list', function (Blueprint $t) {
            $t->increments('id');
            foreach (['pdf_po_refno', 'order_conf_refno', 'customer_name', 'customer_addr', 'delivery_addr', 'porder_date', 'ship_date', 'indc_date', 'updated_by', 'updated_at', 'approved_by', 'approved_at'] as $name) {
                $t->string($name)->nullable();
            }
            foreach (['customer_id', 'isoffline', 'qryapproved', 'qryclosed', 'porder_tot_pcs'] as $name) {
                $t->integer($name)->default(0);
            }
            $t->decimal('porder_tot_val', 12, 2)->default(0);
        });
        $schema->create('porder_pdf_info', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('porder_pdf_list_id');
            $t->string('item_styleno');
            $t->integer('item_order_qty');
            $t->decimal('receipt_unit_price', 10, 4);
            $t->decimal('revised_unit_price', 10, 4);
            foreach (['master_packqty', 'case_packqty', 'qrycancel', 'item_seqno'] as $name) {
                $t->integer($name)->default(0);
            }
            $t->string('updated_by')->nullable();
            $t->string('updated_at')->nullable();
        });
        $schema->create('salesdeliaddrlist', function (Blueprint $t) {
            $t->increments('deliaddr_id');
            $t->integer('customer_id');
            $t->integer('qrycancel')->default(0);
        });
        $schema->create('activity_log', function (Blueprint $t) {
            $t->increments('id');
            foreach (['log_name', 'description', 'subject_type', 'causer_type', 'properties'] as $name) {
                $t->text($name)->nullable();
            }
            $t->integer('subject_id');
            $t->integer('causer_id');
            $t->timestamps();
        });
    }

    private function actor(): User
    {
        return (new User)->forceFill(['id' => 7, 'name' => 'Contract Tester']);
    }

    private function order(): EravPurchaseOrder
    {
        $po = EravPurchaseOrder::create(['pdf_po_refno' => 'TEST-1', 'order_conf_refno' => 'STL-TEST-1', 'customer_id' => 1, 'customer_name' => 'Test buyer', 'customer_addr' => 'Buyer address', 'delivery_addr' => 'Delivery address', 'porder_date' => '2026-11-05', 'ship_date' => '2026-12-01', 'indc_date' => '2027-01-01']);
        EravPurchaseOrderItem::create(['porder_pdf_list_id' => $po->id, 'item_styleno' => 'ST001', 'item_order_qty' => 10, 'receipt_unit_price' => 5, 'revised_unit_price' => 5]);

        return $po;
    }

    public function test_price_updates_keep_original_price_and_audit_the_change(): void
    {
        $po = $this->order();
        $item = $po->items()->first();
        app(SalesContractService::class)->updateItems($po->id, [['id' => $item->id, 'revised_unit_price' => 0]], $this->actor(), true);
        $this->assertEquals(5, $item->fresh()->receipt_unit_price);
        $this->assertEquals(0, $item->fresh()->revised_unit_price);
        $this->assertEquals(0, $po->fresh()->porder_tot_val);
        $this->assertEquals(10, $po->fresh()->porder_tot_pcs);
        $properties = json_decode(DB::connection('erav')->table('activity_log')->value('properties'), true);
        $this->assertEquals(5, $properties['before']['items'][$item->id]['revised_unit_price']);
        $this->assertEquals(0, $properties['after']['items'][$item->id]['revised_unit_price']);
    }

    public function test_foreign_item_rejects_and_rolls_back_all_changes(): void
    {
        $po = $this->order();
        $other = $this->order();
        $own = $po->items()->first();
        $foreign = $other->items()->first();
        try {
            app(SalesContractService::class)->updateItems($po->id, [['id' => $own->id, 'revised_unit_price' => 9], ['id' => $foreign->id, 'revised_unit_price' => 8]], $this->actor(), true);
            $this->fail('Foreign item must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }
        $this->assertEquals(5, $own->fresh()->revised_unit_price);
        $this->assertEquals(0, DB::connection('erav')->table('activity_log')->count());
    }

    public function test_packing_update_recalculates_totals(): void
    {
        $po = $this->order();
        $item = $po->items()->first();
        app(SalesContractService::class)->updateItems($po->id, [['id' => $item->id, 'master_packqty' => 24, 'case_packqty' => 12]], $this->actor(), false);
        $this->assertEquals(24, $item->fresh()->master_packqty);
        $this->assertEquals(12, $item->fresh()->case_packqty);
        $this->assertEquals(50, $po->fresh()->porder_tot_val);
    }

    public function test_foreign_delivery_address_is_rejected(): void
    {
        $po = $this->order();
        $id = DB::connection('erav')->table('salesdeliaddrlist')->insertGetId(['customer_id' => 99]);
        $this->expectException(ValidationException::class);
        app(SalesContractService::class)->updateHeader($po->id, ['delivery_address_id' => $id, 'delivery_addr' => 'Wrong address'], $this->actor());
    }

    public function test_finalize_locks_all_further_edits(): void
    {
        $po = $this->order();
        app(SalesContractService::class)->finalize($po->id, $this->actor());
        $this->assertEquals(1, $po->fresh()->qryapproved);
        $this->assertEquals('Contract Tester', $po->fresh()->approved_by);
        $this->expectException(ValidationException::class);
        app(SalesContractService::class)->updateHeader($po->id, ['customer_addr' => 'Changed'], $this->actor());
    }

    public function test_incomplete_order_cannot_finalize(): void
    {
        $po = $this->order();
        $po->delivery_addr = '';
        $po->save();
        try {
            app(SalesContractService::class)->finalize($po->id, $this->actor());
            $this->fail('Incomplete order must be rejected.');
        } catch (ValidationException $e) {
            $this->assertEquals(0, $po->fresh()->qryapproved);
        }
    }

    public function test_routes_require_staff_role_and_match_before_generic_purchase_order_route(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/purchase-orders/sales-contracts'));
        $this->assertEquals('purchase-orders.sales-contracts.index', $route->getName());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('role:super_admin', $route->gatherMiddleware());
        $this->get('/purchase-orders/sales-contracts')->assertRedirect('/login');
    }

    public function test_print_preserves_gtin_and_shows_draft(): void
    {
        $po = $this->order();
        $po->items->first()->upc_refno = '00882849715372';
        $html = view('sales_contracts.print', compact('po'))->render();
        $this->assertStringContainsString('00882849715372', $html);
        $this->assertStringContainsString('DRAFT', $html);
        $this->assertStringContainsString('50.00', $html);
    }

    public function test_details_and_list_render_with_existing_order_and_missing_customer_master(): void
    {
        $this->withoutVite();
        Schema::connection('erav')->create('salescustomerlist', function (Blueprint $t) {
            $t->increments('customer_id');
            $t->string('customer_name')->nullable();
        });
        Schema::connection('erav')->table('salesdeliaddrlist', function (Blueprint $t) {
            $t->string('delilocation_name')->nullable();
            $t->string('delivery_addr')->nullable();
        });
        $user = $this->actor();
        $role = (new Role)->forceFill(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->setRelation('roles', new Collection([$role]));
        $this->actingAs($user);
        $po = $this->order();
        $this->get('/purchase-orders/sales-contracts')->assertOk()->assertSee('STL-TEST-1');
        $this->get('/purchase-orders/sales-contracts/'.$po->id)->assertOk()->assertSee('Update Order')->assertSee('Update Prices')->assertSee('Corrections / History');
        $po->qryapproved = 1;
        $po->save();
        $this->get('/purchase-orders/sales-contracts/'.$po->id)->assertOk()->assertSee('editing is locked')->assertDontSee('id="pricesModal"', false);
    }

    public function test_factory_role_cannot_read_or_modify_sales_contracts(): void
    {
        $user = $this->actor();
        $role = (new Role)->forceFill(['name' => 'factory', 'guard_name' => 'web']);
        $user->setRelation('roles', new Collection([$role]));
        $this->actingAs($user);
        $this->get('/purchase-orders/sales-contracts')->assertForbidden();
        $this->put('/purchase-orders/sales-contracts/1', [])->assertForbidden();
        $this->post('/purchase-orders/sales-contracts/1/finalize')->assertForbidden();
    }

    public function test_price_validation_rejects_negative_excess_precision_and_duplicate_item_ids(): void
    {
        $rules = (new UpdateSalesContractPricesRequest)->rules();
        foreach ([-1, '1.12345', '1000000'] as $price) {
            $validator = Validator::make(['items' => [['id' => 1, 'revised_unit_price' => $price]]], $rules);
            $this->assertTrue($validator->fails());
        }
        $validator = Validator::make(['items' => [['id' => 1, 'revised_unit_price' => 0], ['id' => 1, 'revised_unit_price' => 5]]], $rules);
        $this->assertTrue($validator->fails());
        $this->assertFalse(Validator::make(['items' => [['id' => 1, 'revised_unit_price' => 0]]], $rules)->fails());
    }
}
