@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item text-secondary">Master Data</li>
            <li class="breadcrumb-item"><a href="{{ route('master-data.seasons.index') }}">Seasons</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-light py-2">
                    <span class="fw-semibold">Edit Season</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('master-data.seasons.update', $season) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label small">Season Name</label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control form-control-sm @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $season->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                        <a href="{{ route('master-data.seasons.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection