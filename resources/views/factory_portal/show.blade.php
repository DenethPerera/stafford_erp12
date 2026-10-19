@extends('layouts.app')

@section('header', 'Order Allocation Details - ' . $order->prodorder_ref)

@section('content')
@php
    $totalUnits = $orderStats['total_units'] ?? ($totalUnits ?? ($order->items->sum('prod_qty') ?? 0));
@endphp
<div class="d-flex flex-column gap-4" x-data="orderDetailApp()">


    <!-- Top Action Bar -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="{{ route('factory-portal.index', ['branch_id' => $branchId]) }}" class="btn btn-sm btn-light rounded-circle p-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">{{ $order->prodorder_ref }}</h3>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                        {{ $order->prod_order_season ?: 'No Season' }}
                    </span>
                    @if($purchaseOrders->count() > 0)
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                            <i class="bi bi-check-circle me-1"></i> {{ $purchaseOrders->count() }} PO(s) Generated
                        </span>
                    @endif
                </div>
                <div class="text-muted small ms-md-4 ps-md-2">
                    <span class="me-3"><i class="bi bi-calendar me-1"></i> Order Date: <strong>{{ $order->prodorder_date ? \Carbon\Carbon::parse($order->prodorder_date)->format('M d, Y') : '-' }}</strong></span>
                    <span class="me-3"><i class="bi bi-truck me-1"></i> Dispatch: <strong>{{ $order->proddispatch_date ? \Carbon\Carbon::parse($order->proddispatch_date)->format('M d, Y') : '-' }}</strong></span>
                    <span><i class="bi bi-building me-1"></i> Factory: <strong>{{ $order->branch?->br_name }}</strong></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 flex-wrap">
                @if(auth()->user()?->isFactory() && !auth()->user()?->isAdmin())
                    <button type="button" class="btn btn-teal text-white rounded-pill px-4 fw-bold shadow-sm" 
                            style="background-color: #00A39A;" data-bs-toggle="modal" data-bs-target="#poModal">
                        <i class="bi bi-file-earmark-plus me-1"></i> Create Purchase Order
                    </button>
                @else
                    <span class="badge bg-light text-muted border rounded-pill px-3 py-2 small d-flex align-items-center">
                        <i class="bi bi-eye me-1"></i> Read Only (Admin View)
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Order Summary Cards -->
    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="text-uppercase fw-bold text-muted small">Total Allocated Units</div>
                    <h3 class="fw-bolder text-primary mb-0 font-monospace">{{ number_format($orderStats['total_units']) }}</h3>
                    <div class="text-muted small mt-1">{{ $enrichedLineItems->count() }} unique color/sizes</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="text-uppercase fw-bold text-muted small">PO Units Progress</div>
                    <h3 class="fw-bolder text-success mb-0 font-monospace">
                        {{ number_format($orderStats['total_ordered']) }} <span class="fs-6 text-muted fw-normal">/ {{ number_format($orderStats['total_units']) }}</span>
                    </h3>
                    <div class="small mt-1 {{ $orderStats['total_remaining'] > 0 ? 'text-warning fw-semibold' : 'text-success' }}">
                        @if($orderStats['total_remaining'] > 0)
                            <i class="bi bi-clock me-1"></i> {{ number_format($orderStats['total_remaining']) }} units remaining to order
                        @else
                            <i class="bi bi-check2-all me-1"></i> All allocated units converted to POs
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="text-uppercase fw-bold text-muted small">Estimated Value</div>
                    <h3 class="fw-bolder text-dark mb-0 font-monospace">${{ number_format($orderStats['total_amount'], 2) }}</h3>
                    <div class="text-muted small mt-1">Based on vendor unit pricing</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="text-uppercase fw-bold text-muted small">Available Suppliers</div>
                    <h3 class="fw-bolder text-teal mb-0 font-monospace" style="color: #00A39A;">
                        {{ $orderStats['suppliers_count'] }}
                    </h3>
                  
                </div>
            </div>
        </div>
    </div>

    <!-- Generated Purchase Orders Section -->
    @if($purchaseOrders->count() > 0)
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-check-fill text-success fs-5"></i>
                    <h5 class="fw-bold text-dark mb-0">Generated Purchase Orders</h5>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3">{{ $purchaseOrders->count() }} Issued</span>
                </div>
                @if(auth()->user()?->isFactory() && !auth()->user()?->isAdmin())
                    <button type="button" class="btn btn-sm btn-teal text-white rounded-pill px-3 fw-bold" 
                            style="background-color: #00A39A;" data-bs-toggle="modal" data-bs-target="#poModal">
                        <i class="bi bi-plus-circle me-1"></i> Create Another PO
                    </button>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 text-uppercase text-muted small fw-bold">PO Number</th>
                            <th class="text-uppercase text-muted small fw-bold">Supplier</th>
                            <th class="text-uppercase text-muted small fw-bold">Order / Delivery Date</th>
                            <th class="text-end text-uppercase text-muted small fw-bold">Items</th>
                            <th class="text-end text-uppercase text-muted small fw-bold">Total Qty (Pcs)</th>
                            <th class="text-end text-uppercase text-muted small fw-bold">Total Amount ($)</th>
                            <th class="text-center text-uppercase text-muted small fw-bold">Status</th>
                            <th class="text-end pe-4 text-uppercase text-muted small fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrders as $po)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('purchase-orders.show', $po->id) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $po->supplier?->supname ?: 'Supplier #' . $po->supplierno }}</div>
                                    <div class="small text-muted font-monospace">{{ $po->supplier?->supregcode ?: '-' }}</div>
                                </td>
                                <td class="small">
                                    <div><i class="bi bi-calendar-event me-1 text-muted"></i> {{ $po->order_date ? $po->order_date->format('M d, Y') : '-' }}</div>
                                    @if($po->delivery_date)
                                        <div class="text-muted"><i class="bi bi-truck me-1"></i> {{ $po->delivery_date->format('M d, Y') }}</div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace small">
                                    {{ $po->items->count() }} line(s)
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    {{ number_format($po->total_qty) }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    ${{ number_format($po->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 text-uppercase">
                                        {{ $po->status }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('purchase-orders.show', $po->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i> View PO
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Line Items Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-0">Order Line Items & Color Matrix</h5>
                <div class="text-muted small">Items mapped to styles with dynamic supplier associations</div>
            </div>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1 font-monospace">
                Total: {{ number_format($orderStats['total_units']) }} pcs
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase text-muted small fw-bold">Pack No</th>
                        <th class="text-uppercase text-muted small fw-bold">Style No</th>
                        <th class="text-uppercase text-muted small fw-bold">Color Code / Description</th>
                        <th class="text-uppercase text-muted small fw-bold">Size</th>
                        <th class="text-uppercase text-muted small fw-bold">Suppliers</th>
                        <th class="text-end text-uppercase text-muted small fw-bold">Allocated Qty</th>
                        <th class="text-end text-uppercase text-muted small fw-bold">PO Status</th>
                        <th class="text-end text-uppercase text-muted small fw-bold">Unit Price ($)</th>
                        <th class="text-end pe-4 text-uppercase text-muted small fw-bold">Line Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrichedLineItems as $item)
                        @php
                            $lineQty = (int) ($item->prod_qty ?: 0);
                            $linePrice = (float) ($item->vendor_unit_price ?: 0);
                            $lineTotal = $lineQty * $linePrice;
                        @endphp
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-muted">{{ $item->pack_no }}</td>
                            <td>
                                <span class="badge bg-dark-subtle text-dark font-monospace px-2 py-1">
                                    {{ $item->item_styleno ?: '-' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->tbl_style_color_desc ?: 'Standard' }}</div>
                                <div class="small text-muted font-monospace">{{ $item->tbl_style_color_code ?: '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                    {{ $item->tbl_style_size_desc ?: 'One Size' }}
                                </span>
                            </td>
                            <td class="small">
                                @if(count($item->supplier_names) > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($item->supplier_names as $sName)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill font-monospace" style="font-size: 11px;">
                                                {{ Str::limit($sName, 22) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">No supplier in costing</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold font-monospace text-dark fs-6">
                                {{ number_format($lineQty) }}
                            </td>
                            <td class="text-end">
                                @if($item->is_fully_ordered)
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 font-monospace">
                                        <i class="bi bi-check-circle me-1"></i> Ordered ({{ number_format($item->ordered_qty) }})
                                    </span>
                                @elseif($item->ordered_qty > 0)
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1 font-monospace">
                                        {{ number_format($item->ordered_qty) }} / {{ number_format($lineQty) }} ordered
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 font-monospace">
                                        Pending PO
                                    </span>
                                @endif
                            </td>
                            <td class="text-end font-monospace text-muted">
                                ${{ number_format($linePrice, 2) }}
                            </td>
                            <td class="text-end pe-4 font-monospace fw-bold text-success">
                                ${{ number_format($lineTotal, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No line items found for this production order in <code>prodorder_info</code>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>



    @if(auth()->user()?->isFactory() && !auth()->user()?->isAdmin())
    <!-- Modal: Create Purchase Order -->
    <div class="modal fade" id="poModal" tabindex="-1" aria-labelledby="poModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <form method="POST" action="{{ route('factory-portal.store-po', ['id' => $order->prodorder_id, 'branch_id' => $branchId]) }}">
                    @csrf
                    <div class="modal-header border-0 bg-teal text-white p-4" style="background-color: #00A39A;">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-file-earmark-plus me-2"></i> Generate Purchase Order from Production Order
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">PO Reference Number <span class="text-danger">*</span></label>
                                <input type="text" name="po_number" id="po_number_input" class="form-control rounded-3 font-monospace" 
                                       value="{{ 'PO-' . ($order->branch?->br_code ?: 'FAC') . '-' . $order->prodorder_ref }}" required>
                                <div class="form-text small">Unique purchase order reference identifier.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Select Supplier <span class="text-danger">*</span></label>
                                @if($suppliersList->count() > 0)
                                    <select name="supplierno" id="po_supplier_select" class="form-select rounded-3" onchange="filterPoItemsBySupplier(this.value)" required>
                                        <option value="">-- Choose Supplier to Filter Items --</option>
                                        @foreach($suppliersList as $sup)
                                            <option value="{{ $sup['supplierno'] }}"
                                                    data-supname="{{ $sup['supname'] }}"
                                                    data-supcode="{{ $sup['supregcode'] }}"
                                                    data-styles="{{ implode(', ', $sup['styles']) }}">
                                                {{ $sup['supname'] }} ({{ $sup['supregcode'] ?: 'No Code' }}) — {{ count($sup['pack_nos']) }} item(s) [Styles: {{ implode(', ', $sup['styles']) }}]
                                            </option>
                                        @endforeach
                                    </select>
                                   
                                @else
                                    <div class="alert alert-warning p-2 small mb-0 rounded-3">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> No suppliers found in style cost sheets (<code>prodstyle_cost</code>) for this order's styles.
                                    </div>
                                @endif
                            </div>

                            <!-- Dynamic Supplier Filter Info Banner -->
                            <div id="supplier_filter_banner" class="col-12 d-none">
                                <div class="p-3 rounded-4 border d-flex flex-wrap align-items-center justify-content-between gap-2" 
                                     style="background-color: #f0fdf4; border-color: #bbf7d0;">
                                    <div>
                                        <span class="text-muted small fw-bold text-uppercase">Filtered Supplier:</span>
                                        <strong class="text-dark ms-1" id="filter_banner_supname"></strong>
                                        <span class="badge rounded-pill ms-2 text-white" style="background-color: #00A39A;" id="filter_banner_count">0 Items</span>
                                    </div>
                                    <div class="small text-muted font-monospace" id="filter_banner_styles"></div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PO Order Date <span class="text-danger">*</span></label>
                                <input type="date" name="order_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Target Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control rounded-3" value="{{ $order->proddispatch_date }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Shipping Method</label>
                                <input type="text" name="shipping_method" class="form-control rounded-3" value="{{ $order->ship_method ?: 'Sea' }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Terms</label>
                                <input type="text" name="payment_terms" class="form-control rounded-3" value="{{ $order->pay_term ?: 'Net 30 days' }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Delivery Address</label>
                                <input type="text" name="delivery_address" class="form-control rounded-3" value="{{ $order->branch?->br_addr ?: '' }}">
                            </div>
                        </div>

                        <!-- PO Items Table with Per-Item Wastage % Column -->
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Purchase Order Line Items</h6>
                                <div class="text-muted small" style="font-size: 11px;">Configure wastage allowance percentage per item or batch-apply across all lines.</div>
                            </div>
                            <div class="d-flex align-items-center gap-1 small">
                                <span class="text-muted small fw-semibold me-1">Set All Lines:</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-2" onclick="batchSetWastage(0)">0%</button>
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-2" onclick="batchSetWastage(3)">3%</button>
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-2" onclick="batchSetWastage(5)">5%</button>
                                    <button type="button" class="btn text-white py-0.5 px-2 fw-bold" style="background-color: #00A39A;" onclick="batchSetWastage(10)">10%</button>
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-2" onclick="batchSetWastage(15)">15%</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-sm align-middle mb-0" id="po_items_table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 text-center" style="width: 45px;">
                                            <input type="checkbox" id="master_item_checkbox" class="form-check-input" onchange="toggleSelectAllItems(this.checked)" title="Select / Deselect All Items" disabled>
                                        </th>
                                        <th class="small fw-bold">Pack No</th>
                                        <th class="small fw-bold">Style</th>
                                        <th class="small fw-bold">Color / Size</th>
                                        <th class="small fw-bold text-end" style="width: 85px;">Rem. Qty</th>
                                        <th class="small fw-bold text-center" style="width: 100px;">Wastage %</th>
                                        <th class="small fw-bold text-end text-primary" style="width: 75px;">+Buffer</th>
                                        <th class="small fw-bold text-end" style="width: 110px;">Final PO Qty</th>
                                        <th class="small fw-bold text-end" style="width: 100px;">Unit Price ($)</th>
                                        <th class="small fw-bold text-end pe-3" style="width: 105px;">Total ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="no_supplier_row">
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="bi bi-funnel fs-3 d-block mb-1" style="color: #00A39A;"></i>
                                            Please select a supplier from the dropdown above to filter production order items.
                                        </td>
                                    </tr>
                                    <tr id="no_matching_items_row" class="d-none">
                                        <td colspan="10" class="text-center py-4 text-warning">
                                            <i class="bi bi-exclamation-triangle fs-3 d-block mb-1"></i>
                                            No line items available for this supplier or all items have already been ordered.
                                        </td>
                                    </tr>
                                    @foreach($enrichedLineItems as $idx => $item)
                                        @php
                                            $allocQty = (int) ($item->prod_qty ?: 0);
                                            $remQty = max(0, (int) $item->remaining_qty);
                                            $unitPrice = (float) ($item->vendor_unit_price ?: 0);
                                            $baseQty = $remQty; // Strictly use remaining quantity (0 if already fully ordered)
                                            $isFulfilled = ($allocQty > 0 && $remQty === 0);
                                        @endphp
                                        <tr class="po-item-row d-none {{ $isFulfilled ? 'table-light opacity-75' : '' }}" 
                                            data-index="{{ $idx }}" 
                                            data-pack-no="{{ $item->pack_no }}"
                                            data-style-no="{{ $item->item_styleno }}"
                                            data-suppliers="{{ json_encode($item->supplier_nos) }}"
                                            data-base-qty="{{ $baseQty }}"
                                            data-orig-qty="{{ $allocQty }}"
                                            data-ordered-qty="{{ $item->ordered_qty }}"
                                            data-rem-qty="{{ $remQty }}">
                                            <td class="ps-3 text-center">
                                                <input type="checkbox" class="form-check-input item-select-checkbox" 
                                                       onchange="onRowSelectionChange(this)" 
                                                       {{ $isFulfilled ? 'disabled' : 'checked' }} 
                                                       title="{{ $isFulfilled ? 'Already fulfilled' : 'Include in this PO' }}">
                                            </td>
                                            <td class="font-monospace small">
                                                <div class="fw-bold">{{ $item->pack_no }}</div>
                                                @if($isFulfilled)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0.5 rounded-pill" style="font-size: 9px;">
                                                        <i class="bi bi-check2"></i>
                                                    </span>
                                                @endif
                                                <input type="hidden" name="items[{{ $idx }}][pack_no]" value="{{ $item->pack_no }}" class="item-pack-input" disabled>
                                                <input type="hidden" name="items[{{ $idx }}][base_quantity]" class="item-base-qty" value="{{ $baseQty }}" disabled>
                                            </td>
                                            <td class="small">
                                                <span class="badge bg-dark-subtle text-dark font-monospace">{{ $item->item_styleno ?: '-' }}</span>
                                            </td>
                                            <td class="small">
                                                <div class="fw-semibold">{{ $item->tbl_style_color_desc ?: '-' }}</div>
                                                <div class="text-muted" style="font-size: 11px;">{{ $item->tbl_style_size_desc ?: '-' }}</div>
                                            </td>
                                            <td class="text-end font-monospace small">
                                                <div class="fw-bold {{ $remQty == 0 ? 'text-success' : 'text-dark' }}">{{ number_format($remQty) }}</div>
                                                @if($item->ordered_qty > 0)
                                                    <div class="text-muted" style="font-size: 10px;">{{ number_format($item->ordered_qty) }} in PO</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="input-group input-group-sm mx-auto" style="max-width: 90px;">
                                                    <input type="number" step="0.5" min="0" max="100" 
                                                           name="items[{{ $idx }}][wastage_percentage]" 
                                                           class="form-control form-control-sm text-end font-monospace p-1 item-wastage-pct" 
                                                           value="{{ $isFulfilled ? 0 : 10 }}" oninput="onRowWastageChange(this)" disabled>
                                                    <span class="input-group-text p-1 text-muted small">%</span>
                                                </div>
                                            </td>
                                            <td class="text-end font-monospace small text-primary fw-semibold item-buffer-label">
                                                +0
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $idx }}][quantity]" 
                                                       class="form-control form-control-sm text-end font-monospace fw-bold item-final-qty" 
                                                       value="{{ $baseQty }}" min="0" oninput="onManualQtyChange(this)" disabled required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" 
                                                       class="form-control form-control-sm text-end font-monospace item-unit-price" 
                                                       value="{{ $unitPrice }}" min="0" oninput="recalcTotals()" disabled required>
                                            </td>
                                            <td class="text-end pe-3 font-monospace fw-bold text-success small item-line-total">
                                                ${{ number_format($baseQty * $unitPrice, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="ps-3 fw-bold small">Filtered Totals:</th>
                                        <th class="text-end font-monospace small text-muted" id="footer_base_total">0</th>
                                        <th class="text-center font-monospace small text-muted" id="footer_avg_pct">10.0%</th>
                                        <th class="text-end font-monospace small text-primary fw-bold" id="footer_buffer_total">+0</th>
                                        <th class="text-end font-monospace fw-bold text-dark" id="footer_final_qty">0 Pcs</th>
                                        <th></th>
                                        <th class="text-end pe-3 font-monospace fw-bold text-success" id="footer_grand_total">$0.00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Special Instructions / Notes</label>
                            <textarea name="notes" class="form-control rounded-3" rows="2" 
                                      placeholder="Optional notes for supplier and audit records..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn_submit_po" class="btn btn-teal text-white rounded-pill px-4 fw-bold" 
                                style="background-color: #00A39A;" disabled>
                            Confirm & Issue Purchase Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
const basePoNumberPrefix = '{{ "PO-" . ($order->branch?->br_code ?: "FAC") . "-" . $order->prodorder_ref }}';

function filterPoItemsBySupplier(supplierno) {
    const select = document.getElementById('po_supplier_select');
    const selectedOption = select.options[select.selectedIndex];
    const supName = selectedOption?.getAttribute('data-supname') || '';
    const supCode = selectedOption?.getAttribute('data-supcode') || '';
    const supStyles = selectedOption?.getAttribute('data-styles') || '';

    const banner = document.getElementById('supplier_filter_banner');
    const noSupRow = document.getElementById('no_supplier_row');
    const noMatchRow = document.getElementById('no_matching_items_row');
    const submitBtn = document.getElementById('btn_submit_po');
    const poNumberInput = document.getElementById('po_number_input');
    const masterCb = document.getElementById('master_item_checkbox');

    if (!supplierno) {
        if (banner) banner.classList.add('d-none');
        if (noSupRow) noSupRow.classList.remove('d-none');
        if (noMatchRow) noMatchRow.classList.add('d-none');
        if (submitBtn) submitBtn.disabled = true;
        if (masterCb) {
            masterCb.checked = false;
            masterCb.disabled = true;
            masterCb.indeterminate = false;
        }

        document.querySelectorAll('.po-item-row').forEach(row => {
            row.classList.add('d-none');
            const cb = row.querySelector('.item-select-checkbox');
            if (cb) cb.checked = false;
            row.querySelectorAll('input:not(.item-select-checkbox)').forEach(inp => inp.disabled = true);
        });
        recalcTotals();
        return;
    }

    if (noSupRow) noSupRow.classList.add('d-none');

    // Update PO Reference with supplier suffix
    if (poNumberInput) {
        const supSuffix = supCode ? ('-' + supCode) : ('-S' + supplierno);
        poNumberInput.value = basePoNumberPrefix + supSuffix;
    }

    let visibleCount = 0;
    let orderableCount = 0;
    const targetSupNo = parseInt(supplierno, 10);

    document.querySelectorAll('.po-item-row').forEach(row => {
        let suppliers = [];
        try {
            suppliers = JSON.parse(row.getAttribute('data-suppliers') || '[]');
        } catch (e) {
            suppliers = [];
        }

        const isMatch = suppliers.includes(targetSupNo);
        const remQty = parseInt(row.getAttribute('data-rem-qty') || '0', 10);
        const cb = row.querySelector('.item-select-checkbox');

        if (isMatch) {
            row.classList.remove('d-none');
            visibleCount++;
            if (remQty > 0) {
                orderableCount++;
                if (cb) {
                    cb.checked = true;
                    cb.disabled = false;
                }
                row.classList.remove('opacity-50');
                row.querySelectorAll('input:not(.item-select-checkbox)').forEach(inp => inp.disabled = false);
            } else {
                if (cb) {
                    cb.checked = false;
                    cb.disabled = true;
                }
                row.classList.add('opacity-50');
                row.querySelectorAll('input:not(.item-select-checkbox)').forEach(inp => inp.disabled = true);
            }
        } else {
            row.classList.add('d-none');
            if (cb) cb.checked = false;
            row.querySelectorAll('input:not(.item-select-checkbox)').forEach(inp => inp.disabled = true);
        }
    });

    if (masterCb) {
        masterCb.disabled = (orderableCount === 0);
        masterCb.checked = (orderableCount > 0);
        masterCb.indeterminate = false;
    }

    if (banner) {
        banner.classList.remove('d-none');
        const nameEl = document.getElementById('filter_banner_supname');
        const countEl = document.getElementById('filter_banner_count');
        const stylesEl = document.getElementById('filter_banner_styles');
        if (nameEl) nameEl.textContent = supName;
        if (countEl) {
            countEl.textContent = visibleCount + ' Item(s)' + (orderableCount < visibleCount ? ` (${orderableCount} pending order)` : '');
        }
        if (stylesEl) stylesEl.textContent = supStyles ? ('Styles: ' + supStyles) : '';
    }

    if (noMatchRow) {
        noMatchRow.classList.toggle('d-none', visibleCount > 0);
    }

    // Apply default 10% wastage to active lines
    batchSetWastage(10);
    recalcTotals();
}

function toggleSelectAllItems(checked) {
    const rows = document.querySelectorAll('.po-item-row:not(.d-none)');
    rows.forEach(row => {
        const remQty = parseInt(row.getAttribute('data-rem-qty') || '0', 10);
        const cb = row.querySelector('.item-select-checkbox');
        if (remQty > 0 && cb) {
            cb.checked = checked;
            row.querySelectorAll('input:not(.item-select-checkbox)').forEach(inp => inp.disabled = !checked);
            row.classList.toggle('opacity-50', !checked);
        }
    });
    recalcTotals();
}

function onRowSelectionChange(checkboxEl) {
    const row = checkboxEl.closest('.po-item-row');
    if (!row) return;

    const isChecked = checkboxEl.checked;
    row.querySelectorAll('input:not(.item-select-checkbox)').forEach(inp => inp.disabled = !isChecked);
    row.classList.toggle('opacity-50', !isChecked);

    // Update master checkbox state
    const orderableRows = Array.from(document.querySelectorAll('.po-item-row:not(.d-none)'))
        .filter(r => parseInt(r.getAttribute('data-rem-qty') || '0', 10) > 0);
    const checkedRows = orderableRows.filter(r => r.querySelector('.item-select-checkbox')?.checked);
    const masterCb = document.getElementById('master_item_checkbox');
    if (masterCb) {
        masterCb.checked = (orderableRows.length > 0 && checkedRows.length === orderableRows.length);
        masterCb.indeterminate = (checkedRows.length > 0 && checkedRows.length < orderableRows.length);
    }

    recalcTotals();
}

function onRowWastageChange(inputEl) {
    const row = inputEl.closest('.po-item-row');
    if (!row) return;

    const baseQty = parseInt(row.getAttribute('data-base-qty') || 0, 10);
    if (baseQty <= 0) {
        const qtyInput = row.querySelector('.item-final-qty');
        if (qtyInput) qtyInput.value = 0;
        const bufferLabel = row.querySelector('.item-buffer-label');
        if (bufferLabel) bufferLabel.textContent = '+0';
        const lineTotalLabel = row.querySelector('.item-line-total');
        if (lineTotalLabel) lineTotalLabel.textContent = '$0.00';
        recalcTotals();
        return;
    }

    const pct = Math.max(0, parseFloat(inputEl.value) || 0);
    const buffer = Math.round(baseQty * (pct / 100));
    const finalQty = baseQty + buffer;
    const price = parseFloat(row.querySelector('.item-unit-price')?.value || 0);

    const qtyInput = row.querySelector('.item-final-qty');
    if (qtyInput) qtyInput.value = finalQty;

    const bufferLabel = row.querySelector('.item-buffer-label');
    if (bufferLabel) bufferLabel.textContent = buffer > 0 ? ('+' + buffer.toLocaleString()) : '0';

    const lineTotalLabel = row.querySelector('.item-line-total');
    if (lineTotalLabel) lineTotalLabel.textContent = '$' + (finalQty * price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    recalcTotals();
}

function onManualQtyChange(inputEl) {
    const row = inputEl.closest('.po-item-row');
    if (!row) return;

    const baseQty = parseInt(row.getAttribute('data-base-qty') || 0, 10);
    const finalQty = Math.max(0, parseInt(inputEl.value) || 0);
    const buffer = Math.max(0, finalQty - baseQty);
    const price = parseFloat(row.querySelector('.item-unit-price')?.value || 0);

    // Calculate corresponding percentage
    const pct = baseQty > 0 ? Math.round(((buffer / baseQty) * 100) * 10) / 10 : 0;
    const pctInput = row.querySelector('.item-wastage-pct');
    if (pctInput) pctInput.value = pct;

    const bufferLabel = row.querySelector('.item-buffer-label');
    if (bufferLabel) bufferLabel.textContent = buffer > 0 ? ('+' + buffer.toLocaleString()) : '0';

    const lineTotalLabel = row.querySelector('.item-line-total');
    if (lineTotalLabel) lineTotalLabel.textContent = '$' + (finalQty * price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    recalcTotals();
}

function batchSetWastage(pct) {
    const rows = document.querySelectorAll('.po-item-row:not(.d-none)');
    rows.forEach(row => {
        const rowCb = row.querySelector('.item-select-checkbox');
        if (rowCb && !rowCb.checked) {
            return; // Skip unselected rows
        }

        const baseQty = parseInt(row.getAttribute('data-base-qty') || 0, 10);
        
        // If remaining quantity is 0 (already fulfilled), keep quantity and buffer at 0
        if (baseQty <= 0) {
            const qtyInput = row.querySelector('.item-final-qty');
            if (qtyInput) qtyInput.value = 0;
            const bufferLabel = row.querySelector('.item-buffer-label');
            if (bufferLabel) bufferLabel.textContent = '+0';
            const pctInput = row.querySelector('.item-wastage-pct');
            if (pctInput) pctInput.value = 0;
            const lineTotalLabel = row.querySelector('.item-line-total');
            if (lineTotalLabel) lineTotalLabel.textContent = '$0.00';
            return;
        }

        const pctInput = row.querySelector('.item-wastage-pct');
        if (pctInput) {
            pctInput.value = pct;
            const buffer = Math.round(baseQty * (pct / 100));
            const finalQty = baseQty + buffer;
            const price = parseFloat(row.querySelector('.item-unit-price')?.value || 0);

            const qtyInput = row.querySelector('.item-final-qty');
            if (qtyInput) qtyInput.value = finalQty;

            const bufferLabel = row.querySelector('.item-buffer-label');
            if (bufferLabel) bufferLabel.textContent = buffer > 0 ? ('+' + buffer.toLocaleString()) : '0';

            const lineTotalLabel = row.querySelector('.item-line-total');
            if (lineTotalLabel) lineTotalLabel.textContent = '$' + (finalQty * price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });

    recalcTotals();
}

function recalcTotals() {
    const rows = document.querySelectorAll('.po-item-row:not(.d-none)');
    let totalBase = 0;
    let totalBuffer = 0;
    let totalFinal = 0;
    let grandTotalAmount = 0;
    let selectedCount = 0;

    rows.forEach(row => {
        const rowCb = row.querySelector('.item-select-checkbox');
        if (rowCb && !rowCb.checked) {
            return; // Only calculate selected items
        }

        const baseQty = parseInt(row.getAttribute('data-base-qty') || 0, 10);
        const finalQty = parseInt(row.querySelector('.item-final-qty')?.value || 0, 10);
        const buffer = Math.max(0, finalQty - baseQty);
        const price = parseFloat(row.querySelector('.item-unit-price')?.value || 0);
        const lineTotal = finalQty * price;

        totalBase += baseQty;
        totalBuffer += buffer;
        totalFinal += finalQty;
        grandTotalAmount += lineTotal;
        selectedCount++;
    });

    const fBase = document.getElementById('footer_base_total');
    const fAvg = document.getElementById('footer_avg_pct');
    const fBuf = document.getElementById('footer_buffer_total');
    const fFin = document.getElementById('footer_final_qty');
    const fTot = document.getElementById('footer_grand_total');

    const avgPct = totalBase > 0 ? ((totalBuffer / totalBase) * 100).toFixed(1) : '0.0';

    if (fBase) fBase.textContent = Number(totalBase).toLocaleString();
    if (fAvg) fAvg.textContent = avgPct + '%';
    if (fBuf) fBuf.textContent = (totalBuffer > 0 ? '+' : '') + Number(totalBuffer).toLocaleString();
    if (fFin) fFin.textContent = Number(totalFinal).toLocaleString() + ' Pcs';
    if (fTot) fTot.textContent = '$' + grandTotalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const submitBtn = document.getElementById('btn_submit_po');
    if (submitBtn) {
        submitBtn.disabled = (totalFinal <= 0 || selectedCount === 0);
    }
}
</script>
@endsection
