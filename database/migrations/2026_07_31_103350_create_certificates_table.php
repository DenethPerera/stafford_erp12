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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->nullable()->unique();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->enum('status', ['valid', 'expiring_soon', 'expired', 'under_renewal'])->default('valid');
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['factory_id', 'standard_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
