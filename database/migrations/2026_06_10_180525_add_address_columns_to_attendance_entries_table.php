<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse-geocoded street addresses for GPS check-in / check-out.
     */
    public function up(): void
    {
        Schema::table('attendance_entries', function (Blueprint $table) {
            $table->text('check_in_address')->nullable()->after('check_in_accuracy_m');
            $table->text('check_out_address')->nullable()->after('check_out_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_entries', function (Blueprint $table) {
            $table->dropColumn(['check_in_address', 'check_out_address']);
        });
    }
};
