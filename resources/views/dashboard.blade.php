@extends('layouts.app')

@section('header', 'Dashboard')

@section('content')
@role('super_admin')
<!-- Stats Grid -->
<div class="row g-3 mb-3">
    <!-- Total Factories -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bubble-card hover-shadow-bubble transition-all">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-2 transition-transform hover-scale-110" style="width: 42px; height: 42px;">
                    <i class="bi bi-buildings-fill fs-5"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Total Factories</p>
                <h3 class="display-6 fw-bolder text-dark mb-0">{{ $totalFactories }}</h3>
            </div>
        </div>
    </div>

    <!-- Total Certificates -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bubble-card hover-shadow-bubble transition-all">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center mb-2 transition-transform hover-scale-110" style="width: 42px; height: 42px;">
                    <i class="bi bi-award-fill fs-5"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Total Certificates</p>
                <h3 class="display-6 fw-bolder text-dark mb-0">{{ $totalCertificates }}</h3>
            </div>
        </div>
    </div>

    <!-- Expiring Soon -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bubble-card hover-shadow-bubble transition-all position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 bg-warning bg-opacity-25 rounded-circle" style="width: 80px; height: 80px; margin-right: -20px; margin-top: -20px; filter: blur(15px);"></div>
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center position-relative z-1">
                <div class="position-relative d-inline-block">
                    <div class="bg-warning bg-opacity-25 text-warning rounded-circle d-flex align-items-center justify-content-center mb-2 transition-transform hover-scale-110" style="width: 42px; height: 42px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <span class="position-absolute top-0 end-0 p-1 bg-warning border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </div>
                <p class="text-uppercase fw-bolder text-warning mb-1" style="font-size: 10px; letter-spacing: 1px;">Expiring Soon</p>
                <h3 class="display-6 fw-bolder text-dark mb-0">{{ $expiringSoon->count() }}</h3>
            </div>
        </div>
    </div>

    <!-- Renewals In Progress -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bubble-card hover-shadow-bubble transition-all">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-2 transition-transform hover-scale-110" style="width: 42px; height: 42px;">
                    <i class="bi bi-arrow-repeat fs-4"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Renewals Active</p>
                <h3 class="display-6 fw-bolder text-dark mb-0">{{ $renewalsInProgress }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Renewal Status -->
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bolder text-dark mb-0">Renewal Status</h6>
    </div>

    <div class="row g-3 pb-1 flex-nowrap overflow-auto" style="scrollbar-width: none;">
        @forelse($activeRenewals as $renewal)
            <div class="col-12 col-md-6 col-xl-4 flex-shrink-0" style="min-width: 320px;">
                <div class="card border-0 shadow-sm rounded-4 h-100 bubble-card hover-shadow-bubble transition-all">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bolder text-dark mb-1" style="font-size: 14px;">{{ $renewal->id }}</h6>
                                <p class="small fw-semibold text-muted mb-0 d-flex align-items-center" style="font-size: 11px;">
                                    <i class="bi bi-building me-1 text-secondary"></i> <span>{{ $renewal->factory }}</span>
                                </p>
                            </div>
                            @if(!empty($renewal->certificateId))
                                <a href="{{ route('certificates.show', $renewal->certificateId) }}"
                                   class="btn btn-light rounded-circle p-1 shadow-sm text-secondary hover-bg-light transition-colors d-flex align-items-center justify-content-center"
                                   style="width: 32px; height: 32px;"
                                   title="Open certificate details">
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 12px;"></i>
                                </a>
                            @else
                                <div class="btn btn-light rounded-circle p-1 shadow-sm text-muted disabled d-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px;"
                                     title="Certificate details unavailable">
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 12px;"></i>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Progress Section -->
                        <div>
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <span class="fw-bolder text-secondary text-uppercase" style="font-size: 9px; letter-spacing: 1px;">{{ $renewal->currentStage }}</span>
                                <span class="fw-bolder {{ $renewal->progress == 100 ? 'text-success' : 'text-info' }}" style="font-size: 11px;">{{ $renewal->progress }}%</span>
                            </div>
                            
                            <!-- Stepped Bar -->
                            <div class="position-relative pt-1 pb-1">
                                <!-- Background line -->
                                <div class="position-absolute top-50 start-0 w-100 bg-light rounded-pill translate-middle-y border" style="height: 5px;"></div>
                                
                                <!-- Filled line -->
                                <div class="position-absolute top-50 start-0 rounded-pill translate-middle-y shadow-sm transition-all" 
                                     style="height: 5px; width: {{ $renewal->progress }}%; background-color: var(--bs-info); transition-duration: 1s;">
                                </div>
                                
                                <!-- Nodes/Dots -->
                                <div class="position-relative d-flex justify-content-between z-1 px-1">
                                    @foreach($renewal->stageLabels as $index => $label)
                                        @php
                                            $nodeProgress = ($index / (count($renewal->stageLabels) - 1)) * 100;
                                            $isCompleted = $renewal->progress >= $nodeProgress;
                                        @endphp
                                        <div class="rounded-circle border border-2 transition-all {{ $isCompleted ? 'bg-white border-white shadow' : 'bg-light border-white shadow-sm' }}" 
                                             style="width: 10px; height: 10px; transition-duration: 0.5s;">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Step Labels -->
                            <div class="position-relative mt-1 w-100" style="height: 12px;">
                                @foreach($renewal->stageLabels as $index => $label)
                                    @php
                                        $percentage = ($index / (count($renewal->stageLabels) - 1)) * 100;
                                        $alignClass = '';
                                        $style = "";
                                        
                                        if ($index == 0) {
                                            $alignClass = 'text-start';
                                            $style = "left: 0;";
                                        } elseif ($index == count($renewal->stageLabels) - 1) {
                                            $alignClass = 'text-end';
                                            $style = "right: 0;";
                                        } else {
                                            $alignClass = 'text-center';
                                            $style = "left: {$percentage}%; transform: translateX(-50%);";
                                        }
                                    @endphp
                                    <div class="position-absolute top-0 {{ $alignClass }}" style="width: 70px; {{ $style }}">
                                        <span class="text-muted fw-bolder text-uppercase d-block text-truncate" style="font-size: 8px; letter-spacing: 0.5px;" title="{{ $label }}">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 text-center text-muted fw-bold small">
                        No renewals currently in progress.
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Alerts Table -->
<div class="mb-2 d-flex align-items-center justify-content-between">
    <h6 class="fw-bolder text-dark mb-0">Action Required</h6>
</div>

<div class="d-flex flex-column gap-2 mb-2">
    @forelse($alerts as $alert)
        <div class="card border-0 shadow-sm rounded-4 hover-shadow-bubble transition-all">
            <div class="card-body p-2 p-md-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                
                <!-- Certificate -->
                <div class="d-flex align-items-center gap-2 col-md-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $alert->status == 'expired' ? 'bg-danger bg-opacity-10 text-danger' : 'bg-warning bg-opacity-10 text-warning' }}" style="width: 36px; height: 36px;">
                        <i class="bi bi-award-fill fs-6"></i>
                    </div>
                    <div>
                        <p class="fw-bolder text-dark mb-0" style="font-size: 12px;">{{ $alert->certificate_number ?? 'N/A' }} ({{ $alert->standard->name ?? '' }})</p>
                        <p class="fw-semibold text-muted mb-0" style="font-size: 10px;">Certificate</p>
                    </div>
                </div>
                
                <!-- Factory (Desktop only) -->
                <div class="d-none d-md-block col-md-3">
                    <p class="fw-semibold text-dark mb-0" style="font-size: 12px;">{{ $alert->factory->name ?? '' }}</p>
                    <p class="fw-semibold text-muted mb-0" style="font-size: 10px;">Factory</p>
                </div>
                
                <!-- Bottom row (Expiry Date + Actions) -->
                <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-2 col-md-4">
                    <div class="flex-shrink-0 me-md-2">
                        <p class="fw-semibold text-dark mb-0" style="font-size: 12px;">{{ $alert->expiry_date->format('Y-m-d') }}</p>
                        <p class="fw-semibold text-muted mb-0" style="font-size: 10px;">Expiry Date</p>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="badge rounded-pill shadow-sm px-2 py-1 fw-bolder text-uppercase {{ $alert->status == 'expired' ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 9px; letter-spacing: 0.5px;">
                            {{ strtoupper($alert->status) }}
                        </span>
                        <a href="{{ route('certificates.show', $alert->id) }}" class="btn btn-light rounded-circle p-1 shadow-sm text-info hover-bg-light transition-colors d-flex align-items-center justify-content-center border" style="width: 32px; height: 32px;">
                            <i class="bi bi-eye-fill" style="font-size: 12px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center text-muted fw-bold small">
                No action required at this time.
            </div>
        </div>
    @endforelse
</div>
@elserole('lab')
    <!-- LAB ROLE DASHBOARD -->
<div class="d-flex flex-column gap-4">
    <div class="card border-0 shadow-sm rounded-4 bubble-card overflow-hidden">
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0 shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg, #124E78, #00A39A);">
                    <i class="bi bi-flask-fill fs-4"></i>
                </div>
                <div>
                    <h4 class="h5 fw-bolder text-dark mb-1">{{ $lab->name ?? 'Laboratory Portal' }}</h4>
                    <p class="text-muted small fw-semibold mb-0">Testing & Compliance Operations Portal.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('lab.pos.index') }}" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-card-list me-1"></i> All Details
                </a>
                <a href="{{ route('lab.tasks.completed') }}" class="btn btn-light btn-sm rounded-pill px-4 fw-bold shadow-sm border">
                    <i class="bi bi-check2-circle me-1"></i> Submitted Reports
                </a>
            </div>
        </div>
    </div>
</div>
@elserole('factory')
    <!-- FACTORY ROLE DASHBOARD -->
<div class="d-flex flex-column gap-4">
    <div class="card border-0 shadow-sm rounded-4 bubble-card overflow-hidden text-white" style="background: linear-gradient(135deg, #00A39A, #124E78);">
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-25 flex-shrink-0 shadow-sm" style="width: 52px; height: 52px;">
                    <i class="bi bi-building fs-4"></i>
                </div>
                <div>
                    <h4 class="h5 fw-bolder text-white mb-1">{{ auth()->user()->branch?->br_name ?? 'Factory Portal' }}</h4>
                    <p class="text-white text-opacity-75 small fw-semibold mb-0">Production orders and purchase order generation.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('factory-portal.index') }}" class="btn btn-light btn-sm rounded-pill px-4 fw-bold shadow-sm text-dark">
                    <i class="bi bi-box-seam me-1"></i> My Allocations
                </a>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-file-earmark-text me-1"></i> Purchase Orders
                </a>
            </div>
        </div>
    </div>
</div>
@endrole
@endsection

@section('scripts')
<style>
    .hover-scale-110:hover { transform: scale(1.1); }
    .transition-transform { transition: transform 0.2s ease-in-out; }
    .transition-all { transition: all 0.3s ease-in-out; }
    .hover-shadow-bubble:hover { box-shadow: 0 10px 25px rgba(0,0,0,0.05) !important; transform: translateY(-2px); }
</style>
@endsection
