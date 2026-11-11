<?php

namespace App\Services;

use App\Models\Erav\EravPurchaseOrder;
use App\Models\Erav\EravSalesDeliveryAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class SalesContractService
{
    public function updateHeader(int $id, array $data, User $user): void
    {
        $this->change($id, $user, 'Order details updated', function ($po) use ($data) {
            $addressId = $data['delivery_address_id'] ?? null;
            unset($data['delivery_address_id']);
            if ($addressId) {
                $address = EravSalesDeliveryAddress::where('customer_id', $po->customer_id)
                    ->where('qrycancel', 0)->find($addressId);
                if (! $address) {
                    throw ValidationException::withMessages(['delivery_address_id' => 'Choose an active delivery address belonging to this customer.']);
                }
            }
            foreach ($data as $key => $value) {
                $data[$key] = $value ?? (in_array($key, ['ship_date', 'indc_date']) ? null : '');
            }
            $po->fill($data);
        });
    }

    public function updateItems(int $id, array $rows, User $user, bool $prices): void
    {
        $this->change($id, $user, $prices ? 'Prices updated' : 'Packing updated', function ($po) use ($rows, $prices) {
            foreach ($rows as $row) {
                $item = $po->items->firstWhere('id', (int) $row['id']);
                if (! $item || $item->qrycancel) {
                    throw ValidationException::withMessages(['items' => 'Every item must be an active item belonging to this order.']);
                }
                $keys = $prices ? ['revised_unit_price'] : ['master_packqty', 'case_packqty'];
                foreach ($keys as $key) {
                    $item->$key = $row[$key];
                }
            }
        });
    }

    public function finalize(int $id, User $user): void
    {
        $this->change($id, $user, 'Order finalized', function ($po) use ($user) {
            if (! $po->order_conf_refno || ! $po->customer_id || ! $po->customer_addr || ! $po->delivery_addr || ! $po->porder_date || ! $po->ship_date || ! $po->indc_date) {
                throw ValidationException::withMessages(['order' => 'Complete the confirmation reference, customer addresses, order date, X-factory date and In-DC date before finalizing.']);
            }
            if (substr($po->indc_date, 0, 10) < substr($po->ship_date, 0, 10)) {
                throw ValidationException::withMessages(['order' => 'In-DC date cannot precede the X-factory date.']);
            }
            $active = $po->items->where('qrycancel', 0);
            if ($active->isEmpty() || $active->contains(fn ($i) => ! $i->item_styleno || $i->item_order_qty <= 0 || ! is_numeric($i->revised_unit_price) || $i->revised_unit_price < 0)) {
                throw ValidationException::withMessages(['items' => 'The order must have valid active items, quantities and revised prices.']);
            }
            $po->qryapproved = 1;
            $po->approved_by = mb_substr($user->name, 0, 20);
            $po->approved_at = now()->format('Y-m-d H:i:s');
        });
    }

    private function change(int $id, User $user, string $description, callable $callback): void
    {
        DB::connection('erav')->transaction(function () use ($id, $user, $description, $callback) {
            $po = EravPurchaseOrder::where('isoffline', 0)->lockForUpdate()->findOrFail($id);
            if ($po->qryapproved || $po->qryclosed) {
                throw ValidationException::withMessages(['order' => 'This order is finalized or closed and cannot be edited.']);
            }
            $po->setRelation('items', $po->items()->lockForUpdate()->get());
            $before = $this->snapshot($po);
            $callback($po);
            $actor = mb_substr($user->name, 0, 20);
            foreach ($po->items as $item) {
                if ($item->isDirty()) {
                    $item->updated_by = $actor;
                    $item->updated_at = now()->format('Y-m-d H:i:s');
                    $item->save();
                }
            }
            $active = $po->items->where('qrycancel', 0);
            $po->porder_tot_pcs = $active->sum('item_order_qty');
            $po->porder_tot_val = round($active->sum(fn ($i) => round($i->item_order_qty * $i->revised_unit_price, 2)), 2);
            $po->updated_by = $actor;
            $po->updated_at = now()->format('Y-m-d H:i:s');
            $po->save();
            // Audit entry and order share the erav transaction.
            $entry = new Activity;
            $entry->setConnection('erav');
            $entry->log_name = 'sales-contract';
            $entry->description = $description;
            $entry->subject_type = $po->getMorphClass();
            $entry->subject_id = $po->id;
            $entry->causer_type = $user->getMorphClass();
            $entry->causer_id = $user->id;
            $entry->properties = collect(['actor' => $user->name, 'before' => $before, 'after' => $this->snapshot($po)]);
            $entry->save();
        });
    }

    private function snapshot(EravPurchaseOrder $po): array
    {
        return ['header' => $po->getAttributes(), 'items' => $po->items->mapWithKeys(fn ($i) => [$i->id => $i->getAttributes()])->all()];
    }
}
