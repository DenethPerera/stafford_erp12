@extends('layouts.app')

@section('header', 'Renewal Stages Configuration')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4 p-md-5">
        <form action="{{ route('standard-stages.index') }}" method="GET" class="d-flex flex-column flex-md-row align-items-md-center gap-3 w-100" id="standardSelectForm">
            <label for="standard_id" class="form-label text-dark fw-bold mb-0 text-nowrap">Select Standard:</label>
            @php
                $selectedStandardModel = $standards->firstWhere('id', $selectedStandardId);
            @endphp
            <div class="position-relative flex-grow-1" style="max-width: 500px;" x-data="{ open: false }" @click.away="open = false">
                <input type="hidden" name="standard_id" id="standard_id" value="{{ $selectedStandardId }}">
                <button @click="open = !open" class="btn btn-light bg-white border shadow-sm fw-semibold text-dark px-4 py-2 w-100 text-start d-flex justify-content-between align-items-center rounded-pill" type="button" id="standardDropdown">
                    <span>
                        @if($selectedStandardModel)
                            {{ $selectedStandardModel->name }} ({{ $selectedStandardModel->code }})
                        @else
                            <span class="text-muted">-- Choose a standard to view its stages --</span>
                        @endif
                    </span>
                    <i class="bi bi-chevron-down text-muted" style="font-size: 12px; transition: transform 0.2s;" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak x-transition.opacity.duration.200ms class="dropdown-menu border-0 shadow-lg rounded-4 w-100 mt-2 py-2 show position-absolute" style="z-index: 1050; display: none;">
                    <li>
                        <a class="dropdown-item fw-bold text-muted py-2 px-4" href="#" @click.prevent="document.getElementById('standard_id').value=''; document.getElementById('standardSelectForm').submit();">
                            -- Choose a standard to view its stages --
                        </a>
                    </li>
                    @foreach($standards as $standard)
                        <li>
                            <a class="dropdown-item fw-bold py-2 px-4 {{ $selectedStandardId == $standard->id ? 'bg-primary bg-opacity-10 text-primary' : 'text-dark hover-bg-light' }}" href="#" @click.prevent="document.getElementById('standard_id').value='{{ $standard->id }}'; document.getElementById('standardSelectForm').submit();" style="transition: all 0.2s;">
                                {{ $standard->name }} ({{ $standard->code }})
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <noscript>
                <button type="submit" class="btn btn-dark fw-bold px-4">Load</button>
            </noscript>
        </form>
    </div>
</div>

@if($selectedStandardId)
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <h2 class="h5 fw-bolder text-dark mb-0 px-2">Stages for {{ $selectedStandard->name }}</h2>
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
                <input type="text" id="stageSearch" class="form-control rounded-pill bg-white shadow-sm border-0 pe-5" placeholder="Search stages..." style="width: 250px;">
                <i class="bi bi-search position-absolute text-muted" style="right: 15px; top: 50%; transform: translateY(-50%);"></i>
            </div>
            <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4" data-bs-toggle="modal" data-bs-target="#addStageModal">
                <i class="bi bi-plus-lg me-2"></i> Add Stage
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stagesTable">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Order</th>
                        <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Stage Information</th>
                        <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="stages-list" data-reorder-url="{{ route('standard-stages.reorder') }}">
                    @forelse($stages as $stage)
                        <tr class="stage-row" data-id="{{ $stage->id }}" data-search="{{ strtolower($stage->name . ' ' . $stage->description) }}" style="cursor: grab;">
                            <td class="py-3 px-4" style="width: 80px;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-grip-vertical text-muted fs-5"></i>
                                    <div class="bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center fw-bold border" style="width: 32px; height: 32px;">
                                        {{ $stage->order_sequence }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <h6 class="fw-bold text-dark mb-1">{{ $stage->name }}</h6>
                                <p class="small text-muted mb-0 fw-semibold">{{ \Illuminate\Support\Str::limit($stage->description, 60) }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px;" title="Edit"
                                        onclick='openEditStageModal({!! json_encode([
                                            "id" => $stage->id,
                                            "standard_id" => $stage->standard_id,
                                            "name" => $stage->name,
                                            "description" => $stage->description,
                                            "order_sequence" => $stage->order_sequence
                                        ]) !!})'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px;" title="Delete"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmModal" 
                                        data-action="{{ route('standard-stages.destroy', $stage) }}"
                                        data-message="Are you sure you want to delete stage &quot;{{ addslashes($stage->name) }}&quot;?"
                                        data-submessage="This may affect renewal workflows.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="d-flex flex-column justify-content-center align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-sm border border-white text-secondary" style="width: 64px; height: 64px;">
                                        <i class="bi bi-diagram-3 fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">No Stages Configured</h5>
                                    <p class="text-muted fw-semibold mb-4">There are no renewal stages configured for this standard yet.</p>
                                    <button type="button" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStageModal">
                                        Add First Stage
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="3" class="text-center py-5 text-muted fw-semibold">
                            No stages match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm rounded-4 text-center">
        <div class="card-body p-5 py-md-5 my-5">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="bi bi-diagram-3 fs-1"></i>
            </div>
            <h3 class="h4 fw-bolder text-dark mb-2">Renewal Workflows</h3>
            <p class="text-muted fw-semibold mb-0">Select a standard from the dropdown above to view and configure its step-by-step renewal process.</p>
        </div>
    </div>
@endif

@if($selectedStandardId)
    <!-- Add Stage Modal -->
    <div class="modal fade {{ $errors->any() && !old('_method') ? 'show' : '' }}" id="addStageModal" tabindex="-1" aria-labelledby="addStageModalLabel" aria-hidden="true" {!! $errors->any() && !old('_method') ? 'style="display: block;"' : '' !!}>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-diagram-3 fs-5"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark" id="addStageModalLabel">Add Renewal Stage</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="{{ route('standard-stages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="standard_id" value="{{ $selectedStandardId }}">
                    <div class="modal-body px-4 py-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Stage Name</label>
                                <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('name') }}" placeholder="e.g., Pre Audit, Document Review" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Order Sequence</label>
                                <input type="number" name="order_sequence" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('order_sequence', count($stages) + 1) }}" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description (Optional)</label>
                            <textarea name="description" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 py-2 shadow-sm">Save Stage</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Stage Modal -->
    <div class="modal fade {{ $errors->any() && old('_method') == 'PUT' ? 'show' : '' }}" id="editStageModal" tabindex="-1" aria-labelledby="editStageModalLabel" aria-hidden="true" {!! $errors->any() && old('_method') == 'PUT' ? 'style="display: block;"' : '' !!}>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-pencil fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="editStageModalLabel">Edit Renewal Stage</h5>
                            <p class="small text-muted fw-semibold mb-0" id="editStageNameDisplay"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="editStageForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="standard_id" value="{{ $selectedStandardId }}">
                    <div class="modal-body px-4 py-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Stage Name</label>
                                <input type="text" name="name" id="edit_name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Order Sequence</label>
                                <input type="number" name="order_sequence" id="edit_order_sequence" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description (Optional)</label>
                            <textarea name="description" id="edit_description" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 py-2 shadow-sm">Update Stage</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="modal-backdrop fade show"></div>
@endif

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    // Drag and Drop functionality
    const stagesList = document.getElementById('stages-list');
    if (stagesList) {
        new Sortable(stagesList, {
            animation: 150,
            ghostClass: 'bg-light',
            handle: '.bi-grip-vertical',
            onEnd: function (evt) {
                const url = stagesList.getAttribute('data-reorder-url');
                const rows = Array.from(stagesList.querySelectorAll('tr.stage-row'));
                const order = rows.map(row => row.getAttribute('data-id'));

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order: order })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // Update order numbers in UI
                        rows.forEach((row, index) => {
                            row.querySelector('.bg-light.text-secondary.rounded-circle').textContent = index + 1;
                        });
                    }
                })
                .catch(error => console.error('Error reordering stages:', error));
            }
        });
    }

    // Search functionality
    document.getElementById('stageSearch')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.stage-row');
        let hasVisible = false;
        
        rows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            if (searchData.includes(query)) {
                row.style.display = '';
                hasVisible = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.style.display = hasVisible ? 'none' : '';
        }
    });

    // Edit Modal functionality
    let editStageModalInstance = null;
    function openEditStageModal(stage) {
        document.getElementById('editStageNameDisplay').textContent = stage.name;
        document.getElementById('editStageForm').action = '/standard-stages/' + stage.id;
        
        document.getElementById('edit_name').value = stage.name || '';
        document.getElementById('edit_order_sequence').value = stage.order_sequence || 1;
        document.getElementById('edit_description').value = stage.description || '';
        
        if (!editStageModalInstance) {
            editStageModalInstance = new bootstrap.Modal(document.getElementById('editStageModal'));
        }
        editStageModalInstance.show();
    }
</script>
@endsection
