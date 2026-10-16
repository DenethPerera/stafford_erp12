@extends('layouts.app')

@section('header', 'Certificate Details')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 bg-white bg-opacity-50 p-4 rounded-4 border shadow-sm" style="backdrop-filter: blur(10px);">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-success border border-white border-2 shadow-sm" style="width: 56px; height: 56px; background-color: rgba(0, 163, 154, 0.1);">
            <i class="bi bi-patch-check-fill fs-3"></i>
        </div>
        <div>
            <h2 class="h5 fw-bolder text-dark mb-1">Certificate Details</h2>
            <p class="small fw-bold text-muted mb-0">{{ $certificate->standard->name }} - #{{ $certificate->certificate_number ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('factories.show', $certificate->factory_id) }}" class="btn btn-light rounded-pill border fw-bold px-4 shadow-sm">
            Back to Factory
        </a>
        <a href="{{ route('certificates.edit', $certificate) }}" class="btn btn-primary rounded-pill fw-bold px-4 shadow-sm d-flex align-items-center">
            <i class="bi bi-pencil me-2"></i> Edit
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 p-4">
    <div class="row g-4">
        <div class="col-md-6 d-flex flex-column gap-4">
            <div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Factory</p>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-buildings-fill text-muted"></i>
                    <p class="h6 fw-bolder text-dark mb-0">{{ $certificate->factory->name }}</p>
                </div>
            </div>
            <div>
                <p class="text-uppercase fw-bolder text-muted mb-2" style="font-size: 10px; letter-spacing: 1px;">Status</p>
                @php
                    $statusColor = match($certificate->status) {
                        'expired' => 'bg-danger bg-opacity-10 text-danger border-danger',
                        'valid' => 'bg-success bg-opacity-10 text-success border-success',
                        'expiring_soon' => 'bg-warning bg-opacity-25 text-dark border-warning',
                        'under_renewal' => 'bg-primary bg-opacity-10 text-primary border-primary',
                        default => 'bg-light text-secondary border-secondary',
                    };
                @endphp
                <span class="badge rounded-pill px-3 py-2 text-uppercase fw-bolder border border-opacity-25 {{ $statusColor }}">
                    {{ str_replace('_', ' ', $certificate->status) }}
                </span>
            </div>
        </div>
        
        <div class="col-md-6 d-flex flex-column gap-4">
            <div>
                <p class="text-uppercase fw-bolder text-muted mb-2" style="font-size: 10px; letter-spacing: 1px;">Timeline</p>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-semibold text-muted">Issued</span>
                    <span class="fw-bolder text-dark">{{ $certificate->issue_date->format('Y-m-d') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-muted">Expires</span>
                    <span class="fw-bolder text-dark">{{ $certificate->expiry_date->format('Y-m-d') }}</span>
                </div>
            </div>
            @if($certificate->document_path)
            <div>
                <p class="text-uppercase fw-bolder text-muted mb-2" style="font-size: 10px; letter-spacing: 1px;">Document</p>
                <button type="button" class="btn btn-light rounded-3 text-primary fw-bold d-inline-flex align-items-center border shadow-sm" data-bs-toggle="modal" data-bs-target="#documentModal">
                    <i class="bi bi-file-earmark-pdf-fill fs-5 me-2"></i> View File
                </button>

                <!-- Document Modal (Bootstrap Native) -->
                <div class="modal fade" id="documentModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered h-100 mt-0" style="max-height: 90vh;">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden h-100">
                            <div class="modal-header border-bottom-0 bg-light">
                                <h5 class="modal-title fw-bolder text-dark d-flex align-items-center">
                                    <i class="bi bi-file-earmark-pdf-fill text-primary me-2"></i> View Document
                                </h5>
                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-2 bg-light">
                                <iframe src="{{ asset('storage/' . $certificate->document_path) }}" class="w-100 h-100 rounded-3 border bg-white" title="Document Viewer" style="min-height: 60vh;"></iframe>
                            </div>
                            <div class="modal-footer bg-white border-top-0 pt-0 mt-2">
                                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    @if($certificate->notes)
    <div class="mt-4 pt-4 border-top">
        <p class="text-uppercase fw-bolder text-muted mb-2" style="font-size: 10px; letter-spacing: 1px;">Notes</p>
        <div class="bg-light p-3 rounded-4 border">
            <p class="mb-0 fw-semibold text-secondary" style="font-size: 14px;">{{ $certificate->notes }}</p>
        </div>
    </div>
    @endif
</div>

<div class="d-flex align-items-center justify-content-between mb-3 mt-5">
    <h3 class="h5 fw-bolder text-dark mb-0">Renewal History</h3>
    @if(in_array($certificate->status, ['expiring_soon', 'expired']))
        <div>
            <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#renewalModal">
                Start Renewal
            </button>
            
            <!-- Renewal Modal (Bootstrap Native) -->
            <div class="modal fade" id="renewalModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title fw-bolder text-dark">Confirm Renewal</h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4">
                            <p class="fw-semibold text-secondary mb-0">Are you sure you want to start a new renewal process for this certificate?</p>
                        </div>
                        <div class="modal-footer border-top-0 d-flex justify-content-end pt-0 pb-4">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border" data-bs-dismiss="modal">Cancel</button>
                            <form action="{{ route('renewals.store', $certificate) }}" method="POST" class="d-inline m-0">
                                @csrf
                                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Start Renewal</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="d-flex flex-column gap-4">
    @forelse($certificate->renewalProcesses()->orderBy('created_at', 'desc')->get() as $process)
        @php
            $processColor = match($process->status) {
                'completed' => 'success',
                'rejected' => 'danger',
                default => 'primary',
            };
        @endphp
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-top border-4 border-{{ $processColor }}">
            <div class="card-header bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $processColor }} bg-opacity-10 text-{{ $processColor }}" style="width: 48px; height: 48px;">
                        <i class="bi bi-arrow-repeat fs-4"></i>
                    </div>
                    <div>
                        <span class="d-block h6 fw-bolder text-dark mb-1">Renewal Process Started</span>
                        <span class="d-block small fw-bold text-muted">{{ $process->started_at->format('M d, Y') }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-4">
                    @if($process->completed_at)
                        <div class="text-end">
                            <span class="d-block text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px;">Completed</span>
                            <span class="d-block small fw-bolder text-dark">{{ $process->completed_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    <span class="badge rounded-pill px-3 py-2 text-uppercase fw-bolder bg-{{ $processColor }} bg-opacity-10 text-{{ $processColor }} border border-{{ $processColor }} border-opacity-25">
                        {{ strtoupper($process->status) }}
                    </span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="py-3 px-4 text-muted text-uppercase fw-bolder border-bottom-0" style="font-size: 11px; letter-spacing: 1px;">Stage</th>
                            <th class="py-3 px-4 text-muted text-uppercase fw-bolder border-bottom-0" style="font-size: 11px; letter-spacing: 1px;">Status</th>
                            <th class="py-3 px-4 text-muted text-uppercase fw-bolder border-bottom-0" style="font-size: 11px; letter-spacing: 1px;">Auditor</th>
                            <th class="py-3 px-4 text-muted text-uppercase fw-bolder border-bottom-0" style="font-size: 11px; letter-spacing: 1px;">Completed</th>
                            <th class="py-3 px-4 text-muted text-uppercase fw-bolder border-bottom-0 text-center" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @foreach($process->auditStages as $stage)
                            <tr>
                                <td class="py-3 px-4 fw-bold text-dark" style="font-size: 14px;">
                                    {{ ucwords(str_replace('_', ' ', $stage->standardStage->name ?? 'Unknown')) }}
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $resultColor = match($stage->result) {
                                            'passed' => 'text-success bg-success border-success',
                                            'pending' => 'text-warning bg-warning border-warning',
                                            default => 'text-danger bg-danger border-danger',
                                        };
                                    @endphp
                                    <span class="badge rounded-pill px-3 py-1 fw-bolder text-uppercase bg-opacity-10 border border-opacity-25 {{ $resultColor }}" style="font-size: 10px; letter-spacing: 0.5px;">
                                        {{ str_replace('_', ' ', strtoupper($stage->result)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 fw-semibold text-secondary small">
                                    {{ $stage->auditor_name ?? '-' }}
                                </td>
                                <td class="py-3 px-4 fw-semibold text-secondary small">
                                    {{ $stage->completed_date ? $stage->completed_date->format('M d, Y') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if(in_array($stage->result, ['pending', 'needs_corrective_action']) && !in_array($process->status, ['completed', 'rejected']) && $process->current_standard_stage_id == $stage->standard_stage_id)
                                        <form action="{{ route('renewals.update_stage', $process) }}" method="POST" class="d-flex justify-content-center"
                                              x-data="{
                                                  dropOpen: false,
                                                  selected: '{{ $stage->result == 'needs_corrective_action' ? 'needs_corrective_action' : 'passed' }}',
                                                  triggerRect: {},
                                                  options: [
                                                      { value: 'passed',                   label: 'Pass',      color: 'text-success', bg: 'bg-success border-success',  dot: 'bg-success' },
                                                      { value: 'failed',                   label: 'Fail',      color: 'text-danger',  bg: 'bg-danger border-danger',   dot: 'bg-danger'    },
                                                      { value: 'needs_corrective_action',  label: 'Needs Fix', color: 'text-warning', bg: 'bg-warning border-warning',  dot: 'bg-warning'   }
                                                  ],
                                                  get current() { return this.options.find(o => o.value === this.selected) || this.options[0]; },
                                                  openDrop() {
                                                      this.triggerRect = this.$refs.dropTrigger.getBoundingClientRect();
                                                      this.dropOpen = !this.dropOpen;
                                                  }
                                              }"
                                              @click.away="dropOpen = false">
                                            @csrf
                                            <input type="hidden" name="audit_stage_id" value="{{ $stage->id }}">
                                            <input type="hidden" name="result" :value="selected">

                                            <div class="d-flex flex-column flex-sm-row align-items-center gap-2">
                                                {{-- Auditor Name Input --}}
                                                <input type="text" name="auditor_name"
                                                       class="form-control rounded-pill px-3 py-1 shadow-sm border"
                                                       style="width: 120px; font-size: 13px;"
                                                       placeholder="Auditor"
                                                       value="{{ $stage->auditor_name }}"
                                                       required>

                                                {{-- Custom Dropdown --}}
                                                <div class="position-relative">
                                                    <button type="button"
                                                            x-ref="dropTrigger"
                                                            @click="openDrop()"
                                                            class="btn btn-light rounded-pill px-3 py-1 shadow-sm border d-flex align-items-center justify-content-between fw-bold"
                                                            style="width: 130px; font-size: 13px;"
                                                            :class="current.color">
                                                        <span class="d-flex align-items-center gap-2">
                                                            <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px;" :class="current.dot"></span>
                                                            <span x-text="current.label"></span>
                                                        </span>
                                                        <i class="bi bi-caret-down-fill text-muted" style="font-size: 10px;" :class="dropOpen ? 'rotate-180' : ''"></i>
                                                    </button>

                                                    {{-- Teleported dropdown --}}
                                                    <template x-teleport="body">
                                                        <div x-show="dropOpen"
                                                             x-cloak
                                                             class="position-fixed bg-white border rounded-4 shadow-sm p-2 d-flex flex-column gap-1"
                                                             style="z-index: 1060; width: 140px;"
                                                             :style="`
                                                                 left: ${triggerRect.left}px;
                                                                 top: ${(triggerRect.bottom + 150 > window.innerHeight)
                                                                     ? (triggerRect.top - 150) + 'px'
                                                                     : (triggerRect.bottom + 5) + 'px'};
                                                             `">
                                                            <template x-for="opt in options" :key="opt.value">
                                                                <button type="button"
                                                                        @click="selected = opt.value; dropOpen = false"
                                                                        class="btn btn-sm d-flex align-items-center gap-2 w-100 text-start fw-bold rounded-3"
                                                                        :class="[opt.color, selected === opt.value ? (opt.bg + ' bg-opacity-10 text-dark') : 'btn-light']">
                                                                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px;" :class="opt.dot"></span>
                                                                    <span x-text="opt.label"></span>
                                                                    <i x-show="selected === opt.value" class="bi bi-check fs-6 ms-auto"></i>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>

                                                {{-- Submit Button --}}
                                                <button type="submit"
                                                        class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                                        style="width: 32px; height: 32px;" title="Submit Result">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm rounded-4 border-2 border-dashed border-secondary bg-light text-center py-5">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="bi bi-clock-history fs-2 text-muted"></i>
            </div>
            <p class="fw-bold text-muted mb-0">No renewal history available for this certificate.</p>
        </div>
    @endforelse
</div>
@endsection
