<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::with('currency')->orderBy('country')->get();
        $currencies = Currency::orderBy('currency_name')->get();

        return view('master-data.countries.index', compact('countries', 'currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => 'required|string',
        ]);

        if ($request->country_id === 'new') {
            $request->validate([
                'country' => ['required', 'string', 'max:450', Rule::unique(Country::class, 'country')],
            ]);

            $country = new Country;
            $country->country = $request->country;
            $country->status = '1';
        } else {
            $request->validate([
                'country_id' => ['required', 'integer', Rule::exists(Country::class, 'idtbl_country')],
            ]);

            $country = Country::findOrFail($request->country_id);
        }

        $request->validate([
            'country_code' => [
                'required', 'string', 'max:20',
                Rule::unique(Country::class, 'country_code')->ignore($country),
            ],
            'currency_id' => 'required|string',
        ]);

        if ($request->currency_id === 'new') {
            $request->validate([
                'currency_name' => ['required', 'string', 'max:50', Rule::unique(Currency::class, 'currency_name')],
            ]);

            $currency = new Currency;
            $currency->currency_name = $request->currency_name;
            $currency->status = 1;
        } else {
            $request->validate([
                'currency_id' => ['required', 'integer', Rule::exists(Currency::class, 'idtbl_currency')],
            ]);

            $currency = Currency::findOrFail($request->currency_id);
        }

        // Save both records together so a failed country save does not leave a new currency behind.
        DB::transaction(function () use ($country, $currency, $request) {
            if (! $currency->exists) {
                $currency->save();
            }

            $country->country_code = $request->country_code;
            $country->currencyid = $currency->idtbl_currency;
            $country->updatedatetime = now();
            $country->save();
        });

        return redirect()->route('master-data.countries.index')
            ->with('success', 'Country code and currency saved successfully.');
    }
}
