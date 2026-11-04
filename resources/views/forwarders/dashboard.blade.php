@extends('layouts.app')

@section('header', 'Forwarder Dashboard')

@section('content')
    <h2 class="fw-bold mb-3">Forwarder Dashboard</h2>

    <p>Welcome, {{ auth()->user()->name }}.</p>

    <p class="text-muted">
        You have successfully logged in as a forwarder.
    </p>

    <form method="POST" action="{{ route('forwarder.logout') }}">
        @csrf

        <button type="submit" class="btn btn-outline-danger">
            Log Out
        </button>
    </form>
@endsection
