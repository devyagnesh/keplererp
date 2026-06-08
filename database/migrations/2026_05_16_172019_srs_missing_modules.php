<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS gaps: payments, license, leave, payroll details, vendor portal, invoice balances.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 40)->unique();
            $table->string('payment_type', 24)->index()->comment('vendor_payment|customer_receipt');
            $table->foreignId('vendor_payable_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 32)->default('NEFT');
            $table->string('utr_reference', 64)->nullable();
            $table->date('payment_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('total_amount');
        });

        Schema::table('vendor_payables', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('amount');
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 64)->unique();
            $table->string('client_name', 255);
            $table->string('domain', 255);
            $table->string('server_fingerprint', 255)->nullable();
            $table->text('token')->nullable();
            $table->json('modules_enabled')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('leave_type', 32)->index();
            $table->text('reason')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('hra', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('pf_deduction', 10, 2)->default(0);
            $table->decimal('esi_deduction', 10, 2)->default(0);
            $table->decimal('professional_tax', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->default(0)->after('is_active');
            $table->decimal('hra', 10, 2)->default(0)->after('basic_salary');
            $table->string('pf_number', 22)->nullable()->after('hra');
            $table->string('esi_number', 17)->nullable()->after('pf_number');
            $table->string('whatsapp', 15)->nullable()->after('phone');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('portal_enabled')->default(false)->after('status');
            $table->string('portal_password', 255)->nullable()->after('portal_enabled');
        });

        $now = now();
        DB::table('accounts')->updateOrInsert(
            ['account_code' => 'BANK-MAIN'],
            [
                'account_name' => 'Bank — Current account',
                'account_type' => 'asset',
                'parent_id' => null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('accounts')->updateOrInsert(
            ['account_code' => 'PF-PAYABLE'],
            [
                'account_name' => 'PF payable',
                'account_type' => 'liability',
                'parent_id' => null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('accounts')->updateOrInsert(
            ['account_code' => 'ESI-PAYABLE'],
            [
                'account_name' => 'ESI payable',
                'account_type' => 'liability',
                'parent_id' => null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('accounts')->updateOrInsert(
            ['account_code' => 'PT-PAYABLE'],
            [
                'account_name' => 'Professional tax payable',
                'account_type' => 'liability',
                'parent_id' => null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('accounts')->updateOrInsert(
            ['account_code' => 'SALARY-EXP'],
            [
                'account_name' => 'Salary expense',
                'account_type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (! DB::table('licenses')->exists()) {
            DB::table('licenses')->insert([
                'license_key' => 'DEV-LOCAL-'.strtoupper(substr(md5((string) config('app.key')), 0, 12)),
                'client_name' => 'Development',
                'domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost',
                'server_fingerprint' => null,
                'token' => null,
                'modules_enabled' => json_encode(['all']),
                'issued_at' => now()->toDateString(),
                'expires_at' => now()->addYears(10)->toDateString(),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['portal_enabled', 'portal_password']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['basic_salary', 'hra', 'pf_number', 'esi_number', 'whatsapp']);
        });

        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('licenses');

        Schema::table('vendor_payables', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });

        Schema::dropIfExists('payments');

        DB::table('accounts')->whereIn('account_code', ['BANK-MAIN', 'PF-PAYABLE', 'ESI-PAYABLE', 'PT-PAYABLE', 'SALARY-EXP'])->delete();
    }
};
