<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS full completion: TDS, batch yield, production/WIP accounts.
     */
    public function up(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'monthly_tds')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->decimal('monthly_tds', 10, 2)->default(0)->after('basic_salary');
            });
        }

        if (Schema::hasTable('bill_of_materials') && ! Schema::hasColumn('bill_of_materials', 'batch_yield_qty')) {
            Schema::table('bill_of_materials', function (Blueprint $table) {
                $table->decimal('batch_yield_qty', 15, 4)->default(1)->after('version');
            });
        }

        $now = now();
        $accounts = [
            ['account_code' => 'WIP-PROD', 'account_name' => 'Work in progress — production', 'account_type' => 'asset'],
            ['account_code' => 'INV-ADJUST', 'account_name' => 'Inventory adjustment expense', 'account_type' => 'expense'],
            ['account_code' => 'TDS-PAYABLE', 'account_name' => 'TDS payable', 'account_type' => 'liability'],
        ];

        foreach ($accounts as $row) {
            DB::table('accounts')->updateOrInsert(
                ['account_code' => $row['account_code']],
                array_merge($row, ['parent_id' => null, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'monthly_tds')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('monthly_tds');
            });
        }

        if (Schema::hasTable('bill_of_materials') && Schema::hasColumn('bill_of_materials', 'batch_yield_qty')) {
            Schema::table('bill_of_materials', function (Blueprint $table) {
                $table->dropColumn('batch_yield_qty');
            });
        }

        DB::table('accounts')->whereIn('account_code', ['WIP-PROD', 'INV-ADJUST', 'TDS-PAYABLE'])->delete();
    }
};
