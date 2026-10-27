@php
    $isShipmentActive = request()->routeIs('shipment-management.*');

    $shipmentLinks = [
        'shipment-management.create' => 'Create Shipment',
        'shipment-management.scan' => 'Shipment Scan',
        'shipment-management.forwarder-login' => 'Forwarder Login',
    ];
@endphp

<a class="nav-item px-3 py-2 {{ $isShipmentActive ? 'active' : '' }}"
   data-bs-toggle="collapse"
   href="#{{ $collapseId }}"
   role="button"
   aria-expanded="{{ $isShipmentActive ? 'true' : 'false' }}"
   aria-controls="{{ $collapseId }}"
   title="Shipment Management">

    <div class="nav-icon-wrapper me-3"
         style="width: 32px; height: 32px;">
        <i class="bi bi-box-seam fs-5 text-primary"></i>
    </div>

    <span class="flex-grow-1 {{ $isMobile ? '' : 'sidebar-text' }}">
        Shipment Management
    </span>

    <i class="bi bi-chevron-down ms-auto {{ $isMobile ? '' : 'sidebar-text' }}"
       style="font-size: 10px;"></i>
</a>

<div class="collapse {{ $isShipmentActive ? 'show' : '' }}"
     id="{{ $collapseId }}">
    <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
        @foreach($shipmentLinks as $routeName => $label)
            <a href="{{ route($routeName) }}"
               class="text-decoration-none fw-bold submenu-item {{ request()->routeIs($routeName) ? 'active' : 'text-muted' }}"
               style="font-size: 13px;">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
