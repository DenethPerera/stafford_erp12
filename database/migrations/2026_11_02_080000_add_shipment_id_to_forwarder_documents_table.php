<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forwarder_documents', function (Blueprint $table) {
            $table->string('shipment_id')->nullable();
        });

        // Add the new index before removing the index used by the foreign key.
        Schema::table('forwarder_documents', function (Blueprint $table) {
            $table->unique(
                ['forwarder_id', 'shipment_id'],
                'forwarder_documents_forwarder_shipment_unique'
            );
        });

        Schema::table('forwarder_documents', function (Blueprint $table) {
            $table->dropUnique('forwarder_documents_forwarder_id_unique');
        });
    }

    public function down(): void
    {
        // Restore this constraint first so rollback never discards shipment rows.
        Schema::table('forwarder_documents', function (Blueprint $table) {
            $table->unique('forwarder_id');
        });

        Schema::table('forwarder_documents', function (Blueprint $table) {
            $table->dropUnique('forwarder_documents_forwarder_shipment_unique');
            $table->dropColumn('shipment_id');
        });
    }
};
