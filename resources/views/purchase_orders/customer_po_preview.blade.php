@extends('layouts.app')

@section('header', 'Customer PO Upload')

@section('content')
@php
    $groups    = collect($poGroups);
    $newGroups = $groups->where('is_duplicate', false);
    $newCount  = $newGroups->count();
    $dupCount  = $groups->count() - $newCount;
@endphp

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="fw-semibold">{{ $originalFileName }}</div>
            <div class="text-muted small">
                {{ $newCount }} of {{ $groups->count() }} PO(s) to import &middot;
                {{ number_format($newGroups->sum('total_pcs')) }} pcs &middot;
                {{ number_format($newGroups->sum('total_val'), 2) }}
            </div>
        </div>
        <form method="POST" action="{{ route('purchase-orders.customer-po-upload.confirm') }}" id="confirmPoForm" class="d-flex gap-2">
            @csrf
            <input type="hidden" name="preview_token" value="{{ $token }}">
            <a href="{{ route('purchase-orders.customer-po-upload.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" id="confirmBtn" class="btn btn-success" {{ $newCount === 0 ? 'disabled' : '' }}>Confirm</button>
        </form>
    </div>
</div>

@if($dupCount > 0)
    <div class="alert alert-warning py-2">{{ $dupCount }} PO(s) already exist and will be skipped. Changes to their quantities, prices or items will not be applied.</div>
@endif

@foreach($poGroups as $group)
    @php $isDup = $group['is_duplicate'] ?? false; @endphp
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>PO {{ $group['po_number'] }}</strong>
                <span class="text-muted ms-2">{{ $group['customer_name'] }}</span>
            </div>
            <div class="small">
                @if($isDup)
                    <span class="text-danger me-3">Already exists (ID {{ $group['duplicate_id'] }})</span>
                @endif
                {{ number_format($group['total_pcs']) }} pcs &middot; {{ number_format($group['total_val'], 2) }}
            </div>
        </div>

        <div class="card-body py-2 small text-muted">
            Date: {{ $group['order_date'] ?: '-' }}
            @if(!empty($group['season'])) &nbsp;|&nbsp; Season: {{ $group['season'] }} @endif
            @if(!empty($group['delivery_addr'])) &nbsp;|&nbsp; Destination: {{ $group['delivery_addr'] }} @endif
            @if(!empty($group['ship_date'])) &nbsp;|&nbsp; Ship: {{ $group['ship_date'] }} @endif
            @if(!empty($group['indc_date'])) &nbsp;|&nbsp; In-DC: {{ $group['indc_date'] }} @endif
            @if(!empty($group['vendor'])) &nbsp;|&nbsp; Vendor: {{ $group['vendor'] }} @endif
        </div>

        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Style</th>
                        <th>Description</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th>UPC</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end pe-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['items'] as $i)
                        <tr>
                            <td class="ps-3">{{ $i['seq_no'] }}</td>
                            <td>{{ $i['style'] }}</td>
                            <td>{{ $i['description'] }}</td>
                            <td>{{ ($i['color_code'] && $i['color_code'] !== '-') ? $i['color_code'] . ' ' : '' }}{{ $i['color_name'] }}</td>
                            <td>{{ $i['size'] }}</td>
                            <td>{{ $i['upc'] }}</td>
                            <td class="text-end">{{ number_format($i['qty']) }}</td>
                            <td class="text-end">{{ number_format($i['unit_price'], 2) }}</td>
                            <td class="text-end pe-3">{{ number_format($i['line_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

<script>
document.getElementById('confirmPoForm').addEventListener('submit', function () {
    var b = document.getElementById('confirmBtn');
    setTimeout(function () { b.disabled = true; b.innerText = 'Saving...'; }, 0);
});
</script>
@endsection
