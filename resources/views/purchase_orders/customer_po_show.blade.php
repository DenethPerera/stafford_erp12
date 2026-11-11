@extends('layouts.app')

@section('header', 'Customer PO Details')

@section('content')
@php
    $totalQty = $po->items->sum('item_order_qty');
    $totalVal = $po->items->sum(fn($i) => ($i->item_order_qty ?? 0) * ($i->receipt_unit_price ?? 0));
    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '-';
@endphp

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>PO {{ $po->pdf_po_refno }}</strong>
        <a href="{{ route('purchase-orders.customer-po-upload.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3"><div class="text-muted">Customer</div>{{ $po->customer_name ?: ($customer->customer_name ?? '-') }}</div>
            <div class="col-md-3"><div class="text-muted">PO Date</div>{{ $fmt($po->porder_date) }}</div>
            <div class="col-md-3"><div class="text-muted">Season</div>{{ $po->item_dev_season ?: '-' }}</div>
            <div class="col-md-3"><div class="text-muted">File</div>{{ $po->pdf_file_name ?: '-' }}</div>
            <div class="col-md-3"><div class="text-muted">Total Pcs</div>{{ number_format($po->porder_tot_pcs ?? $totalQty) }}</div>
            <div class="col-md-3"><div class="text-muted">Total Value</div>{{ number_format($po->porder_tot_val ?? $totalVal, 2) }}</div>
            <div class="col-md-3"><div class="text-muted">Ship / In-DC</div>{{ $fmt($po->ship_date) }} / {{ $fmt($po->indc_date) }}</div>
            <div class="col-md-3"><div class="text-muted">Created By</div>{{ $po->created_by ?: '-' }}</div>
            <div class="col-12"><div class="text-muted">Destination</div>{{ $po->delivery_addr ?: '-' }}</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white"><strong>Items</strong> <span class="text-muted small ms-2">{{ $po->items->count() }}</span></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
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
                @forelse($po->items as $item)
                    <tr>
                        <td class="ps-3">{{ $item->item_seqno ?: $loop->iteration }}</td>
                        <td>{{ $item->item_styleno }}</td>
                        <td>{{ $item->item_description }}</td>
                        <td>{{ ($item->item_colorcode && $item->item_colorcode !== '-') ? $item->item_colorcode . ' ' : '' }}{{ $item->item_colordesc }}</td>
                        <td>{{ $item->size_code }}</td>
                        <td>{{ $item->upc_refno }}</td>
                        <td class="text-end">{{ number_format($item->item_order_qty ?? 0) }}</td>
                        <td class="text-end">{{ number_format($item->receipt_unit_price ?? 0, 2) }}</td>
                        <td class="text-end pe-3">{{ number_format(($item->item_order_qty ?? 0) * ($item->receipt_unit_price ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No items.</td></tr>
                @endforelse
            </tbody>
            @if($po->items->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-semibold">
                        <td colspan="6" class="text-end ps-3">Total</td>
                        <td class="text-end">{{ number_format($totalQty) }}</td>
                        <td></td>
                        <td class="text-end pe-3">{{ number_format($totalVal, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
