<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('goods_receipt_lines', 'expiry_date')) {
            Schema::table('goods_receipt_lines', function (Blueprint $table) {
                $table->date('expiry_date')->nullable()->after('serial_no')->comment('Batch expiry from GRN');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('goods_receipt_lines', 'expiry_date')) {
            Schema::table('goods_receipt_lines', function (Blueprint $table) {
                $table->dropColumn('expiry_date');
            });
        }
    }
};
