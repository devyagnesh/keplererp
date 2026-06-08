<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentDesignationSeeder::class,
            DemoUsersSeeder::class,
        ]);

        if (Company::query()->count() === 0) {
            Company::factory()->create([
                'company_name' => 'Kepler Tools Pvt. Ltd.',
                'legal_name' => 'Kepler Tools Private Limited',
                'whatsapp_enabled' => false,
            ]);
        }
    }
}
