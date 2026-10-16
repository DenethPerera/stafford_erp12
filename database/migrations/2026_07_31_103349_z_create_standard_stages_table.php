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
        Schema::create('standard_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order_sequence');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['standard_id', 'order_sequence']);
            $table->unique(['standard_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_stages');
    }
};
