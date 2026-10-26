{{-- @extends('layouts.app')

@section('header', 'Create Shipment')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h2 class="h5 fw-bold">Create Shipment</h2>
        <p class="text-muted mb-0">Shipment creation form will appear here.</p>
    </div>
</div>
@endsection --}}
@extends('layouts.app')

@section('header', 'Create New Shipment')

@section('content')


@php
// Country dropdown options.
// $countries = [
//     'LK' => 'Sri Lanka',
//     'BD' => 'Bangladesh',
//     'CN' => 'China',
//     'IN' => 'India',
//     'ID' => 'Indonesia',
//     'MY' => 'Malaysia',
//     'PK' => 'Pakistan',
//     'SG' => 'Singapore',
//     'TH' => 'Thailand',
//     'TR' => 'Turkey',
//     'AE' => 'United Arab Emirates',
//     'GB' => 'United Kingdom',
//     'US' => 'United States',
//     'VN' => 'Vietnam',
// ];
    // Factory dropdown options.
    // $factories = [
    //     'factory-1' => 'Colombo Apparel Factory',
    //     'factory-2' => 'Kandy Garment Factory',
    //     'factory-3' => 'Galle Manufacturing',
    //     'factory-4' => 'Negombo Textile Factory',
    // ];


@endphp


<style>
    .shipment-details {
        background: #ffffff;
        border-radius: 22px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .shipment-details h2 {
        color: #124E78;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 30px;
    }

    .shipment-details .form-label,
    .shipment-details legend {
        color: #475569;
        font-size: 15px;
        font-weight: 700;
    }

    .shipment-details .form-control,
    .shipment-details .form-select,
    .shipment-details .factory-button {
        min-height: 55px;
        border: 1px solid #dbe0e6;
        border-radius: 15px;
        padding: 12px 15px;
    }

    .shipment-details .form-control:focus,
    .shipment-details .form-select:focus {
        border-color: #00A39A;
        box-shadow: 0 0 0 3px rgba(0, 163, 154, 0.12);
    }

    .shipment-details .factory-button {
        background: #ffffff;
        color: #343a40;
    }

    .shipment-details .factory-menu {
        max-height: 250px;
        overflow-y: auto;
    }

    .shipment-details .factory-button.is-invalid {
        border-color: #dc3545;
    }

    .shipment-details .shipment-actions {
        border-top: 1px solid #dbe0e6;
        margin-top: 30px;
        padding-top: 30px;
    }

    .shipment-details .shipment-actions .btn {
        min-height: 48px;
        border-radius: 50px;
        padding: 10px 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .shipment-details .cancel-button {
        background: #f8f9fa;
        border: 1px solid #dbe0e6;
        color: #212529;
    }

    .shipment-details .cancel-button:hover {
        background: #e9ecef;
    }

    .shipment-details .reset-button {
        background: #ffffff;
        border: 1px solid #7b8794;
        color: #6c757d;
    }

    .shipment-details .reset-button:hover {
        background: #f8f9fa;
    }

    .shipment-details .create-shipment-button {
        background: #00A39A;
        border: 1px solid #00A39A;
        color: #ffffff;
        font-weight: 700;
        box-shadow: 0 5px 15px rgba(0, 163, 154, 0.25);
    }

    .shipment-details .create-shipment-button:hover {
        background: #008b83;
        border-color: #008b83;
        color: #ffffff;
    }

    @media (max-width: 576px) {
        .shipment-details {
            padding: 22px;
        }

        .shipment-details .shipment-actions .btn {
            width: 100%;
        }
    }
</style>




<div class="shipment-details">

    <h2>Shipment Details</h2>


    <form id="shipment-form" novalidate>

        {{-- Country of Origin and Buyer ID --}}
<div class="row g-4 mb-4">

    <div class="col-md-6">
        <label for="country-of-origin" class="form-label mb-2">
            Country of Origin <span class="text-danger">*</span>
        </label>

        {{-- <select
            id="country-of-origin"
            name="country_of_origin"
            class="form-select"
            required
        >
            @foreach($countries as $code => $name)
                <option value="{{ $code }}">
                    {{ $name }} ({{ $code }})
                </option>
            @endforeach
        </select> --}}
        <select
    id="country-of-origin"
    name="country_of_origin"
    class="form-select"
    required
>
    <option value="">Select a country</option>

    @foreach($countries as $code => $name)
        <option
            value="{{ $code }}"
            @selected(old('country_of_origin') === (string) $code)
        >
            {{ $name }} ({{ $code }})
        </option>
    @endforeach
</select>
    </div>

    <div class="col-md-6">
        <label for="buyer-id" class="form-label mb-2">
            Buyer ID
        </label>

        <input
            id="buyer-id"
            name="buyer_id"
            type="text"
            class="form-control"
            placeholder="Enter Buyer ID"
        >
    </div>

</div>

{{-- Shipment ID mode --}}
<fieldset class="mb-3">
    <legend class="mb-3">Shipment ID Option</legend>

    <div class="d-flex flex-wrap gap-3">

        <div class="form-check">
            <input
                id="manual-id"
                type="radio"
                name="shipment_id_mode"
                value="manual"
                class="form-check-input"
            >

            <label for="manual-id" class="form-check-label">
                Manual Entry
            </label>
        </div>

        <div class="form-check">
            <input
                id="auto-id"
                type="radio"
                name="shipment_id_mode"
                value="auto"
                class="form-check-input"
                checked
            >

            <label for="auto-id" class="form-check-label">
                Auto Generate
            </label>
        </div>

    </div>
</fieldset>

{{-- Shipment ID --}}
<div class="mb-4">
    <label for="shipment-id" class="form-label mb-2">
        Shipment ID <span class="text-danger">*</span>
    </label>

    <input
        id="shipment-id"
        name="shipment_id"
        type="text"
        class="form-control bg-light"
        value="LK-000001"
        placeholder="Enter Shipment ID"
        aria-describedby="shipment-id-help"
        readonly
        required
    >

    <div
        id="shipment-id-help"
        class="form-text"
        aria-live="polite"
    >
        Sample ID preview. The prefix follows the selected country.
    </div>
</div>

{{-- Purchase Order ID --}}
<div class="mb-4">
    <label for="purchase-order-id" class="form-label mb-2">
        Purchase Order ID
    </label>

    <input
        id="purchase-order-id"
        name="purchase_order_id"
        type="text"
        class="form-control"
        placeholder="Enter Purchase Order ID"
    >
</div>



        {{-- Shipment Type --}}
        <fieldset class="mb-4">
            <legend class="mb-3">
                Shipment Type <span class="text-danger">*</span>
            </legend>

            <div class="d-flex flex-wrap gap-3">

                <div class="form-check">
                    <input
                        id="individual"
                        type="radio"
                        name="shipment_type"
                        value="individual"
                        class="form-check-input"
                        checked
                    >

                    <label for="individual" class="form-check-label">
                        Individual Shipment
                    </label>
                </div>

                <div class="form-check">
                    <input
                        id="consolidated"
                        type="radio"
                        name="shipment_type"
                        value="consolidated"
                        class="form-check-input"
                    >

                    <label for="consolidated" class="form-check-label">
                        Consolidated Shipment
                    </label>
                </div>

            </div>
        </fieldset>

        {{-- Individual Shipment: select one factory --}}
        <div id="individual-group">
            <label for="individual-factory" class="form-label mb-2">
                Factory <span class="text-danger">*</span>
            </label>

            <select
                id="individual-factory"
                name="factory_id"
                class="form-select"
                required
            >
                <option value="">Select a factory</option>

                @foreach($factories as $id => $name)
                    <option value="{{ $id }}">
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Consolidated Shipment: select multiple factories --}}
        <div id="consolidated-group" class="d-none">
            <label
                id="factories-label"
                for="factory-button"
                class="form-label mb-2"
            >
                Factories <span class="text-danger">*</span>
            </label>

            <div class="dropdown">
                <button
                    id="factory-button"
                    type="button"
                    class="btn factory-button dropdown-toggle w-100 text-start"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-controls="factory-options"
                    aria-describedby="factory-help factory-error"
                    aria-invalid="false"
                    disabled
                >
                    Select factories
                </button>

                <div
                    id="factory-options"
                    class="dropdown-menu factory-menu w-100 p-3"
                    role="group"
                    aria-labelledby="factories-label"
                >
                    @foreach($factories as $id => $name)
                        <div class="form-check mb-2">
                            <input
                                id="multiple-{{ $id }}"
                                type="checkbox"
                                name="factory_ids[]"
                                value="{{ $id }}"
                                class="form-check-input factory-checkbox"
                                disabled
                            >

                            <label
                                for="multiple-{{ $id }}"
                                class="form-check-label"
                            >
                                {{ $name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="factory-help" class="form-text">
                Select at least two factories.
            </div>

            <div id="factory-error" class="invalid-feedback">
                Please select at least two factories.
            </div>
        </div>

        {{-- Form buttons --}}
        <div class="shipment-actions d-flex flex-wrap justify-content-end gap-2">

            <a
                href="{{ route('shipment-management.index') }}"
                class="btn cancel-button"
            >
                Cancel
            </a>

            <button type="reset" class="btn reset-button">
                Reset
            </button>

            <button type="submit" class="btn create-shipment-button">
                <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>
                Create Shipment
            </button>

        </div>

    </form>

</div>


@endsection

@push('scripts')
<script>




document.addEventListener('DOMContentLoaded', function () {


    // Get the elements used by this page.
    const shipmentForm = document.getElementById('shipment-form');

    // Get the new form elements.
    const countrySelect = document.getElementById('country-of-origin');
    const manualIdRadio = document.getElementById('manual-id');
    const autoIdRadio = document.getElementById('auto-id');
    const shipmentId = document.getElementById('shipment-id');
    const shipmentIdHelp = document.getElementById('shipment-id-help');

    // Sample sequence for the UI demonstration.
    const sampleSequence = '000001';

    // Remember the user's manual ID when switching modes.
    let manualShipmentId = '';


    // Update the generated ID when the country changes.
function updateGeneratedShipmentId() {
    if (autoIdRadio.checked) {
        const countryCode = countrySelect.value;
        // shipmentId.value = countrySelect.value + '-' + sampleSequence;
         shipmentId.value = countryCode? countryCode + '-' + sampleSequence: '';
    }
}

// Switch between Manual Entry and Auto Generate.
function changeShipmentIdMode() {
    if (autoIdRadio.checked) {
        shipmentId.readOnly = true;
        shipmentId.classList.add('bg-light');

        shipmentIdHelp.textContent =
            'Shipment ID will be generated automatically.';

        updateGeneratedShipmentId();
    } else {
        shipmentId.readOnly = false;
        shipmentId.classList.remove('bg-light');

        shipmentId.value = manualShipmentId;

        shipmentIdHelp.textContent =
            'Enter your Shipment ID manually.';
    }
}

    const individualRadio = document.getElementById('individual');
    const consolidatedRadio = document.getElementById('consolidated');

    const individualGroup = document.getElementById('individual-group');
    const consolidatedGroup = document.getElementById('consolidated-group');

    const individualFactory = document.getElementById('individual-factory');

    const factoryButton = document.getElementById('factory-button');
    const factoryError = document.getElementById('factory-error');

    const factoryCheckboxes = document.querySelectorAll('.factory-checkbox');



    // Count the selected factories.
    function countSelectedFactories() {
        let count = 0;

        for (let i = 0; i < factoryCheckboxes.length; i++) {
            if (factoryCheckboxes[i].checked) {
                count = count + 1;
            }
        }

        return count;
    }


    // Update the consolidated dropdown button.
    function updateFactoryButton() {
        const count = countSelectedFactories();

        if (count === 0) {
            factoryButton.textContent = 'Select factories';
        } else if (count === 1) {
            factoryButton.textContent = '1 factory selected';
        } else {
            factoryButton.textContent = count + ' factories selected';
        }
    }


    // Clear the consolidated factory error.
    function clearFactoryError() {
        factoryButton.classList.remove('is-invalid');
        factoryButton.setAttribute('aria-invalid', 'false');

        factoryError.classList.remove('d-block');
    }


    // Show the correct factory selection.
    function changeShipmentType() {
        if (individualRadio.checked) {
            individualGroup.classList.remove('d-none');
            consolidatedGroup.classList.add('d-none');

            individualFactory.disabled = false;
            individualFactory.required = true;

            factoryButton.disabled = true;

            for (let i = 0; i < factoryCheckboxes.length; i++) {
                factoryCheckboxes[i].disabled = true;
            }
        } else {
            individualGroup.classList.add('d-none');
            consolidatedGroup.classList.remove('d-none');

            individualFactory.disabled = true;
            individualFactory.required = false;

            factoryButton.disabled = false;

            for (let i = 0; i < factoryCheckboxes.length; i++) {
                factoryCheckboxes[i].disabled = false;
            }
        }

        clearFactoryError();
        updateFactoryButton();
    }


    // Consolidated shipments require at least two factories.
    function validateFactories() {
        const count = countSelectedFactories();

        updateFactoryButton();

        if (count < 2) {
            factoryButton.classList.add('is-invalid');
            factoryButton.setAttribute('aria-invalid', 'true');

            factoryError.classList.add('d-block');

            return false;
        } else {
            clearFactoryError();

            return true;
        }
    }


    // Validate the form and show a demo confirmation.
    function createShipment(event) {
        // Stop the form from submitting or saving data.
        event.preventDefault();

        // Check the required individual factory selection.
        if (shipmentForm.reportValidity() === false) {
            return;
        }

        // Check the number of consolidated factories.
        if (consolidatedRadio.checked) {
            const factoriesValid = validateFactories();

            if (factoriesValid === false) {
                factoryButton.focus();
                return;
            }
        }

        alert('Validation passed. Demo only — no shipment was saved.');
    }


    // Restore default values after Reset is clicked.
function resetShipment() {
    // Wait until the browser finishes resetting the fields.
    setTimeout(function () {
        manualShipmentId = '';

        changeShipmentIdMode();
        changeShipmentType();
    }, 0);
}

    // Update the generated prefix when the country changes.
countrySelect.addEventListener('change', updateGeneratedShipmentId);

// Change the Shipment ID mode.
manualIdRadio.addEventListener('change', changeShipmentIdMode);
autoIdRadio.addEventListener('change', changeShipmentIdMode);

// Remember the manually entered ID.
shipmentId.addEventListener('input', function () {
    if (manualIdRadio.checked) {
        manualShipmentId = shipmentId.value;
    }
});


    // Listen for shipment type changes.
    individualRadio.addEventListener('change', changeShipmentType);
    consolidatedRadio.addEventListener('change', changeShipmentType);


    // Listen for factory checkbox changes.
    for (let i = 0; i < factoryCheckboxes.length; i++) {
        factoryCheckboxes[i].addEventListener('change', validateFactories);
    }


    // Listen for Create Shipment and Reset.
    shipmentForm.addEventListener('submit', createShipment);
    shipmentForm.addEventListener('reset', resetShipment);


    // Set up the initial page state.
    changeShipmentType();
    changeShipmentIdMode();

});
</script>
@endpush
