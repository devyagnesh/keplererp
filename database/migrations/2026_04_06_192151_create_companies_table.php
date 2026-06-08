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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255)->comment('Trading / display name');
            $table->string('legal_name', 255)->comment('Registered legal entity name');
            $table->char('gstin', 15)->comment('15-char GST identification number');
            $table->char('pan', 10)->comment('Permanent account number');
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100);
            $table->char('state_code', 2)->comment('GST state code');
            $table->char('pincode', 6);
            $table->string('phone', 15);
            $table->string('email', 255);
            $table->string('logo', 500)->nullable()->comment('Public storage path for logo');
            $table->date('financial_year_start')->comment('Typically April 1');
            $table->char('currency', 3)->default('INR');
            $table->string('invoice_prefix', 20);
            $table->string('po_prefix', 20);
            $table->string('default_tax_type', 20)->comment('IGST or CGST_SGST');
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('einvoice_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('gstin');
            $table->index('state_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
