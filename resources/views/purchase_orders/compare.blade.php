@extends('layouts.app')
@section('header', 'Order File Inspection')
@section('content')
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Order List</strong>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#compareFileModal">Find file</button>
    </div>
    <div class="card-body">
        <p class="text-muted small">Compare only — no orders are saved or updated.</p>
        @if($selectedCustomer)
            <p><strong>{{ $selectedCustomer->customer_name }} ({{ $selectedCustomer->customer_ref }})</strong><br>{{ $selectedFile }}</p>
        @endif
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>PO Refno</th><th>Order Date</th><th>In-DC Date</th><th>Ship Date</th><th>Revised Item Count</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($results as $result)
                    <tr>
                        <td>{{ $result['po_number'] }}</td><td>{{ $result['incoming']['order_date'] }}</td>
                        <td>{{ $result['incoming']['indc_date'] ?? '-' }}</td><td>{{ $result['incoming']['ship_date'] ?? '-' }}</td>
                        <td>{{ $result['revised_count'] }}</td><td>{{ $result['status'] }}</td>
                        <td><a href="#compare-po-{{ $loop->index }}" class="btn btn-sm btn-outline-primary">View comparison</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Select a customer and file using Find file.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if($results)
    <div class="alert alert-info">Revised Item Count counts matched items with changed fields. New, missing and ambiguous items are shown separately. A changed size is shown as unmatched items. “Not in CSV” does not mean cancelled or deleted.</div>
@endif
@foreach($results as $result)
<div class="card border-0 shadow-sm mb-3" id="compare-po-{{ $loop->index }}">
    <div class="card-header bg-white"><strong>PO {{ $result['po_number'] }}</strong> — {{ $result['status'] }}</div>
    <div class="card-body">
        @if($result['header_changes'])
        <h6>Order details changed</h6>
        <table class="table table-sm"><thead><tr><th>Field</th><th>Current DB</th><th>Incoming CSV</th></tr></thead><tbody>
            @foreach($result['header_changes'] as $change)
                <tr class="table-warning"><td>{{ $change['field'] }}</td><td>{{ $change['old'] ?: '—' }}</td><td>{{ $change['new'] ?: '—' }}</td></tr>
            @endforeach
        </tbody></table>
        @endif
        <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Status</th><th>Source</th><th>Style</th><th>Colour</th><th>Size</th><th>Qty</th><th>Unit price</th><th>Changes (DB → CSV)</th></tr></thead>
            <tbody>
            @forelse($result['items'] as $item)
                @php $display = $item['new'] ?? $item['old']; @endphp
                <tr class="{{ $item['status'] === 'Unchanged' ? '' : 'table-warning' }}">
                    <td>{{ $item['status'] }}</td><td>{{ $item['new'] ? 'CSV' : 'DB' }}</td>
                    <td>{{ $display['style'] }}</td><td>{{ $display['color_code'] }} {{ $display['color_name'] ?? '' }}</td>
                    <td>{{ $display['size'] }}</td><td>{{ $display['qty'] }}</td><td>{{ number_format((float)$display['unit_price'], 4) }}</td>
                    <td>
                    @foreach($item['changes'] as $change)
                        <div><strong>{{ $change['field'] }}:</strong> {{ $change['old'] === '' ? '—' : $change['old'] }} → {{ $change['new'] === '' ? '—' : $change['new'] }}</div>
                    @endforeach
                    @if(str_starts_with($item['status'], 'Ambiguous')) Cannot pair repeated style/colour/size automatically. @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Resolve the multiple matching database POs before comparing their items.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endforeach
<div class="modal fade" id="compareFileModal" tabindex="-1" aria-labelledby="compareFileTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="compareFileTitle">Select customer and CSV file</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form method="GET" action="{{ route('purchase-orders.compare.index') }}">
            <div class="modal-body">
                <label for="compareCustomer" class="form-label">Customer</label>
                <select id="compareCustomer" name="customer_id" class="form-select mb-3" required>
                    <option value="">Select customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->customer_id }}" @selected((string)request('customer_id') === (string)$customer->customer_id)>{{ $customer->customer_ref }} — {{ $customer->customer_name }}</option>
                    @endforeach
                </select>
                <label for="compareFile" class="form-label">Inbox file (newest modified first)</label>
                <select id="compareFile" name="ftp_file" class="form-select" required>
                    <option value="">Select file</option>
                    @foreach($files as $file)<option value="{{ $file }}" @selected(request('ftp_file') === $file)>{{ $file }}</option>@endforeach
                </select>
                @if($files->isEmpty())<p class="text-muted mt-2">No CSV/TXT files are available in the inbox.</p>@endif
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" @disabled($files->isEmpty())>Compare</button></div>
        </form>
    </div></div>
</div>
@endsection
