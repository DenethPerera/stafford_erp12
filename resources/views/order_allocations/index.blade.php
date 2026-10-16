@extends('layouts.app')

@section('header', 'Production Order Allocations')

@section('content')
<div class="d-flex flex-column gap-4">

    <!-- Flash message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Top Action Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white" 
         style="background: linear-gradient(135deg, #00A39A 0%, #124E78 100%);">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h3 class="fw-bold mb-1">Production Order Allocations</h3>
                <p class="mb-0 opacity-90 small">
                    Orders allocated across factories in <code>prodorder_list</code>. Monitor quantities, factory allocation, and issued purchase orders.
                </p>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('order-allocations.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control bg-light border-start-0 rounded-end-pill" 
                               placeholder="Search order ref, buy no, or ID..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="branch_id" class="form-select bg-light rounded-pill">
                        <option value="">All Factories / Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                                {{ $b->br_name }} ({{ $b->br_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="season" class="form-select bg-light rounded-pill">
                        <option value="">All Seasons</option>
                        @foreach($seasons as $s)
                            <option value="{{ $s }}" {{ $season == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-teal text-white w-100 rounded-pill px-3" style="background-color: #00A39A;">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    @if($search || $branchId || $season)
                        <a href="{{ route('order-allocations.index') }}" class="btn btn-light rounded-pill px-3">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase text-muted small fw-bold">Order ID / Ref</th>
                        <th class="text-uppercase text-muted small fw-bold">Allocated Factory</th>
                        <th class="text-uppercase text-muted small fw-bold">Season</th>
                        <th class="text-uppercase text-muted small fw-bold">Delivery Date</th>
                        <th class="text-end text-uppercase text-muted small fw-bold">Allocated Qty</th>
                        <th class="text-center text-uppercase text-muted small fw-bold pe-4">Purchase Order</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $totalQty = $order->items->sum('prod_qty');
                            $poNumber = $createdPos->get($order->prodorder_id);
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-monospace">{{ $order->prodorder_ref }}</div>
                                <div class="small text-muted">ID: {{ $order->prodorder_id }} | Buy: {{ $order->buy_no ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $order->branch?->br_name ?? 'Branch ' . $order->mbranchlist_id }}</div>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 11px;">
                                    {{ $order->branch?->br_code ?: 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                    {{ $order->prod_order_season ?: '-' }}
                                </span>
                            </td>
                            <td class="small">
                                {{ $order->proddispatch_date ? \Carbon\Carbon::parse($order->proddispatch_date)->format('M d, Y') : '-' }}
                            </td>
                            <td class="text-end">
                                <span class="fw-bold font-monospace text-primary fs-6">{{ number_format($totalQty) }}</span>
                                <div class="small text-muted">{{ $order->items->count() }} items</div>
                            </td>
                            <td class="text-center pe-4">
                                @if($poNumber)
                                    <span class="badge bg-info-subtle text-info font-monospace rounded-pill px-3 py-1">
                                        <i class="bi bi-file-earmark-check me-1"></i> {{ $poNumber }}
                                    </span>
                                @else
                                    <span class="text-muted small">Not Issued</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                                No allocated production orders found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
