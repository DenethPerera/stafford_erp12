@php
    $isPoActive = request()->routeIs('purchase-orders.*');
@endphp

<a class="nav-item px-3 py-2 {{ $isPoActive ? 'active' : '' }}"
   data-bs-toggle="collapse"
   href="#{{ $collapseId }}"
   role="button"
   aria-expanded="{{ $isPoActive ? 'true' : 'false' }}"
   aria-controls="{{ $collapseId }}"
   title="Purchase Orders">

    <div class="nav-icon-wrapper me-3"
         style="width: 32px; height: 32px;">
        <i class="bi bi-file-earmark-text-fill fs-5" style="color: #10b981;"></i>
    </div>

    <span class="flex-grow-1 {{ $isMobile ? '' : 'sidebar-text' }}">
        Purchase Orders
    </span>

    <i class="bi bi-chevron-down ms-auto {{ $isMobile ? '' : 'sidebar-text' }}"
       style="font-size: 10px;"></i>
</a>

<div class="collapse {{ $isPoActive ? 'show' : '' }}"
     id="{{ $collapseId }}">
    <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
        <a href="{{ route('purchase-orders.index') }}"
           class="text-decoration-none fw-bold submenu-item {{ (request()->routeIs('purchase-orders.index') || (request()->routeIs('purchase-orders.*') && !request()->routeIs('purchase-orders.customer-po-upload.*') && !request()->routeIs('purchase-orders.sales-contracts.*') && !request()->routeIs('purchase-orders.compare.*'))) ? 'active' : 'text-muted' }}"
           style="font-size: 13px;">
            @if(request()->routeIs('purchase-orders.index') || (request()->routeIs('purchase-orders.*') && !request()->routeIs('purchase-orders.customer-po-upload.*') && !request()->routeIs('purchase-orders.sales-contracts.*') && !request()->routeIs('purchase-orders.compare.*')))
                <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i>
            @endif
            Supplier POs
        </a>
        <a href="{{ route('purchase-orders.customer-po-upload.index') }}"
           class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('purchase-orders.customer-po-upload.*') ? 'active' : 'text-muted' }}"
           style="font-size: 13px;">
            @if(request()->routeIs('purchase-orders.customer-po-upload.*'))
                <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i>
            @endif
            Customer PO Upload
        </a>
        <a href="{{ route('purchase-orders.compare.index') }}"
           class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('purchase-orders.compare.*') ? 'active' : 'text-muted' }}"
           style="font-size: 13px;">Compare</a>
        @role('super_admin')
        <a href="{{ route('purchase-orders.sales-contracts.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('purchase-orders.sales-contracts.*') ? 'active' : 'text-muted' }}" style="font-size:13px;">Sales Contract</a>
        @endrole
    </div>
</div>

