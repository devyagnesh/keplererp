<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS-aligned schema additions: stock ledger, reservations, COA, posted journals,
     * vendor payables, invoices, extended master/transaction fields.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('po_approval_threshold', 15, 2)->nullable()->after('einvoice_enabled')
                ->comment('PO total above this requires finance approval');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 15, 2)->default(0)->after('payment_terms')
                ->comment('0 = unlimited per SRS convention');
            $table->decimal('credit_used', 15, 2)->default(0)->after('credit_limit');
            $table->unsignedSmallInteger('payment_terms_days')->default(30)->after('credit_used');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('hsn_code', 8)->default('00000000')->after('name');
            $table->decimal('gst_rate', 5, 2)->default(18)->after('hsn_code');
            $table->string('item_type', 32)->default('RAW_MATERIAL')->after('gst_rate')->index();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('account_name', 255);
            $table->string('account_type', 24)->index();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('batch_no', 50)->nullable();
            $table->string('serial_no', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('transaction_type', 32)->index();
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('qty_in', 15, 4)->nullable();
            $table->decimal('qty_out', 15, 4)->nullable();
            $table->decimal('balance_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['warehouse_id', 'item_id', 'id']);
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('status', 24)->default('reserved')->index();
            $table->timestamps();

            $table->index(['sales_order_id', 'item_id', 'warehouse_id']);
        });

        Schema::create('accounting_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 80)->index();
            $table->unsignedBigInteger('reference_id')->index();
            $table->text('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('accounting_journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();

            $table->index('journal_entry_id');
        });

        Schema::create('vendor_payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 24)->default('open')->index();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->char('place_of_supply', 2);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('taxable_value', 15, 2);
            $table->decimal('cgst', 15, 2)->default(0);
            $table->decimal('sgst', 15, 2)->default(0);
            $table->decimal('igst', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('requested_by')->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejected_reason')->nullable()->after('approved_at');
            $table->softDeletes();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('pr_id')->nullable()->after('id')->constrained('purchase_requisitions')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('vendor_id')->constrained()->nullOnDelete();
            $table->date('expected_delivery')->nullable()->after('order_date');
            $table->unsignedSmallInteger('payment_terms_days')->default(30)->after('expected_delivery');
            $table->decimal('subtotal', 15, 2)->default(0)->after('payment_terms_days');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('finance_approved_at')->nullable();
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->default(0)->after('unit_cost');
            $table->decimal('taxable_value', 15, 2)->default(0)->after('gst_rate');
            $table->decimal('cgst', 15, 2)->default(0);
            $table->decimal('sgst', 15, 2)->default(0);
            $table->decimal('igst', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('status', 24)->default('posted')->after('notes')->index();
            $table->timestamp('posted_at')->nullable()->after('status');
        });

        Schema::table('goods_receipt_lines', function (Blueprint $table) {
            $table->decimal('accepted_qty', 15, 4)->nullable()->after('quantity');
            $table->decimal('rejected_qty', 15, 4)->nullable()->after('accepted_qty');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('quotation_id')->nullable()->after('id')->constrained('sales_quotations')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->date('expected_dispatch')->nullable()->after('order_date');
            $table->unsignedSmallInteger('payment_terms_days')->default(30)->after('expected_dispatch');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->softDeletes();
        });

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->default(0)->after('unit_price');
            $table->decimal('taxable_value', 15, 2)->default(0);
            $table->decimal('cgst', 15, 2)->default(0);
            $table->decimal('sgst', 15, 2)->default(0);
            $table->decimal('igst', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('bom_id')->nullable()->after('item_id')->constrained('bill_of_materials')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('bom_id')->constrained()->nullOnDelete();
            $table->decimal('actual_qty', 15, 4)->nullable()->after('qty_planned');
        });

        DB::table('goods_receipt_lines')->update([
            'accepted_qty' => DB::raw('quantity'),
            'rejected_qty' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bom_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn('actual_qty');
        });

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropColumn(['gst_rate', 'taxable_value', 'cgst', 'sgst', 'igst', 'line_total']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('quotation_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn([
                'expected_dispatch', 'payment_terms_days', 'subtotal', 'discount_amount',
                'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'total_amount',
            ]);
        });

        Schema::table('goods_receipt_lines', function (Blueprint $table) {
            $table->dropColumn(['accepted_qty', 'rejected_qty']);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['status', 'posted_at']);
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn(['gst_rate', 'taxable_value', 'cgst', 'sgst', 'igst', 'line_total']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('pr_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('finance_approved_by');
            $table->dropColumn([
                'expected_delivery', 'payment_terms_days', 'subtotal', 'discount_amount',
                'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'total_amount',
                'finance_approved_at',
            ]);
        });

        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'rejected_reason']);
        });

        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('vendor_payables');
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journal_entries');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_ledger');

        Schema::dropIfExists('accounts');

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['hsn_code', 'gst_rate', 'item_type']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'credit_used', 'payment_terms_days']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('po_approval_threshold');
        });
    }
};
