<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_documents', function (Blueprint $table) {
            $table->string('shipment_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_documents', function (Blueprint $table) {
            $table->dropColumn('shipment_id');
        });
    }
};
