<?php

namespace Tests\Unit;

use App\Services\CustomerPoUploadService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class NonFtpCustomerPoUploadTest extends TestCase
{
    private CustomerPoUploadService $service;
    private string $path;
    private array $extraPaths = [];
    private array $header;
    private array $row;
    private object $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container;
        $container->instance('validator', new Factory(new Translator(new ArrayLoader, 'en'), $container));
        Facade::setFacadeApplication($container);
        $this->service = new class extends CustomerPoUploadService {
            public int $duplicateLookups = 0;
            protected function findDuplicateId(string $poNumber, int $customerId): ?int
            {
                $this->duplicateLookups++;
                return $poNumber === 'EXISTING' ? 99 : null;
            }
        };
        $this->path = tempnam(sys_get_temp_dir(), 'nonftp-po-');
        $this->customer = (object) ['customer_id'=>7, 'customer_ref'=>'ACME', 'customer_name'=>'Acme', 'customer_addr'=>'Address'];
        $this->header = ['PO', 'Order Date', 'Style', 'Description', 'Color Code', 'Color Name', 'Size', 'UPC', 'Qty', 'Cost', 'SeqNo', 'Destination', 'Season', 'Customer No', 'Customer Ref'];
        $this->row = ['P-001', '2026-11-13', 'STYLE', 'Shirt', 'NV', 'Navy', 'L', '001234', '10', '2.5', '', 'Warehouse', 'Summer', 'EXT-123', 'ACME'];
    }

    protected function tearDown(): void
    {
        foreach (array_merge([$this->path], $this->extraPaths) as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        parent::tearDown();
    }

    private function parse(array $rows): array
    {
        $h = fopen($this->path, 'w');
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
        return $this->service->parseNonFtp($this->path, $this->customer);
    }

    private function assertInvalid(array $rows, string $message): void
    {
        try {
            $this->parse($rows);
            self::fail('Invalid file was accepted.');
        } catch (ValidationException $e) {
            self::assertStringContainsString($message, $e->errors()['csv_file'][0]);
        }
    }

    public function test_valid_grouping_totals_and_leading_zeroes(): void
    {
        $other = $this->row;
        $other[6] = 'XL';
        $other[8] = '20';
        $groups = $this->parse([$this->header, $this->row, $other]);
        self::assertCount(1, $groups);
        self::assertSame(30, $groups[0]['total_pcs']);
        self::assertSame(75.0, $groups[0]['total_val']);
        self::assertSame('001234', $groups[0]['items'][0]['upc']);
        self::assertSame(7, $groups[0]['customer_id']);
    }

    public function test_valid_thousands_and_invalid_decimal_commas(): void
    {
        $this->row[8] = '1,000';
        $this->row[9] = '$1,000.50';
        $g = $this->parse([$this->header, $this->row])[0];
        self::assertSame(1000, $g['items'][0]['qty']);
        self::assertSame(1000.5, $g['items'][0]['unit_price']);
        foreach ([8, 9] as $index) {
            foreach (['5,6', '1,00', '1.000,50', '1e3', '-2'] as $bad) {
                $row = $this->row;
                $row[$index] = $bad;
                $this->assertInvalid([$this->header, $row], 'Row 2:');
            }
        }
    }

    public function test_missing_po_or_style_is_never_skipped_or_generated(): void
    {
        foreach ([0, 2] as $index) {
            $row = $this->row;
            $row[$index] = '';
            $this->assertInvalid([$this->header, $row], 'Row 2:');
        }
        $headers = $this->header;
        array_shift($headers);
        $row = $this->row;
        array_shift($row);
        $this->assertInvalid([$headers, $row], 'PO number column is required');
    }

    public function test_invalid_later_row_prevents_all_preview_and_duplicate_lookups(): void
    {
        $bad = $this->row;
        $bad[2] = '';
        $this->assertInvalid([$this->header, $this->row, $bad], 'Row 3:');
        self::assertSame(0, $this->service->duplicateLookups);
    }

    public function test_exact_duplicate_is_rejected_not_silently_removed(): void
    {
        $this->assertInvalid([$this->header, $this->row, $this->row], 'Duplicate item');
    }

    public function test_distinct_explicit_sequences_allow_intentional_repeated_products(): void
    {
        $a = $this->row;
        $b = $this->row;
        $a[10] = '1';
        $b[10] = '2';
        $g = $this->parse([$this->header, $a, $b])[0];
        self::assertCount(2, $g['items']);
        self::assertSame(20, $g['total_pcs']);
        $b[10] = '1';
        $this->assertInvalid([$this->header, $a, $b], 'Duplicate sequence');
    }

    public function test_conflicting_po_header_values_are_rejected(): void
    {
        foreach ([1=>'2026-11-14', 11=>'Other warehouse', 12=>'Winter', 13=>'OTHER'] as $index=>$value) {
            $b = $this->row;
            $b[6] = 'XL';
            $b[$index] = $value;
            $this->assertInvalid([$this->header, $this->row, $b], 'Row 3: PO P-001 has conflicting');
        }
    }

    public function test_required_reference_and_other_explicit_customer_identity_match(): void
    {
        foreach (['Customer ID'=>'7', 'Customer Name'=>'Acme'] as $label=>$value) {
            $header = array_merge($this->header, [$label]);
            $row = array_merge($this->row, [$value]);
            self::assertSame(7, $this->parse([$header, $row])[0]['customer_id']);
            $row[count($row)-1] = 'WRONG';
            $this->assertInvalid([$header, $row], 'does not match the selected customer');
        }
        // External Customer No is retained, not guessed to be an ERP customer ref.
        self::assertSame('EXT-123', $this->parse([$this->header, $this->row])[0]['vendor']);
    }

    public function test_lengths_fail_before_existing_save_truncation(): void
    {
        foreach ([0=>22, 2=>51, 3=>201, 4=>51, 5=>101, 6=>51, 7=>51, 11=>201, 12=>51, 13=>16] as $index=>$length) {
            $row = $this->row;
            $row[$index] = str_repeat('X', $length);
            $this->assertInvalid([$this->header, $row], 'exceeds');
        }
    }

    public function test_header_can_follow_more_than_fifteen_preamble_rows(): void
    {
        $rows = array_fill(0, 20, ['Preamble']);
        $g = $this->parse(array_merge($rows, [$this->header, $this->row]));
        self::assertSame('P-001', $g[0]['po_number']);
    }

    public function test_duplicate_alias_headings_and_unquoted_extra_cells_are_rejected(): void
    {
        $this->assertInvalid([array_merge($this->header, ['Quantity']), array_merge($this->row, ['10'])], 'Duplicate/ambiguous heading');
        $this->assertInvalid([$this->header, array_merge($this->row, ['EXTRA'])], 'Unexpected data beyond');
    }

    public function test_missing_and_impossible_dates_are_rejected(): void
    {
        foreach (['', '20260230'] as $date) {
            $row = $this->row;
            $row[1] = $date;
            $this->assertInvalid([$this->header, $row], 'Row 2: Order date');
        }
    }

    public function test_sample_layout_detects_reordered_item_columns(): void
    {
        $rows = [['Company'], ['PO No:', 'SAMPLE'], ['Order Date:', '2026-11-13'], ['Customer No.', 'ACME']];
        $rows = array_merge($rows, array_fill(0, 16, []), [
            ['Cost','StyleCode','Qty','Size','StyleDescription','SeqNo'],
            ['2.5','STYLE','10','L','Shirt','1'],
        ]);
        $g = $this->parse($rows)[0];
        self::assertSame('SAMPLE', $g['po_number']);
        self::assertSame(25.0, $g['total_val']);
        self::assertSame('Shirt', $g['items'][0]['description']);
    }

    public function test_sample_missing_header_is_rejected_without_fixed_row_fallback(): void
    {
        $this->assertInvalid([
            ['Company'], ['PO No:', 'SAMPLE'], ['Order Date:', '2026-11-13'], ['Customer No.', 'ACME'],
            [], ['Unrecognised table'], ['1', 'STYLE', 'Shirt', 'NV', 'Navy', 'L', '10', '2.5'],
        ], 'Could not find an item header');
    }

    public function test_existing_po_duplicate_flag_is_preserved(): void
    {
        $this->row[0] = 'EXISTING';
        $g = $this->parse([$this->header, $this->row])[0];
        self::assertTrue($g['is_duplicate']);
        self::assertSame(99, $g['duplicate_id']);
    }

    public function test_xlsx_and_xls_keep_existing_readers_and_text_identifiers(): void
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            self::markTestSkipped('Existing PhpSpreadsheet dependency is unavailable.');
        }
        foreach (['Xlsx'=>'xlsx', 'Xls'=>'xls'] as $writerType=>$extension) {
            $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->fromArray([$this->header, $this->row], null, 'A1');
            $sheet->setCellValueExplicit('H2', '001234', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $path = $this->path.'.'.$extension;
            $this->extraPaths[] = $path;
            \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, $writerType)->save($path);
            $book->disconnectWorksheets();
            $g = $this->service->parseNonFtp($path, $this->customer)[0];
            self::assertSame('001234', $g['items'][0]['upc']);
            self::assertSame(25.0, $g['total_val']);
        }
    }

    public function test_tabular_reference_column_is_mandatory_even_with_customer_name(): void
    {
        $header = $this->header;
        $row = $this->row;
        array_pop($header);
        array_pop($row);
        $header[] = 'Customer Name';
        $row[] = 'Acme';
        $this->assertInvalid([$header, $row], 'Customer Ref column is required');
    }

    public function test_tabular_reference_is_checked_on_every_row_before_preview(): void
    {
        foreach (['', 'OTHER'] as $reference) {
            $other = $this->row;
            $other[6] = 'XL';
            $other[14] = $reference;
            $this->assertInvalid([$this->header, $this->row, $other], 'Row 3: customer_ref does not match');
        }
        self::assertSame(0, $this->service->duplicateLookups);
        $row = $this->row;
        $row[14] = ' acme ';
        self::assertSame(7, $this->parse([$this->header, $row])[0]['customer_id']);
    }

    public function test_sample_customer_number_must_match_selected_reference(): void
    {
        $rows = [
            ['Company'], ['PO No:', 'SAMPLE'], ['Order Date:', '2026-11-13'], ['Customer No.', 'ACME'],
            [], ['SeqNo', 'StyleCode', 'Qty', 'Cost'],
            ['1', 'STYLE', '10', '2.5'],
        ];
        self::assertSame(7, $this->parse($rows)[0]['customer_id']);
        foreach (['', 'OTHER', 'LV-1'] as $reference) {
            $rows[3][1] = $reference;
            $this->assertInvalid($rows, 'Row 4:');
        }
    }

    public function test_selected_customer_without_reference_is_rejected_for_both_layouts(): void
    {
        $this->customer->customer_ref = '';
        $this->assertInvalid([$this->header, $this->row], 'customer_ref does not match');
        $this->assertInvalid([
            ['Company'], ['PO No:', 'SAMPLE'], ['Order Date:', '2026-11-13'], ['Customer No.', 'ACME'],
            [], ['Style', 'Qty', 'Cost'], ['STYLE', '10', '2.5'],
        ], 'selected customer must have a reference');
    }

}
