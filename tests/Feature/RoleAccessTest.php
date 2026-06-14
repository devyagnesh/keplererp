<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Verifies SRS role matrix: each role reaches allowed modules and is blocked elsewhere.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(string $roleName): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($roleName);

        return $user;
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function allowedRoutesProvider(): array
    {
        return [
            'Purchase Manager vendors' => ['Purchase Manager', 'admin.vendors.index', 200],
            'Purchase Manager PO' => ['Purchase Manager', 'admin.purchase.orders.index', 200],
            'Purchase Manager PR' => ['Purchase Manager', 'admin.purchase.requisitions.index', 200],
            'Purchase Manager GRN' => ['Purchase Manager', 'admin.purchase.grns.index', 200],
            'Sales Manager customers' => ['Sales Manager', 'admin.customers.index', 200],
            'Sales Manager quotations' => ['Sales Manager', 'admin.sales.quotations.index', 200],
            'Sales Manager orders' => ['Sales Manager', 'admin.sales.orders.index', 200],
            'Warehouse Manager inventory' => ['Warehouse Manager', 'admin.warehouses.index', 200],
            'Warehouse Manager adjust' => ['Warehouse Manager', 'admin.inventory.adjust.form', 200],
            'Warehouse Manager transfer' => ['Warehouse Manager', 'admin.inventory.transfer.form', 200],
            'Warehouse Manager traceability' => ['Warehouse Manager', 'admin.inventory.traceability.index', 200],
            'Accountant vouchers' => ['Accountant', 'admin.finance.vouchers.index', 200],
            'Accountant payments' => ['Accountant', 'admin.finance.payments.index', 200],
            'Accountant reports' => ['Accountant', 'admin.reports.index', 200],
            'HR Manager employees' => ['HR Manager', 'admin.hr.employees.index', 200],
            'HR Manager attendance' => ['HR Manager', 'admin.hr.attendance.index', 200],
            'HR Manager payroll' => ['HR Manager', 'admin.hr.payroll-runs.index', 200],
            'HR Manager payroll rules' => ['HR Manager', 'admin.hr.payroll-settings.edit', 200],
            'Production Supervisor BOM' => ['Production Supervisor', 'admin.production.boms.index', 200],
            'Production Supervisor work orders' => ['Production Supervisor', 'admin.production.work-orders.index', 200],
            'Staff reports' => ['Staff', 'admin.reports.index', 200],
            'Super Admin dashboard' => ['Super Admin', 'admin.dashboard', 200],
            'Admin users' => ['Admin', 'admin.users.index', 200],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function forbiddenRoutesProvider(): array
    {
        return [
            'Purchase Manager sales' => ['Purchase Manager', 'admin.sales.orders.index'],
            'Purchase Manager users' => ['Purchase Manager', 'admin.users.index'],
            'Sales Manager vendors' => ['Sales Manager', 'admin.vendors.index'],
            'Sales Manager purchase PO' => ['Sales Manager', 'admin.purchase.orders.index'],
            'Warehouse Manager finance' => ['Warehouse Manager', 'admin.finance.payments.index'],
            'Warehouse Manager purchase PO create' => ['Warehouse Manager', 'admin.purchase.orders.create'],
            'Accountant inventory master' => ['Accountant', 'admin.items.index'],
            'HR Manager purchase' => ['HR Manager', 'admin.purchase.orders.index'],
            'HR Manager company setup' => ['HR Manager', 'admin.company.edit'],
            'HR Manager dashboard' => ['HR Manager', 'admin.dashboard'],
            'Production Supervisor sales' => ['Production Supervisor', 'admin.sales.quotations.index'],
            'Staff vendors' => ['Staff', 'admin.vendors.index'],
            'Staff dashboard' => ['Staff', 'admin.dashboard'],
            'Employee admin users' => ['Employee', 'admin.users.index'],
            'Employee purchase' => ['Employee', 'admin.purchase.orders.index'],
        ];
    }

    #[DataProvider('allowedRoutesProvider')]
    public function test_role_can_access_allowed_route(string $role, string $routeName, int $expectedStatus): void
    {
        $this->actingAs($this->actingAsRole($role))
            ->get(route($routeName))
            ->assertStatus($expectedStatus);
    }

    #[DataProvider('forbiddenRoutesProvider')]
    public function test_role_cannot_access_forbidden_route(string $role, string $routeName): void
    {
        $this->actingAs($this->actingAsRole($role))
            ->get(route($routeName))
            ->assertForbidden();
    }

    public function test_employee_can_access_payslip_portal_when_linked(): void
    {
        $user = $this->actingAsRole('Employee');
        Employee::query()->create([
            'emp_code' => 'EMP-PAY',
            'name' => 'Portal User',
            'user_id' => $user->id,
            'is_active' => true,
            'basic_salary' => '0',
        ]);

        $this->actingAs($user)
            ->get(route('employee.payslips.index'))
            ->assertOk();
    }

    public function test_staff_cannot_access_users_module_via_middleware(): void
    {
        $this->actingAs($this->actingAsRole('Staff'))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_hr_manager_home_redirects_to_employees_not_company_setup(): void
    {
        $this->actingAs($this->actingAsRole('HR Manager'))
            ->get(route('admin.home'))
            ->assertRedirect(route('admin.hr.employees.index'));
    }

    public function test_warehouse_manager_cannot_create_purchase_order(): void
    {
        $this->actingAs($this->actingAsRole('Warehouse Manager'))
            ->get(route('admin.purchase.orders.create'))
            ->assertForbidden();
    }

    public function test_sales_manager_cannot_create_purchase_order(): void
    {
        $this->actingAs($this->actingAsRole('Sales Manager'))
            ->get(route('admin.purchase.orders.create'))
            ->assertForbidden();
    }

    public function test_warehouse_manager_can_dispatch_sales_orders(): void
    {
        $this->actingAs($this->actingAsRole('Warehouse Manager'))
            ->get(route('admin.sales.orders.index'))
            ->assertOk();
    }
}
