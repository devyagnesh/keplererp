<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

/**
 * Default HR master data for SRS department/designation fields.
 */
class DepartmentDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'PROD', 'name' => 'Production'],
            ['code' => 'PUR', 'name' => 'Purchase'],
            ['code' => 'SALES', 'name' => 'Sales'],
            ['code' => 'FIN', 'name' => 'Finance'],
            ['code' => 'HR', 'name' => 'Human Resources'],
        ];

        foreach ($departments as $row) {
            Department::query()->firstOrCreate(['code' => $row['code']], array_merge($row, ['is_active' => true]));
        }

        $designations = [
            ['code' => 'MGR', 'name' => 'Manager'],
            ['code' => 'EXE', 'name' => 'Executive'],
            ['code' => 'SUP', 'name' => 'Supervisor'],
            ['code' => 'OPR', 'name' => 'Operator'],
        ];

        foreach ($designations as $row) {
            Designation::query()->firstOrCreate(['code' => $row['code']], array_merge($row, ['is_active' => true]));
        }
    }
}
