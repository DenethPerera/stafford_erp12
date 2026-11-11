<div class="modal fade" id="packingModal" tabindex="-1" aria-labelledby="packingTitle" aria-hidden="true">
<div class="modal-dialog">
<form class="modal-content" method="POST" action="{{ route('purchase-orders.sales-contracts.packing.update',$po->id) }}">@csrf @method('PUT')<div class="modal-header">
<h5 id="packingTitle" class="modal-title">Update Pack Details</h5>
<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close">
</button>
</div>
<div class="modal-body">
<label for="packItem" class="form-label">Item</label>
<select class="form-select mb-3" name="items[0][id]" id="packItem" required>
<option value="">Select item</option>@foreach($activeItems as $item)<option value="{{ $item->id }}" data-master="{{ $item->master_packqty }}" data-case="{{ $item->case_packqty }}">#{{ $item->item_seqno }} ? {{ $item->item_styleno }} / {{ $item->item_colordesc }} / {{ $item->size_code }}</option>@endforeach</select>
<label class="form-label" for="masterPack">Master Pack Qty</label>
<input class="form-control mb-3" id="masterPack" name="items[0][master_packqty]" type="number" min="0" max="2147483647" step="1" required>
<label class="form-label" for="casePack">Case Pack Qty</label>
<input class="form-control" id="casePack" name="items[0][case_packqty]" type="number" min="0" max="2147483647" step="1" required>
</div>
<div class="modal-footer">
<button class="btn btn-primary">Save Packing</button>
</div>
</form>
</div>
</div>
<script>document.getElementById('packItem').addEventListener('change',function(){document.getElementById('masterPack').value=this.selectedOptions[0].dataset.master ?? '';document.getElementById('casePack').value=this.selectedOptions[0].dataset.case ?? '';});</script>
