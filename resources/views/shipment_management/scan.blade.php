@extends('layouts.app')

@section('header', 'Shipment Scan')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h2 class="h5 fw-bold">Shipment Scan</h2>
        {{-- Shipment ID search --}}
<div class="row g-3 align-items-end mt-1">
    <div class="col-12 col-md-6 col-lg-4">
        <label for="shipmentIdInput" class="form-label">
            Shipment ID
        </label>

        <input
            type="text"
            id="shipmentIdInput"
            class="form-control"
            placeholder="Example: SHP001"
        >
    </div>

    <div class="col-12 col-md-auto">
        <button
            type="button"
            id="shipmentSearchButton"
            class="btn btn-primary w-100"
        >
            Search
        </button>
    </div>
</div>

{{-- Hidden until a search error occurs --}}
<div
    id="shipmentSearchError"
    class="alert alert-danger mt-3 mb-0 d-none"
    role="alert"
></div>
    </div>
    {{-- Hidden until a shipment is found --}}
<div id="shipmentDetails" class="border rounded-3 p-3 p-md-4 mt-4 d-none">
    <h3 class="h5 fw-bold border-bottom pb-3 mb-4">
        Shipment Details
    </h3>

    {{-- One column on mobile, two on tablets, three on larger screens --}}
    <div class="row g-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Shipment ID</div>
            <div id="detailShipmentId" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">BL Number</div>
            <div id="detailBlNumber" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Booking Number</div>
            <div id="detailBookingNumber" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Vessel / Voyage</div>
            <div id="detailVesselVoyage" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">POL (Origin)</div>
            <div id="detailPol" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">POD (Destination)</div>
            <div id="detailPod" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">ETD</div>
            <div id="detailEtd" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">ETA</div>
            <div id="detailEta" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Transit Time</div>
            <div id="detailTransitTime" class="fw-semibold"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Container Number</div>
            <div id="detailContainerNumber" class="fw-semibold text-break"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Seal Number</div>
            <div id="detailSealNumber" class="fw-semibold text-break"></div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-muted small mb-1">Carrier</div>
            <div id="detailCarrier" class="fw-semibold"></div>
        </div>
    </div>
</div>
</div>


@endsection
