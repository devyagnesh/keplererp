<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS v1.1 remaining: transfer workflow, leave balances, stock reconciliation, GST ARN, vendor portal password.
     */
    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 40)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status', 24)->default('draft')->index();
            $table->text('reason')->nullable();
            $table->string('vehicle_no', 32)->nullable();
            $table->string('lr_number', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_requested', 15, 4);
            $table->decimal('qty_dispatched', 15, 4)->nullable();
            $table->decimal('qty_received', 15, 4)->nullable();
            $table->string('batch_no', 64)->nullable();
            $table->string('serial_no', 64)->nullable();
            $table->string('variance_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type', 16);
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('entitled_days', 6, 2)->default(0);
            $table->decimal('used_days', 6, 2)->default(0);
            $table->decimal('balance_days', 6, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type', 'fiscal_year']);
        });

        Schema::create('stock_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number', 40)->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status', 16)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('system_qty', 15, 4)->default(0);
            $table->decimal('physical_qty', 15, 4)->default(0);
            $table->decimal('variance_qty', 15, 4)->default(0);
            $table->string('reason', 255)->nullable();
            $table->boolean('adjustment_posted')->default(false);
            $table->timestamps();
        });

        if (Schema::hasTable('gst_period_locks')) {
            Schema::table('gst_period_locks', function (Blueprint $table) {
                if (! Schema::hasColumn('gst_period_locks', 'gstr1_arn')) {
                    $table->string('gstr1_arn', 64)->nullable()->after('locked_at');
                    $table->timestamp('gstr1_filed_at')->nullable()->after('gstr1_arn');
                    $table->string('gstr3b_arn', 64)->nullable()->after('gstr1_filed_at');
                    $table->timestamp('gstr3b_filed_at')->nullable()->after('gstr3b_arn');
                    $table->decimal('gstr3b_tax_paid', 15, 2)->nullable()->after('gstr3b_filed_at');
                }
            });
        }

        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (! Schema::hasColumn('vendors', 'portal_must_change_password')) {
                    $table->boolean('portal_must_change_password')->default(false)->after('portal_password');
                    $table->timestamp('portal_password_changed_at')->nullable()->after('portal_must_change_password');
                }
            });
        }

        if (Schema::hasTable('payroll_runs')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                if (! Schema::hasColumn('payroll_runs', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('processed_by')->constrained('users')->nullOnDelete();
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                    $table->timestamp('paid_at')->nullable()->after('approved_at');
                }
            });
        }

        if (Schema::hasTable('goods_receipts') && ! Schema::hasColumn('goods_receipts', 'qc_photo_path')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                $table->string('qc_photo_path', 500)->nullable()->after('posted_at');
                $table->string('qc_officer_name', 120)->nullable()->after('qc_photo_path');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reconciliation_lines');
        Schema::dropIfExists('stock_reconciliations');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('warehouse_transfer_lines');
        Schema::dropIfExists('warehouse_transfers');

        if (Schema::hasTable('gst_period_locks')) {
            Schema::table('gst_period_locks', function (Blueprint $table) {
                $table->dropColumn(['gstr1_arn', 'gstr1_filed_at', 'gstr3b_arn', 'gstr3b_filed_at', 'gstr3b_tax_paid']);
            });
        }

        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn(['portal_must_change_password', 'portal_password_changed_at']);
            });
        }

        if (Schema::hasTable('payroll_runs')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by');
                $table->dropColumn(['approved_at', 'paid_at']);
            });
        }

        if (Schema::hasTable('goods_receipts')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                $table->dropColumn(['qc_photo_path', 'qc_officer_name']);
            });
        }
    }
};
