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
            $table->string('poc_type')->nullable()->after('point_of_contact');
            $table->string('poc_gln')->nullable()->after('poc_type');
            $table->string('poc_alternate_id')->nullable()->after('poc_gln');
            $table->string('poc_name')->nullable()->after('poc_alternate_id');
            $table->string('poc_address_line1')->nullable()->after('poc_name');
            $table->string('poc_address_line2')->nullable()->after('poc_address_line1');
            $table->string('poc_apt_number')->nullable()->after('poc_address_line2');
            $table->string('poc_city')->nullable()->after('poc_apt_number');
            $table->string('poc_state_province')->nullable()->after('poc_city');
            $table->string('poc_country')->nullable()->after('poc_state_province');
            $table->string('poc_postal_code')->nullable()->after('poc_country');
            $table->string('poc_phone')->nullable()->after('poc_postal_code');
            $table->string('poc_email')->nullable()->after('poc_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stafford_qualifications', function (Blueprint $table) {
            $table->dropColumn([
                'poc_type', 'poc_gln', 'poc_alternate_id', 'poc_name', 'poc_address_line1',
                'poc_address_line2', 'poc_apt_number', 'poc_city', 'poc_state_province',
                'poc_country', 'poc_postal_code', 'poc_phone', 'poc_email'
            ]);
        });
    }
};
