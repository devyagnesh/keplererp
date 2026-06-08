<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS v1.0 + v1.1 addendum tables and columns (cPanel-compatible; no Horizon/Redis requirements).
     */
    public function up(): void
    {
        if (Schema::hasTable('departments') && ! $this->isRecorded()) {
            $this->down();
        }

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->default('Ship To');
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100);
            $table->char('state_code', 2);
            $table->string('pincode', 6);
            $table->boolean('is_default_shipping')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('unit_price', 15, 4);
            $table->timestamps();
            $table->unique(['price_list_id', 'item_id']);
        });

        Schema::create('sales_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('enquiry_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name', 120);
            $table->string('phone', 15);
            $table->string('email', 191)->nullable();
            $table->string('status', 24)->default('open')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('po_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('approval_level')->default(1);
            $table->string('action', 24)->index();
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->string('approver_designation', 100)->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->useCurrent();
            $table->index(['purchase_order_id', 'approval_level']);
        });

        Schema::create('grn_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 40)->unique();
            $table->foreignId('goods_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('draft')->index();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('grn_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('batch_no', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_number', 40)->unique();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grn_return_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 24)->default('posted')->index();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('credit_note_date')->index();
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 24)->default('posted')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('taxable_value', 15, 2);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('gst_period_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('locked_at')->useCurrent();
            $table->unique(['period_year', 'period_month']);
        });

        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('planned_qty', 15, 4);
            $table->decimal('actual_qty', 15, 4)->nullable();
            $table->timestamps();
            $table->unique(['production_order_id', 'item_id']);
        });

        Schema::create('production_stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('status', 24)->default('reserved')->index();
            $table->timestamps();
            $table->index(['production_order_id', 'item_id', 'warehouse_id'], 'prod_stock_res_prod_item_wh_idx');
        });

        if (! Schema::hasColumn('warehouses', 'manager_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->foreignId('manager_id')->nullable()->after('city')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('items', 'is_batch_tracked')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('is_batch_tracked')->default(false)->after('item_type');
                $table->boolean('is_serial_tracked')->default(false)->after('is_batch_tracked');
                $table->decimal('reorder_qty', 15, 4)->default(0)->after('reorder_level');
            });
        }

        if (! Schema::hasColumn('customers', 'price_list_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('price_list_id')->nullable()->after('payment_terms_days')
                    ->constrained('price_lists')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('vendors', 'vendor_type')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('vendor_type', 24)->default('SUPPLIER')->after('status')->index();
                $table->decimal('credit_limit', 15, 2)->nullable()->after('payment_terms');
                $table->string('bank_name', 100)->nullable()->after('credit_limit');
                $table->string('bank_account_no', 20)->nullable();
                $table->char('bank_ifsc', 11)->nullable();
                $table->decimal('rating', 3, 2)->default(0)->after('bank_ifsc');
            });
        }

        if (! Schema::hasColumn('purchase_requisitions', 'department_id')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('requested_by')
                    ->constrained('departments')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('purchase_requisition_lines', 'estimated_price')) {
            Schema::table('purchase_requisition_lines', function (Blueprint $table) {
                $table->decimal('estimated_price', 15, 2)->nullable()->after('quantity');
                $table->string('purpose', 255)->nullable()->after('estimated_price');
            });
        }

        if (! Schema::hasColumn('purchase_orders', 'approval_level')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unsignedTinyInteger('approval_level')->default(0)->after('status');
            });
        }

        if (! Schema::hasColumn('goods_receipts', 'qc_photo_path')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                $table->string('qc_photo_path', 500)->nullable()->after('notes');
            });
        }

        if (! Schema::hasColumn('goods_receipt_lines', 'qc_status')) {
            Schema::table('goods_receipt_lines', function (Blueprint $table) {
                $table->string('qc_status', 16)->nullable()->after('rejected_qty');
                $table->text('qc_remarks')->nullable()->after('qc_status');
            });
        }

        if (! Schema::hasColumn('sales_orders', 'courier_name')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->foreignId('customer_address_id')->nullable()->after('customer_id')
                    ->constrained('customer_addresses')->nullOnDelete();
                $table->string('courier_name', 120)->nullable()->after('notes');
                $table->string('tracking_number', 80)->nullable()->after('courier_name');
                $table->string('transporter_name', 120)->nullable()->after('tracking_number');
                $table->timestamp('processing_at')->nullable()->after('dispatched_at');
            });
        }

        if (! Schema::hasColumn('sales_dispatch_challans', 'vehicle_no')) {
            Schema::table('sales_dispatch_challans', function (Blueprint $table) {
                $table->string('vehicle_no', 20)->nullable()->after('challan_number');
                $table->string('lr_number', 50)->nullable()->after('vehicle_no');
            });
        }

        if (! Schema::hasColumn('invoices', 'tcs_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('tcs_amount', 15, 2)->default(0)->after('igst_amount');
            });
        }

        if (! Schema::hasColumn('production_orders', 'sales_order_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->foreignId('sales_order_id')->nullable()->after('item_id')
                    ->constrained('sales_orders')->nullOnDelete();
                $table->timestamp('actual_start')->nullable()->after('planned_end');
                $table->timestamp('actual_end')->nullable()->after('actual_start');
            });
        }

        if (! Schema::hasColumn('employees', 'department_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('phone')->constrained('departments')->nullOnDelete();
                $table->foreignId('designation_id')->nullable()->after('department_id')->constrained('designations')->nullOnDelete();
                $table->date('date_of_birth')->nullable()->after('designation_id');
                $table->string('gender', 16)->nullable()->after('date_of_birth');
                $table->string('employment_type', 24)->default('FULL_TIME')->after('gender');
                $table->char('pan', 10)->nullable()->after('employment_type');
                $table->char('aadhaar', 12)->nullable()->after('pan');
                $table->string('bank_account_no', 20)->nullable()->after('aadhaar');
                $table->char('bank_ifsc', 11)->nullable()->after('bank_account_no');
                $table->string('uan', 22)->nullable()->after('esi_number');
                $table->boolean('pf_opted_in')->default(true)->after('uan');
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'attendance_locked')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->boolean('attendance_locked')->default(false)->after('status');
            });
        }

        if (! Schema::hasColumn('payroll_details', 'working_days')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                $table->unsignedSmallInteger('working_days')->default(0)->after('employee_id');
                $table->unsignedSmallInteger('present_days')->default(0)->after('working_days');
                $table->unsignedSmallInteger('lop_days')->default(0)->after('present_days');
                $table->decimal('conveyance', 10, 2)->default(0)->after('hra');
                $table->decimal('tds', 10, 2)->default(0)->after('professional_tax');
                $table->decimal('other_deductions', 10, 2)->default(0)->after('tds');
                $table->decimal('pf_employer', 10, 2)->default(0)->after('other_deductions');
                $table->decimal('esi_employer', 10, 2)->default(0)->after('pf_employer');
                $table->string('payment_status', 16)->default('PENDING')->after('net_salary');
            });
        }
    }

    protected function isRecorded(): bool
    {
        return DB::table('migrations')
            ->where('migration', '2026_05_31_163644_srs_full_compliance_modules')
            ->exists();
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_details', 'working_days')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                $table->dropColumn([
                    'working_days', 'present_days', 'lop_days', 'conveyance', 'tds',
                    'other_deductions', 'pf_employer', 'esi_employer', 'payment_status',
                ]);
            });
        }

        if (Schema::hasColumn('payroll_runs', 'attendance_locked')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropColumn('attendance_locked');
            });
        }

        if (Schema::hasColumn('employees', 'department_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropForeign(['designation_id']);
                $table->dropColumn([
                    'department_id', 'designation_id', 'date_of_birth', 'gender', 'employment_type',
                    'pan', 'aadhaar', 'bank_account_no', 'bank_ifsc', 'uan', 'pf_opted_in',
                ]);
            });
        }

        if (Schema::hasColumn('production_orders', 'sales_order_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->dropForeign(['sales_order_id']);
                $table->dropColumn(['sales_order_id', 'actual_start', 'actual_end']);
            });
        }

        if (Schema::hasColumn('invoices', 'tcs_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('tcs_amount');
            });
        }

        if (Schema::hasColumn('sales_dispatch_challans', 'vehicle_no')) {
            Schema::table('sales_dispatch_challans', function (Blueprint $table) {
                $table->dropColumn(['vehicle_no', 'lr_number']);
            });
        }

        if (Schema::hasColumn('sales_orders', 'customer_address_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['customer_address_id']);
                $table->dropColumn(['customer_address_id', 'courier_name', 'tracking_number', 'transporter_name', 'processing_at']);
            });
        }

        if (Schema::hasColumn('goods_receipt_lines', 'qc_status')) {
            Schema::table('goods_receipt_lines', function (Blueprint $table) {
                $table->dropColumn(['qc_status', 'qc_remarks']);
            });
        }

        if (Schema::hasColumn('goods_receipts', 'qc_photo_path')) {
            Schema::table('goods_receipts', function (Blueprint $table) {
                $table->dropColumn('qc_photo_path');
            });
        }

        if (Schema::hasColumn('purchase_orders', 'approval_level')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('approval_level');
            });
        }

        if (Schema::hasColumn('purchase_requisition_lines', 'estimated_price')) {
            Schema::table('purchase_requisition_lines', function (Blueprint $table) {
                $table->dropColumn(['estimated_price', 'purpose']);
            });
        }

        if (Schema::hasColumn('purchase_requisitions', 'department_id')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        if (Schema::hasColumn('vendors', 'vendor_type')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn(['vendor_type', 'credit_limit', 'bank_name', 'bank_account_no', 'bank_ifsc', 'rating']);
            });
        }

        if (Schema::hasColumn('customers', 'price_list_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['price_list_id']);
                $table->dropColumn('price_list_id');
            });
        }

        if (Schema::hasColumn('items', 'is_batch_tracked')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn(['is_batch_tracked', 'is_serial_tracked', 'reorder_qty']);
            });
        }

        if (Schema::hasColumn('warehouses', 'manager_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropForeign(['manager_id']);
                $table->dropColumn('manager_id');
            });
        }

        Schema::dropIfExists('production_stock_reservations');

        Schema::dropIfExists('production_order_materials');
        Schema::dropIfExists('gst_period_locks');
        Schema::dropIfExists('credit_note_lines');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('grn_return_lines');
        Schema::dropIfExists('grn_returns');
        Schema::dropIfExists('po_approvals');
        Schema::dropIfExists('sales_enquiries');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
    }
};
