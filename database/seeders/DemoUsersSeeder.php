<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Demo login for every SRS role — password is "password" for all accounts.
 */
class DemoUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    /**
     * @return array<int, array{name: string, email: string, role: string, phone: string}>
     */
    public static function accounts(): array
    {
        return [
            ['name' => 'Super Admin', 'email' => 'superadmin@gmail.com', 'role' => 'Super Admin', 'phone' => '9000000001'],
            ['name' => 'System Admin', 'email' => 'admin@gmail.com', 'role' => 'Admin', 'phone' => '9000000002'],
            ['name' => 'Priya Purchase', 'email' => 'purchase@gmail.com', 'role' => 'Purchase Manager', 'phone' => '9000000003'],
            ['name' => 'Rahul Sales', 'email' => 'sales@gmail.com', 'role' => 'Sales Manager', 'phone' => '9000000004'],
            ['name' => 'Suresh Warehouse', 'email' => 'warehouse@gmail.com', 'role' => 'Warehouse Manager', 'phone' => '9000000005'],
            ['name' => 'Anita Accounts', 'email' => 'accountant@gmail.com', 'role' => 'Accountant', 'phone' => '9000000006'],
            ['name' => 'Meena HR', 'email' => 'hr@gmail.com', 'role' => 'HR Manager', 'phone' => '9000000007'],
            ['name' => 'Vikram Production', 'email' => 'production@gmail.com', 'role' => 'Production Supervisor', 'phone' => '9000000008'],
            ['name' => 'Kiran Staff', 'email' => 'staff@gmail.com', 'role' => 'Staff', 'phone' => '9000000009'],
            ['name' => 'Amit Employee', 'email' => 'employee@gmail.com', 'role' => 'Employee', 'phone' => '9000000010'],
        ];
    }

    public function run(): void
    {
        $password = Hash::make(self::DEMO_PASSWORD);

        foreach (self::accounts() as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $password,
                    'phone' => $row['phone'],
                    'whatsapp_number' => $row['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::query()->where('name', $row['role'])->where('guard_name', 'web')->first();
            if ($role !== null) {
                $user->syncRoles([$role]);
            }
        }

        $employeeUser = User::query()->where('email', 'employee@gmail.com')->first();
        if ($employeeUser !== null) {
            Employee::query()->updateOrCreate(
                ['emp_code' => 'EMP-DEMO'],
                [
                    'name' => 'Amit Employee',
                    'department' => 'Production',
                    'designation' => 'Operator',
                    'user_id' => $employeeUser->id,
                    'is_active' => true,
                    'basic_salary' => '18000.00',
                    'pf_number' => 'PFDEMO001',
                    'pf_opted_in' => true,
                    'join_date' => now()->subYear()->toDateString(),
                ]
            );
        }

        $this->command?->info('Demo users seeded. Password for all: '.self::DEMO_PASSWORD);
        $this->command?->table(
            ['Role', 'Email', 'Password'],
            array_map(
                fn (array $row): array => [$row['role'], $row['email'], self::DEMO_PASSWORD],
                self::accounts()
            )
        );
    }
}
