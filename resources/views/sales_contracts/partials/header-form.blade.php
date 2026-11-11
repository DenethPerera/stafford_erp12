<form method="POST" action="{{ route('purchase-orders.sales-contracts.update',$po->id) }}" class="card mb-4">@csrf @method('PUT')
<fieldset class="card-body" @disabled($locked)>
<div class="row g-3">
@php
$fields = ['porder_date'=>['Order date','date'], 'order_conf_refno'=>['Confirm Ref','text'], 'ship_date'=>['X factory','date'], 'indc_date'=>['In-DC Date','date'], 'ship_term'=>['Shipping Term','text'], 'sales_rep_id'=>['Salesperson Ref','text'], 'supplier_refno'=>['Supplier Num','text'], 'item_dev_season'=>['Order Season','text'], 'fob_code'=>['INCOTERMS','text'], 'ship_method'=>['Ship Via','text']];
$limits = ['order_conf_refno'=>25,'ship_term'=>50,'sales_rep_id'=>15,'supplier_refno'=>15,'item_dev_season'=>50,'fob_code'=>20,'ship_method'=>20];
@endphp
@foreach($fields as $name=>[$label,$type])
<div class="col-md-3">
<label class="form-label" for="{{ $name }}">{{ $label }}</label>
<input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" class="form-control" value="{{ old($name,$po->$name) }}" @if(isset($limits[$name])) maxlength="{{ $limits[$name] }}" @endif @required(in_array($name,['porder_date','order_conf_refno']))>
</div>
@endforeach
<div class="col-md-3">
<label class="form-label">PO Num</label>
<input class="form-control" value="{{ $po->pdf_po_refno }}" readonly>
</div>
<div class="col-md-3">
<label class="form-label">Order Qty</label>
<input class="form-control" value="{{ number_format($activeItems->sum('item_order_qty')) }}" readonly>
</div>
<div class="col-md-3">
<label class="form-label">Order Value</label>
<input class="form-control" value="{{ number_format($activeItems->sum(fn($i)=>round($i->item_order_qty*$i->revised_unit_price,2)),2) }}" readonly>
</div>
<div class="col-md-3">
<label class="form-label">Active Item Rows</label>
<input class="form-control" value="{{ $activeItems->count() }}" readonly>
</div>
<div class="col-md-6">
<label class="form-label">Sold To</label>
<input class="form-control mb-2" value="{{ $po->customer_name }}" readonly>
<label for="customer_addr" class="visually-hidden">Sold To address</label>
<textarea class="form-control" id="customer_addr" name="customer_addr" rows="3" maxlength="200" required>{{ old('customer_addr',$po->customer_addr) }}</textarea>
</div>
<div class="col-md-6">
<label for="delivery_address_id" class="form-label">Ship To</label>
<select id="delivery_address_id" name="delivery_address_id" class="form-select mb-2">
<option value="">Keep / enter order delivery address</option>@foreach($addresses as $address)<option value="{{ $address->deliaddr_id }}" data-address="{{ $address->delivery_addr }}" @selected((string)old('delivery_address_id') === (string)$address->deliaddr_id)>{{ $address->delilocation_name }}</option>@endforeach</select>
<label for="delivery_addr" class="visually-hidden">Ship To address</label>
<textarea class="form-control" id="delivery_addr" name="delivery_addr" rows="3" maxlength="200" required>{{ old('delivery_addr',$po->delivery_addr) }}</textarea>
</div>
</div>
</fieldset>@unless($locked)<div class="card-footer text-end">
<button class="btn btn-primary">Update Order</button>
</div>@endunless</form>
<script>
document.getElementById('delivery_address_id').addEventListener('change', function () { const address=this.selectedOptions[0].dataset.address; if(address !== undefined) document.getElementById('delivery_addr').value=address; });
</script>
