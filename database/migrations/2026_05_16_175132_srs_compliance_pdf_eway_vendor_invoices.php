<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('eway_enabled')->default(false)->after('einvoice_enabled');
        });

        Schema::table('sales_dispatch_challans', function (Blueprint $table) {
            $table->string('eway_bill_no', 64)->nullable()->after('dispatched_at')->index();
            $table->text('eway_qr')->nullable()->after('eway_bill_no');
            $table->timestamp('eway_generated_at')->nullable()->after('eway_qr');
        });

        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_payable_id')->constrained()->cascadeOnDelete();
            $table->string('vendor_invoice_number', 50)->index();
            $table->date('invoice_date');
            $table->decimal('amount', 15, 2);
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status', 24)->default('uploaded')->index();
            $table->decimal('po_amount', 15, 2)->nullable();
            $table->decimal('grn_amount', 15, 2)->nullable();
            $table->string('match_status', 24)->nullable()->index();
            $table->text('match_notes')->nullable();
            $table->foreignId('uploaded_by_vendor')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
            $table->unique(['vendor_id', 'vendor_invoice_number'], 'vendor_invoices_vendor_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_invoices');

        Schema::table('sales_dispatch_challans', function (Blueprint $table) {
            $table->dropColumn(['eway_bill_no', 'eway_qr', 'eway_generated_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('eway_enabled');
        });
    }
};
