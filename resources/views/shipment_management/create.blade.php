

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
    // Restore the form after a validation error.
    $oldPurchaseOrderIds = array_values((array) old('purchase_order_ids', []));
    $oldFactoryIds = old('shipment_type', 'individual') === 'individual'
        ? [old('factory_id', '')]
        : array_values((array) old('factory_ids', []));
    $manualShipmentId = old('shipment_id_mode') === 'manual' ? old('shipment_id', '') : '';
@endphp


<style>
    .shipment-details {
        background: #ffffff;
        max-width: 70rem;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .shipment-details h2 {
        color: #124E78;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
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
        min-height: 42px;
        border: 1px solid #dbe0e6;
        border-radius: 10px;
        padding: 8px 12px;
    }

    .shipment-details .form-select {
        padding-right: 36px;
    }

    .shipment-details .form-control:focus,
    .shipment-details .form-select:focus {
        border-color: #00A39A;
        box-shadow: 0 0 0 3px rgba(0, 163, 154, 0.12);
    }

    .shipment-details .shipment-fields {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr));
        align-items: start;
        gap: 20px 24px;
    }

    .shipment-details .shipment-field {
        min-width: 0;
        margin: 0;
    }

    .shipment-details .shipment-id-options {
        margin: 8px 0 0;
        font-size: 14px;
    }

    .shipment-details .factory-button {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .shipment-details .factory-menu {
        min-width: 100%;
        overflow-wrap: anywhere;
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
        margin-top: 20px;
        padding-top: 16px;
    }

    .shipment-details .shipment-actions .btn {
        min-height: 42px;
        border-radius: 50px;
        padding: 8px 22px;
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
            padding: 16px;
        }

        .shipment-details .shipment-actions .btn {
            width: 100%;
        }
    }
</style>




<div class="shipment-details">

    <h2>Shipment Details</h2>


    <form id="shipment-form"
    method="POST"
    action="{{ route('shipment-management.store') }}"
    novalidate >

    @csrf

        <div class="shipment-fields">

        {{-- Country of Origin and Buyer ID --}}

    <div class="shipment-field">
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

    <div class="shipment-field">
        <label for="buyer-id" class="form-label mb-2">
            Buyer <span class="text-danger">*</span>
        </label>
        <select id="buyer-id" name="buyer_id" class="form-select" required>
            <option value="">Select a buyer</option>
            @foreach($buyers as $id => $name)
                <option value="{{ $id }}" @selected((string) old('buyer_id', '') === (string) $id)>
                    {{ $name }}
                </option>
            @endforeach
        </select>

    </div>

{{-- Shipment ID --}}
<div class="shipment-field">
    <label for="shipment-id" class="form-label mb-2">
        Shipment ID <span class="text-danger">*</span>
    </label>

    <input
        id="shipment-id"
        name="shipment_id"
        type="text"
        class="form-control bg-light"
        value="{{ old('shipment_id', '') }}"
        placeholder="Enter Shipment ID"
        aria-describedby="shipment-id-help"
        readonly
        required
    >

    <fieldset class="shipment-id-options">
        <legend class="visually-hidden">Shipment ID Option</legend>
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input id="manual-id" type="radio" name="shipment_id_mode" value="manual"
                    class="form-check-input" @checked(old('shipment_id_mode', 'auto') === 'manual')>
                <label for="manual-id" class="form-check-label">Manual Entry</label>
            </div>
            <div class="form-check">
                <input id="auto-id" type="radio" name="shipment_id_mode" value="auto"
                    class="form-check-input" @checked(old('shipment_id_mode', 'auto') === 'auto')>
                <label for="auto-id" class="form-check-label">Auto Generate</label>
            </div>
        </div>
    </fieldset>

    <div
        id="shipment-id-help"
        class="form-text"
        aria-live="polite"
    >
        Select a country to display the next Shipment ID.
    </div>
</div>

{{-- Purchase Orders --}}
<div class="shipment-field">
    <label id="purchase-orders-label" for="purchase-order-button" class="form-label mb-2">
        Purchase Orders <span class="text-danger">*</span>
    </label>
    <div class="dropdown">
        <button
            id="purchase-order-button"
            type="button"
            class="btn factory-button dropdown-toggle w-100 text-start"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-controls="purchase-order-options"
            aria-describedby="shipment-selection-message"
            aria-invalid="false"
            disabled
        >
            Select purchase orders
        </button>
        <div
            id="purchase-order-options"
            class="dropdown-menu factory-menu w-100 p-3"
            role="group"
            aria-labelledby="purchase-orders-label"
        ></div>
    </div>
    <div id="shipment-selection-message" class="form-text" role="status">Select a buyer first.</div>
</div>

        {{-- Shipment Type --}}
        <fieldset class="shipment-field">
            <legend class="mb-2">
                Shipment Type <span class="text-danger">*</span>
            </legend>

            <div class="d-flex flex-column gap-2">

                <div class="form-check">
                    <input
                        id="individual"
                        type="radio"
                        name="shipment_type"
                        value="individual"
                        class="form-check-input"
                        @checked(old('shipment_type', 'individual') === 'individual')
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
                        @checked(old('shipment_type', 'individual') === 'consolidated')
                    >

                    <label for="consolidated" class="form-check-label">
                        Consolidated Shipment
                    </label>
                </div>

            </div>
        </fieldset>

        {{-- Individual Shipment: select one factory --}}
        <div id="individual-group" class="shipment-field">
            <label for="individual-factory" class="form-label mb-2">
                Factory <span class="text-danger">*</span>
            </label>

            <select id="individual-factory" name="factory_id" class="form-select" required disabled>
                <option value="">Select purchase orders first</option>
            </select>
        </div>

        {{-- Consolidated Shipment: select multiple factories --}}
        <div id="consolidated-group" class="d-none shipment-field">
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
                ></div>
            </div>

            <div id="factory-help" class="form-text">
                Select at least two factories.
            </div>

            <div id="factory-error" class="invalid-feedback">
                Please select at least two factories.
            </div>
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

    <div class="shipment-details mt-4">
    <h2>Created Shipments</h2>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">Shipment ID</th>
                    <th scope="col">Country</th>
                    <th scope="col">Buyer</th>
                    <th scope="col">Type</th>
                    <th scope="col">Factory Count</th>
                    <th scope="col">Created On</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($shipments as $shipment)
                    <tr>
                        <td>{{ $shipment->shipment_id }}</td>

                        <td>
                            {{ $countries[$shipment->country_of_origin]
                                ?? $shipment->country_of_origin }}
                        </td>

                        <td>
                            {{ $buyers[$shipment->buyer_id] ?? 'Unavailable' }}
                        </td>

                        <td>{{ ucfirst($shipment->shipment_type) }}</td>

                         <td>
                            @forelse ($shipment->factory_ids ?? [] as $factoryId)
        <div>
            {{ $factoryNames[$factoryId] ?? 'Factory unavailable' }}
        </div>
    @empty
        No factories
    @endforelse
</td>

                        <td>
                           {{ $shipment->created_at?->timezone('Asia/Colombo')->format('d M Y, h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No shipments created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $shipments->links('pagination::bootstrap-5') }}
</div>

</div>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const shipmentForm = document.getElementById('shipment-form');
    const buyerSelect = document.getElementById('buyer-id');
    const purchaseOrderButton = document.getElementById('purchase-order-button');
    const purchaseOrderOptions = document.getElementById('purchase-order-options');
    const selectionMessage = document.getElementById('shipment-selection-message');
    const individualRadio = document.getElementById('individual');
    const consolidatedRadio = document.getElementById('consolidated');
    const individualFactory = document.getElementById('individual-factory');
    const factoryButton = document.getElementById('factory-button');
    const factoryOptions = document.getElementById('factory-options');
    const factoryError = document.getElementById('factory-error');
    const purchaseOrdersUrl = @json(route('shipment-management.purchase-orders'));
    let factoryLinks = [];
    let requestNumber = 0;

    // 1. Read the selected values.
    function selectedPurchaseOrderIds() {
        const ids = [];
        for (const checkbox of purchaseOrderOptions.querySelectorAll('input:checked')) {
            ids.push(checkbox.value);
        }
        return ids;
    }

    function updatePurchaseOrderButton() {
        const count = selectedPurchaseOrderIds().length;
        if (count === 0) {
            purchaseOrderButton.textContent = 'Select purchase orders';
        } else if (count === 1) {
            purchaseOrderButton.textContent = '1 purchase order selected';
        } else {
            purchaseOrderButton.textContent = count + ' purchase orders selected';
        }
        purchaseOrderButton.classList.remove('is-invalid');
        purchaseOrderButton.setAttribute('aria-invalid', 'false');
        selectionMessage.classList.remove('text-danger');
    }

    function selectedFactoryIds() {
        const ids = [];
        if (individualFactory.value) {
            ids.push(individualFactory.value);
        }
        for (const checkbox of factoryOptions.querySelectorAll('input:checked')) {
            ids.push(checkbox.value);
        }
        return ids;
    }

    function updateFactoryButton() {
        const count = factoryOptions.querySelectorAll('input:checked').length;
        factoryButton.textContent = count ? count + ' factories selected' : 'Select factories';
        factoryButton.classList.remove('is-invalid');
        factoryButton.setAttribute('aria-invalid', 'false');
        factoryError.classList.remove('d-block');
    }

    // 2. Individual: common factories. Consolidated: all related factories.
    function showFactories(savedIds = []) {
        const purchaseOrderIds = selectedPurchaseOrderIds();
        const keepIds = [];
        for (const id of savedIds) {
            keepIds.push(String(id));
        }

        const factories = {};
        for (const link of factoryLinks) {
            if (purchaseOrderIds.includes(String(link.purchase_order_id))) {
                const id = String(link.factory_id);
                if (!factories[id]) {
                    factories[id] = { name: link.factory_name, count: 0 };
                }
                factories[id].count++;
            }
        }

        document.getElementById('individual-group').classList.toggle('d-none', !individualRadio.checked);
        document.getElementById('consolidated-group').classList.toggle('d-none', individualRadio.checked);
        individualFactory.innerHTML = '<option value="">Select a factory</option>';
        individualFactory.disabled = !individualRadio.checked || purchaseOrderIds.length === 0;
        individualFactory.required = individualRadio.checked;
        factoryOptions.innerHTML = '';

        for (const id in factories) {
            const factory = factories[id];
            if (individualRadio.checked) {
                if (factory.count === purchaseOrderIds.length) {
                    const option = new Option(factory.name, id);
                    option.selected = keepIds.length === 1 && keepIds.includes(id);
                    individualFactory.add(option);
                }
            } else {
                const row = document.createElement('div');
                row.className = 'form-check mb-2';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'factory_ids[]';
                checkbox.value = id;
                checkbox.id = 'multiple-' + id;
                checkbox.className = 'form-check-input factory-checkbox';
                checkbox.checked = keepIds.includes(id);
                const label = document.createElement('label');
                label.htmlFor = checkbox.id;
                label.className = 'form-check-label';
                label.textContent = factory.name;
                row.append(checkbox, label);
                factoryOptions.append(row);
            }
        }

        if (individualRadio.checked && purchaseOrderIds.length > 0 && individualFactory.options.length === 1) {
            individualFactory.options[0].text = 'No factory is connected to all selected POs';
        }
        factoryButton.disabled = individualRadio.checked || factoryOptions.children.length === 0;
        updateFactoryButton();
    }

    // 3. Load the buyer's POs and their factory links.
    async function loadPurchaseOrders(savedPoIds = [], savedFactoryIds = []) {
        const currentRequest = ++requestNumber;
        factoryLinks = [];
        purchaseOrderOptions.innerHTML = '';
        purchaseOrderButton.disabled = true;
        updatePurchaseOrderButton();
        showFactories([]);
        selectionMessage.textContent = 'Select a buyer first.';
        if (!buyerSelect.value) {
            return;
        }

        selectionMessage.textContent = 'Loading purchase orders...';
        try {
            const response = await fetch(purchaseOrdersUrl + '?buyer_id=' + encodeURIComponent(buyerSelect.value), {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) {
                throw new Error('Unable to load purchase orders.');
            }
            const data = await response.json();
            // Ignore an older response when the buyer changes quickly.
            if (currentRequest !== requestNumber) {
                return;
            }

            const keepIds = [];
            for (const id of savedPoIds) {
                keepIds.push(String(id));
            }
            for (const purchaseOrder of data.purchase_orders) {
                const id = String(purchaseOrder.id);
                const row = document.createElement('div');
                row.className = 'form-check mb-2';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'purchase_order_ids[]';
                checkbox.value = id;
                checkbox.id = 'purchase-order-' + id;
                checkbox.className = 'form-check-input';
                checkbox.checked = keepIds.includes(id);
                const label = document.createElement('label');
                label.htmlFor = checkbox.id;
                label.className = 'form-check-label';
                label.textContent = purchaseOrder.pdf_po_refno;
                row.append(checkbox, label);
                purchaseOrderOptions.append(row);
            }
            factoryLinks = data.factory_links;
            purchaseOrderButton.disabled = data.purchase_orders.length === 0;
            updatePurchaseOrderButton();
            selectionMessage.textContent = data.purchase_orders.length
                ? 'Select one or more purchase orders.' : 'No active purchase orders available.';
            showFactories(savedFactoryIds);
        } catch (error) {
            if (currentRequest === requestNumber) {
                selectionMessage.textContent = 'Unable to load purchase orders. Select the buyer again.';
            }
        }
    }

    buyerSelect.onchange = function () {
        loadPurchaseOrders();
    };
    purchaseOrderOptions.onchange = function () {
        updatePurchaseOrderButton();
        selectionMessage.textContent = 'Select one or more purchase orders.';
        showFactories(selectedFactoryIds());
    };
    function changeShipmentType() {
        showFactories(selectedFactoryIds());
    }
    individualRadio.onchange = changeShipmentType;
    consolidatedRadio.onchange = changeShipmentType;
    factoryOptions.onchange = updateFactoryButton;

    // 4. Check required fields. Laravel checks all relationships when saving.
    shipmentForm.onsubmit = function (event) {
        if (purchaseOrderButton.disabled || selectedPurchaseOrderIds().length === 0) {
            event.preventDefault();
            selectionMessage.textContent = 'Select a buyer and at least one PO.';
            selectionMessage.classList.add('text-danger');
            purchaseOrderButton.classList.add('is-invalid');
            purchaseOrderButton.setAttribute('aria-invalid', 'true');
            purchaseOrderButton.focus();
            return;
        }
        if (!shipmentForm.reportValidity()) {
            event.preventDefault();
            return;
        }
        if (consolidatedRadio.checked && selectedFactoryIds().length < 2) {
            event.preventDefault();
            factoryError.textContent = 'Please select at least two factories.';
            factoryError.classList.add('d-block');
            factoryButton.classList.add('is-invalid');
            factoryButton.setAttribute('aria-invalid', 'true');
            factoryButton.focus();
        }
    };

    // 5. Keep the existing country and Shipment ID behavior.
    const countrySelect = document.getElementById('country-of-origin');
    const manualIdRadio = document.getElementById('manual-id');
    const autoIdRadio = document.getElementById('auto-id');
    const shipmentId = document.getElementById('shipment-id');
    const shipmentIdHelp = document.getElementById('shipment-id-help');
    const nextShipmentNumbers = @json($nextShipmentNumbers);
    let manualShipmentId = @json($manualShipmentId);

    function updateGeneratedShipmentId() {
        if (autoIdRadio.checked) {
            const country = countrySelect.value;
            const number = nextShipmentNumbers[country] ?? 1;
            shipmentId.value = country ? country + '-' + String(number).padStart(6, '0') : '';
        }
    }

    function changeShipmentIdMode() {
        shipmentId.readOnly = autoIdRadio.checked;
        shipmentId.classList.toggle('bg-light', autoIdRadio.checked);
        if (autoIdRadio.checked) {
            shipmentIdHelp.textContent = 'This Shipment ID will be used when you save.';
            updateGeneratedShipmentId();
        } else {
            shipmentId.value = manualShipmentId;
            shipmentIdHelp.textContent = 'Enter your Shipment ID manually.';
        }
    }

    countrySelect.onchange = updateGeneratedShipmentId;
    manualIdRadio.onchange = changeShipmentIdMode;
    autoIdRadio.onchange = changeShipmentIdMode;
    shipmentId.oninput = function () {
        if (manualIdRadio.checked) {
            manualShipmentId = shipmentId.value;
        }
    };
    shipmentForm.onreset = function () {
        setTimeout(function () {
            manualShipmentId = '';
            changeShipmentIdMode();
            loadPurchaseOrders();
        }, 0);
    };

    changeShipmentIdMode();
    loadPurchaseOrders(@json($oldPurchaseOrderIds), @json($oldFactoryIds));
});
</script>
@endpush
