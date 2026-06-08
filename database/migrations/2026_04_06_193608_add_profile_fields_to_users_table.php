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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 15)->nullable()->after('password')->comment('10-digit Indian mobile');
            $table->string('whatsapp_number', 15)->nullable()->after('phone');
            $table->unsignedBigInteger('employee_id')->nullable()->after('whatsapp_number')->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('employee_id')->index();
            $table->boolean('is_active')->default(true)->after('warehouse_id')->index();
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone',
                'whatsapp_number',
                'employee_id',
                'warehouse_id',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
