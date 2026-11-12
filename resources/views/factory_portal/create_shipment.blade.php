@extends('layouts.app')

@section('header', $editingDelivery ? 'Edit Shipment' : 'Create Shipment')

@section('content')
<style>
    .factory-shipment .section-title {
        color: #124e78;
        font-weight: 800;
    }

    .factory-shipment .section-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #e8f7f5;
        color: #008b83;
        border-radius: 12px;
        flex-shrink: 0;
    }

    .factory-shipment .form-label {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 700;
    }

    .factory-shipment .form-control,
    .factory-shipment .form-select {
        min-height: 43px;
        border-color: #dce5eb;
        border-radius: 10px;
    }

    .factory-shipment .form-control:focus,
    .factory-shipment .form-select:focus {
        border-color: #00a39a;
        box-shadow: 0 0 0 0.2rem rgba(0, 163, 154, 0.12);
    }

    .factory-shipment .form-control[readonly],
    .factory-shipment .form-select:disabled {
        background-color: #f1f5f9;
        color: #64748b;
    }

    .factory-shipment .packing-editor {
        background-color: #f7fbfb;
        border: 1px solid #e1eeed;
        border-radius: 12px;
    }

    .factory-shipment .packing-editor summary {
        color: #008b83;
        font-weight: 700;
        cursor: pointer;
    }

    .factory-shipment .form-check-input:checked {
        background-color: #00a39a;
        border-color: #00a39a;
    }
</style>

<div class="factory-shipment d-flex flex-column gap-4">
    <!-- Page title and simple navigation actions. -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-icon"><i class="bi bi-truck fs-5" aria-hidden="true"></i></span>
                <div>
                    <h2 class="h4 section-title mb-1">Order Delivery</h2>
                    <p class="text-muted small mb-0">{{ $editingDelivery ? 'Update delivery #' . $editingDelivery->id . '.' : 'Prepare a new factory shipment.' }}</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('shipment-details.index') }}" class="btn btn-light border rounded-pill px-3">
                    <i class="bi bi-search me-1" aria-hidden="true"></i> Find Shipment
                </a>
                <button type="button" class="btn btn-outline-primary rounded-circle"
                        style="width: 44px; height: 44px;" data-bs-toggle="modal" data-bs-target="#shipment-history-modal"
                        aria-label="View created shipment history" title="Shipment history">
                    <i class="bi bi-clock-history fs-5" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    @if (!$assignmentId)
        <div class="alert alert-info rounded-4 mb-0">
            Open Shipment Details and click Create Shipment on the relevant row.
        </div>
    @endif

    <form id="factory-shipment-form" method="POST"
          action="{{ $editingDelivery ? route('create-shipment.update', $editingDelivery->id) : route('create-shipment.store') }}"
          class="d-flex flex-column gap-4">
        @csrf
        @if ($editingDelivery)
            @method('PUT')
        @endif
        <input type="hidden" name="assignment_id" value="{{ $assignmentId }}">
        <!-- Shipment and invoice information. -->
        <section class="card border-0 shadow-sm rounded-4 bg-white" aria-labelledby="shipment-information-heading">
            <div class="card-body p-4">
                <h3 id="shipment-information-heading" class="h6 section-title mb-4">
                    <i class="bi bi-file-earmark-text me-2" aria-hidden="true"></i>Shipment Information
                </h3>
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label for="production_order_id" class="form-label">Order Ref. No.</label>
                        <select id="production_order_id" name="production_order_id" class="form-select" required>
                            <option value="">Select an order reference</option>
                            @foreach ($productionOrders as $productionOrder)
                                <option value="{{ $productionOrder->prodorder_id }}"
                                        @selected(old('production_order_id', $formData['production_order_id'] ?? $selectedProductionOrderId) == $productionOrder->prodorder_id)>
                                    {{ $productionOrder->prodorder_ref }}
                                </option>
                            @endforeach
                        </select>
                        @if ($assignmentId && $productionOrders->isEmpty())
                            <div class="form-text">No active production orders found for this PO and factory.</div>
                        @endif
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="factory_name" class="form-label">Factory Name</label>
                        <input type="text" id="factory_name" class="form-control" value="{{ $factoryName ?? 'Factory unavailable' }}" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="prepared_date" class="form-label">Prepared Date</label>
                        <input type="date" id="prepared_date" name="prepared_date" class="form-control" value="{{ old('prepared_date', $formData['prepared_date'] ?? now('Asia/Colombo')->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <input type="text" id="remarks" name="remarks" class="form-control" value="{{ old('remarks', $formData['remarks'] ?? '') }}" placeholder="Optional remarks" maxlength="100">
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="loading_port" class="form-label">Loading Port</label>
                        <input type="text" id="loading_port" name="loading_port" class="form-control" value="{{ old('loading_port') }}" placeholder="Enter loading port" maxlength="255">
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="customer_name" class="form-label">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ $customerName }}" placeholder="Enter customer name" maxlength="255">
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="container_number" class="form-label">Container No.</label>
                        <input type="text" id="container_number" name="container_number" class="form-control" value="{{ old('container_number', $formData['container_number'] ?? '') }}" placeholder="Enter container number" maxlength="50">
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="vessel" class="form-label">Vessel</label>
                        <input type="text" id="vessel" name="vessel" class="form-control" value="{{ old('vessel', $formData['vessel'] ?? '') }}" placeholder="Enter vessel name" maxlength="25">
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="vendor_invoice_number" class="form-label">Vendor Invoice No.</label>
                        <input type="text" id="vendor_invoice_number" name="vendor_invoice_number" class="form-control" value="{{ old('vendor_invoice_number', $formData['vendor_invoice_number'] ?? '') }}" placeholder="Enter vendor invoice number" maxlength="40" required>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="stafford_invoice_number" class="form-label">Stafford Invoice No.</label>
                        <input type="text" id="stafford_invoice_number" name="stafford_invoice_number" class="form-control" value="{{ old('stafford_invoice_number', $formData['stafford_invoice_number'] ?? '') }}" placeholder="Enter Stafford invoice number" maxlength="40">
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="ship_via" class="form-label">Ship Via</label>
                        <select id="ship_via" name="ship_via" class="form-select" required>
                            <option value="">Select shipping method</option>
                            <option value="courier" @selected(old('ship_via', $formData['ship_via'] ?? '') === 'courier')>Courier</option>
                            <option value="sea" @selected(old('ship_via', $formData['ship_via'] ?? '') === 'sea')>Sea</option>
                            <option value="air" @selected(old('ship_via', $formData['ship_via'] ?? '') === 'air')>Air</option>
                            <option value="road" @selected(old('ship_via', $formData['ship_via'] ?? '') === 'road')>Road</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="etd" class="form-label">ETD</label>
                        <input type="date" id="etd" name="etd" class="form-control" value="{{ old('etd', $formData['etd'] ?? '') }}" aria-describedby="etd-help">
                        <div id="etd-help" class="form-text">Estimated departure date</div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="shipment_number" class="form-label">Shipment No.</label>
                        <input type="text" id="shipment_number" name="shipment_number" class="form-control" value="{{ $shipmentNumber }}" placeholder="Shipment number" maxlength="15" readonly>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label for="pl_label" class="form-label">PL Label</label>
                        <input type="text" id="pl_label" name="pl_label" class="form-control" value="{{ old('pl_label', $formData['pl_label'] ?? '') }}" placeholder="Optional packing list label" maxlength="30">
                    </div>
                </div>
            </div>
        </section>

        <!-- One item row, matching the original Order Delivery screen. -->
        <section class="card border-0 shadow-sm rounded-4 bg-white" aria-labelledby="order-item-heading">
            <div class="card-body p-4">
                <h3 id="order-item-heading" class="h6 section-title mb-4">
                    <i class="bi bi-box-seam me-2" aria-hidden="true"></i>Order Item
                </h3>
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label for="purchase_order_id" class="form-label">PO No.</label>
                        <select id="purchase_order_id" name="purchase_order_id" class="form-select" aria-describedby="po-help" @disabled($assignmentId)>
                            <option value="">Select a purchase order</option>
                            @foreach ($purchaseOrders as $poId => $poNumber)
                                <option value="{{ $poId }}" @selected($selectedPurchaseOrderId == $poId)>{{ $poNumber }}</option>
                            @endforeach
                        </select>
                        <div id="po-help" class="form-text">
                            @if ($purchaseOrders->isEmpty())
                                No purchase orders assigned to your factory yet.
                            @else
                                Purchase orders assigned to your factory.
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="production_item_id" class="form-label">Style Code</label>
                        <select id="production_item_id" name="production_item_id" class="form-select" required>
                            <option value="">Select a style / color / size</option>
                            @foreach ($orderItems as $orderItem)
                                <option value="{{ $orderItem->pack_no }}"
                                        data-order-id="{{ $orderItem->prodorder_id }}"
                                        data-style-id="{{ $orderItem->style_id }}"
                                        data-style-code="{{ $orderItem->item_styleno }}"
                                        data-color-code="{{ $orderItem->item_colorcode }}"
                                        data-color="{{ $orderItem->tbl_style_color_desc }}"
                                        data-size="{{ $orderItem->tbl_style_size_desc }}"
                                        data-quantity="{{ $orderItem->prod_qty }}"
                                        @selected(old('production_item_id', $formData['production_item_id'] ?? '') == $orderItem->pack_no)>
                                    {{ $orderItem->item_styleno }} / {{ $orderItem->tbl_style_color_desc }}
                                    / {{ $orderItem->tbl_style_size_desc }} — Pack {{ $orderItem->pack_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="text" id="color" class="form-control" placeholder="Item color" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="size" class="form-label">Size</label>
                        <input type="text" id="size" class="form-control" placeholder="Item size" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="order_quantity" class="form-label">Order Qty</label>
                        <input type="number" id="order_quantity" class="form-control" placeholder="Order quantity" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="ship_quantity" class="form-label">Ship Qty</label>
                        <input type="number" id="ship_quantity" name="ship_quantity" class="form-control" value="{{ old('ship_quantity', $formData['ship_quantity'] ?? '') }}" placeholder="Enter quantity" min="1" step="1" required>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="full_cartons" class="form-label">Full Ctns</label>
                        <input type="number" id="full_cartons" class="form-control" placeholder="Full cartons" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="odd_cartons" class="form-label">Odd Ctns</label>
                        <input type="number" id="odd_cartons" class="form-control" placeholder="Odd cartons" readonly>
                    </div>
                </div>
            </div>
        </section>

        <!-- Weight fields and an HTML expand/collapse editor for carton dimensions. -->
        <section id="packing-details" class="card border-0 shadow-sm rounded-4 bg-white" aria-labelledby="packing-heading">
            <div class="card-body p-4">
                <h3 id="packing-heading" class="h6 section-title mb-4">
                    <i class="bi bi-box2 me-2" aria-hidden="true"></i>Packing Details
                </h3>
                <p class="text-muted small">Total weights include any odd carton. Choose a carton for the selected item's style and size.</p>
                <div class="mb-4">
                    <label for="carton_dimension_id" class="form-label">Carton Dimension</label>
                    <select id="carton_dimension_id" name="carton_dimension_id" class="form-select" required>
                        <option value="">Select a carton dimension</option>
                        @foreach ($cartonDimensions as $carton)
                            <option value="{{ $carton->idtbl_carton_dimension }}"
                                    data-style-id="{{ $carton->prodstyle_list_id }}"
                                    data-size="{{ $carton->tbl_style_size_desc }}"
                                    data-length="{{ $carton->carton_length }}"
                                    data-width="{{ $carton->carton_width }}"
                                    data-height="{{ $carton->carton_height }}"
                                    data-pieces="{{ $carton->pcs_per_carton }}"
                                    data-item-weight="{{ $carton->item_weight }}"
                                    data-carton-weight="{{ $carton->carton_weight }}"
                                    @selected(old('carton_dimension_id', $formData['carton_dimension_id'] ?? '') == $carton->idtbl_carton_dimension)>
                                {{ $carton->styleid }} / {{ $carton->tbl_style_size_desc }}
                                / {{ $carton->carton_length }} × {{ $carton->carton_width }} × {{ $carton->carton_height }} cm
                                / {{ $carton->pcs_per_carton }} pcs
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <label for="total_net_weight" class="form-label">Total Net Weight (kg)</label>
                        <input type="number" id="total_net_weight" name="total_net_weight" class="form-control" value="{{ old('total_net_weight', $formData['total_net_weight'] ?? '') }}" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <label for="total_gross_weight" class="form-label">Total Gross Weight (kg)</label>
                        <input type="number" id="total_gross_weight" name="total_gross_weight" class="form-control" value="{{ old('total_gross_weight', $formData['total_gross_weight'] ?? '') }}" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <label for="odd_net_weight" class="form-label">Odd Net Weight (kg)</label>
                        <input type="number" id="odd_net_weight" name="odd_net_weight" class="form-control" value="{{ old('odd_net_weight', $formData['odd_net_weight'] ?? '') }}" placeholder="0.00" min="0" step="0.01">
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <label for="odd_gross_weight" class="form-label">Odd Gross Weight (kg)</label>
                        <input type="number" id="odd_gross_weight" name="odd_gross_weight" class="form-control" value="{{ old('odd_gross_weight', $formData['odd_gross_weight'] ?? '') }}" placeholder="0.00" min="0" step="0.01">
                    </div>
                </div>

                <details class="packing-editor p-3 mb-4" open>
                    <summary><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Edit Carton Dimensions</summary>
                    <div class="row g-3 mt-1">
                        <div class="col-sm-6 col-xl-3">
                            <label for="carton_length" class="form-label">Length (cm)</label>
                            <input type="number" id="carton_length" name="carton_length" class="form-control" value="{{ old('carton_length', $formData['carton_length'] ?? '') }}" min="0.01" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <label for="carton_width" class="form-label">Width (cm)</label>
                            <input type="number" id="carton_width" name="carton_width" class="form-control" value="{{ old('carton_width', $formData['carton_width'] ?? '') }}" min="0.01" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <label for="carton_height" class="form-label">Height (cm)</label>
                            <input type="number" id="carton_height" name="carton_height" class="form-control" value="{{ old('carton_height', $formData['carton_height'] ?? '') }}" min="0.01" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <label for="pieces_per_carton" class="form-label">Pcs / Ctn</label>
                            <input type="number" id="pieces_per_carton" name="pieces_per_carton" class="form-control" value="{{ old('pieces_per_carton', $formData['pieces_per_carton'] ?? '') }}" min="1" step="1" placeholder="Pieces per carton" required>
                        </div>
                    </div>
                </details>

                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label for="carton_dimensions" class="form-label">L × W × H (cm)</label>
                        <input type="text" id="carton_dimensions" class="form-control" placeholder="Length × Width × Height" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="weight_per_piece" class="form-label">Kg / Pcs</label>
                        <input type="number" id="weight_per_piece" class="form-control" placeholder="Weight per piece" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="weight_per_carton" class="form-label">Kg / Ctn</label>
                        <input type="number" id="weight_per_carton" class="form-control" placeholder="Weight per carton" readonly>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="cbm_per_carton" class="form-label">CBM / Ctn</label>
                        <input type="number" id="cbm_per_carton" class="form-control" placeholder="Volume per carton" readonly>
                    </div>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4 bg-white" aria-labelledby="ship-to-heading">
            <div class="card-body p-4">
                <h3 id="ship-to-heading" class="h6 section-title mb-3">
                    <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Ship-to Address
                </h3>
                <div class="d-flex flex-wrap gap-3 mb-3" role="group" aria-label="Address entry method">
                    <div class="form-check">
                        <input type="radio" id="address_mode_existing" name="address_mode" value="existing"
                               class="form-check-input" @checked(old('address_mode', 'existing') === 'existing')>
                        <label for="address_mode_existing" class="form-check-label">Select saved address</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" id="address_mode_manual" name="address_mode" value="manual"
                               class="form-check-input" @checked(old('address_mode') === 'manual')>
                        <label for="address_mode_manual" class="form-check-label">Enter address manually</label>
                    </div>
                </div>
                <div id="saved-address-fields" @if(old('address_mode') === 'manual') hidden @endif>
                    <label for="delivery_address_id" class="form-label">Ship-to Address</label>
                    <select id="delivery_address_id" name="delivery_address_id" class="form-select mb-3"
                            @required(old('address_mode', 'existing') === 'existing') @disabled(old('address_mode') === 'manual')>
                        <option value="">Select a delivery address</option>
                        @foreach ($deliveryAddresses as $address)
                            <option value="{{ $address->id }}"
                                    data-style-code="{{ $address->item_styleno }}"
                                    data-color-code="{{ $address->item_colorcode }}"
                                    data-address="{{ $address->custdest_addr }}"
                                    @selected(old('delivery_address_id', $formData['delivery_address_id'] ?? '') == $address->id)>
                                {{ $address->custdest_name }} / {{ $address->item_styleno }} / {{ $address->item_colordesc }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="manual-address-name-fields" class="mb-3" @if(old('address_mode') !== 'manual') hidden @endif>
                    <label for="manual_address_name" class="form-label">Address Name (optional)</label>
                    <input type="text" id="manual_address_name" name="manual_address_name" class="form-control"
                           value="{{ old('manual_address_name') }}" maxlength="50" placeholder="Warehouse or delivery location"
                           @disabled(old('address_mode') !== 'manual')>
                </div>
                <label for="ship_to_address" class="form-label">Delivery Address</label>
                <textarea id="ship_to_address" name="manual_address" class="form-control" rows="3" maxlength="200"
                          placeholder="Selected delivery address" aria-describedby="address-help"
                          @readonly(old('address_mode') !== 'manual') @required(old('address_mode') === 'manual')>{{ old('manual_address') }}</textarea>
                <div id="address-help" class="form-text">Select a saved address or enter a new delivery address. Maximum 200 characters.</div>
            </div>
            <div class="card-footer bg-light border-0 rounded-bottom-4 p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="form-check form-switch mb-0">
                    <input type="hidden" name="approve_shipment" value="0">
                    <input type="checkbox" id="approve_shipment" name="approve_shipment" value="1" class="form-check-input" role="switch" @checked(old('approve_shipment', $formData['approve_shipment'] ?? false)) @disabled(!$assignmentId)>
                    <label for="approve_shipment" class="form-check-label fw-bold text-muted">Approve Shipment</label>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span id="save-help" class="text-muted small">Saves shipment information, one order item, packing details and the delivery address.</span>
                    <button type="submit" class="btn bubble-btn px-4" aria-describedby="save-help" @disabled(!$assignmentId || $orderItems->isEmpty())>
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i> {{ $editingDelivery ? 'Update Shipment' : 'Save Shipment' }}
                    </button>
                    @if ($editingDelivery)
                        <a href="{{ route('create-shipment.index', ['history' => 1]) }}" class="btn btn-light border">Cancel</a>
                    @endif
                </div>
            </div>
        </section>
    </form>
</div>

<!-- Keep the history outside the entry form so opening it preserves unsaved work. -->
<div class="modal fade" id="shipment-history-modal" tabindex="-1"
     aria-labelledby="shipment-history-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="shipment-history-title">
                    <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Created Shipment History
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close history"></button>
            </div>
            <div class="modal-body">
                @if (session('success'))
                    <div class="alert alert-success py-2" role="alert">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger py-2" role="alert">{{ session('error') }}</div>
                @endif
                <div class="position-relative mb-3" style="width: 500px; max-width: 100%;">
                    <label for="shipment-history-search" class="visually-hidden">Search shipment number, delivery number, invoice number or date</label>
                    <input type="search" id="shipment-history-search"
                           class="form-control rounded-pill bg-white shadow-sm border-0 pe-5 py-3"
                           placeholder="Search shipments..." aria-controls="shipment-history-content">
                    <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y text-muted me-3"
                       style="pointer-events: none;" aria-hidden="true"></i>
                </div>
                <div id="shipment-history-content" aria-live="polite" aria-busy="false"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="refresh-shipment-history">
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Fetch the saved list only when the history popup is opened or refreshed.
    const historyModal = document.getElementById('shipment-history-modal');
    const historyContent = document.getElementById('shipment-history-content');
    const historyRefresh = document.getElementById('refresh-shipment-history');
    const historySearch = document.getElementById('shipment-history-search');

    // Match the shipment number, delivery number, invoice number or date shown in each summary.
    function filterShipmentHistory() {
        const searchText = historySearch.value.trim().toLowerCase();
        const shipments = historyContent.querySelectorAll('.history-shipment');
        let visibleCount = 0;

        for (const shipment of shipments) {
            const summaryText = shipment.querySelector('summary').textContent.toLowerCase();

            if (summaryText.includes(searchText)) {
                shipment.classList.remove('d-none');
                visibleCount++;
            } else {
                shipment.classList.add('d-none');
            }
        }

        const noResults = document.getElementById('shipment-history-no-results');
        if (noResults) {
            noResults.hidden = visibleCount > 0;
        }
    }

    async function loadShipmentHistory() {
        historyContent.textContent = 'Loading shipment history...';
        historyContent.setAttribute('aria-busy', 'true');
        historyRefresh.disabled = true;
        try {
            const response = await fetch(@json(route('create-shipment.history')), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            if (!response.ok || response.redirected) {
                throw new Error('Unable to load shipment history.');
            }
            historyContent.innerHTML = await response.text();
            filterShipmentHistory();
        } catch (error) {
            historyContent.textContent = 'Unable to load shipment history. Click Refresh to try again.';
        } finally {
            historyContent.setAttribute('aria-busy', 'false');
            historyRefresh.disabled = false;
        }
    }

    historyModal.addEventListener('show.bs.modal', loadShipmentHistory);
    historyRefresh.addEventListener('click', loadShipmentHistory);
    historySearch.addEventListener('input', filterShipmentHistory);
    @if (request('history') === '1')
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(historyModal).show();
        });
    @endif

    const orderSelect = document.getElementById('production_order_id');
    const itemSelect = document.getElementById('production_item_id');
    const cartonSelect = document.getElementById('carton_dimension_id');
    const addressSelect = document.getElementById('delivery_address_id');
    const manualAddressRadio = document.getElementById('address_mode_manual');
    const addressText = document.getElementById('ship_to_address');
    let manualAddressDraft = addressText.value;

    function calculateCartons() {
        const quantity = Number(document.getElementById('ship_quantity').value);
        const pieces = Number(document.getElementById('pieces_per_carton').value);
        if (pieces < 1 || quantity < 1) {
            document.getElementById('full_cartons').value = '';
            document.getElementById('odd_cartons').value = '';
            return;
        }
        document.getElementById('full_cartons').value = Math.floor(quantity / pieces);
        document.getElementById('odd_cartons').value = quantity % pieces > 0 ? 1 : 0;
    }

    function updateCartonPreview() {
        const length = Number(document.getElementById('carton_length').value);
        const width = Number(document.getElementById('carton_width').value);
        const height = Number(document.getElementById('carton_height').value);
        document.getElementById('carton_dimensions').value = length && width && height ? length + ' × ' + width + ' × ' + height : '';
        document.getElementById('cbm_per_carton').value = length && width && height ? (length * width * height / 1000000).toFixed(6) : '';
        calculateCartons();
    }

    function showCartonWeights() {
        const option = cartonSelect.options[cartonSelect.selectedIndex];
        document.getElementById('weight_per_piece').value = option.dataset.itemWeight || '';
        document.getElementById('weight_per_carton').value = option.dataset.cartonWeight || '';
    }

    function fillCartonFields() {
        const option = cartonSelect.options[cartonSelect.selectedIndex];
        document.getElementById('carton_length').value = option.dataset.length || '';
        document.getElementById('carton_width').value = option.dataset.width || '';
        document.getElementById('carton_height').value = option.dataset.height || '';
        document.getElementById('pieces_per_carton').value = option.dataset.pieces || '';
        showCartonWeights();
        updateCartonPreview();
    }

    function showDeliveryAddress() {
        if (manualAddressRadio.checked) {
            return;
        }
        const option = addressSelect.options[addressSelect.selectedIndex];
        addressText.value = option.dataset.address || '';
    }

    function updateAddressMode() {
        const manual = manualAddressRadio.checked;
        document.getElementById('saved-address-fields').hidden = manual;
        document.getElementById('manual-address-name-fields').hidden = !manual;
        document.getElementById('manual_address_name').disabled = !manual;
        addressSelect.disabled = manual;
        addressSelect.required = !manual;
        addressText.readOnly = !manual;
        addressText.required = manual;
        addressText.placeholder = manual ? 'Enter the full delivery address' : 'Selected delivery address';
        if (manual) {
            addressText.value = manualAddressDraft;
        } else {
            showDeliveryAddress();
        }
    }

    function filterPackingOptions() {
        const item = itemSelect.options[itemSelect.selectedIndex];
        for (const option of cartonSelect.options) {
            if (option.value !== '') {
                option.disabled = !item.value || option.dataset.styleId !== item.dataset.styleId || option.dataset.size !== item.dataset.size;
                option.hidden = option.disabled;
            }
        }
        if (cartonSelect.options[cartonSelect.selectedIndex].disabled) {
            cartonSelect.value = '';
            fillCartonFields();
        }

        for (const option of addressSelect.options) {
            if (option.value !== '') {
                option.disabled = !item.value || option.dataset.styleCode !== item.dataset.styleCode || option.dataset.colorCode !== item.dataset.colorCode;
                option.hidden = option.disabled;
            }
        }
        if (addressSelect.options[addressSelect.selectedIndex].disabled) {
            addressSelect.value = '';
        }
        showCartonWeights();
        showDeliveryAddress();
    }

    function showItemDetails() {
        const item = itemSelect.options[itemSelect.selectedIndex];
        document.getElementById('color').value = item.dataset.color || '';
        document.getElementById('size').value = item.dataset.size || '';
        document.getElementById('order_quantity').value = item.dataset.quantity || '';
        filterPackingOptions();
        updateCartonPreview();
    }

    function filterOrderItems() {
        for (const option of itemSelect.options) {
            if (option.value !== '') {
                option.disabled = option.dataset.orderId !== orderSelect.value;
                option.hidden = option.disabled;
            }
        }
        if (itemSelect.options[itemSelect.selectedIndex].disabled) {
            itemSelect.value = '';
        }
        showItemDetails();
    }

    orderSelect.addEventListener('change', filterOrderItems);
    itemSelect.addEventListener('change', showItemDetails);
    cartonSelect.addEventListener('change', fillCartonFields);
    addressSelect.addEventListener('change', showDeliveryAddress);
    document.getElementById('address_mode_existing').addEventListener('change', updateAddressMode);
    manualAddressRadio.addEventListener('change', updateAddressMode);
    addressText.addEventListener('input', function () {
        if (manualAddressRadio.checked) {
            manualAddressDraft = addressText.value;
        }
    });
    document.getElementById('ship_quantity').addEventListener('input', calculateCartons);
    document.getElementById('pieces_per_carton').addEventListener('input', calculateCartons);
    document.getElementById('carton_length').addEventListener('input', updateCartonPreview);
    document.getElementById('carton_width').addEventListener('input', updateCartonPreview);
    document.getElementById('carton_height').addEventListener('input', updateCartonPreview);
    document.getElementById('factory-shipment-form').addEventListener('reset', function () {
        setTimeout(function () {
            manualAddressDraft = addressText.defaultValue;
            updateAddressMode();
            filterOrderItems();
        }, 0);
    });
    updateAddressMode();
    filterOrderItems();
</script>
@endpush
