@extends('layouts.app')

@section('header', 'Factory Production Portal')

@section('content')
<div class="d-flex flex-column gap-4">


    <!-- Top Action Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white" 
         style="background: linear-gradient(135deg, #00A39A 0%, #124E78 100%);">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-white text-dark rounded-pill px-3 py-1 mb-2 font-monospace">
                    Factory Portal
                </span>
                <h3 class="fw-bold mb-1">
                    {{ $currentBranch ? $currentBranch->br_name : 'Factory Dashboard' }}
                </h3>
                <p class="mb-0 opacity-90 small">
                    Production orders assigned to your factory unit. View allocated quantities and generate Purchase Orders for suppliers.
                </p>
            </div>

            @if(auth()->user()?->isAdmin() && $allBranches->count() > 1)
                <!-- Admin Branch Switcher Dropdown -->
                <div class="bg-white bg-opacity-10 p-2 rounded-4 backdrop-blur border border-white border-opacity-25">
                    <form method="GET" action="{{ route('factory-portal.index') }}" class="d-flex align-items-center gap-2">
                        <label class="text-white small fw-semibold text-nowrap mb-0">Switch Factory:</label>
                        <select name="branch_id" class="form-select form-select-sm rounded-pill border-0" onchange="this.form.submit()">
                            @foreach($allBranches as $b)
                                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                                    {{ $b->br_name }} ({{ $b->br_code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3">
        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(0, 163, 154, 0.12); color: #00A39A;">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 11px;">Assigned Orders</div>
                        <h4 class="fw-bolder text-dark mb-0">{{ number_format($totalOrdersCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <i class="bi bi-file-earmark-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 11px;">POs Created</div>
                        <h4 class="fw-bolder text-dark mb-0">{{ number_format($posCreatedCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                        <i class="bi bi-stack fs-4"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 11px;">Allocated Units</div>
                        <h4 class="fw-bolder text-dark mb-0">{{ number_format($totalAllocatedPcs) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('factory-portal.index') }}" class="row g-2 align-items-center">
                @if(request('branch_id'))
                    <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                @endif
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control bg-light border-start-0 rounded-end-pill" 
                               placeholder="Search allocated orders by ref or buy number..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-3">
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
                    @if($search || $season)
                        <a href="{{ route('factory-portal.index', ['branch_id' => $branchId]) }}" class="btn btn-light rounded-pill px-3">
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
                        <th class="ps-4 text-uppercase text-muted small fw-bold">Order Ref</th>
                        <th class="text-uppercase text-muted small fw-bold">Order Date</th>
                        <th class="text-uppercase text-muted small fw-bold">Season</th>
                        <th class="text-uppercase text-muted small fw-bold">Dispatch Date</th>
                        <th class="text-end text-uppercase text-muted small fw-bold">Allocated Units</th>
                        <th class="text-center text-uppercase text-muted small fw-bold">PO Status</th>
                        <th class="text-end pe-4 text-uppercase text-muted small fw-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $totalQty = $order->items->sum('prod_qty');
                            $pos = $purchaseOrders->get($order->prodorder_id, collect());
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-monospace">{{ $order->prodorder_ref }}</div>
                                <div class="small text-muted">Buy No: {{ $order->buy_no ?: '-' }}</div>
                            </td>
                            <td class="small">
                                {{ $order->prodorder_date ? \Carbon\Carbon::parse($order->prodorder_date)->format('M d, Y') : '-' }}
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
                                <div class="small text-muted">{{ $order->items->count() }} line items</div>
                            </td>
                            <td class="text-center">
                                @if($pos->count() === 1)
                                    @php $singlePo = $pos->first(); @endphp
                                    <a href="{{ route('purchase-orders.show', $singlePo->id) }}" 
                                       class="badge bg-info-subtle text-info text-decoration-none rounded-pill px-3 py-1 font-monospace"
                                       title="Supplier: {{ $singlePo->supplier?->supname }}">
                                        <i class="bi bi-file-earmark-check me-1"></i> {{ $singlePo->po_number }}
                                    </a>
                                @elseif($pos->count() > 1)
                                    <a href="{{ route('factory-portal.show', ['id' => $order->prodorder_id, 'branch_id' => $branchId]) }}" 
                                       class="badge bg-success-subtle text-success text-decoration-none rounded-pill px-3 py-1 font-monospace"
                                       title="View {{ $pos->count() }} Purchase Orders in order details">
                                        <i class="bi bi-files me-1"></i> {{ $pos->count() }} POs Created
                                    </a>
                                @else
                                    <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
                                        PO Not Created
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('factory-portal.show', ['id' => $order->prodorder_id, 'branch_id' => $branchId]) }}" 
                                   class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i> View Order
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No production orders allocated to your factory.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $orders->appends(['branch_id' => $branchId])->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
