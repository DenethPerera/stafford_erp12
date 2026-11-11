<?php
namespace Tests\Unit;
use App\Services\CustomerPoCompareService;
use PHPUnit\Framework\TestCase;
class CustomerPoCompareServiceTest extends TestCase
{
    private function po(): array
    {
        return ['po_number'=>'123', 'order_date'=>'2026-07-23', 'items'=>[[
            'style'=>'DG20W', 'color_code'=>'PM', 'size'=>'S', 'qty'=>528, 'unit_price'=>6.9, 'upc'=>'8.83E+11',
        ]]];
    }
    public function test_detects_quantity_price_and_dates(): void
    {
        $new=$this->po();
        $new['items'][0]['qty']=500;
        $new['items'][0]['unit_price']=7.2;
        $new['ship_date']='2026-12-01';
        $r=(new CustomerPoCompareService)->compare($new,$this->po());
        self::assertSame(1,$r['revised_count']);
        self::assertCount(2,$r['items'][0]['changes']);
        self::assertSame('Ship date',$r['header_changes'][0]['field']);
    }
    public function test_ignores_numeric_formatting(): void
    {
        $old=$this->po();
        $old['items'][0]['unit_price']='6.9000';
        $old['items'][0]['qty']='528';
        self::assertSame('Unchanged',(new CustomerPoCompareService)->compare($this->po(),$old)['status']);
    }
    public function test_size_change_is_unmatched(): void
    {
        $new=$this->po();
        $new['items'][0]['size']='XL';
        $r=(new CustomerPoCompareService)->compare($new,$this->po());
        self::assertSame(['Not in CSV','New item'],array_column($r['items'],'status'));
        self::assertSame(0,$r['revised_count']);
    }
    public function test_duplicate_keys_require_review(): void
    {
        $old=$this->po();
        $old['items'][]=$old['items'][0];
        $r=(new CustomerPoCompareService)->compare($this->po(),$old);
        self::assertSame(['Ambiguous DB item','Ambiguous DB item','Ambiguous CSV item'],array_column($r['items'],'status'));
    }
    public function test_identifies_new_po(): void
    {
        self::assertSame('New PO',(new CustomerPoCompareService)->compare($this->po(),null)['status']);
    }
}
