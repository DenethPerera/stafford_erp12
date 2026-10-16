<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('stafford_qualifications', function (Blueprint $table) {
        $table->string('season', 50)->nullable()->after('style_no');
    });
}

public function down()
{
    Schema::table('stafford_qualifications', function (Blueprint $table) {
        $table->dropColumn('season');
    });
}
};
