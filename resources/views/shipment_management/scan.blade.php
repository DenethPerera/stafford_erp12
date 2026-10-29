@extends('layouts.app')

@section('header', 'Shipment Scan')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h2 class="h5 fw-bold">Shipment Scan</h2>

        {{-- Normal GET search form --}}
        <form
            method="GET"
            action="{{ route('shipment-management.scan') }}"
            class="row g-3 align-items-end mt-1"
        >
            <div class="col-12 col-md-6 col-lg-4">
                <label for="shipmentIdInput" class="form-label">
                    Shipment ID
                </label>

                <input
                    type="text"
                    id="shipmentIdInput"
                    name="shipment_id"
                    class="form-control"
                    value="{{ $shipmentId }}"
                    placeholder="Enter Shipment ID"
                    maxlength="255"
                    required
                >
            </div>

            <div class="col-12 col-md-auto">
                <button
                    type="submit"
                    id="shipmentSearchButton"
                    class="btn btn-primary w-100"
                >
                    Search
                </button>
            </div>
        </form>

        @if (session('success'))
    <div class="alert alert-success mt-3 mb-0" role="alert">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

        @if ($searchError)
            <div class="alert alert-danger mt-3 mb-0" role="alert">
                {{ $searchError }}
            </div>
        @endif



       @if ($shipment)
       @if ($shipment->import_id === null)
    @if (count($importOptions) > 0)
        <form
            method="POST"
            action="{{ route('shipment-management.save-import') }}"
            class="row g-3 align-items-end mt-3"
        >
            @csrf

            <input
                type="hidden"
                name="shipment_id"
                value="{{ $shipment->shipment_id }}"
            >

            <div class="col-12 col-lg-9">
                <label for="importIdInput" class="form-label">
                    Matching Shipping Record
                </label>

                <select
                    id="importIdInput"
                    name="import_id"
                    class="form-select"
                    required
                >
                    <option value="">Choose a shipping record</option>

                    @foreach ($importOptions as $option)
                        <option
                            value="{{ $option['id'] }}"
                            @selected(
                                (string) old('import_id') ===
                                (string) $option['id']
                            )
                        >
                            PO: {{ $option['po_numbers'] ?: 'Not available' }}
                            | BL: {{ $option['bl_number'] ?: 'Not available' }}
                            | Vessel: {{ $option['vessel_name'] ?: 'Not available' }}
                            | Record: {{ $option['id'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-lg-3">
                <button type="submit" class="btn btn-primary w-100">
                    Save Shipping Record
                </button>
            </div>
        </form>
    @else
        <div class="alert alert-info mt-3 mb-0">
            No shipping records match this shipment's POs.
        </div>
    @endif
@endif


    @foreach ($shippingDetails as $details)
        <div
            id="shipmentDetails-{{ $loop->iteration }}"
            class="border rounded-3 p-3 p-md-4 mt-4"
        >




        <h3 class="h5 fw-bold border-bottom pb-3 mb-3">
            Shipment Details
        </h3>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <span class="text-muted">Export / Import:</span>
                <span class="fw-semibold">
                    {{ $details['export_import'] ?: 'Not available' }}
                </span>
            </div>

            <div class="col-12 col-md-6">
                <span class="text-muted">Shipment Status:</span>
                <span class="fw-semibold">
                    {{ $details['shipment_status'] ?: 'Not available' }}
                </span>
            </div>
        </div>

        {{-- One column on mobile, two on tablets, four on large screens --}}
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <dl class="mb-0">
                    <dt class="text-muted small fw-normal mb-1">
                        BL Number
                    </dt>
                    <dd class="fw-semibold text-break mb-3">
                        {{ $details['bl_number'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        Booking Number
                    </dt>
                    <dd class="fw-semibold text-break mb-3">
                        {{ $details['booking_number'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        Shipment ID
                    </dt>
                    <dd class="fw-semibold text-break mb-0">
                        {{ $shipment->shipment_id }}
                    </dd>
                </dl>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <dl class="mb-0">
                    <dt class="text-muted small fw-normal mb-1">
                        Vessel / Voyage
                    </dt>
                    <dd class="fw-semibold text-break mb-3">
                        @if ($details['vessel_name'] || $details['voyage_number'])
                            {{ $details['vessel_name'] ?: 'Not available' }}
                            /
                            {{ $details['voyage_number'] ?: 'Not available' }}
                        @else
                            Not available
                        @endif
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        POL (Origin)
                    </dt>
                    <dd class="fw-semibold text-break mb-3">
                        {{ $details['pol'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        POD (Destination)
                    </dt>
                    <dd class="fw-semibold text-break mb-0">
                        {{ $details['pod'] ?: 'Not available' }}
                    </dd>
                </dl>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <dl class="mb-0">
                    <dt class="text-muted small fw-normal mb-1">
                        ETD
                    </dt>
                    <dd class="fw-semibold mb-3">
                        {{ $details['etd'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        ETA
                    </dt>
                    <dd class="fw-semibold mb-3">
                        {{ $details['eta'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        Transit Time
                    </dt>
                    <dd class="fw-semibold mb-0">
                        {{ $details['transit_time'] ?: 'Not available' }}
                    </dd>
                </dl>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <dl class="mb-0">
                    <dt class="text-muted small fw-normal mb-1">
                        Container Number
                    </dt>
                    <dd class="fw-semibold text-break mb-3">
                        {{ $details['container_number'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        Seal Number
                    </dt>
                    <dd class="fw-semibold text-break mb-3">
                        {{ $details['seal_number'] ?: 'Not available' }}
                    </dd>

                    <dt class="text-muted small fw-normal mb-1">
                        Carrier
                    </dt>
                    <dd class="fw-semibold text-break mb-0">
                        {{ $details['carrier'] ?: 'Not available' }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    @endforeach
@endif
    </div>
</div>
@endsection
