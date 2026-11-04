<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_documents', function (Blueprint $table) {
            $table->id();
            // Factory IDs belong to the separate ERAV database.
            $table->unsignedBigInteger('mbranchlist_id')->index();
            $table->string('document_type');
            $table->string('original_name');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_documents');
    }
};
