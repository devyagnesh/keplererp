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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique()->comment('Short warehouse code');
            $table->string('name', 120);
            $table->string('city', 80)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->unique()->comment('Stock keeping unit');
            $table->string('name', 191);
            $table->string('uom', 16)->default('PCS')->comment('Unit of measure');
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'item_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type', 32)->index()->comment('adjustment, transfer_out, transfer_in, grn');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 40)->unique();
            $table->date('required_date')->nullable()->index();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
            $table->index(['purchase_requisition_id', 'item_id'], 'pr_lines_pr_item_idx');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 40)->unique();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->date('order_date')->index();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->timestamps();
            $table->index(['purchase_order_id', 'item_id'], 'po_lines_po_item_idx');
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 40)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->timestamp('received_at')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
            $table->index(['goods_receipt_id', 'item_id'], 'grn_lines_grn_item_idx');
        });

        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('quote_date')->index();
            $table->date('valid_until')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->timestamps();
            $table->index(['sales_quotation_id', 'item_id'], 'sq_lines_sq_item_idx');
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('order_date')->index();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->timestamps();
            $table->index(['sales_order_id', 'item_id'], 'so_lines_so_item_idx');
        });

        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['parent_item_id', 'version']);
        });

        Schema::create('bill_of_material_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('quantity_per', 15, 6);
            $table->timestamps();
            $table->index(['bill_of_material_id', 'component_item_id'], 'bom_lines_bom_comp_idx');
        });

        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number', 40)->unique();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('qty_planned', 15, 4);
            $table->string('status', 24)->default('planned')->index();
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number', 40)->unique();
            $table->date('voucher_date')->index();
            $table->text('narration')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('journal_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained()->cascadeOnDelete();
            $table->string('account_code', 32)->index();
            $table->string('account_name', 120)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_code', 32)->unique();
            $table->string('name', 120);
            $table->string('email', 191)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('department', 80)->nullable()->index();
            $table->string('designation', 80)->nullable();
            $table->date('join_date')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attendance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date')->index();
            $table->string('status', 16)->index();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('attendance_entries');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('journal_voucher_lines');
        Schema::dropIfExists('journal_vouchers');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bill_of_material_lines');
        Schema::dropIfExists('bill_of_materials');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_quotation_lines');
        Schema::dropIfExists('sales_quotations');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requisition_lines');
        Schema::dropIfExists('purchase_requisitions');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('items');
        Schema::dropIfExists('warehouses');
    }
};
