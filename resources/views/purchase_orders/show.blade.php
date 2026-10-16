@extends('layouts.app')

@section('header', 'Purchase Order - ' . $po->po_number)

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-light rounded-circle p-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h4 class="fw-bold text-dark mb-0 font-monospace">{{ $po->po_number }}</h4>
            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 text-uppercase">
                {{ $po->status }}
            </span>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print PO
            </button>
            <a href="{{ route('factory-portal.show', ['id' => $po->prodorder_id, 'branch_id' => $po->mbranchlist_id]) }}" 
               class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-box-seam me-1"></i> View Production Order
            </a>
        </div>
    </div>

    <!-- Printable Sheet Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white p-4 p-md-5">
        
        <!-- Document Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <span class="badge bg-teal text-white px-3 py-1 rounded-pill mb-2" style="background-color: #00A39A;">
                    STAFFORD ERP - PURCHASE ORDER
                </span>
                <h2 class="fw-bolder text-dark mb-1 font-monospace">{{ $po->po_number }}</h2>
                <div class="text-muted small">
                    Created on: <strong>{{ $po->created_at ? $po->created_at->format('M d, Y H:i') : '-' }}</strong> by {{ $po->creator?->name ?? 'System' }}
                </div>
            </div>
            <div class="text-end">
                <div class="text-uppercase fw-bold text-muted small">Total Value</div>
                <h2 class="fw-bolder text-success font-monospace mb-0">${{ number_format($po->total_amount, 2) }}</h2>
                <span class="badge bg-light text-dark border font-monospace">{{ number_format($po->total_qty) }} Total Pcs</span>
            </div>
        </div>

        <!-- Parties Section (Factory / Issuer & Supplier) -->
        <div class="row g-4 mb-4">
            <!-- Factory -->
            <div class="col-md-6">
                <div class="p-3 rounded-4 bg-light h-100 border border-light">
                    <div class="text-uppercase fw-bold text-muted small mb-2">
                        <i class="bi bi-building me-1 text-primary"></i> Issuer (Factory):
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $po->branch?->br_name ?? 'Factory ' . $po->mbranchlist_id }}</h5>
                    <div class="small text-muted font-monospace mb-1">Code: {{ $po->branch?->br_code ?: 'N/A' }}</div>
                    <div class="small text-secondary">{{ $po->branch?->br_addr }}</div>
                </div>
            </div>

            <!-- Supplier -->
            <div class="col-md-6">
                <div class="p-3 rounded-4 bg-light h-100 border border-light">
                    <div class="text-uppercase fw-bold text-muted small mb-2">
                        <i class="bi bi-truck me-1 text-success"></i> Supplier / Vendor:
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $po->supplier?->supname ?? 'Supplier ' . $po->supplierno }}</h5>
                    <div class="small text-muted font-monospace mb-1">Reg Code: {{ $po->supplier?->supregcode }}</div>
                    <div class="small text-secondary mb-1">{{ $po->supplier?->supaddr }}</div>
                    <div class="small text-muted">
                        Contact: {{ $po->supplier?->sup_contact_name }} | Email: {{ $po->supplier?->supemail }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Specifications -->
        <div class="row g-3 mb-4 p-3 rounded-4 bg-light border border-light">
            <div class="col-6 col-md-3">
                <div class="text-muted small">Production Order Ref</div>
                <div class="fw-bold font-monospace text-dark">{{ $po->prodOrder?->prodorder_ref ?? $po->prodorder_id }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">PO Date</div>
                <div class="fw-bold text-dark">{{ $po->order_date ? $po->order_date->format('M d, Y') : '-' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Target Delivery Date</div>
                <div class="fw-bold text-dark">{{ $po->delivery_date ? $po->delivery_date->format('M d, Y') : '-' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Season</div>
                <div class="fw-bold text-dark">{{ $po->season ?: '-' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Base Production Qty</div>
                <div class="fw-bold font-monospace text-dark">{{ number_format($po->base_total_qty ?: $po->total_qty) }} pcs</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Wastage / Scrap Buffer</div>
                <div class="fw-bold text-primary">
                    @if($po->wastage_percentage > 0)
                        <span class="badge bg-primary-subtle text-primary font-monospace">+{{ number_format($po->wastage_percentage, 1) }}% Allowance</span>
                    @else
                        <span class="text-muted">None (0%)</span>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Payment Terms</div>
                <div class="fw-bold text-dark">{{ $po->payment_terms ?: 'Net 30' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Currency / FOB</div>
                <div class="fw-bold text-dark">{{ $po->currency ?: 'USD' }} / {{ $po->fob_code ?: 'FOB' }}</div>
            </div>
        </div>

        <!-- Line Items Table -->
        <h5 class="fw-bold text-dark mb-3">Itemized Order Specifications</h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="small fw-bold">#</th>
                        <th class="small fw-bold">Pack No</th>
                        <th class="small fw-bold">Color Code / Description</th>
                        <th class="small fw-bold">Size</th>
                        <th class="small fw-bold">UPC / Ref</th>
                        <th class="small fw-bold text-end">Base Qty</th>
                        <th class="small fw-bold text-center">Wastage %</th>
                        <th class="small fw-bold text-end text-primary">+Buffer</th>
                        <th class="small fw-bold text-end">Order Qty (Pcs)</th>
                        <th class="small fw-bold text-end">Unit Price ($)</th>
                        <th class="small fw-bold text-end">Total Amount ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sumBase = 0;
                        $sumBuffer = 0;
                    @endphp
                    @foreach($po->items as $idx => $item)
                        @php
                            $itemBase = $item->base_quantity ?: $item->quantity;
                            $itemBuffer = $item->wastage_qty ?: max(0, $item->quantity - $itemBase);
                            $sumBase += $itemBase;
                            $sumBuffer += $itemBuffer;
                        @endphp
                        <tr>
                            <td class="text-muted small">{{ $idx + 1 }}</td>
                            <td class="font-monospace small text-muted">{{ $item->pack_no ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->color_desc ?: 'Standard' }}</div>
                                <div class="small text-muted font-monospace">{{ $item->color_code }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                    {{ $item->size_desc ?: 'One Size' }}
                                </span>
                            </td>
                            <td class="small font-monospace text-muted">{{ $item->upc_refno ?: '-' }}</td>
                            <td class="text-end font-monospace text-muted">{{ number_format($itemBase) }}</td>
                            <td class="text-center font-monospace small">
                                @if($item->wastage_percentage > 0)
                                    <span class="badge bg-light text-dark border">{{ number_format($item->wastage_percentage, 1) }}%</span>
                                @elseif($itemBuffer > 0 && $itemBase > 0)
                                    <span class="badge bg-light text-dark border">{{ number_format(($itemBuffer / $itemBase) * 100, 1) }}%</span>
                                @else
                                    <span class="text-muted">0%</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace text-primary fw-semibold">
                                {{ $itemBuffer > 0 ? ('+' . number_format($itemBuffer)) : '-' }}
                            </td>
                            <td class="text-end fw-bold font-monospace text-dark fs-6">{{ number_format($item->quantity) }}</td>
                            <td class="text-end font-monospace text-muted">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-bold font-monospace text-success">${{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end fw-bold">Grand Totals:</th>
                        <th class="text-end font-monospace text-muted">{{ number_format($sumBase) }}</th>
                        <th class="text-center font-monospace small text-muted">{{ $po->wastage_percentage > 0 ? number_format($po->wastage_percentage, 1) . '%' : '-' }}</th>
                        <th class="text-end font-monospace text-primary fw-bold">{{ $sumBuffer > 0 ? ('+' . number_format($sumBuffer)) : '0' }}</th>
                        <th class="text-end fw-bold font-monospace fs-6 text-dark">{{ number_format($po->total_qty) }} Pcs</th>
                        <th></th>
                        <th class="text-end fw-bold font-monospace fs-6 text-success">${{ number_format($po->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($po->notes)
            <div class="p-3 rounded-4 bg-light border border-light mb-4">
                <div class="fw-bold small text-muted mb-1">Special Terms / Instructions:</div>
                <div class="small text-dark">{{ $po->notes }}</div>
            </div>
        @endif

        <!-- Footer Sign-off -->
        <div class="row pt-5 mt-4 border-top g-4">
            <div class="col-6 col-md-4 text-center">
                <div class="border-bottom pb-4 mb-2"></div>
                <div class="small fw-bold text-dark">Authorized Factory Signature</div>
                <div class="small text-muted">{{ $po->branch?->br_name }}</div>
            </div>
            <div class="col-6 col-md-4 text-center">
                <div class="border-bottom pb-4 mb-2"></div>
                <div class="small fw-bold text-dark">Stafford ERP System Validation</div>
                <div class="small text-muted">Auto-Recorded & Verified</div>
            </div>
            <div class="col-12 col-md-4 text-center">
                <div class="border-bottom pb-4 mb-2"></div>
                <div class="small fw-bold text-dark">Supplier Acceptance</div>
                <div class="small text-muted">{{ $po->supplier?->supname }}</div>
            </div>
        </div>

    </div>

</div>
@endsection
