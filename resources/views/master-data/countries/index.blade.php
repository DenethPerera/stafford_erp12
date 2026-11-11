@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item text-secondary">Master Data</li>
            <li class="breadcrumb-item active" aria-current="page">Countries</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <span class="fw-semibold">Assign Country Code and Currency</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('master-data.countries.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="country_id" class="form-label small">Country</label>
                            <select name="country_id" id="country_id" class="form-select form-select-sm @error('country_id') is-invalid @enderror" onchange="changeCountry(true)" required>
                                @if($countries->isNotEmpty())
                                    <option value="">Select a country</option>
                                @endif
                                @foreach($countries as $country)
                                    <option value="{{ $country->idtbl_country }}" data-code="{{ $country->country_code }}" data-currency="{{ $country->currencyid }}" @selected(old('country_id') == $country->idtbl_country)>{{ $country->country }}</option>
                                @endforeach
                                <option value="new" @selected(old('country_id', $countries->isEmpty() ? 'new' : '') === 'new')>Add new country</option>
                            </select>
                            @error('country_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Select an existing country or choose Add new country.</div>
                        </div>

                        <div class="mb-3" id="new-country-field" @if(old('country_id', $countries->isEmpty() ? 'new' : '') !== 'new') hidden @endif>
                            <label for="country" class="form-label small">New Country Name</label>
                            <input type="text" name="country" id="country" class="form-control form-control-sm @error('country') is-invalid @enderror" value="{{ old('country') }}" maxlength="450" placeholder="e.g. Sri Lanka">
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="country_code" class="form-label small">Country Code</label>
                            <input type="text" name="country_code" id="country_code" class="form-control form-control-sm @error('country_code') is-invalid @enderror" value="{{ old('country_code') }}" maxlength="20" placeholder="e.g. LK" required>
                            @error('country_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="currency_id" class="form-label small">Currency</label>
                            <select name="currency_id" id="currency_id" class="form-select form-select-sm @error('currency_id') is-invalid @enderror" onchange="changeCurrency()" required>
                                @if($currencies->isNotEmpty())
                                    <option value="">Select a currency</option>
                                @endif
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->idtbl_currency }}" @selected(old('currency_id') == $currency->idtbl_currency)>{{ $currency->currency_name }}</option>
                                @endforeach
                                <option value="new" @selected(old('currency_id', $currencies->isEmpty() ? 'new' : '') === 'new')>Add new currency</option>
                            </select>
                            @error('currency_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Select an existing currency or choose Add new currency.</div>
                        </div>

                        <div class="mb-3" id="new-currency-field" @if(old('currency_id', $currencies->isEmpty() ? 'new' : '') !== 'new') hidden @endif>
                            <label for="currency_name" class="form-label small">New Currency</label>
                            <input type="text" name="currency_name" id="currency_name" class="form-control form-control-sm @error('currency_name') is-invalid @enderror" value="{{ old('currency_name') }}" maxlength="50" placeholder="e.g. LKR">
                            @error('currency_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <span class="fw-semibold">Countries List</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Country</th>
                                <th>Country Code</th>
                                <th>Currency</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($countries as $country)
                                <tr>
                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                    <td>{{ $country->country }}</td>
                                    <td>{{ $country->country_code === '' ? 'Not assigned' : $country->country_code }}</td>
                                    <td>{{ $country->currency->currency_name ?? 'Not assigned' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No countries found. Add a country with its code and currency.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function changeCountry(updateCode) {
        var countrySelect = document.getElementById('country_id');
        var isNewCountry = countrySelect.value === 'new';

        document.getElementById('new-country-field').hidden = !isNewCountry;
        document.getElementById('country').required = isNewCountry;
        document.getElementById('country').disabled = !isNewCountry;

        if (updateCode) {
            var selectedCountry = countrySelect.options[countrySelect.selectedIndex];
            document.getElementById('country_code').value = selectedCountry.dataset.code || '';
            document.getElementById('currency_id').value = selectedCountry.dataset.currency || '';
            if (isNewCountry && document.getElementById('currency_id').options.length === 1) {
                document.getElementById('currency_id').value = 'new';
            }
            changeCurrency();
        }
    }

    function changeCurrency() {
        var isNewCurrency = document.getElementById('currency_id').value === 'new';

        document.getElementById('new-currency-field').hidden = !isNewCurrency;
        document.getElementById('currency_name').required = isNewCurrency;
        document.getElementById('currency_name').disabled = !isNewCurrency;
    }

    changeCountry(false);
    changeCurrency();
</script>
@endsection
