<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_lines', function (Blueprint $table) {
            $table->string('batch_no', 50)->nullable()->after('rejected_qty');
            $table->string('serial_no', 50)->nullable()->after('batch_no');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('irn', 64)->nullable()->after('status')->index();
            $table->string('ack_no', 64)->nullable()->after('irn');
            $table->text('einvoice_qr')->nullable()->after('ack_no');
            $table->timestamp('irn_generated_at')->nullable()->after('einvoice_qr');
        });

        Schema::create('sales_dispatch_challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_number', 40)->unique();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('dispatched_at')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40)->index();
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['vendor_id', 'document_type']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 80)->index();
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('vendor_documents');
        Schema::dropIfExists('sales_dispatch_challans');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['irn', 'ack_no', 'einvoice_qr', 'irn_generated_at']);
        });

        Schema::table('goods_receipt_lines', function (Blueprint $table) {
            $table->dropColumn(['batch_no', 'serial_no']);
        });
    }
};
