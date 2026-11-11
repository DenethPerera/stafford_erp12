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
       <div class="d-flex flex-wrap align-items-center gap-3 mt-4 mb-3">
           <div>
               <h2 class="h5 fw-bold mb-1">{{ $shipment->shipment_id }}</h2>
               <div class="small text-muted">
                   {{ ($shippingDetails[0]['export_import'] ?? null) ?: '-' }}
                   Shipment
               </div>
           </div>

           @if (!empty($shippingDetails[0]['shipment_status']))
               <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">
                   {{ $shippingDetails[0]['shipment_status'] }}
               </span>
           @endif
       </div>

       <div class="row g-3 align-items-stretch">
       <div class="col-12 col-lg-8 d-flex flex-column gap-3">
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
            class="border rounded-3 p-3 flex-grow-1"
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

        {{-- Four field groups on wide screens, stacked on smaller screens. --}}
        <div class="row g-4">
            <div class="col-12 col-sm-6 col-xl-3">
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

            <div class="col-12 col-sm-6 col-xl-3">
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

            <div class="col-12 col-sm-6 col-xl-3">
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

            <div class="col-12 col-sm-6 col-xl-3">
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
       </div>

       <aside class="col-12 col-lg-4" aria-labelledby="shipmentDocumentsHeading">
           <div class="border rounded-3 p-3 h-100">
               <h3 id="shipmentDocumentsHeading" class="h5 fw-bold mb-2">
                   Uploaded Shipment Documents
               </h3>
               <p class="text-muted small text-break mb-3">
                   Shipment ID: {{ $shipment->shipment_id }}
               </p>

               <div class="d-flex flex-column gap-3">
                   @forelse ($shipmentDocuments as $document)
                       <div class="border rounded-3 p-3">
                           <div class="fw-bold">{{ $document->document_type }}</div>
                           <div class="text-muted small text-break mt-1">
                               {{ $document->original_name }}
                           </div>
                           <div class="text-muted small mt-1">
                               Uploaded {{ $document->created_at->format('d M Y') }}
                           </div>
                           <div class="d-flex flex-wrap gap-2 mt-3">
                               <a href="{{ route('shipment-management.documents.show', $document->id) }}"
                                  class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                  aria-label="View {{ $document->original_name }}">
                                   View
                               </a>
                               <a href="{{ route('shipment-management.documents.show', ['shipmentDocument' => $document->id, 'download' => 1]) }}"
                                  class="btn btn-sm btn-outline-secondary"
                                  aria-label="Download {{ $document->original_name }}">
                                   Download
                               </a>
                           </div>
                       </div>
                   @empty
                       <p class="text-muted mb-0">
                           No documents have been uploaded for this shipment yet.
                       </p>
                   @endforelse
               </div>
           </div>
       </aside>
       </div>

    {{-- Full-width container details below the shipment and documents row. --}}
    <div class="border rounded-3 p-3 p-md-4 mt-4">
    <h3 class="h5 fw-bold mb-0">
        <button
            type="button"
            class="btn p-0 w-100 text-start fw-bold d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            data-bs-target="#shipmentContainerDetails"
            aria-expanded="true"
            aria-controls="shipmentContainerDetails"
        >
            <span>
                <i class="bi bi-box-seam me-2" aria-hidden="true"></i>
                Container Details ({{ count($containerDetails) }} containers)
            </span>

            <i class="bi bi-chevron-expand" aria-hidden="true"></i>
        </button>
    </h3>

    <div class="collapse show" id="shipmentContainerDetails">
        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col" class="text-nowrap">Container No.</th>
                        <th scope="col" class="text-nowrap">Size / Type</th>
                        <th scope="col" class="text-nowrap">Seal No.</th>
                        <th scope="col">Status</th>
                        <th scope="col">Location</th>
                        <th scope="col">Remarks</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($containerDetails as $container)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-nowrap">
                                {{ $container['container_no'] }}
                            </td>
                            <td class="text-nowrap">
                                {{ $container['size_type'] !== '' ? $container['size_type'] : '-' }}
                            </td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                No container details found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="border rounded-3 p-3 p-md-4 mt-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <h3 class="h6 fw-bold mb-0">
            <i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>
            Consolidation
            ({{ count($consolidationFactories) }} Factories / Programs)
        </h3>

        <div class="d-flex flex-wrap gap-3 small text-muted">
            <span>
                <strong>Ordered Qty:</strong>
                {{ $consolidationTotals['ordered_qty'] !== null
                    ? number_format($consolidationTotals['ordered_qty'], 2)
                    : '-' }}
            </span>

            <span>
                <strong>Shipped Qty:</strong>
                {{ $consolidationTotals['shipped_qty'] !== null
                    ? number_format($consolidationTotals['shipped_qty'], 2) : '-' }}
            </span>
            <span>
                <strong>CTN:</strong>
                {{ $consolidationTotals['cartons'] !== null
                    ? number_format($consolidationTotals['cartons'], 0) : '-' }}
            </span>
            <span>
                <strong>CBM:</strong>
                {{ $consolidationTotals['cbm'] !== null
                    ? number_format($consolidationTotals['cbm'], 3) : '-' }}
            </span>
            <span>
                <strong>Est. Shipped Value:</strong>
                @if ($consolidationTotals['shipped_values'] === null)
                    -
                @else
                    @foreach ($consolidationTotals['shipped_values'] as $currency => $value)
                        <span class="text-nowrap">{{ $currency }} {{ number_format($value, 2) }}</span>
                    @endforeach
                @endif
            </span>
        </div>
    </div>

    <p class="small text-muted mb-3">
        {{-- Rows combine sizes and colors for each PO and style.
        Ordered quantities cover full linked POs, with shared items counted once overall.
        Shipping totals include matching active deliveries only.
        Shipped values are estimates at PO revised unit prices, using the linked
        production order's currency. Different currencies are shown separately. --}}
    </p>

    @forelse ($consolidationFactories as $factory)
        <div class="card border rounded-3 overflow-hidden mb-3">
            <div
                class="card-header border-bottom p-3"
                style="background-color: {{ ['#eaf4ff', '#eaf8ef', '#f3edff'][$loop->index % 3] }};"
            >
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="fw-bold">
                        <i class="bi bi-building me-2" aria-hidden="true"></i>
                        {{ $factory['factory_name'] !== ''
                            ? $factory['factory_name']
                            : '-' }}
                    </div>

                    <div class="d-flex flex-wrap gap-3 small">
                        <span>
                            <strong>Linked PO ordered Qty:</strong>
                            {{ $factory['ordered_qty'] !== null
                                ? number_format($factory['ordered_qty'], 2)
                                : '-' }}
                        </span>

                        <span>
                            <strong>Shipped Qty:</strong>
                            {{ $factory['shipped_qty'] !== null
                                ? number_format($factory['shipped_qty'], 2) : '-' }}
                        </span>
                        <span>
                            <strong>CTN:</strong>
                            {{ $factory['cartons'] !== null
                                ? number_format($factory['cartons'], 0) : '-' }}
                        </span>
                        <span>
                            <strong>CBM:</strong>
                            {{ $factory['cbm'] !== null
                                ? number_format($factory['cbm'], 3) : '-' }}
                        </span>
                        <span>
                            <strong>Est. Shipped Value:</strong>
                            @if ($factory['shipped_values'] === null)
                                -
                            @else
                                @foreach ($factory['shipped_values'] as $currency => $value)
                                    <span class="text-nowrap">{{ $currency }} {{ number_format($value, 2) }}</span>
                                @endforeach
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-nowrap">PO #</th>
                            <th scope="col">Style / Description</th>
                            <th scope="col" class="text-end text-nowrap">
                                Ordered Qty
                            </th>
                            <th scope="col" class="text-end text-nowrap">
                                Shipped Qty
                            </th>
                            <th scope="col">CTN</th>
                            <th scope="col">CBM</th>
                            <th scope="col" class="text-end text-nowrap">
                                Est. Shipped Value
                            </th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($factory['rows'] as $row)
                            <tr>
                                <td class="text-nowrap">
                                    {{ trim((string) $row->pdf_po_refno) !== ''
                                        ? $row->pdf_po_refno
                                        : '-' }}
                                </td>

                                @if (! $row->has_items)
                                    <td colspan="7" class="text-muted">
                                        No active PO items found
                                    </td>
                                @else
                                    <td>
                                        <div class="fw-semibold text-break">
                                            {{ trim((string) $row->item_styleno) !== ''
                                                ? $row->item_styleno
                                                : '-' }}
                                        </div>

                                        <div class="small text-muted text-break">
                                            {{ trim((string) $row->item_description) !== ''
                                                ? $row->item_description
                                                : '-' }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ is_numeric($row->item_order_qty)
                                            ? number_format((float) $row->item_order_qty, 2)
                                            : '-' }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ $row->shipped_qty !== null
                                            ? number_format($row->shipped_qty, 2) : '-' }}
                                    </td>
                                    <td>
                                        {{ $row->cartons !== null
                                            ? number_format($row->cartons, 0) : '-' }}
                                    </td>
                                    <td>
                                        {{ $row->cbm !== null
                                            ? number_format($row->cbm, 3) : '-' }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        @if ($row->shipped_values === null)
                                            -
                                        @else
                                            @foreach ($row->shipped_values as $currency => $value)
                                                <div>{{ $currency }} {{ number_format($value, 2) }}</div>
                                            @endforeach
                                        @endif
                                    </td>

                                    <td>-</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">
            No factories or purchase orders found for this shipment.
        </p>
    @endforelse
</div>
@endif
    </div>
</div>
@endsection
