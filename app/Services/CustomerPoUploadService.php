<?php

namespace App\Services;

use App\Models\Erav\EravPurchaseOrder;
use App\Models\Erav\EravPurchaseOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerPoUploadService
{
    private const CONN        = 'erav';
    private const TABLE_LIST  = 'porder_pdf_list';
    private const TABLE_INFO  = 'porder_pdf_info';

    /** @var array<string, array<string, object>> cache of SHOW COLUMNS results */
    protected array $columnCache = [];

    /* =====================================================================
     |  PARSING
     * ===================================================================*/

    /**
     * Parse a Non-FTP CSV / Excel file.
     * Supports the "sample layout" (PO header rows on top + item table)
     * and flat tabular files (headings row + item rows).
     */
    public function parseNonFtp(string $filePath, object $customer): array
    {
        $rows = $this->readFileRows($filePath);

        if (empty($rows)) {
            throw ValidationException::withMessages(['csv_file' => 'The uploaded file is empty.']);
        }

        if ($this->isSampleLayout($rows)) {
            return $this->parseSampleLayoutNonFtp($rows, $customer);
        }

        return $this->parseTabularNonFtp($rows, $customer);
    }

    protected function isSampleLayout(array $rows): bool
    {
        if (count($rows) < 6) {
            return false;
        }

        $row1Text = strtoupper(trim((string) ($rows[1][0] ?? '')));
        $row2Text = strtoupper(trim((string) ($rows[2][0] ?? '')));

        return str_contains($row1Text, 'PO NO') || str_contains($row2Text, 'ORDER DATE');
    }

    /**
     * Sample layout: header metadata followed by a named item table.
     */
    protected function parseSampleLayoutNonFtp(array $rows, object $customer): array
    {
        $poCell = trim((string) ($rows[1][1] ?? ''));
        $dateCell = trim((string) ($rows[2][1] ?? ''));
        $customerNo = trim((string) ($rows[3][1] ?? ''));
        if ($poCell === '') {
            $poCell = preg_replace('/^PO\s*No[.:]?\s*/i', '', trim((string) ($rows[1][0] ?? '')));
        }
        if ($dateCell === '') {
            $dateCell = preg_replace('/^Order\s*Date[.:]?\s*/i', '', trim((string) ($rows[2][0] ?? '')));
        }
        if ($customerNo === '') {
            $customerNo = preg_replace('/^Customer\s*No[.:]?\s*/i', '', trim((string) ($rows[3][0] ?? '')));
        }
        if ($poCell === '') {
            $this->nonFtpError(2, 'PO number is required.');
        }
        $expectedRef = trim((string) ($customer->customer_ref ?? ''));
        if ($customerNo === '' || $expectedRef === '') {
            $this->nonFtpError(4, 'Customer No. is required, and the selected customer must have a reference.');
        }
        if (mb_strtolower(trim($customerNo)) !== mb_strtolower($expectedRef)) {
            $this->nonFtpError(4, 'File Customer No. does not match the selected customer reference.');
        }
        $this->validateNonFtpLengths(['po_number' => $poCell, 'vendor' => $customerNo], 2);
        $defaults = [
            'po_number' => $poCell,
            'order_date' => $this->nonFtpDate($dateCell, 3),
            // Customer No must match customer_ref. Preserve the existing
            // supplier/account field mapping; no database schema change.
            'customer_no' => $customerNo,
        ];
        [$headerIndex, $map] = $this->findNonFtpHeader($rows, 4);
        return $this->parseNonFtpItems($rows, $headerIndex, $map, $customer, $defaults);
    }

    protected function parseTabularNonFtp(array $rows, object $customer): array
    {
        [$headerIndex, $map] = $this->findNonFtpHeader($rows);
        foreach (['po_number' => 'PO number', 'order_date' => 'Order date', 'customer_ref' => 'Customer Ref'] as $field => $label) {
            if (!isset($map[$field])) {
                $this->nonFtpError($headerIndex + 1, "{$label} column is required.");
            }
        }
        return $this->parseNonFtpItems($rows, $headerIndex, $map, $customer);
    }

    /**
     * Scan named headers without guessing a fixed row or fixed column order.
     */
    protected function findNonFtpHeader(array $rows, int $start = 0): array
    {
        foreach ($rows as $index => $row) {
            if ($index < $start || $this->isEmptyRow($row)) {
                continue;
            }
            $map = $this->mapHeadings($row);
            foreach ($row as $column => $label) {
                $clean = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $label))), '_');
                if (in_array($clean, ['customer_id', 'customer_ref', 'customer_reference'], true)) {
                    $map[$clean === 'customer_id' ? 'customer_id' : 'customer_ref'] = $column;
                }
            }
            if (!isset($map['style'], $map['qty'], $map['cost'])) {
                continue;
            }
            // Reject duplicate recognised headings, including alias collisions.
            $seen = [];
            foreach ($row as $label) {
                $single = $this->mapHeadings([$label]);
                $clean = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $label))), '_');
                if (in_array($clean, ['customer_id', 'customer_ref', 'customer_reference'], true)) {
                    $single = [$clean === 'customer_id' ? 'customer_id' : 'customer_ref' => 0];
                }
                foreach (array_keys($single) as $field) {
                    if (isset($seen[$field])) {
                        $this->nonFtpError($index + 1, "Duplicate/ambiguous heading for {$field}.");
                    }
                    $seen[$field] = true;
                }
            }
            return [$index, $map];
        }
        throw ValidationException::withMessages([
            'csv_file' => 'Could not find an item header containing Style, Qty and Cost. No fixed-row fallback was used.',
        ]);
    }

    protected function parseNonFtpItems(
        array $rows,
        int $headerIndex,
        array $map,
        object $customer,
        array $defaults = []
    ): array {
        $groups = [];
        $seenItems = [];
        $seenSequences = [];
        $headerWidth = count($rows[$headerIndex]);
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;
            if ($this->isEmptyRow($row)) {
                continue;
            }
            if (!$this->isEmptyRow(array_slice($row, $headerWidth))) {
                $this->nonFtpError($rowNum, 'Unexpected data beyond the header columns. Check CSV quoting.');
            }
            $get = fn($key) => isset($map[$key])
                ? trim((string) ($row[$map[$key]] ?? ''))
                : (string) ($defaults[$key] ?? '');

            foreach (['customer_id', 'customer_ref', 'customer_name'] as $field) {
                if (isset($map[$field])) {
                    $actual = $get($field);
                    $expected = trim((string) ($customer->$field ?? ''));
                    if ($actual === '' || $expected === ''
                        || mb_strtolower($actual) !== mb_strtolower($expected)) {
                        $this->nonFtpError($rowNum, "{$field} does not match the selected customer.");
                    }
                }
            }
            $poNumber = $get('po_number');
            $style = $get('style');
            if ($poNumber === '' || $style === '') {
                $this->nonFtpError($rowNum, 'PO number and Style are required. No item rows were skipped.');
            }
            $qtyValue = $this->normalizeAbNumber($get('qty'));
            $costValue = $this->normalizeAbNumber($get('cost'), true);
            if ($qtyValue === null || (float) $qtyValue < 1 || (float) $qtyValue > 2147483647) {
                $this->nonFtpError($rowNum, 'Quantity must be a positive whole number; use commas only in groups of three.');
            }
            if ($costValue === null || !is_finite((float) $costValue)) {
                $this->nonFtpError($rowNum, 'Cost must use a decimal dot, valid thousands groups and at most four decimal places.');
            }
            $header = [
                'po_number' => $poNumber,
                'order_date' => $this->nonFtpDate($get('order_date'), $rowNum),
                'customer_id' => $customer->customer_id,
                'customer_ref' => $customer->customer_ref ?? '',
                'customer_name' => $customer->customer_name ?? '',
                'customer_addr' => $customer->customer_addr ?? '',
                'delivery_addr' => $get('delivery_addr') !== '' ? $get('delivery_addr') : ($customer->customer_addr ?? ''),
                'season' => $get('season'),
                'vendor' => $get('customer_no'),
            ];
            $item = [
                'style' => $style,
                'description' => $get('description') !== '' ? $get('description') : $style,
                'color_code' => $get('color_code') !== '' ? $get('color_code') : '-',
                'color_name' => $get('color_name'),
                'size' => $get('size'),
                'upc' => $get('upc') !== '' ? $get('upc') : null,
                'qty' => (int) $qtyValue,
                'unit_price' => round((float) $costValue, 4),
            ];
            $item['line_total'] = round($item['qty'] * $item['unit_price'], 2);
            if (!is_finite($item['line_total'])) {
                $this->nonFtpError($rowNum, 'Line total is outside the supported numeric range.');
            }
            $this->validateNonFtpLengths(array_merge($header, $item), $rowNum);
            $groupKey = $customer->customer_id . ':' . $poNumber;
            if (isset($groups[$groupKey])) {
                foreach (['order_date', 'delivery_addr', 'season', 'vendor'] as $field) {
                    if ($groups[$groupKey][$field] !== $header[$field]) {
                        $this->nonFtpError($rowNum, "PO {$poNumber} has conflicting {$field} values.");
                    }
                }
            }
            $explicitSeq = $get('seq_no');
            if ($explicitSeq !== ''
                && (!preg_match('/^[0-9]+$/D', $explicitSeq)
                    || (float) $explicitSeq < 1 || (float) $explicitSeq > 2147483647)) {
                $this->nonFtpError($rowNum, 'Sequence number must be a positive integer.');
            }
            $seq = $explicitSeq !== '' ? (int) $explicitSeq : count($groups[$groupKey]['items'] ?? []) + 1;
            if (isset($seenSequences[$groupKey][$seq])) {
                $this->nonFtpError($rowNum, "Duplicate sequence {$seq} in PO {$poNumber}.");
            }
            // Explicit distinct line numbers can represent intentional repeated
            // products. Without them, identical business rows require review.
            $identity = $item;
            if ($explicitSeq !== '') {
                $identity['seq_no'] = $seq;
            }
            $fingerprint = serialize($identity);
            if (isset($seenItems[$groupKey][$fingerprint])) {
                $first = $seenItems[$groupKey][$fingerprint];
                $this->nonFtpError($rowNum, "Duplicate item in PO {$poNumber} (first seen at row {$first}). Use distinct SeqNo values for intentional separate lines.");
            }
            $seenItems[$groupKey][$fingerprint] = $rowNum;
            $seenSequences[$groupKey][$seq] = true;
            $groups[$groupKey] ??= $header + ['items' => []];
            $item = ['seq_no' => $seq] + $item;
            $groups[$groupKey]['items'][] = $item;
        }
        if (!$groups) {
            throw ValidationException::withMessages(['csv_file' => 'No line item data found.']);
        }
        // All rows are validated before duplicate DB lookups or preview results.
        return array_values(array_map(fn($g) => $this->finalizeGroup($g), $groups));
    }

    protected function validateNonFtpLengths(array $values, int $rowNum): void
    {
        // STL- + PO must fit the existing 25-character confirmation reference.
        $limits = ['po_number' => 21, 'style' => 50, 'description' => 200,
            'color_code' => 50, 'color_name' => 100, 'size' => 50, 'upc' => 50,
            'customer_name' => 50, 'customer_addr' => 200, 'delivery_addr' => 200,
            'season' => 50, 'vendor' => 15];
        foreach ($limits as $field => $limit) {
            if (isset($values[$field]) && mb_strlen((string) $values[$field]) > $limit) {
                $this->nonFtpError($rowNum, "{$field} exceeds {$limit} characters; shorten it before import.");
            }
        }
    }

    protected function nonFtpDate(string $value, int $rowNum): string
    {
        try {
            return $this->normalizeDate($value);
        } catch (ValidationException $e) {
            $this->nonFtpError($rowNum, 'Order date is missing or invalid.');
        }
    }

    protected function nonFtpError(int $rowNum, string $message): never
    {
        throw ValidationException::withMessages(['csv_file' => "Row {$rowNum}: {$message}"]);
    }

    /**
     * Parse the 37-column Alpha Broder FTP CSV. Rows are grouped by PO number (col 0).
     */
    public function parseAbFtp(string $filePath, ?object $customer = null): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw ValidationException::withMessages(['ftp_file' => 'Could not open FTP file on server.']);
        }

        try {
            $headerRow = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$headerRow) {
                throw ValidationException::withMessages(['ftp_file' => 'FTP CSV file is empty.']);
            }

            $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]);

            $expected = explode(',', 'PO,Master PO,Created On,Last Modified On,X-Factory Date,In-DC Date,Quantity,Original Quantity,Vendor,Factory Name,Destination,Destination Name,INCOTERMS,MILL CODE,BRAND,FACTORY,ON BOARD DATE,CARRIER,EXPECTED RECEIPT DATE,COUNTRY OF ORIGIN,Style,Season,Description,Color,Selling Description,Size,Universal Product Code,Original Quantity,Quantity,Quantity Shipped,Master Pack Group,Case Pack Ratio,Unit Cost,Currency,Total Cost,Expected Receipt Date,Acknowledged');
            $normalise = fn($v) => strtolower(trim((string) $v));
            if (array_map($normalise, array_slice($headerRow, 0, 37)) !== array_map($normalise, $expected)
                || array_filter(array_slice($headerRow, 37), fn($v) => trim((string) $v) !== '')) {
                throw ValidationException::withMessages([
                    'ftp_file' => 'Invalid AB-FTP headers. Expected the standard 37 columns in their original order. Only empty trailing columns are allowed.',
                ]);
            }

            $poGroups = [];
            $rowNum   = 1;
            $customerCache = [];

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rowNum++;
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $row = $this->normalizeAbFtpRow($row, $rowNum);
                if (($error = $this->abFtpRowError($row)) !== null) {
                    throw ValidationException::withMessages(['ftp_file' => "Row {$rowNum}: {$error}"]);
                }
                $dates = [];
                foreach ([2, 4, 5] as $index) {
                    // Already strictly validated by abFtpRowError().
                    $dates[$index] = \DateTimeImmutable::createFromFormat('!Ymd', trim((string) $row[$index]))->format('Y-m-d');
                }
                $poNumber = trim((string) ($row[0] ?? ''));
                if ($poNumber === '') {
                    continue;
                }

                $rowCustomer = $this->resolveAbCustomer(
                    (string) $row[8],
                    $rowNum,
                    $customerCache
                );

                // Compare supplies a selected customer: never compare another account.
                if ($customer !== null
                    && (int) $customer->customer_id !== (int) $rowCustomer->customer_id) {
                    throw ValidationException::withMessages([
                        'ftp_file' => "Row {$rowNum}: PO {$poNumber} belongs to "
                            . $rowCustomer->customer_ref
                            . ', which differs from the selected customer.',
                    ]);
                }

                $groupKey = $rowCustomer->customer_id . ':' . $poNumber;

                $style   = trim((string) ($row[20] ?? ''));
                $rawQty  = $this->normalizeAbNumber((string) $row[28]);
                $rawCost = $this->normalizeAbNumber((string) $row[32], true);

                $qty       = (int) $rawQty;
                $unitPrice = round((float) $rawCost, 4);

                if (!isset($poGroups[$groupKey])) {
                    $orderDate = $dates[2];
                    $shipDate  = $dates[4];
                    $indcDate  = $dates[5];

                    $vendor          = trim((string) ($row[8] ?? ''));
                    $destinationName = trim((string) ($row[11] ?? ($row[10] ?? '')));
                    $incoterms       = trim((string) ($row[12] ?? ''));
                    $carrier         = trim((string) ($row[17] ?? ''));
                    $season          = trim((string) ($row[21] ?? ''));

                    $poGroups[$groupKey] = [
                        'po_number'     => $poNumber,
                        'order_date'    => $orderDate ?: now()->format('Y-m-d'),
                        'customer_id'   => $rowCustomer->customer_id,
                        'customer_ref'  => $rowCustomer->customer_ref ?? '',
                        'customer_name' => $rowCustomer->customer_name ?? '',
                        'customer_addr' => $rowCustomer->customer_addr ?? '',
                        'delivery_addr' => $destinationName ?: ($rowCustomer->customer_addr ?? ''),
                        'ship_date'     => $shipDate,
                        'indc_date'     => $indcDate,
                        'ship_term'     => $incoterms,
                        'fob_code'      => $incoterms ?: null,
                        'ship_method'   => $carrier ?: 'Sea',
                        'vendor'        => $vendor,
                        'season'        => $season,
                        'items'         => [],
                    ];
                }

                $seq = count($poGroups[$groupKey]['items']) + 1;

                $poGroups[$groupKey]['items'][] = [
                    'seq_no'      => $seq,
                    'style'       => $style,
                    'description' => trim((string) ($row[22] ?? '')) ?: $style,
                    'color_code'  => trim((string) ($row[23] ?? '')) ?: '-',
                    'color_name'  => trim((string) ($row[24] ?? '')),
                    'size'        => trim((string) ($row[25] ?? '')),
                    'upc'         => trim((string) ($row[26] ?? '')) ?: null,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,
                    'line_total'  => round($qty * $unitPrice, 2),
                    'master_pack' => (int) $this->normalizeAbNumber((string) $row[30]),
                    'case_pack'   => (int) $this->normalizeAbNumber((string) $row[31]),
                ];
            }


            if (empty($poGroups)) {
                throw ValidationException::withMessages(['ftp_file' => 'No line item data found in the AB-FTP CSV file.']);
            }

            return array_values(array_map(fn($g) => $this->finalizeGroup($g), $poGroups));
        } finally {
            fclose($handle);
        }
    }
    protected function resolveAbCustomer(
    string $vendor,
    int $rowNum,
    array &$cache
): object {
    // Use only after this business mapping is confirmed.
    $referenceByVendor = [
        '6734'  => 'AB-US',
        '51049' => 'AB-CND',
    ];

    // Treat 06734 and 6734 as the same numeric vendor code.
    $vendorKey = ltrim(trim($vendor), '0');
    $reference = $referenceByVendor[$vendorKey] ?? null;

    if ($reference === null) {
        throw ValidationException::withMessages([
            'ftp_file' =>
                "Row {$rowNum}: Unknown AlphaBroder vendor '{$vendor}'.",
        ]);
    }

    if (!isset($cache[$reference])) {
        $matches = DB::connection(self::CONN)
            ->table('salescustomerlist')
            ->where('customer_ref', $reference)
            ->where('isoffline', 0)
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'ftp_file' =>
                    "Row {$rowNum}: Expected one active customer for {$reference}.",
            ]);
        }

        $cache[$reference] = $matches->first();
    }

    return $cache[$reference];
}


    /**
     * Recover a single split text field only when one schema alignment is valid.
     * No file names, row ranges, destinations or province names are assumed.
     */
    protected function normalizeAbFtpRow(array $row, int $rowNum): array
    {
        // Preserve standard rows; retain compatibility with empty trailing cells.
        if (count($row) === 37) {
            return $row;
        }
        if (count($row) > 37 && !array_filter(array_slice($row, 37), fn($v) => trim((string) $v) !== '')) {
            return array_slice($row, 0, 37);
        }

        $extra = count($row) - 37;
        $candidates = [];
        if ($extra > 0 && $extra <= 8) {
            // Free text fields in the verified header. Codes, numbers and dates
            // cannot absorb a comma. Multiple independently split fields are
            // deliberately not inferred.
            foreach ([9, 11, 14, 21, 22, 24] as $index) {
                $candidate = $row;
                $parts = array_slice($row, $index, $extra + 1);
                if (count(array_filter($parts, fn($v) => trim((string) $v) !== '')) !== count($parts)) {
                    continue;
                }
                array_splice($candidate, $index, $extra + 1, [implode(',', $parts)]);
                if ($this->abFtpRowError($candidate, true) === null) {
                    $candidates[] = $candidate;
                }
            }
        }

        if (count($candidates) !== 1) {
            throw ValidationException::withMessages(['ftp_file' =>
                "Row {$rowNum}: Found " . count($row) . ' columns; expected 37. '
                . (count($candidates) > 1 ? 'Ambiguous text-field alignment.' : 'No safe text-field alignment found.')
                . ' Re-export comma-containing fields with CSV quotes.']);
        }

        return $candidates[0];
    }

    /**
     * Check schema anchors, including unused fields, to avoid accepting a shifted
     * row merely because its quantity happens to be numeric.
     */

    /**
     * AB exports use a decimal dot and optional groups of three thousands digits.
     * Return null for malformed values; never guess a decimal-comma conversion.
     */
    protected function normalizeAbNumber(string $value, bool $decimal = false): ?string
    {
        $value = trim($value);
        // Preserve support for a single leading dollar sign on cost fields.
        if ($decimal && str_starts_with($value, '$')) {
            $value = substr($value, 1);
        }
        $integer = '(?:[0-9]+|[1-9][0-9]{0,2}(?:,[0-9]{3})+)';
        $pattern = '/^' . $integer . ($decimal ? '(?:\.[0-9]{1,4})?' : '') . '$/D';
        if (!preg_match($pattern, $value)) {
            return null;
        }

        return str_replace(',', '', $value);
    }

    protected function abFtpRowError(array $row, bool $repair = false): ?string
    {
        if (count($row) !== 37) {
            return 'Expected 37 columns.';
        }
        $value = fn($i) => trim((string) $row[$i]);
        foreach ([0 => 50, 20 => 50, 25 => 50] as $i => $limit) {
            if ($value($i) === '' || mb_strlen($value($i)) > $limit) {
                return 'PO, Style and Size are required (maximum 50 characters).';
            }
        }
        // Prevent the existing save mappings from silently truncating CSV fields.
        foreach ([8 => 15, 11 => 200, 12 => 20, 17 => 20, 21 => 50, 22 => 200, 23 => 50, 24 => 100, 26 => 50] as $i => $limit) {
            if (mb_strlen($value($i)) > $limit) {
                return 'Column ' . ($i + 1) . " exceeds the supported {$limit} characters.";
            }
        }
        foreach ([2, 3, 4, 5, 16, 18, 35] as $i) {
            $v = $value($i);
            if ($v === '' && !in_array($i, [2, 4, 5], true)) {
                continue;
            }
            $date = preg_match('/^\d{8}$/D', $v) ? \DateTimeImmutable::createFromFormat('!Ymd', $v) : false;
            if (!$date || $date->format('Ymd') !== $v) {
                return 'Column ' . ($i + 1) . ' must contain a valid YYYYMMDD date.';
            }
        }
        foreach ([6, 7, 27, 28, 29, 30, 31] as $i) {
            $v = $value($i);
            if ($v === '' && $i !== 28) {
                continue;
            }
            $v = $this->normalizeAbNumber($v);
            if ($v === null || (float) $v > 2147483647 || ($i === 28 && (float) $v < 1)) {
                return 'Column ' . ($i + 1) . ' must contain a valid whole-number quantity.';
            }
        }
        foreach ([32, 34] as $i) {
            $v = $value($i);
            if ($v === '' && $i === 34 && !$repair) {
                continue;
            }
            $v = $this->normalizeAbNumber($v, true);
            if ($v === null || !is_finite((float) $v)) {
                return 'Column ' . ($i + 1) . ' must contain a non-negative cost with up to four decimal places.';
            }
        }
        if (!preg_match('/^[A-Z]{3}$/D', $value(33))) {
            return 'Currency must be a three-letter uppercase code.';
        }
        if (!in_array(strtolower($value(36)), ['yes', 'no', 'y', 'n', ''], true)) {
            return 'Acknowledged must be Yes/No or empty.';
        }
        if ($repair) {
            // Strong anchors for recovery only. Unknown commercial codes are
            // accepted on correctly aligned rows, but are not evidence to repair.
            if (!in_array(strtoupper($value(12)), ['EXW', 'FCA', 'FAS', 'FOB', 'CFR', 'CIF', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP', 'DDU', 'DAT', 'PPD', 'COL'], true)) {
                return 'Unrecognised INCOTERMS anchor.';
            }
            foreach ([8, 10, 13, 15, 17, 19, 20, 23, 25] as $i) {
                $pattern = in_array($i, [8, 10, 13, 15, 19, 20, 23], true)
                    ? '/^[A-Za-z0-9][A-Za-z0-9_\/.&+%-]*$/D'
                    : '/^[A-Za-z0-9][A-Za-z0-9 _\/.()&+%-]*$/D';
                if (!preg_match($pattern, $value($i))) {
                    return 'Invalid code anchor in column ' . ($i + 1) . '.';
                }
            }
            if (!preg_match('/^[A-Z]{2}$/D', $value(19))) {
                return 'Country of origin must be a two-letter anchor.';
            }
        }
        return null;
    }

    /**
     * Add totals + duplicate flags to a parsed PO group.
     */
    protected function finalizeGroup(array $group): array
    {
        $group['total_pcs'] = (int) array_sum(array_column($group['items'], 'qty'));
        $group['total_val'] = round((float) array_sum(array_column($group['items'], 'line_total')), 2);

        $dupId = $this->findDuplicateId($group['po_number'], (int) $group['customer_id']);
        $group['is_duplicate'] = $dupId !== null;
        $group['duplicate_id'] = $dupId;

        return $group;
    }

    protected function findDuplicateId(string $poNumber, int $customerId): ?int
    {
        $id = EravPurchaseOrder::where('pdf_po_refno', mb_substr(trim($poNumber), 0, 50))
            ->where('customer_id', $customerId)
            ->value('id');

        return $id ? (int) $id : null;
    }

    /* =====================================================================
     |  SAVING
     * ===================================================================*/

    /**
     * Save validated PO groups into porder_pdf_list (header) and porder_pdf_info (lines)
     * inside ONE transaction. Either everything is saved or nothing is.
     */
    public function saveCustomerPos(array $poGroups, string $originalFileName, object $user): array
    {
        $connection = DB::connection(self::CONN);
        $lock = $connection->selectOne("SELECT GET_LOCK('customer_po_upload', 10) AS acquired");
        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages(['preview_token' => 'Another PO import is running. Please try again shortly.']);
        }
        try {
            return $connection->transaction(function () use ($poGroups, $originalFileName, $user) {
                $savedPos          = 0;
                $savedItems        = 0;
                $totalPcs          = 0;
                $totalVal          = 0.0;
                $skippedDuplicates = 0;

                $now   = now()->format('Y-m-d H:i:s');
                $actor = mb_substr((string) ($user->name ?? $user->email ?? 'admin'), 0, 20);

                foreach ($poGroups as $poData) {
                    $poNumber = trim((string) $poData['po_number']);

                    if ($this->findDuplicateId($poNumber, (int) $poData['customer_id']) !== null) {
                        $skippedDuplicates++;
                        continue;
                    }

                    $headerData = [
                        'pdf_po_refno'       => mb_substr($poNumber, 0, 50),
                        'porder_date'        => $poData['order_date'] ?: now()->format('Y-m-d'),
                        'supplier_refno'     => mb_substr((string) ($poData['vendor'] ?? ''), 0, 15),
                        'pdf_file_name'      => mb_substr($originalFileName, 0, 200),
                        'file_owner'         => 'COMM0',
                        'porder_tot_pages'   => 1,
                        'porder_tot_pcs'     => (int) $poData['total_pcs'],
                        'porder_tot_val'     => (float) $poData['total_val'],
                        'customer_id'        => (int) $poData['customer_id'],
                        'customer_name'      => mb_substr((string) ($poData['customer_name'] ?? ''), 0, 50),
                        'customer_addr'      => mb_substr((string) ($poData['customer_addr'] ?? ''), 0, 200),
                        'delivery_addr'      => mb_substr((string) ($poData['delivery_addr'] ?? ($poData['customer_addr'] ?? '')), 0, 200),
                        'deli_term'          => isset($poData['deli_term']) ? mb_substr((string) $poData['deli_term'], 0, 50) : null,
                        'ship_date'          => $poData['ship_date'] ?? null,
                        'indc_date'          => $poData['indc_date'] ?? null,
                        'ship_term'          => mb_substr((string) ($poData['ship_term'] ?? ''), 0, 50),
                        'ship_method'        => mb_substr((string) ($poData['ship_method'] ?? 'Sea'), 0, 20),
                        'fob_code'           => isset($poData['fob_code']) ? mb_substr((string) $poData['fob_code'], 0, 20) : null,
                        'order_conf_refno'   => mb_substr('STL-' . $poNumber, 0, 50),
                        'stafford_doc_refno' => mb_substr('STL-' . $poNumber, 0, 50),
                        'item_dev_season'    => mb_substr((string) ($poData['season'] ?? ''), 0, 50),
                        'qryclosed'          => 0,
                        'qryapproved'        => 0,
                        'isoffline'          => 0,
                        'created_by'         => $actor,
                        'created_at'         => $now,
                    ];

                    $header = EravPurchaseOrder::create($this->prepareRow(self::TABLE_LIST, $headerData));

                    foreach ($poData['items'] as $item) {
                        $itemData = [
                            'porder_pdf_list_id' => $header->id,
                            'item_seqno'         => (int) $item['seq_no'],
                            'item_pageno'        => 1,
                            'item_styleno'       => mb_substr((string) ($item['style'] ?? ''), 0, 50),
                            'item_code'          => mb_substr((string) ($item['style'] ?? ''), 0, 50),
                            'item_description'   => mb_substr((string) ($item['description'] ?? ''), 0, 200),
                            'item_colorcode'     => mb_substr((string) ($item['color_code'] ?? '-'), 0, 50),
                            'item_colordesc'     => mb_substr((string) ($item['color_name'] ?? ''), 0, 100),
                            'item_extcolor'      => null,
                            'size_code'          => mb_substr((string) ($item['size'] ?? ''), 0, 50),
                            'upc_refno'          => isset($item['upc']) ? mb_substr((string) $item['upc'], 0, 50) : null,
                            'vendor_item_code'   => '',
                            'item_order_qty'     => (int) $item['qty'],
                            'receipt_unit_price' => (float) $item['unit_price'],
                            'revised_unit_price' => (float) $item['unit_price'],
                            'master_packqty'     => (int) ($item['master_pack'] ?? 0),
                            'case_packqty'       => (int) ($item['case_pack'] ?? 0),
                            'qrycancel'          => 0,
                            'created_by'         => $actor,
                            'created_at'         => $now,
                        ];

                        EravPurchaseOrderItem::create($this->prepareRow(self::TABLE_INFO, $itemData));
                        $savedItems++;
                    }

                    $savedPos++;
                    $totalPcs += (int) $poData['total_pcs'];
                    $totalVal += (float) $poData['total_val'];
                }

                return [
                    'saved_pos'          => $savedPos,
                    'saved_items'        => $savedItems,
                    'total_pcs'          => $totalPcs,
                    'total_val'          => round($totalVal, 2),
                    'skipped_duplicates' => $skippedDuplicates,
                ];
            });
        } finally {
            $connection->selectOne("SELECT RELEASE_LOCK('customer_po_upload') AS released");
        }
    }

    /**
     * Make a row safe for the legacy table:
     *  1. drops keys that do not exist as columns (and logs them, so you can fix the mapping)
     *  2. fills NOT NULL columns that have no default (legacy tables often have these)
     */
    protected function prepareRow(string $table, array $data): array
    {
        $columns = $this->tableColumns($table);
        if (empty($columns)) {
            return $data; // could not read schema - insert as-is
        }

        $dropped = array_diff_key($data, $columns);
        if (!empty($dropped)) {
            Log::warning("CustomerPoUpload: columns not found in {$table}, skipped: " . implode(', ', array_keys($dropped)));
        }

        $row = array_intersect_key($data, $columns);

        foreach ($columns as $name => $col) {
            if (array_key_exists($name, $row)) {
                continue;
            }

            $extra = strtolower((string) ($col->Extra ?? ''));
            if (($col->Null ?? 'YES') === 'NO'
                && ($col->Default ?? null) === null
                && !str_contains($extra, 'auto_increment')
                && !str_contains($extra, 'generated')) {
                $row[$name] = $this->defaultForType((string) $col->Type);
            }
        }

        return $row;
    }

    protected function tableColumns(string $table): array
    {
        if (isset($this->columnCache[$table])) {
            return $this->columnCache[$table];
        }

        $map = [];
        try {
            foreach (DB::connection(self::CONN)->select("SHOW COLUMNS FROM `{$table}`") as $col) {
                $map[$col->Field] = $col;
            }
        } catch (Throwable $e) {
            Log::warning("CustomerPoUpload: could not read columns of {$table}: " . $e->getMessage());
        }

        return $this->columnCache[$table] = $map;
    }

    protected function defaultForType(string $type)
    {
        $t = strtolower($type);

        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|bit)/', $t)) {
            return 0;
        }
        if (str_starts_with($t, 'datetime') || str_starts_with($t, 'timestamp')) {
            return now()->format('Y-m-d H:i:s');
        }
        if (str_starts_with($t, 'date')) {
            return now()->format('Y-m-d');
        }
        if (str_starts_with($t, 'time')) {
            return '00:00:00';
        }
        if (str_starts_with($t, 'year')) {
            return now()->format('Y');
        }
        if (str_starts_with($t, 'enum') && preg_match("/enum\('([^']*)'/i", $type, $m)) {
            return $m[1];
        }

        return '';
    }

    /* =====================================================================
     |  FILE / VALUE HELPERS
     * ===================================================================*/

    protected function readFileRows(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } catch (Throwable $e) {
                throw ValidationException::withMessages(['csv_file' => 'Could not read Excel file: ' . $e->getMessage()]);
            }
        }

        $rows   = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw ValidationException::withMessages(['csv_file' => 'Could not read the uploaded CSV file.']);
        }

        $first = true;
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($first && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                $first  = false;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    protected function mapHeadings(array $headerRow): array
    {
        $aliases = [
            'seq_no'        => ['seqno', 'sequence', 'sequence_no', 'line', 'line_no', 'line_number', 'item_no'],
            'po_number'     => ['po', 'po_no', 'po_number', 'po_num', 'purchase_order', 'purchase_order_no', 'order_no'],
            'order_date'    => ['date', 'order_date', 'po_date', 'purchase_order_date', 'created_date'],
            'customer_no'   => ['customer_no', 'customer_number', 'customer_code', 'buyer_no', 'account'],
            'customer_name' => ['organization', 'customer', 'customer_name', 'buyer', 'company'],
            'delivery_addr' => ['delivery_address', 'delivery_addr', 'ship_to_address', 'destination'],
            'season'        => ['season', 'dev_season'],
            'style'         => ['style', 'stylecode', 'style_code', 'style_no', 'item', 'item_code', 'sku', 'product_code'],
            'description'   => ['description', 'styledescription', 'style_description', 'item_description', 'product_description'],
            'color_code'    => ['colorcode', 'color_code', 'colour_code', 'color_no'],
            'color_name'    => ['color', 'colorname', 'color_name', 'colour_name', 'selling_description', 'colorway'],
            'size'          => ['size', 'size_code', 'size_name', 'garment_size'],
            'upc'           => ['upc', 'universal_product_code', 'barcode'],
            'qty'           => ['qty', 'quantity', 'order_qty', 'order_quantity', 'units'],
            'cost'          => ['cost', 'unit_cost', 'unit_price', 'price', 'rate'],
        ];

        $lookup = [];
        foreach ($aliases as $field => $names) {
            foreach ($names as $name) {
                $lookup[$name] = $field;
            }
        }

        $map = [];
        foreach ($headerRow as $idx => $label) {
            $cleaned = strtolower(trim((string) $label));
            $cleaned = trim(preg_replace('/[^a-z0-9]+/', '_', $cleaned), '_');
            if (isset($lookup[$cleaned])) {
                $map[$lookup[$cleaned]] = $idx;
            }
        }

        return $map;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $val) {
            if ($val !== null && trim((string) $val) !== '') {
                return false;
            }
        }
        return true;
    }

    protected function parseAbFtpDate(?string $value): ?string
    {
        $val = trim((string) $value);
        if ($val === '') {
            return null;
        }

        if (preg_match('/^\d{8}$/', $val)) {
            try {
                return Carbon::createFromFormat('Ymd', $val)->format('Y-m-d');
            } catch (Throwable $e) {
                return null;
            }
        }

        return $this->normalizeDate($val);
    }

    protected function normalizeDate(?string $val): string
    {
        $val = trim((string) $val);
        if ($val === '') {
            throw ValidationException::withMessages(['csv_file' => 'Order date is required.']);
        }

        // Excel serial date (e.g. 45605)
        if (is_numeric($val) && (float) $val > 20000 && (float) $val < 80000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $val)->format('Y-m-d');
            } catch (Throwable $e) {
                // fall through
            }
        }

        foreach (['Ymd', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $val);
            if ($date && $date->format($format) === $val) {
                return $date->format('Y-m-d');
            }
        }
        throw ValidationException::withMessages(['csv_file' => 'Invalid order date: ' . $val]);
    }
}
