<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_orders', 'vendor_delivery_status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('vendor_delivery_status', 24)->nullable()->after('status')->index();
                $table->text('vendor_delivery_notes')->nullable()->after('vendor_delivery_status');
                $table->timestamp('vendor_delivery_updated_at')->nullable()->after('vendor_delivery_notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'vendor_delivery_status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn(['vendor_delivery_status', 'vendor_delivery_notes', 'vendor_delivery_updated_at']);
            });
        }
    }
};
