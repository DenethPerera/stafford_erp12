<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Sales Contract {{ $po->order_conf_refno }}</title>
<style>body{font:13px Arial,sans-serif;color:#222;margin:30px}table{width:100%;border-collapse:collapse;margin:20px 0}th,td{padding:7px;border:1px solid #ccc;text-align:left}.number{text-align:right}.addresses{display:flex;gap:30px}.addresses>div{flex:1;white-space:pre-wrap}thead{display:table-header-group}tr{break-inside:avoid}@media print{.no-print{display:none}body{margin:0}@page{size:A4 landscape;margin:12mm}}</style>
</head>
<body>
<button class="no-print" onclick="window.print()">Print / Save as PDF</button>
<h2>Stafford Textile Ltd ? Sales Contract</h2>@unless($po->qryapproved)<h3>DRAFT ? {{ $po->qryclosed ? 'Closed' : 'Not finalized' }}</h3>@endunless<p>Confirm Ref: {{ $po->order_conf_refno }} | Customer PO: {{ $po->pdf_po_refno }} | Order date: {{ $po->porder_date }}</p>
<div class="addresses">
<div>
<strong>Sold To: {{ $po->customer_name }}</strong>
<br>{{ $po->customer_addr }}</div>
<div>
<strong>Ship To</strong>
<br>{{ $po->delivery_addr }}</div>
</div>
<p>X factory: {{ $po->ship_date }} | In-DC: {{ $po->indc_date }} | Shipping Term: {{ $po->ship_term }} | INCOTERMS: {{ $po->fob_code }} | Ship Via: {{ $po->ship_method }}</p>
<table>
<thead>
<tr>
<th>Seq</th>
<th>Style / Item</th>
<th>Description</th>
<th>Color</th>
<th>Size</th>
<th>GTIN</th>
<th>Qty</th>
<th>Unit Price</th>
<th>Value</th>
</tr>
</thead>
<tbody>@php $items=$po->items->where('qrycancel',0); @endphp @foreach($items as $item)<tr>
<td>{{ $item->item_seqno }}</td>
<td>{{ $item->item_styleno }} / {{ $item->item_code }}</td>
<td>{{ $item->item_description }}</td>
<td>{{ $item->item_colorcode }} {{ $item->item_colordesc }}</td>
<td>{{ $item->size_code }}</td>
<td>{{ $item->upc_refno }}</td>
<td class="number">{{ number_format($item->item_order_qty) }}</td>
<td class="number">{{ number_format($item->revised_unit_price,4) }}</td>
<td class="number">{{ number_format(round($item->item_order_qty*$item->revised_unit_price,2),2) }}</td>
</tr>@endforeach</tbody>
</table>
<p>Total Qty: {{ number_format($items->sum('item_order_qty')) }} | Total Value: {{ number_format($items->sum(fn($i)=>round($i->item_order_qty*$i->revised_unit_price,2)),2) }}</p>@if($po->qryapproved)<p>Approved by {{ $po->approved_by }} ? {{ $po->approved_at }}</p>@endif</body>
</html>
