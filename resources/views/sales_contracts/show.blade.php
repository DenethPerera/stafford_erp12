@extends('layouts.app')
@section('header', 'Sales Contract ? Order Details')
@section('content')
@php $locked = (bool) ($po->qryapproved || $po->qryclosed); $activeItems = $po->items->where('qrycancel',0); @endphp
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
<h2 class="mb-0">Order Details: {{ $po->pdf_po_refno }}</h2>
<div class="d-flex gap-2">
<a class="btn btn-outline-primary" href="{{ route('purchase-orders.customer-po-upload.index') }}">New Order / Upload</a>
<a class="btn btn-outline-primary" href="{{ route('purchase-orders.sales-contracts.index') }}">Find</a>@unless($locked)<form method="POST" action="{{ route('purchase-orders.sales-contracts.finalize',$po->id) }}" onsubmit="return confirm('Finalize this order? Further editing will be locked.');">@csrf<button class="btn btn-primary">Finalize</button>
</form>@endunless<a class="btn btn-outline-primary" href="{{ route('purchase-orders.sales-contracts.print',$po->id) }}" target="_blank" rel="noopener">Print</a>
</div>
</div>
@if($locked)<div class="alert alert-info">{{ $po->qryclosed ? 'Closed' : 'Finalized' }} order ? editing is locked. @if($po->approved_by) Approved by {{ $po->approved_by }} on {{ $po->approved_at }}.@endif</div>@endif
@include('sales_contracts.partials.header-form')
@include('sales_contracts.partials.corrections')
@include('sales_contracts.partials.items-table')
@unless($locked)
@include('sales_contracts.partials.prices-modal')
@include('sales_contracts.partials.packing-modal')
@endunless
@endsection
