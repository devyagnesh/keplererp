<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('pf_enabled')->default(true);
            $table->decimal('pf_employee_rate', 8, 4)->default(0.12)->comment('e.g. 0.12 = 12%');
            $table->decimal('pf_employer_rate', 8, 4)->default(0.12);
            $table->decimal('pf_wage_ceiling', 12, 2)->default(15000)->comment('PF wage ceiling for mandatory calc');
            $table->decimal('pf_max_monthly_contribution', 12, 2)->default(1800)->comment('Cap per month when ceiling applies');
            $table->boolean('pf_allow_opt_in_above_ceiling')->default(true)->comment('If basic > ceiling, PF when employee opted in');
            $table->boolean('esi_enabled')->default(true);
            $table->decimal('esi_gross_ceiling', 12, 2)->default(21000);
            $table->decimal('esi_employee_rate', 8, 4)->default(0.0075);
            $table->decimal('esi_employer_rate', 8, 4)->default(0.0325);
            $table->boolean('pt_enabled')->default(true);
            $table->decimal('pt_monthly_amount', 12, 2)->default(200);
            $table->decimal('pt_min_gross', 12, 2)->default(10000)->comment('PT applies when gross exceeds this');
            $table->timestamps();
        });

        Schema::create('allowance_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('included_in_esi_gross')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allowance_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('monthly_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'allowance_type_id']);
        });

        if (! Schema::hasColumn('payroll_details', 'earnings_breakdown')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                $table->json('earnings_breakdown')->nullable()->after('conveyance');
            });
        }

        DB::table('payroll_settings')->insert([
            'pf_enabled' => true,
            'pf_employee_rate' => 0.12,
            'pf_employer_rate' => 0.12,
            'pf_wage_ceiling' => 15000,
            'pf_max_monthly_contribution' => 1800,
            'pf_allow_opt_in_above_ceiling' => true,
            'esi_enabled' => true,
            'esi_gross_ceiling' => 21000,
            'esi_employee_rate' => 0.0075,
            'esi_employer_rate' => 0.0325,
            'pt_enabled' => true,
            'pt_monthly_amount' => 200,
            'pt_min_gross' => 10000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $now = now();
        DB::table('allowance_types')->insert([
            [
                'code' => 'HRA',
                'name' => 'House Rent Allowance',
                'sort_order' => 10,
                'is_active' => true,
                'included_in_esi_gross' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CONVEYANCE',
                'name' => 'Conveyance',
                'sort_order' => 20,
                'is_active' => true,
                'included_in_esi_gross' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $hraTypeId = DB::table('allowance_types')->where('code', 'HRA')->value('id');
        $employees = DB::table('employees')->where('hra', '>', 0)->get(['id', 'hra']);

        foreach ($employees as $emp) {
            if ($hraTypeId === null || bccomp((string) $emp->hra, '0', 2) <= 0) {
                continue;
            }
            $exists = DB::table('employee_allowances')
                ->where('employee_id', $emp->id)
                ->where('allowance_type_id', $hraTypeId)
                ->exists();
            if (! $exists) {
                DB::table('employee_allowances')->insert([
                    'employee_id' => $emp->id,
                    'allowance_type_id' => $hraTypeId,
                    'monthly_amount' => $emp->hra,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_details', 'earnings_breakdown')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                $table->dropColumn('earnings_breakdown');
            });
        }
        Schema::dropIfExists('employee_allowances');
        Schema::dropIfExists('allowance_types');
        Schema::dropIfExists('payroll_settings');
    }
};
