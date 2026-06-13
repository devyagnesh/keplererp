<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS zero-gap: payroll arrear, sales pick list, GRN QC on header.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payroll_arrears')) {
            Schema::create('payroll_arrears', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('arrear_year');
                $table->unsignedTinyInteger('arrear_month');
                $table->unsignedTinyInteger('days_count');
                $table->decimal('amount', 15, 2);
                $table->string('note', 120)->default('Salary Arrear');
                $table->string('status', 16)->default('pending')->index();
                $table->foreignId('settled_payroll_run_id')->nullable()->constrained('payroll_runs')->nullOnDelete();
                $table->timestamps();
                $table->unique(['employee_id', 'arrear_year', 'arrear_month']);
            });
        }

        if (Schema::hasTable('payroll_details')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                if (! Schema::hasColumn('payroll_details', 'arrear_amount')) {
                    $table->decimal('arrear_amount', 15, 2)->default(0)->after('gross_salary');
                    $table->string('arrear_note', 120)->nullable()->after('arrear_amount');
                }
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('sales_orders', 'pick_confirmed_at')) {
                    $table->timestamp('pick_confirmed_at')->nullable()->after('processing_at');
                    $table->string('packaging_notes', 500)->nullable()->after('pick_confirmed_at');
                }
            });
        }

        if (Schema::hasTable('goods_receipts')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                if (! Schema::hasColumn('goods_receipts', 'qc_officer_name')) {
                    $table->string('qc_officer_name', 120)->nullable()->after('notes');
                }
                if (! Schema::hasColumn('goods_receipts', 'qc_photo_path')) {
                    $table->string('qc_photo_path', 500)->nullable()->after('qc_officer_name');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_arrears');

        if (Schema::hasTable('payroll_details')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                $table->dropColumn(['arrear_amount', 'arrear_note']);
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['pick_confirmed_at', 'packaging_notes']);
            });
        }
    }
};
