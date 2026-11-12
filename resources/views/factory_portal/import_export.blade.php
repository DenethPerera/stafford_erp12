@extends('layouts.app')

@section('header', 'Import/Export')

@section('content')
<style>
    .import-export .section-title {
        color: #124e78;
        font-weight: 800;
    }

    .import-export .section-icon {
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

    .import-export .form-label {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 700;
    }

    .import-export .form-control,
    .import-export .form-select {
        min-height: 43px;
        border-color: #dce5eb;
        border-radius: 10px;
    }

    .import-export .form-control:focus,
    .import-export .form-select:focus {
        border-color: #00a39a;
        box-shadow: 0 0 0 0.2rem rgba(0, 163, 154, 0.12);
    }

    .import-export .container-row .form-control {
        min-width: 0;
    }
</style>

<div class="import-export d-flex flex-column gap-4">
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-4 d-flex align-items-center gap-3">
            <span class="section-icon"><i class="bi bi-arrow-down-up fs-5" aria-hidden="true"></i></span>
            <div>
                <h2 class="h4 section-title mb-1">Import/Export</h2>
                <p class="text-muted small mb-0">Enter purchase order, container, and shipping details.</p>
            </div>
        </div>
    </div>

    <!-- This first step builds the form. Saving will be connected separately. -->
    <form id="import-export-form">
        <section class="card border-0 shadow-sm rounded-4 bg-white" aria-labelledby="shipping-details-heading">
            <div class="card-body p-4">
                <h3 id="shipping-details-heading" class="h6 section-title mb-4">
                    <i class="bi bi-box-seam me-2" aria-hidden="true"></i>Shipping Information
                </h3>

                <!-- Bootstrap shows two columns on desktop and one on mobile. -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="purchase_order_id" class="form-label">PO Number <span class="text-danger">*</span></label>
                        <select id="purchase_order_id" name="purchase_order_id" class="form-select" required aria-describedby="purchase-order-help">
                            <option value="">Select a purchase order</option>
                            @foreach ($purchaseOrders as $poId => $poNumber)
                                <option value="{{ $poId }}">{{ $poNumber }}</option>
                            @endforeach
                        </select>
                        <div id="purchase-order-help" class="form-text">
                            @if ($purchaseOrders->isEmpty())
                                No purchase orders assigned to your factory yet.
                            @else
                                Purchase orders assigned to your factory.
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="vessel_name" class="form-label">VSL Name</label>
                        <input type="text" id="vessel_name" name="vessel_name" class="form-control" placeholder="Enter vessel name">
                    </div>

                    <div class="col-md-6">
                        <label for="hbl_number" class="form-label">HBL #</label>
                        <input type="text" id="hbl_number" name="hbl_number" class="form-control" placeholder="Enter house bill of lading number">
                    </div>

                    <fieldset class="col-md-6">
                        <legend class="float-none w-auto form-label">Container Numbers</legend>
                        <div id="container-rows" class="d-flex flex-column gap-2">
                            <div class="container-row d-flex align-items-center gap-2">
                                <input type="text" name="container_numbers[]" class="form-control" placeholder="Enter container number" aria-label="Container number">
                                <button type="button" class="remove-container btn btn-outline-danger rounded-3 flex-shrink-0" aria-label="Remove container number" title="Remove container number">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" id="add-container" class="bubble-btn mt-2">
                            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add Container
                        </button>
                    </fieldset>

                    <div class="col-md-6">
                        <label for="container_type" class="form-label">Container Type</label>
                        <input type="text" id="container_type" name="container_type" class="form-control" placeholder="Enter container type">
                    </div>

                    <div class="col-md-6">
                        <label for="shipping_documents" class="form-label">Shipping Docs</label>
                        <input type="file" id="shipping_documents" name="shipping_documents[]" class="form-control" multiple aria-describedby="shipping-documents-help">
                        <div id="shipping-documents-help" class="form-text">You can select more than one document.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="etd" class="form-label">ETD</label>
                        <input type="date" id="etd" name="etd" class="form-control" aria-describedby="etd-help">
                        <div id="etd-help" class="form-text">Estimated departure date</div>
                    </div>

                    <div class="col-md-6">
                        <label for="loading_port" class="form-label">Loading Port</label>
                        <input type="text" id="loading_port" name="loading_port" class="form-control" placeholder="Enter loading port">
                    </div>

                    <div class="col-md-6">
                        <label for="place_of_delivery" class="form-label">Place of Delivery</label>
                        <input type="text" id="place_of_delivery" name="place_of_delivery" class="form-control" placeholder="Enter delivery location">
                    </div>

                    <div class="col-md-6">
                        <label for="eta" class="form-label">ETA</label>
                        <input type="date" id="eta" name="eta" class="form-control" aria-describedby="eta-help">
                        <div id="eta-help" class="form-text">Estimated arrival date</div>
                    </div>

                    <div class="col-md-6">
                        <label for="discharge_port" class="form-label">Discharge Port</label>
                        <input type="text" id="discharge_port" name="discharge_port" class="form-control" placeholder="Enter discharge port">
                    </div>

                    <div class="col-md-6">
                        <label for="shipping_mode" class="form-label">Shipping Mode</label>
                        <select id="shipping_mode" name="shipping_mode" class="form-select">
                            <option value="">Select shipping mode</option>
                            <option value="sea">Sea</option>
                            <option value="air">Air</option>
                            <option value="road">Road</option>
                            <option value="courier">Courier</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Enter any additional details"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="delivery_status" class="form-label">Delivery Status</label>
                        <select id="delivery_status" name="delivery_status" class="form-select">
                            <option value="">Select delivery status</option>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="invoice_number" class="form-label">Invoice #</label>
                        <input type="text" id="invoice_number" name="invoice_number" class="form-control" placeholder="Enter invoice number">
                    </div>
                </div>

                <div class="border-top mt-4 pt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <p class="text-muted small mb-0">Form preview: details and documents are not saved yet.</p>
                    <button type="reset" class="btn btn-light border rounded-pill px-4">
                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Clear Form
                    </button>
                </div>
            </div>
        </section>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('import-export-form');
        const containerRows = document.getElementById('container-rows');
        const addContainerButton = document.getElementById('add-container');

        // Keep one empty row when the last container is removed.
        function connectRemoveButton(row) {
            row.querySelector('.remove-container').addEventListener('click', function () {
                if (containerRows.children.length > 1) {
                    row.remove();
                    addContainerButton.focus();
                } else {
                    row.querySelector('input').value = '';
                    row.querySelector('input').focus();
                }
            });
        }

        connectRemoveButton(containerRows.firstElementChild);

        // Copy an empty row and connect its remove button.
        addContainerButton.addEventListener('click', function () {
            const newRow = containerRows.firstElementChild.cloneNode(true);
            newRow.querySelector('input').value = '';
            connectRemoveButton(newRow);
            containerRows.appendChild(newRow);
            newRow.querySelector('input').focus();
        });

        form.addEventListener('reset', function () {
            while (containerRows.children.length > 1) {
                containerRows.lastElementChild.remove();
            }
        });

        // Prevent Enter from submitting before a save endpoint is added.
        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });
    });
</script>
@endpush
