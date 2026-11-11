@extends('layouts.app')
@section('header', 'Sales Contracts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
<div>
<h3>Sales Contracts</h3>
<span class="text-muted">Review and finalize uploaded customer orders.</span>
</div>
<a class="btn btn-primary" href="{{ route('purchase-orders.customer-po-upload.index') }}">Upload Customer PO</a>
</div>
<form class="card card-body mb-3" method="GET">
<div class="row g-2">
<div class="col-md-7">
<input class="form-control" name="search" value="{{ $search }}" placeholder="PO number, confirmation reference or customer" aria-label="Search orders">
</div>
<div class="col-md-3">
<select class="form-select" name="status" aria-label="Status">
<option value="">All statuses</option>@foreach(['draft'=>'Draft','finalized'=>'Finalized','closed'=>'Closed'] as $value=>$label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
</div>
<div class="col-md-2">
<button class="btn btn-primary w-100">Find</button>
</div>
</div>
</form>
<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th>PO Num</th>
<th>Confirm Ref</th>
<th>Customer</th>
<th>Order date</th>
<th class="text-end">Qty</th>
<th class="text-end">Value</th>
<th>Status</th>
<th>
</th>
</tr>
</thead>
<tbody>@forelse($orders as $order)<tr>
<td>{{ $order->pdf_po_refno }}</td>
<td>{{ $order->order_conf_refno }}</td>
<td>{{ $order->customer_name }}</td>
<td>{{ $order->porder_date }}</td>
<td class="text-end">{{ number_format($order->porder_tot_pcs) }}</td>
<td class="text-end">{{ number_format($order->porder_tot_val,2) }}</td>
<td>
<span class="badge {{ $order->qryapproved || $order->qryclosed ? 'bg-success' : 'bg-secondary' }}">{{ $order->qryclosed ? 'Closed' : ($order->qryapproved ? 'Finalized' : 'Draft') }}</span>
</td>
<td>
<a class="btn btn-sm btn-outline-primary" href="{{ route('purchase-orders.sales-contracts.show',$order->id) }}">Open</a>
</td>
</tr>@empty<tr>
<td colspan="8" class="text-center py-4">No matching orders.</td>
</tr>@endforelse</tbody>
</table>
</div>
<div class="card-footer">{{ $orders->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
