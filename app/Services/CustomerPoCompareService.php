<?php

namespace App\Services;

class CustomerPoCompareService
{
    /**
     * Conservative identity: style + colour code + size. UPC can be rounded by
     * spreadsheet exports, so it is compared as a field, never used as identity.
     * A changed size is an unmatched item, not an inferred replacement.
     */
    public function compare(array $incoming, ?array $existing): array
    {
        $result = ['po_number' => $incoming['po_number'], 'incoming' => $incoming,
            'status' => $existing === null ? 'New PO' : 'Unchanged',
            'header_changes' => [], 'items' => [], 'revised_count' => 0];
        if ($existing === null) {
            foreach ($incoming['items'] as $item) {
                $result['items'][] = ['status' => 'New PO item', 'old' => null, 'new' => $item, 'changes' => []];
            }
            return $result;
        }
        foreach (['order_date' => 'Order date', 'ship_date' => 'Ship date', 'indc_date' => 'In-DC date',
            'delivery_addr' => 'Destination', 'ship_term' => 'Shipping terms', 'ship_method' => 'Carrier',
            'season' => 'Season', 'vendor' => 'Vendor'] as $field => $label) {
            $old = $this->text($existing[$field] ?? null);
            $new = $this->text($incoming[$field] ?? null);
            if (str_ends_with($field, 'date')) {
                $old = substr($old, 0, 10);
                $new = substr($new, 0, 10);
            }
            if ($old !== $new) {
                $result['header_changes'][] = ['field' => $label, 'old' => $old, 'new' => $new];
            }
        }
        $oldBuckets = $this->buckets($existing['items']);
        $newBuckets = $this->buckets($incoming['items']);
        foreach (array_unique(array_merge(array_keys($oldBuckets), array_keys($newBuckets))) as $key) {
            $old = $oldBuckets[$key] ?? [];
            $new = $newBuckets[$key] ?? [];
            if (count($old) > 1 || count($new) > 1) {
                foreach ($old as $item) {
                    $result['items'][] = ['status' => 'Ambiguous DB item', 'old' => $item, 'new' => null, 'changes' => []];
                }
                foreach ($new as $item) {
                    $result['items'][] = ['status' => 'Ambiguous CSV item', 'old' => null, 'new' => $item, 'changes' => []];
                }
                continue;
            }
            $before = $old[0] ?? null;
            $after = $new[0] ?? null;
            $changes = [];
            if ($before && $after) {
                foreach (['qty' => 'Quantity', 'unit_price' => 'Unit price', 'description' => 'Description',
                    'color_name' => 'Colour name', 'upc' => 'UPC', 'master_pack' => 'Master pack', 'case_pack' => 'Case pack'] as $field => $label) {
                    $a = $this->text($before[$field] ?? null);
                    $b = $this->text($after[$field] ?? null);
                    $same = in_array($field, ['qty', 'master_pack', 'case_pack'], true)
                        ? (int) $a === (int) $b
                        : ($field === 'unit_price' ? round((float) $a, 4) === round((float) $b, 4) : $a === $b);
                    if (!$same) {
                        $changes[] = ['field' => $label, 'old' => $a, 'new' => $b];
                    }
                }
            }
            $status = !$before ? 'New item' : (!$after ? 'Not in CSV' : ($changes ? 'Changed' : 'Unchanged'));
            if ($status === 'Changed') {
                $result['revised_count']++;
            }
            $result['items'][] = ['status' => $status, 'old' => $before, 'new' => $after, 'changes' => $changes];
        }
        if ($result['header_changes'] || array_filter($result['items'], fn($i) => $i['status'] !== 'Unchanged')) {
            $result['status'] = 'Review required';
        }
        return $result;
    }

    private function text($value): string
    {
        return trim((string) $value);
    }

    private function buckets(array $items): array
    {
        $buckets = [];
        foreach ($items as $item) {
            $key = json_encode(array_map(fn($field) => $this->text($item[$field] ?? null), ['style', 'color_code', 'size']));
            $buckets[$key][] = $item;
        }
        return $buckets;
    }
}
