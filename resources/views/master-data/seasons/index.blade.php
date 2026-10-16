@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    {{-- Breadcrumb showing parent Master Data --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item text-secondary">Master Data</li>
            <li class="breadcrumb-item active" aria-current="page">Seasons</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Add Form  2000421999(1002674)-->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <span class="fw-semibold">Add Season</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('master-data.seasons.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label small">Season Name</label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control form-control-sm @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" 
                                   placeholder="e.g. Summer 2026" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <span class="fw-semibold">Seasons List</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Name</th>
                                <th style="width: 140px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($seasons as $season)
                                <tr>
                                    <td class="text-center text-muted">{{ $loop->iteration + ($seasons->currentPage() - 1) * $seasons->perPage() }}</td>
                                    <td>{{ $season->name }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('master-data.seasons.edit', $season) }}" class="btn btn-outline-secondary btn-sm py-0">Edit</a>
                                        <form action="{{ route('master-data.seasons.destroy', $season) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($seasons->hasPages())
                    <div class="card-footer py-2">
                        {{ $seasons->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection