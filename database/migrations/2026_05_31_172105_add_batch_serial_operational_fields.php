<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_order_lines', 'batch_no')) {
            Schema::table('sales_order_lines', function (Blueprint $table) {
                $table->string('batch_no', 50)->nullable()->after('line_total');
                $table->string('serial_no', 50)->nullable()->after('batch_no');
            });
        }

        if (! Schema::hasColumn('stock_movements', 'batch_no')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->string('batch_no', 50)->nullable()->after('item_id');
                $table->string('serial_no', 50)->nullable()->after('batch_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_order_lines', 'batch_no')) {
            Schema::table('sales_order_lines', function (Blueprint $table) {
                $table->dropColumn(['batch_no', 'serial_no']);
            });
        }

        if (Schema::hasColumn('stock_movements', 'batch_no')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropColumn(['batch_no', 'serial_no']);
            });
        }
    }
};
