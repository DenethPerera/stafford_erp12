<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ERP data lives on erav, not the application's default connection.
        // Keep historical references and allow multiple deliveries per number.
        DB::connection('erav')->statement(
            'ALTER TABLE prodshipment_list '
            .'MODIFY COLUMN shipment_refno VARCHAR(255) '
            .'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, '
            .'ADD INDEX prodshipment_list_shipment_refno_index (shipment_refno)'
        );
    }

    public function down(): void
    {
        $erp = DB::connection('erav');

        if ($erp->table('prodshipment_list')
            ->whereRaw('CHAR_LENGTH(shipment_refno) > 15')->exists()) {
            throw new RuntimeException(
                'Cannot restore the 15-character limit: longer shipment references exist.'
            );
        }

        // Retain Unicode support so rollback cannot corrupt new references.
        $erp->statement(
            'ALTER TABLE prodshipment_list '
            .'MODIFY COLUMN shipment_refno VARCHAR(15) '
            .'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, '
            .'DROP INDEX prodshipment_list_shipment_refno_index'
        );
    }
};
