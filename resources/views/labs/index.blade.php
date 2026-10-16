@extends('layouts.app')

@section('header', 'Labs Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Labs</h2>
    <a href="{{ route('labs.create') }}" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4">
        <i class="bi bi-plus-lg me-2"></i> Add Lab
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center fw-semibold rounded-3 mb-4 border-0 bg-success bg-opacity-10 text-success" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        {{ session('success') }}
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Name</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Email</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labs as $lab)
                    <tr>
                        <td class="py-3 px-4">
                            <span class="fw-bold text-dark small d-block">{{ $lab->name }}</span>
                            @if($lab->lab_code)
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-semibold mt-1" style="font-size: 11px;">
                                    <i class="bi bi-tag me-1"></i>{{ $lab->lab_code }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-secondary small fw-semibold">{{ $lab->email ?: 'N/A' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-secondary small fw-semibold">
                                {{ $lab->contact_person ?: 'N/A' }} 
                                @if($lab->phone) ({{ $lab->phone }}) @endif
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $lab->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }} rounded-pill px-3 py-2 border fw-bold">
                                {{ $lab->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('labs.edit', $lab) }}" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Edit Lab">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('labs.destroy', $lab) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this lab?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Delete Lab">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($labs->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $labs->links() }}
    </div>
    @endif
</div>
@endsection
