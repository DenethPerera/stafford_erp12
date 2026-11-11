<p class="small text-muted mb-3">
    {{ $historyShipments->count() }} created deliveries for {{ $factoryName ?? 'your factory' }}.
    Expand a delivery to see its saved details.
</p>

@forelse ($historyShipments as $delivery)
    <div class="d-flex align-items-start gap-2 mb-2">
    <details class="border rounded-3 flex-grow-1" style="min-width: 0;">
        <summary class="p-3 bg-light rounded-3" style="cursor: pointer; overflow-wrap: anywhere;">
            <strong>{{ $delivery->shipment_refno ?: 'Shipment number unavailable' }}</strong>
            <span class="small text-muted ms-2">Delivery #{{ $delivery->id }}</span>
            <span class="small ms-2">{{ $delivery->shipment_invno ?: 'No vendor invoice' }}</span>
            <span class="small text-muted ms-2">{{ $delivery->shiporder_docdate }}</span>
        </summary>

        <div class="p-3" style="overflow-wrap: anywhere;">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @if ($delivery->isoffline)
                    <span class="badge bg-secondary">Inactive</span>
                @else
                    <span class="badge bg-success">Active</span>
                @endif
                @if ($delivery->qryapproved)
                    <span class="badge bg-success">Approved</span>
                @else
                    <span class="badge bg-warning text-dark">Not approved</span>
                @endif
                @if ($delivery->qryfinalized)
                    <span class="badge bg-primary">Finalized</span>
                @else
                    <span class="badge bg-secondary">Not finalized</span>
                @endif
            </div>

            {{-- A simple label/value list keeps the saved information compact. --}}
            @php
                $shipmentDetails = [
                    'Shipment No.' => $delivery->shipment_refno,
                    'Order Ref. No.' => $delivery->prodorder_ref,
                    'PO No.' => $delivery->pdf_po_refno,
                    'Factory' => $factoryName,
                    'Customer' => $delivery->customer_name,
                    'Prepared Date' => $delivery->shiporder_docdate,
                    'Vendor Invoice No.' => $delivery->shipment_invno,
                    'Stafford Invoice No.' => $delivery->stafford_invno,
                    'Ship Via' => $delivery->ship_method,
                    'ETD' => $delivery->plan_etd,
                    'Container No.' => $delivery->ship_container_no,
                    'Vessel' => $delivery->vessel_code,
                    'PL Label' => $delivery->pl_labels,
                    'Total Cartons' => $delivery->ship_tot_cartons,
                    'Total Net Weight (kg)' => $delivery->ship_net_weight,
                    'Total Gross Weight (kg)' => $delivery->ship_gross_weight,
                ];
            @endphp
            <dl class="row g-2 small mb-2">
                @foreach ($shipmentDetails as $label => $value)
                    <div class="col-6 col-md-4">
                        <dt class="text-muted fw-normal">{{ $label }}</dt>
                        <dd class="mb-1 fw-semibold">{{ $value === null || $value === '' ? 'Unavailable' : $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="small mb-3">
                <span class="text-muted">Remarks:</span>
                <span style="white-space: pre-line;">{{ $delivery->shipment_moreinfo ?: 'None' }}</span>
            </div>

            <h3 class="h6 mb-2">Order Items and Packing Details</h3>
            @forelse ($historyItems[$delivery->id] ?? [] as $item)
                <details class="border rounded-3 mb-2">
                    <summary class="p-2" style="cursor: pointer;">
                        <strong>{{ $item->style_code ?? 'Style unavailable' }}</strong>
                        / {{ $item->tbl_style_color_desc ?: 'Color unavailable' }}
                        / {{ $item->tbl_style_size_desc ?: 'Size unavailable' }}
                        <span class="small text-muted ms-2">{{ $item->ship_qty }} pcs</span>
                        @if ($item->qrycancel)
                            <span class="badge bg-secondary ms-2">Cancelled</span>
                        @endif
                    </summary>
                    <div class="px-3 pb-3 pt-2">
                        @php
                            $itemDetails = [
                                'Style Code' => $item->style_code,
                                'Color Code' => $item->tbl_style_color_code,
                                'Color' => $item->tbl_style_color_desc,
                                'Size' => $item->tbl_style_size_desc,
                                'Order Qty' => $item->order_quantity,
                                'Ship Qty' => $item->ship_qty,
                                'Full Cartons' => $item->full_carton_nos,
                                'Odd Cartons' => $item->odd_carton_nos,
                                'Pcs / Ctn' => $item->pcs_per_carton,
                                'Length (cm)' => $item->carton_length,
                                'Width (cm)' => $item->carton_width,
                                'Height (cm)' => $item->carton_height,
                                'Net Weight (kg)' => $item->pack_netwgt,
                                'Gross Weight (kg)' => $item->pack_grossweight,
                                'Odd Net Weight (kg)' => $item->odditems_netwgt,
                                'Odd Gross Weight (kg)' => $item->oddpack_grossweight,
                                'Kg / Pcs' => $item->item_weight,
                                'Kg / Ctn' => $item->carton_weight,
                                'CBM / Ctn' => $item->cbm_per_carton,
                                'Total CBM' => round(($item->full_carton_nos + $item->odd_carton_nos) * $item->cbm_per_carton, 6),
                            ];
                        @endphp
                        <dl class="row g-2 small mb-2">
                            @foreach ($itemDetails as $label => $value)
                                <div class="col-6 col-md-4">
                                    <dt class="text-muted fw-normal">{{ $label }}</dt>
                                    <dd class="mb-1">{{ $value === null || $value === '' ? 'Unavailable' : $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <div class="small">
                            <div class="text-muted">Ship-to Address</div>
                            <div class="fw-semibold">{{ $item->custdest_name ?? 'Address name unavailable' }}</div>
                            <div style="white-space: pre-line;">{{ $item->custdest_addr ?? 'Address unavailable' }}</div>
                        </div>
                    </div>
                </details>
            @empty
                <p class="small text-muted mb-2">No saved order items for this delivery.</p>
            @endforelse

            <details class="mt-3 small">
                <summary class="text-muted" style="cursor: pointer;">Preparation and Approval Details</summary>
                <dl class="row g-2 mt-1 mb-0">
                    <div class="col-6 col-md-4"><dt>Prepared By</dt><dd>{{ $delivery->prepare_by ?: 'Unavailable' }}</dd></div>
                    <div class="col-6 col-md-4"><dt>Created On</dt><dd>{{ $delivery->prep_time ?: 'Unavailable' }}</dd></div>
                    <div class="col-6 col-md-4"><dt>Approved By</dt><dd>{{ $delivery->approve_by ?: 'Unavailable' }}</dd></div>
                    <div class="col-6 col-md-4"><dt>Approved On</dt><dd>{{ $delivery->approve_on ?: 'Unavailable' }}</dd></div>
                    <div class="col-6 col-md-4"><dt>Finalized By</dt><dd>{{ $delivery->finalize_by ?: 'Unavailable' }}</dd></div>
                    <div class="col-6 col-md-4"><dt>Finalized On</dt><dd>{{ $delivery->finalize_time ?: 'Unavailable' }}</dd></div>
                </dl>
            </details>
        </div>
    </details>
    <div class="d-flex flex-wrap gap-1 pt-2" style="flex-shrink: 0;">
        <a href="{{ route('create-shipment.edit', $delivery->id) }}" class="btn btn-sm btn-outline-primary"
           aria-label="Edit delivery {{ $delivery->id }}">Edit</a>
        <form method="POST" action="{{ route('create-shipment.destroy', $delivery->id) }}"
              onsubmit="return confirm('Delete this delivery and its order items? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"
                    aria-label="Delete delivery {{ $delivery->id }}">Delete</button>
        </form>
    </div>
    </div>
@empty
    <div class="text-center text-muted py-4">
        <i class="bi bi-clock-history fs-2 d-block mb-2" aria-hidden="true"></i>
        No shipments created yet.
    </div>
@endforelse
