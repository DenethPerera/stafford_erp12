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
        Schema::table('standards', function (Blueprint $table) {
            if (Schema::hasColumn('standards', 'issuing_body')) {
                $table->dropColumn('issuing_body');
            }
            if (Schema::hasColumn('standards', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('standards', function (Blueprint $table) {
            $table->string('issuing_body')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
        });
    }
};
