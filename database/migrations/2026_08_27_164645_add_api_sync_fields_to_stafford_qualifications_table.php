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
        Schema::table('stafford_qualifications', function (Blueprint $table) {
            $table->string('api_sync_status')->nullable()->default('pending');
            $table->string('api_import_id')->nullable();
            $table->text('api_sync_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stafford_qualifications', function (Blueprint $table) {
            $table->dropColumn(['api_sync_status', 'api_import_id', 'api_sync_message']);
        });
    }
};
