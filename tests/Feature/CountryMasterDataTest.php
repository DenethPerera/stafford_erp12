<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CountryMasterDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('tbl_currency', function (Blueprint $table) {
            $table->increments('idtbl_currency');
            $table->string('currency_name', 50);
            $table->integer('status')->default(1);
        });
        DB::table('tbl_currency')->insert([
            'idtbl_currency' => 7, 'currency_name' => 'USD', 'status' => 1,
        ]);

        // Match the required columns in the existing legacy table.
        Schema::create('tbl_country', function (Blueprint $table) {
            $table->increments('idtbl_country');
            $table->string('country_code', 20);
            $table->string('country', 450);
            $table->integer('currencyid');
            $table->enum('status', ['1', '2'])->default('1');
            $table->dateTime('updatedatetime');
        });

        $user = new User(['name' => 'Test Admin']);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => 'super_admin', 'guard_name' => 'web'])]));
        $this->actingAs($user);
    }

    public function test_empty_page_allows_adding_a_country_and_shows_the_menu(): void
    {
        $this->get(route('master-data.countries.index'))
            ->assertOk()
            ->assertSee('No countries found.')
            ->assertSee('Add new country')
            ->assertSee('New Country Name')
            ->assertSee('Add new currency')
            ->assertSee('USD')
            ->assertSee('id="masterDataCollapse"', false)
            ->assertSee('id="mobileMasterDataCollapse"', false)
            ->assertSee('value="new" selected', false);
    }

    public function test_existing_countries_are_available_in_the_dropdown_including_missing_codes(): void
    {
        $this->addCountry('Sri Lanka', '');
        $this->addCountry('India', 'IN');

        $response = $this->get(route('master-data.countries.index'))->assertOk();
        $response->assertSee('data-code="IN"', false)
            ->assertSee('data-code=""', false)
            ->assertSee('data-currency="7"', false)
            ->assertSee('USD')
            ->assertSee('Not assigned');
        $this->assertSame(['India', 'Sri Lanka'], $response->viewData('countries')->pluck('country')->all());
    }

    public function test_assigning_a_code_and_currency_preserves_the_country_name_and_status(): void
    {
        $id = $this->addCountry('Sri Lanka', '');
        $otherId = $this->addCountry('India', 'IN');
        DB::table('tbl_currency')->insert([
            'idtbl_currency' => 8, 'currency_name' => 'LKR', 'status' => 1,
        ]);

        $this->post(route('master-data.countries.store'), [
            'country_id' => (string) $id,
            'country_code' => 'LK',
            'currency_id' => '8',
            'country' => 'Do not rename the existing country',
        ])->assertRedirect(route('master-data.countries.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tbl_country', 2);
        $this->assertDatabaseHas('tbl_country', [
            'idtbl_country' => $id, 'country' => 'Sri Lanka', 'country_code' => 'LK',
            'currencyid' => 8, 'status' => '2',
        ]);
        $this->assertDatabaseHas('tbl_country', ['idtbl_country' => $otherId, 'country_code' => 'IN', 'currencyid' => 7]);
        $this->assertNotSame('2020-01-01 00:00:00', DB::table('tbl_country')->where('idtbl_country', $id)->value('updatedatetime'));

        // Saving the same code for the same country is allowed.
        $this->post(route('master-data.countries.store'), [
            'country_id' => (string) $id, 'country_code' => 'LK', 'currency_id' => '8',
        ])->assertSessionHasNoErrors();
    }

    public function test_new_country_can_be_added_to_an_empty_table(): void
    {
        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => 'LK', 'currency_id' => '7',
        ])->assertRedirect(route('master-data.countries.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tbl_country', [
            'country' => 'Sri Lanka', 'country_code' => 'LK', 'currencyid' => 7, 'status' => '1',
        ]);
        $this->assertNotNull(DB::table('tbl_country')->value('updatedatetime'));
    }

    public function test_new_country_can_also_be_added_when_other_countries_exist(): void
    {
        $this->addCountry('India', 'IN');

        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => 'LK', 'currency_id' => '7',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tbl_country', 2);
        $this->assertDatabaseHas('tbl_country', ['country' => 'Sri Lanka', 'country_code' => 'LK']);
    }

    public function test_invalid_input_is_rejected_without_creating_countries(): void
    {
        $invalidInputs = [
            [[], 'country_id'],
            [['country_id' => '999', 'country_code' => 'LK'], 'country_id'],
            [['country_id' => 'invalid', 'country_code' => 'LK'], 'country_id'],
            [['country_id' => 'new', 'country_code' => 'LK'], 'country'],
            [['country_id' => 'new', 'country' => str_repeat('a', 451), 'country_code' => 'LK'], 'country'],
            [['country_id' => 'new', 'country' => 'Sri Lanka'], 'country_code'],
            [['country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => '   '], 'country_code'],
            [['country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => str_repeat('a', 21)], 'country_code'],
        ];

        foreach ($invalidInputs as [$input, $field]) {
            $this->from(route('master-data.countries.index'))
                ->post(route('master-data.countries.store'), array_merge(['currency_id' => '7'], $input))
                ->assertRedirect(route('master-data.countries.index'))
                ->assertSessionHasErrors($field);
            $this->assertDatabaseCount('tbl_country', 0);
        }
    }

    public function test_duplicate_names_and_codes_are_rejected(): void
    {
        $this->addCountry('India', 'IN');
        $id = $this->addCountry('Sri Lanka', 'LK');

        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'India', 'country_code' => 'OTHER', 'currency_id' => '7',
        ])->assertSessionHasErrors('country');

        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'New Country', 'country_code' => 'IN', 'currency_id' => '7',
        ])->assertSessionHasErrors('country_code');

        $this->post(route('master-data.countries.store'), [
            'country_id' => (string) $id, 'country_code' => 'IN', 'currency_id' => '7',
        ])->assertSessionHasErrors('country_code');

        $this->assertDatabaseCount('tbl_country', 2);
        $this->assertDatabaseHas('tbl_country', ['idtbl_country' => $id, 'country_code' => 'LK']);
    }

    public function test_country_management_requires_super_admin_role(): void
    {
        $user = new User(['name' => 'Factory User']);
        $user->id = 2;
        $user->setRelation('roles', collect([new Role(['name' => 'factory', 'guard_name' => 'web'])]));
        $this->actingAs($user);

        $this->get(route('master-data.countries.index'))->assertForbidden();
        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => 'LK',
        ])->assertForbidden();
        $this->assertDatabaseCount('tbl_country', 0);
    }

    public function test_new_currency_can_be_created_and_assigned_to_a_new_country(): void
    {
        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => 'LK',
            'currency_id' => 'new', 'currency_name' => 'LKR',
        ])->assertRedirect(route('master-data.countries.index'))->assertSessionHasNoErrors();

        $currencyId = DB::table('tbl_currency')->where('currency_name', 'LKR')->value('idtbl_currency');
        $this->assertNotNull($currencyId);
        $this->assertDatabaseHas('tbl_currency', ['currency_name' => 'LKR', 'status' => 1]);
        $this->assertDatabaseHas('tbl_country', ['country' => 'Sri Lanka', 'currencyid' => $currencyId]);
        $this->get(route('master-data.countries.index'))->assertOk()->assertSee('LKR');
    }

    public function test_new_currency_can_be_assigned_to_an_existing_country(): void
    {
        $id = $this->addCountry('Sri Lanka', 'LK');

        $this->post(route('master-data.countries.store'), [
            'country_id' => (string) $id, 'country_code' => 'LK',
            'currency_id' => 'new', 'currency_name' => 'LKR',
        ])->assertSessionHasNoErrors();

        $currencyId = DB::table('tbl_currency')->where('currency_name', 'LKR')->value('idtbl_currency');
        $this->assertDatabaseHas('tbl_country', ['idtbl_country' => $id, 'currencyid' => $currencyId]);
        $this->assertDatabaseCount('tbl_country', 1);
        $this->assertDatabaseCount('tbl_currency', 2);
    }

    public function test_currency_can_be_added_when_the_currency_table_is_empty(): void
    {
        DB::table('tbl_currency')->delete();
        $this->get(route('master-data.countries.index'))
            ->assertOk()->assertSee('id="new-currency-field" >', false);

        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => 'LK',
            'currency_id' => 'new', 'currency_name' => 'LKR',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tbl_currency', 1);
        $this->assertDatabaseCount('tbl_country', 1);
    }

    public function test_invalid_or_duplicate_currency_is_rejected_without_saving_any_records(): void
    {
        $invalidInputs = [
            [[], 'currency_id'],
            [['currency_id' => '999'], 'currency_id'],
            [['currency_id' => 'invalid'], 'currency_id'],
            [['currency_id' => 'new'], 'currency_name'],
            [['currency_id' => 'new', 'currency_name' => '   '], 'currency_name'],
            [['currency_id' => 'new', 'currency_name' => str_repeat('a', 51)], 'currency_name'],
            [['currency_id' => 'new', 'currency_name' => 'USD'], 'currency_name'],
        ];

        foreach ($invalidInputs as [$input, $field]) {
            $this->post(route('master-data.countries.store'), array_merge([
                'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => 'LK',
            ], $input))->assertSessionHasErrors($field);

            $this->assertDatabaseCount('tbl_country', 0);
            $this->assertDatabaseCount('tbl_currency', 1);
        }
    }

    public function test_invalid_country_code_does_not_create_a_new_currency(): void
    {
        $this->post(route('master-data.countries.store'), [
            'country_id' => 'new', 'country' => 'Sri Lanka', 'country_code' => '',
            'currency_id' => 'new', 'currency_name' => 'LKR',
        ])->assertSessionHasErrors('country_code');

        $this->assertDatabaseCount('tbl_country', 0);
        $this->assertDatabaseCount('tbl_currency', 1);
    }

    private function addCountry(string $name, string $code): int
    {
        return DB::table('tbl_country')->insertGetId([
            'country' => $name, 'country_code' => $code,
            'currencyid' => 7, 'status' => '2', 'updatedatetime' => '2020-01-01 00:00:00',
        ]);
    }
}
