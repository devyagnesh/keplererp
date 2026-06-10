<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GPS check-in / check-out fields for self-service and HR map views.
     */
    public function up(): void
    {
        Schema::table('attendance_entries', function (Blueprint $table) {
            $table->timestamp('check_in_at')->nullable()->after('status');
            $table->timestamp('check_out_at')->nullable()->after('check_in_at');
            $table->decimal('check_in_latitude', 10, 8)->nullable()->comment('WGS84 degrees');
            $table->decimal('check_in_longitude', 11, 8)->nullable()->comment('WGS84 degrees');
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->decimal('check_in_accuracy_m', 8, 3)->nullable()->comment('GPS horizontal accuracy metres');
            $table->decimal('check_out_accuracy_m', 8, 3)->nullable();
            $table->decimal('check_in_altitude_m', 10, 3)->nullable();
            $table->decimal('check_out_altitude_m', 10, 3)->nullable();
            $table->json('check_in_meta')->nullable()->comment('Full device geolocation payload');
            $table->json('check_out_meta')->nullable();
            $table->string('source', 24)->default('hr_manual')->index()->comment('hr_manual or self_service');
            $table->foreignId('marked_by_user_id')->nullable()->after('source')->constrained('users')->nullOnDelete();

            $table->index(['work_date', 'employee_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_entries', function (Blueprint $table) {
            $table->dropForeign(['marked_by_user_id']);
            $table->dropIndex(['work_date', 'employee_id', 'source']);
            $table->dropColumn([
                'check_in_at',
                'check_out_at',
                'check_in_latitude',
                'check_in_longitude',
                'check_out_latitude',
                'check_out_longitude',
                'check_in_accuracy_m',
                'check_out_accuracy_m',
                'check_in_altitude_m',
                'check_out_altitude_m',
                'check_in_meta',
                'check_out_meta',
                'source',
                'marked_by_user_id',
            ]);
        });
    }
};
