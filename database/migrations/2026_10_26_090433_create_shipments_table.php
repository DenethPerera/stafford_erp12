<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->string('country_of_origin', 10);
        $table->string('buyer_id')->nullable();
        $table->string('shipment_id')->unique();
        $table->string('shipment_id_mode', 10);
        $table->unsignedBigInteger('purchase_order_id')->nullable();
        $table->string('shipment_type', 20);
        $table->json('factory_ids');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('shipments');
}
};
