<div class="card mb-4">
<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
<strong>P.O. Item Detail</strong>
<div class="d-flex gap-2">@unless($locked)<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#packingModal">Update Pack Details</button>
<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pricesModal">Update Prices</button>@endunless</div>
</div>
<div class="card-body">
<label for="itemSearch" class="form-label">Search items</label>
<input class="form-control mb-3" id="itemSearch" type="search" placeholder="Style, color, size or GTIN">
<div class="table-responsive">
<table class="table table-sm align-middle" id="contractItems">
<thead>
<tr>
<th>Seq No</th>
<th>Page</th>
<th>Style</th>
<th>Item Code</th>
<th>Description</th>
<th>Color</th>
<th>Size</th>
<th class="text-end">Qty</th>
<th class="text-end">Unit Price</th>
<th>GTIN #</th>
<th>Master Pack</th>
<th>Case Pack</th>
<th class="text-end">Total</th>
</tr>
</thead>
<tbody>@forelse($activeItems as $item)<tr>
<td>{{ $item->item_seqno }}</td>
<td>{{ $item->item_pageno }}</td>
<td>{{ $item->item_styleno }}</td>
<td>{{ $item->item_code }}</td>
<td>{{ $item->item_description }}</td>
<td>{{ $item->item_colorcode }} {{ $item->item_colordesc }}</td>
<td>{{ $item->size_code }}</td>
<td class="text-end">{{ number_format($item->item_order_qty) }}</td>
<td class="text-end">{{ number_format($item->revised_unit_price,4) }}</td>
<td>{{ $item->upc_refno }}</td>
<td>{{ $item->master_packqty }}</td>
<td>{{ $item->case_packqty }}</td>
<td class="text-end">{{ number_format(round($item->item_order_qty*$item->revised_unit_price,2),2) }}</td>
</tr>@empty<tr>
<td colspan="13">No active items.</td>
</tr>@endforelse</tbody>
</table>
</div>
</div>
</div>
<script>document.getElementById('itemSearch').addEventListener('input',function(){const term=this.value.toLowerCase();document.querySelectorAll('#contractItems tbody tr').forEach(row=>row.hidden=!row.textContent.toLowerCase().includes(term));});</script>
