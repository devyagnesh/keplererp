<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed minimal chart of accounts for auto-posted journals (SRS finance hooks).
     */
    public function up(): void
    {
        $now = now();
        $rows = [
            ['account_code' => 'INV-ASSET', 'account_name' => 'Inventory — Stock in hand', 'account_type' => 'asset', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'AP-TRADE', 'account_name' => 'Trade payables', 'account_type' => 'liability', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'AR-TRADE', 'account_name' => 'Trade receivables', 'account_type' => 'asset', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'SALES-REV', 'account_name' => 'Sales revenue', 'account_type' => 'revenue', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'CGST-IN', 'account_name' => 'CGST input tax', 'account_type' => 'asset', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'SGST-IN', 'account_name' => 'SGST input tax', 'account_type' => 'asset', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'IGST-IN', 'account_name' => 'IGST input tax', 'account_type' => 'asset', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'CGST-OUT', 'account_name' => 'CGST output tax', 'account_type' => 'liability', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'SGST-OUT', 'account_name' => 'SGST output tax', 'account_type' => 'liability', 'parent_id' => null, 'is_system' => true],
            ['account_code' => 'IGST-OUT', 'account_name' => 'IGST output tax', 'account_type' => 'liability', 'parent_id' => null, 'is_system' => true],
        ];

        foreach ($rows as $row) {
            DB::table('accounts')->updateOrInsert(
                ['account_code' => $row['account_code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('account_code', [
            'INV-ASSET', 'AP-TRADE', 'AR-TRADE', 'SALES-REV',
            'CGST-IN', 'SGST-IN', 'IGST-IN', 'CGST-OUT', 'SGST-OUT', 'IGST-OUT',
        ])->delete();
    }
};
