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
        Schema::create('audit_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('renewal_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('standard_stage_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->enum('result', ['pending', 'passed', 'failed', 'needs_corrective_action'])->default('pending');
            $table->string('auditor_name')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['renewal_process_id', 'standard_stage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_stages');
    }
};
