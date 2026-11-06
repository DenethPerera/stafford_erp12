<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FactoryShipmentSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Share an isolated database so the existing ERP joins can be tested.
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
        });
        Schema::create('shipment_order_factories', function (Blueprint $table) {
            $table->id();
            $table->integer('shipment_id');
            $table->integer('purchase_order_id');
            $table->integer('factory_id');
        });
        Schema::create('mbranchlist', function (Blueprint $table) {
            $table->id();
            $table->string('br_name');
        });
        Schema::create('porder_pdf_list', function (Blueprint $table) {
            $table->id();
            $table->string('pdf_po_refno');
        });
        DB::table('mbranchlist')->insert([
            ['id' => 10, 'br_name' => 'My Factory'],
            ['id' => 20, 'br_name' => 'Other Factory'],
        ]);
        DB::table('porder_pdf_list')->insert(['id' => 100, 'pdf_po_refno' => 'PO-100']);

        $user = new User(['name' => 'Factory User', 'mbranchlist_id' => 10]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
        $this->actingAs($user);
    }

    public function test_search_matches_partial_ids_and_stays_within_the_logged_in_factory(): void
    {
        $this->addShipment('AF-000015', 10);
        $this->addShipment('SHIP-TEST-001', 10);
        $this->addShipment('AF-000016', 20);

        $response = $this->get(route('shipment-details.index', [
            'shipment_search' => '  AF-  ', 'factory_id' => 20,
        ]))->assertOk();
        $response->assertSee('AF-000015')->assertDontSee('SHIP-TEST-001')->assertDontSee('AF-000016');
        $this->assertSame('AF-', $response->viewData('shipmentSearch'));
        $this->assertSame(1, $response->viewData('shipmentRows')->total());

        foreach ([[], ['shipment_search' => '   ']] as $parameters) {
            $all = $this->get(route('shipment-details.index', $parameters))->assertOk();
            $this->assertSame(2, $all->viewData('shipmentRows')->total());
        }

        $this->get(route('shipment-details.index', ['shipment_search' => 'AF-000016']))
            ->assertOk()->assertSee('No shipments match your search.')->assertDontSee('Other Factory');
    }

    public function test_search_is_preserved_when_changing_pages(): void
    {
        for ($number = 1; $number <= 16; $number++) {
            $this->addShipment(sprintf('AF-%06d', $number), 10);
        }
        $this->addShipment('SHIP-OTHER', 10);

        $response = $this->get(route('shipment-details.index', [
            'shipment_search' => 'AF-', 'page' => 2,
        ]))->assertOk()->assertDontSee('SHIP-OTHER');
        $rows = $response->viewData('shipmentRows');
        $this->assertSame(16, $rows->total());
        $this->assertCount(1, $rows);
        parse_str(parse_url($rows->previousPageUrl(), PHP_URL_QUERY), $parameters);
        $this->assertSame('AF-', $parameters['shipment_search']);
    }

    public function test_search_rejects_arrays_and_overlong_input(): void
    {
        foreach ([['AF-'], str_repeat('A', 256)] as $search) {
            $this->from(route('shipment-details.index'))
                ->get(route('shipment-details.index', ['shipment_search' => $search]))
                ->assertRedirect(route('shipment-details.index'))
                ->assertSessionHasErrors('shipment_search');
        }
    }

    private function addShipment(string $reference, int $factoryId): void
    {
        $shipmentId = DB::table('shipments')->insertGetId(['shipment_id' => $reference]);
        DB::table('shipment_order_factories')->insert([
            'shipment_id' => $shipmentId,
            'purchase_order_id' => 100,
            'factory_id' => $factoryId,
        ]);
    }
}
