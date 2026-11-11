<div class="modal fade" id="pricesModal" tabindex="-1" aria-labelledby="pricesTitle" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<form class="modal-content" method="POST" action="{{ route('purchase-orders.sales-contracts.prices.update',$po->id) }}">@csrf @method('PUT')<div class="modal-header">
<h5 id="pricesTitle" class="modal-title">Update Prices</h5>
<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close">
</button>
</div>
<div class="modal-body">
<p class="text-muted">Select an item to update. Original receipt prices are retained.</p>
<label for="priceItem" class="form-label">Item</label>
<select class="form-select mb-3" name="items[0][id]" id="priceItem" required>
<option value="">Select item</option>@foreach($activeItems as $item)<option value="{{ $item->id }}" data-price="{{ $item->revised_unit_price }}">#{{ $item->item_seqno }} ? {{ $item->item_styleno }} / {{ $item->item_colordesc }} / {{ $item->size_code }} / {{ $item->upc_refno }}</option>@endforeach</select>
<label class="form-label" for="revisedPrice">Revised unit price</label>
<input class="form-control" id="revisedPrice" name="items[0][revised_unit_price]" type="number" min="0" max="999999.9999" step="0.0001" required>
</div>
<div class="modal-footer">
<button class="btn btn-primary">Save Price</button>
</div>
</form>
</div>
</div>
<script>document.getElementById('priceItem').addEventListener('change',function(){document.getElementById('revisedPrice').value=this.selectedOptions[0].dataset.price ?? '';});</script>
