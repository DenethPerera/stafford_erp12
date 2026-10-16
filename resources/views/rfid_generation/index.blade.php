@extends('layouts.app')

@section('header', 'RFID Generation')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- Header & Action Toolbar -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h2 class="h5 fw-bolder text-dark mb-1">RFID Generation & Management</h2>
                    <p class="small text-muted fw-semibold mb-0">View auto-generated RFID identifiers linked to Purchase Order line items and export data to Excel.</p>
                </div>

                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100 w-md-auto">
                    <!-- Search Form -->
                    <form method="GET" action="{{ route('rfid-generation.index') }}" class="d-flex align-items-center gap-2 flex-grow-1 flex-sm-grow-0">
                        <div class="position-relative w-100" style="min-width: 260px;">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   class="form-control rounded-pill bg-light border-light fw-semibold ps-4 pe-5 py-2 shadow-none" 
                                   placeholder="Search by PO Number...">
                            
                            @if(request('search'))
                                <a href="{{ route('rfid-generation.index') }}" 
                                   class="btn btn-sm btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-4 p-0 text-decoration-none" 
                                   title="Clear search">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            @endif

                            <button type="submit" 
                                    class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-2 p-0 text-decoration-none" 
                                    title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Excel Export Button -->
                    <a href="{{ route('rfid-generation.export', request()->only('search')) }}" 
                       class="btn btn-success fw-bold rounded-pill shadow-sm d-flex align-items-center justify-content-center px-4 py-2 text-nowrap"
                       style="background: linear-gradient(135deg, #10b981, #059669); border: none;">
                        <i class="bi bi-file-earmark-excel-fill me-2 fs-5"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- RFID List Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bubble-card">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bolder text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-upc-scan text-primary fs-5"></i>
                    <span>RFID Items List</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 12px;">
                        {{ $items->total() }} Record(s)
                    </span>
                </h5>
            </div>
            @if(request('search'))
                <div>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                        Filtered by PO: "{{ request('search') }}"
                    </span>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">
                        <th class="py-3 px-4">RFID</th>
                        <th class="py-3 px-3">PO Number</th>
                        <th class="py-3 px-3">Style</th>
                        <th class="py-3 px-3">Size</th>
                        <th class="py-3 px-4">Color</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <!-- RFID Column -->
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark text-white rounded-pill px-3 py-2 font-monospace fw-bolder shadow-sm tracking-wider" style="font-size: 12px; letter-spacing: 0.5px;">
                                        <i class="bi bi-upc-scan me-1 text-info"></i> {{ $item->id }}
                                    </span>
                                </div>
                            </td>

                            <!-- PO Number Column -->
                            <td class="py-3 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-file-earmark-text-fill"></i>
                                    </div>
                                    <span class="fw-bolder text-dark fs-6">{{ $item->purchaseOrder?->pdf_po_refno ?: '-' }}</span>
                                </div>
                            </td>

                            <!-- Style Column -->
                            <td class="py-3 px-3">
                                <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold" style="font-size: 12px;">
                                    {{ $item->item_styleno ?: '-' }}
                                </span>
                            </td>

                            <!-- Size Column -->
                            <td class="py-3 px-3">
                                <span class="fw-bold text-dark small">
                                    {{ $item->size_code ?: '-' }}
                                </span>
                            </td>

                            <!-- Color Column -->
                            <td class="py-3 px-4">
                                <span class="fw-semibold text-secondary small">
                                    {{ $item->item_colordesc ?: '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-upc-scan display-4 text-muted opacity-50 d-block mb-3"></i>
                                <div class="fw-bold fs-6">No RFID Records Found</div>
                                <div class="small">
                                    @if(request('search'))
                                        No PO items match the PO Number "{{ request('search') }}". <a href="{{ route('rfid-generation.index') }}" class="fw-bold text-primary">View all records</a>
                                    @else
                                        There are currently no PO items available in the database.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($items->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
                <div class="text-muted small fw-semibold">
                    Showing <span class="fw-bold text-dark">{{ $items->firstItem() ?? 0 }}</span> to <span class="fw-bold text-dark">{{ $items->lastItem() ?? 0 }}</span> of <span class="fw-bold text-dark">{{ $items->total() }}</span> items
                </div>
                <div>
                    {{ $items->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
