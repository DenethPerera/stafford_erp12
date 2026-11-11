<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forwarder_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forwarder_id')
                ->unique()
                ->constrained('forwarders')
                ->cascadeOnDelete();
            $table->string('cargo_security_certificate')->nullable();
            $table->string('container_inspection_checklist')->nullable();
            $table->string('consolidation_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forwarder_documents');
    }
};
