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
        Schema::create('stafford_qualifications', function (Blueprint $table) {
            $table->id();
            
            // PO Fields
            $table->string('po_number')->nullable();
            $table->string('po_date')->nullable();
            $table->string('buyer')->nullable();
            $table->string('shipment')->nullable();
            $table->string('origin')->nullable();
            
            // Product Fields
            $table->string('style_no')->nullable();
            $table->string('erp_style_desc')->nullable();
            $table->string('product_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('color')->nullable();
            $table->string('product_style')->nullable();
            $table->string('primary_product_id')->nullable();
            $table->string('gtin')->nullable();
            $table->string('upc')->nullable();
            
            // Certificate Fields
            $table->string('cert_type')->nullable();
            $table->string('intended_use')->nullable();
            $table->string('children_product')->nullable();
            $table->string('cert_language')->nullable();
            $table->string('cert_scope')->nullable();
            $table->string('cert_no')->nullable();
            
            // Factory Fields
            $table->string('factory_name')->nullable();
            $table->string('factory_address')->nullable();
            $table->string('factory_city')->nullable();
            $table->string('factory_state')->nullable();
            $table->string('factory_postal')->nullable();
            $table->string('factory_country')->nullable();
            $table->string('factory_gln')->nullable();
            $table->string('factory_alt_id')->nullable();
            $table->string('factory_contact')->nullable();
            $table->string('factory_email')->nullable();
            $table->string('factory_phone')->nullable();
            
            // Importer Fields
            $table->string('importer_name')->nullable();
            $table->string('importer_address')->nullable();
            $table->string('importer_city')->nullable();
            $table->string('importer_state')->nullable();
            $table->string('importer_postal')->nullable();
            $table->string('importer_country')->nullable();
            
            // Certifier Fields
            $table->string('certifier_name')->nullable();
            $table->string('certifier_title')->nullable();
            $table->string('certifier_email')->nullable();
            $table->string('certifier_phone')->nullable();
            
            // Tests (JSON)
            $table->json('tests')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stafford_qualifications');
    }
};
