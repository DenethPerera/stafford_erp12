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
        Schema::create('renewal_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_standard_stage_id')->nullable()->constrained('standard_stages')->nullOnDelete();
            $table->date('started_at');
            $table->enum('status', ['in_progress', 'completed', 'rejected'])->default('in_progress');
            $table->date('completed_at')->nullable();
            $table->date('new_expiry_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewal_processes');
    }
};
