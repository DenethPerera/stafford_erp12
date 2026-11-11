<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy before replacing the integer column so existing IDs are retained.
        Schema::table('shipments', function (Blueprint $table) {
            $table->json('purchase_order_ids_copy')->nullable();
        });

        DB::table('shipments')->orderBy('id')->chunkById(200, function ($shipments) {
            $links = DB::table('shipment_order_factories')
                ->whereIn('shipment_id', $shipments->pluck('id'))
                ->orderBy('purchase_order_id')
                ->get(['shipment_id', 'purchase_order_id'])
                ->groupBy('shipment_id');

            foreach ($shipments as $shipment) {
                $ids = collect([$shipment->purchase_order_id])
                    ->merge(($links->get($shipment->id) ?? collect())->pluck('purchase_order_id'))
                    ->filter(fn ($id) => $id !== null)
                    ->map(fn ($id) => (int) $id)
                    ->unique()->values()->all();

                DB::table('shipments')->where('id', $shipment->id)->update([
                    'purchase_order_ids_copy' => $ids ? json_encode($ids, JSON_THROW_ON_ERROR) : null,
                ]);
            }
        });

        $this->replacePurchaseOrderColumn('json');
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_ids_copy')->nullable();
        });

        DB::table('shipments')->orderBy('id')->chunkById(200, function ($shipments) {
            foreach ($shipments as $shipment) {
                $ids = json_decode($shipment->purchase_order_id ?? '[]', true, 512, JSON_THROW_ON_ERROR);

                // Restore the old single-PO behavior; multiple POs remain in the links table.
                DB::table('shipments')->where('id', $shipment->id)->update([
                    'purchase_order_ids_copy' => count($ids) === 1 ? $ids[0] : null,
                ]);
            }
        });

        $this->replacePurchaseOrderColumn('bigint unsigned');
    }

    private function replacePurchaseOrderColumn(string $type): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('purchase_order_id');
        });

        if (DB::getDriverName() === 'mysql') {
            // CHANGE also supports MariaDB versions without RENAME COLUMN.
            DB::statement('ALTER TABLE shipments CHANGE purchase_order_ids_copy purchase_order_id '.$type.' NULL');
        } else {
            Schema::table('shipments', function (Blueprint $table) {
                $table->renameColumn('purchase_order_ids_copy', 'purchase_order_id');
            });
        }
    }
};
