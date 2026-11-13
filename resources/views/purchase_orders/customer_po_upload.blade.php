@extends('layouts.app')

@section('header', 'Customer PO Upload')

@section('content')
{{-- Flash and validation messages are rendered once by layouts.app. --}}
@php $abMode = old('upload_type') === 'AB_FTP'; @endphp

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="text-primary">CSV Upload :</strong>
        <div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="upload_toggle" id="toggleNonFtp" value="NON_FTP" {{ $abMode ? '' : 'checked' }}>
                <label class="form-check-label" for="toggleNonFtp">Non-FTP</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="upload_toggle" id="toggleAbFtp" value="AB_FTP" {{ $abMode ? 'checked' : '' }}>
                <label class="form-check-label" for="toggleAbFtp">AB-FTP</label>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div id="sectionNonFtp" class="{{ $abMode ? 'd-none' : '' }}">
            <form method="POST" action="{{ route('purchase-orders.customer-po-upload.preview') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="upload_type" value="NON_FTP">

                <select name="customer_id" class="form-select mb-3" required>
                    <option value="" selected disabled>Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->customer_id }}" {{ (string) old('customer_id') === (string) $customer->customer_id ? 'selected' : '' }}>
                            {{ $customer->customer_ref }} - {{ $customer->customer_name }}
                        </option>
                    @endforeach
                </select>

                <div class="row g-2">
                    <div class="col">
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary px-4 js-submit-once">Upload</button>
                    </div>
                </div>
            </form>
        </div>

        <div id="sectionAbFtp" class="{{ $abMode ? '' : 'd-none' }}">
            <form method="POST" action="{{ route('purchase-orders.customer-po-upload.preview') }}">
                @csrf
                <input type="hidden" name="upload_type" value="AB_FTP">
                <p class="text-muted small">Newest modified files are listed first. Refresh after a new file arrives. Existing POs are skipped; revisions are not applied.</p>

                <div class="row g-2">
                    <div class="col">
                        <select name="ftp_file" class="form-select" required>
                            <option value="" selected disabled>Select File</option>
                            @foreach($ftpFiles as $file)
                                <option value="{{ $file }}" {{ old('ftp_file') === $file ? 'selected' : '' }}>{{ $file }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary px-4 js-submit-once" {{ count($ftpFiles) === 0 ? 'disabled' : '' }}>Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Customer Purchase Orders</strong>
        <span class="text-muted small">{{ number_format($recentPos->total()) }} records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">ID</th>
                    <th>PO No</th>
                    <th>Customer</th>
                    <th>PO Date</th>
                    <th class="text-end">Pcs</th>
                    <th class="text-end">Value</th>
                    <th>File</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPos as $item)
                    <tr>
                        <td class="ps-3">{{ $item->id }}</td>
                        <td>{{ $item->pdf_po_refno }}</td>
                        <td>{{ $item->customer_name }}</td>
                        <td>{{ $item->porder_date ? \Carbon\Carbon::parse($item->porder_date)->format('Y-m-d') : '' }}</td>
                        <td class="text-end">{{ number_format($item->porder_tot_pcs ?? 0) }}</td>
                        <td class="text-end">{{ number_format($item->porder_tot_val ?? 0, 2) }}</td>
                        <td class="text-truncate" style="max-width:160px;" title="{{ $item->pdf_file_name }}">{{ $item->pdf_file_name }}</td>
                        <td class="text-end pe-3">
                            @role('super_admin')
                            <a href="{{ route('purchase-orders.sales-contracts.show', $item->id) }}" class="btn btn-sm btn-outline-success">Sales Contract</a>
                            @endrole
                            <a href="{{ route('purchase-orders.customer-po-upload.show', $item->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($recentPos->hasPages())
        <div class="card-footer bg-white">{{ $recentPos->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Only this upload page opts into auto-dismiss for layout notifications.
    document.querySelectorAll('.content-area > .alert').forEach(function (alert) {
        var dismissTimer = setTimeout(function () { alert.remove(); }, 8000);
        var closeButton = alert.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                clearTimeout(dismissTimer);
                alert.remove();
            });
        }
    });

    var non = document.getElementById('toggleNonFtp');
    function sw() {
        document.getElementById('sectionNonFtp').classList.toggle('d-none', !non.checked);
        document.getElementById('sectionAbFtp').classList.toggle('d-none', non.checked);
    }
    non.addEventListener('change', sw);
    document.getElementById('toggleAbFtp').addEventListener('change', sw);

    document.querySelectorAll('form').forEach(function (f) {
        f.addEventListener('submit', function () {
            var b = f.querySelector('.js-submit-once');
            if (b) { setTimeout(function () { b.disabled = true; }, 0); }
        });
    });
});
</script>
@endsection
