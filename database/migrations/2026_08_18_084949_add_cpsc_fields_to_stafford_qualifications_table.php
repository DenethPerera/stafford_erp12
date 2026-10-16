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
            $table->string('product_id_type')->nullable()->after('primary_product_id');
            $table->text('description')->nullable()->after('erp_style_desc');
            $table->string('version_id')->nullable()->after('id');
            $table->string('status')->default('Incomplete')->after('version_id');
            $table->date('manufacture_start_date')->nullable()->after('factory_phone');
            $table->date('manufacture_end_date')->nullable()->after('manufacture_start_date');
            $table->string('lot_number')->nullable()->after('manufacture_end_date');
            $table->string('lot_assigned_by')->nullable()->after('lot_number');
            $table->date('last_test_date')->nullable()->after('cert_no');
            $table->string('point_of_contact')->nullable()->after('certifier_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stafford_qualifications', function (Blueprint $table) {
            $table->dropColumn([
                'product_id_type',
                'description',
                'version_id',
                'status',
                'manufacture_start_date',
                'manufacture_end_date',
                'lot_number',
                'lot_assigned_by',
                'last_test_date',
                'point_of_contact'
            ]);
        });
    }
};
