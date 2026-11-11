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

class CustomerPoUploadParserTest extends TestCase
{
    private string $path;
    private array $header;
    private array $row;
    private CustomerPoUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // A standalone container: no application bootstrap, DB connection or migrations.
        $container = new Container;
        $container->instance('validator', new Factory(new Translator(new ArrayLoader, 'en'), $container));
        Facade::setFacadeApplication($container);
        $this->service = new class extends CustomerPoUploadService {
            protected function findDuplicateId(string $poNumber, int $customerId): ?int
            {
                return $poNumber === 'EXISTING' ? 99 : null;
            }
        };
        $this->path = tempnam(sys_get_temp_dir(), 'po-parser-');
        $this->header = explode(',', 'PO,Master PO,Created On,Last Modified On,X-Factory Date,In-DC Date,Quantity,Original Quantity,Vendor,Factory Name,Destination,Destination Name,INCOTERMS,MILL CODE,BRAND,FACTORY,ON BOARD DATE,CARRIER,EXPECTED RECEIPT DATE,COUNTRY OF ORIGIN,Style,Season,Description,Color,Selling Description,Size,Universal Product Code,Original Quantity,Quantity,Quantity Shipped,Master Pack Group,Case Pack Ratio,Unit Cost,Currency,Total Cost,Expected Receipt Date,Acknowledged');
        $this->row = explode(',', '136294,136294,20260806,20260809,20261211,20270215,2400,2400,06734,STAFFORD,SSOLT,Destination,PPD,MC,Brand,Factory,20261218,CARRIER,20270215,EG,M118,Season,Description,8Y,SAFETY YELLOW,S,00882849720437,36,36,0,36,36,4.6900,USD,168.8400,20270215,No');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        parent::tearDown();
    }

    private function parse(array $rows, ?array $header = null): array
    {
        $handle = fopen($this->path, 'w');
        fputcsv($handle, $header ?? $this->header);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        return $this->service->parseAbFtp($this->path, (object) [
            'customer_id' => 1, 'customer_name' => 'AlphaBroder',
        ]);
    }

    public function test_groups_items_and_preserves_upc_leading_zeroes(): void
    {
        $groups = $this->parse([$this->row, $this->row]);
        self::assertCount(1, $groups);
        self::assertCount(2, $groups[0]['items']);
        self::assertSame(72, $groups[0]['total_pcs']);
        self::assertSame(337.68, $groups[0]['total_val']);
        self::assertSame('00882849720437', $groups[0]['items'][0]['upc']);
    }

    public function test_existing_po_is_flagged_even_when_quantity_changes(): void
    {
        $this->row[0] = 'EXISTING';
        $this->row[28] = '500';
        $groups = $this->parse([$this->row]);
        self::assertTrue($groups[0]['is_duplicate']);
        self::assertSame(99, $groups[0]['duplicate_id']);
    }

    public function test_rejects_shifted_headers(): void
    {
        array_splice($this->header, 20, 0, ['Extra']);
        $this->expectException(ValidationException::class);
        $this->parse([$this->row]);
    }

    public function test_accepts_empty_trailing_cells(): void
    {
        $this->header[] = '';
        $this->row[] = '';
        self::assertCount(1, $this->parse([$this->row]));
    }

    public function test_rejects_fractional_quantity(): void
    {
        $this->row[28] = '3.5';
        $this->expectException(ValidationException::class);
        $this->parse([$this->row]);
    }

    public function test_rejects_impossible_date(): void
    {
        $this->row[2] = '20260230';
        $this->expectException(ValidationException::class);
        $this->parse([$this->row]);
    }

    public function test_preserves_scientific_notation_upc_as_text(): void
    {
        $this->row[26] = '8.83E+11';
        $groups = $this->parse([$this->row]);
        self::assertSame('8.83E+11', $groups[0]['items'][0]['upc']);
    }

    public function test_rejects_short_rows(): void
    {
        $this->expectException(ValidationException::class);
        $this->parse([array_slice($this->row, 0, 29)]);
    }

    public function test_preserves_correctly_quoted_commas(): void
    {
        $this->row[11] = 'Distribution Centre, North Region';
        $this->row[22] = 'Shirt, long sleeve';
        $group = $this->parse([$this->row])[0];
        self::assertSame($this->row[11], $group['delivery_addr']);
        self::assertSame($this->row[22], $group['items'][0]['description']);
    }

    public function test_repairs_unique_destination_split_without_sample_names(): void
    {
        array_splice($this->row, 11, 1, ['Distribution Centre', ' North Region']);
        $group = $this->parse([$this->row])[0];
        self::assertSame('Distribution Centre, North Region', $group['delivery_addr']);
        self::assertSame('M118', $group['items'][0]['style']);
        self::assertSame('S', $group['items'][0]['size']);
        self::assertSame(36, $group['items'][0]['qty']);
        self::assertSame(4.69, $group['items'][0]['unit_price']);
        self::assertSame('PPD', $group['ship_term']);
        self::assertSame(168.84, $group['total_val']);
    }

    public function test_groups_mixed_normal_and_repaired_rows(): void
    {
        $other = $this->row;
        $other[0] = 'OTHER';
        array_splice($other, 11, 1, ['Distribution Centre', ' North Region']);
        $groups = $this->parse([$this->row, $other, $other]);
        self::assertCount(2, $groups);
        self::assertSame(72, $groups[1]['total_pcs']);
        self::assertSame(337.68, $groups[1]['total_val']);
    }

    public function test_rejects_ambiguous_description_colour_split_with_row_number(): void
    {
        array_splice($this->row, 22, 1, ['Long', 'Sleeve']);
        try {
            $this->parse([$this->row]);
            self::fail('Ambiguous repair was accepted.');
        } catch (ValidationException $e) {
            self::assertStringContainsString('Row 2:', $e->errors()['ftp_file'][0]);
            self::assertStringContainsString('Ambiguous', $e->errors()['ftp_file'][0]);
        }
    }

    public function test_invalid_row_after_valid_row_returns_no_partial_preview(): void
    {
        $bad = $this->row;
        $bad[33] = 'not-currency';
        try {
            $this->parse([$this->row, $bad]);
            self::fail('Invalid file was accepted.');
        } catch (ValidationException $e) {
            self::assertStringContainsString('Row 3:', $e->errors()['ftp_file'][0]);
        }
    }

    public function test_rejects_unrecoverable_extra_field(): void
    {
        $this->row[] = 'unexpected';
        $this->expectException(ValidationException::class);
        $this->parse([$this->row]);
    }

    public function test_repaired_existing_po_keeps_duplicate_flag(): void
    {
        $this->row[0] = 'EXISTING';
        array_splice($this->row, 11, 1, ['Distribution Centre', ' North Region']);
        $group = $this->parse([$this->row])[0];
        self::assertTrue($group['is_duplicate']);
        self::assertSame(99, $group['duplicate_id']);
    }

    public function test_non_ftp_tabular_workflow_is_unchanged(): void
    {
        $h = fopen($this->path, 'w');
        fputcsv($h, ['PO', 'Order Date', 'Style', 'Qty', 'Cost', 'Size']);
        fputcsv($h, ['NON-FTP', '2026-07-23', 'STYLE1', '10', '2.5000', 'L']);
        fclose($h);
        $groups = $this->service->parseNonFtp($this->path, (object) ['customer_id' => 1]);
        self::assertSame('NON-FTP', $groups[0]['po_number']);
        self::assertSame(10, $groups[0]['total_pcs']);
        self::assertSame(25.0, $groups[0]['total_val']);
    }

    public function test_non_ftp_sample_layout_is_unchanged(): void
    {
        $h = fopen($this->path, 'w');
        foreach ([
            ['Company'], ['PO No:', 'SAMPLE-PO'], ['Order Date:', '2026-07-23'], ['Customer No.', 'AB'],
            [], ['SeqNo', 'StyleCode', 'StyleDescription', 'ColorCode', 'ColorName', 'Size', 'Qty', 'Cost'],
            ['1', 'STYLE1', 'Shirt', 'NV', 'Navy', 'L', '10', '2.5'],
        ] as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
        $groups = $this->service->parseNonFtp($this->path, (object) ['customer_id' => 1]);
        self::assertSame('SAMPLE-PO', $groups[0]['po_number']);
        self::assertSame(25.0, $groups[0]['total_val']);
    }

}
