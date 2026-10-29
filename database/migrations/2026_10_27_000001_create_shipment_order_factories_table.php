<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_order_factories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained('shipments')->cascadeOnDelete();
            $table->integer('purchase_order_id');
            $table->integer('factory_id');
            $table->unique(
                ['shipment_id', 'purchase_order_id', 'factory_id'],
                'shipment_po_factory_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_order_factories');
    }
};
