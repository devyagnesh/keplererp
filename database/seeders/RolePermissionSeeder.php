<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds SRS-aligned permissions and default roles (ManufactureERP Module 2).
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        $permissionNames = [
            'company.view',
            'company.edit',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',
            'vendors.approve',
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.grn',
            'purchase.pr.create',
            'purchase.po.create',
            'purchase.po.approve',
            'purchase.grn.create',
            'sales.quotation.create',
            'sales.order.create',
            'sales.invoice.create',
            'sales.dispatch',
            'production.bom.create',
            'production.order.create',
            'production.log',
            'finance.voucher.create',
            'finance.payment.approve',
            'finance.reports.view',
            'hr.employee.manage',
            'hr.attendance.mark',
            'hr.payroll.run',
            'hr.payslip.view',
            'reports.sales',
            'reports.purchase',
            'reports.inventory',
            'reports.finance',
        ];

        foreach ($permissionNames as $name) {
            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => $guard]
            );
        }

        $all = Permission::query()->where('guard_name', $guard)->pluck('name')->all();

        $roleMatrix = [
            'Super Admin' => $all,
            'Admin' => $all,
            'Purchase Manager' => [
                'company.view',
                'vendors.view',
                'vendors.create',
                'vendors.edit',
                'vendors.approve',
                'inventory.view',
                'inventory.grn',
                'purchase.pr.create',
                'purchase.po.create',
                'purchase.po.approve',
                'purchase.grn.create',
                'reports.purchase',
                'reports.inventory',
            ],
            'Sales Manager' => [
                'company.view',
                'customers.view',
                'customers.create',
                'customers.edit',
                'inventory.view',
                'sales.quotation.create',
                'sales.order.create',
                'sales.invoice.create',
                'sales.dispatch',
                'reports.sales',
            ],
            'Warehouse Manager' => [
                'company.view',
                'inventory.view',
                'inventory.adjust',
                'inventory.transfer',
                'inventory.grn',
                'purchase.grn.create',
                'sales.dispatch',
                'reports.inventory',
            ],
            'Accountant' => [
                'company.view',
                'finance.voucher.create',
                'finance.payment.approve',
                'finance.reports.view',
                'reports.finance',
                'reports.purchase',
                'reports.sales',
            ],
            'HR Manager' => [
                'company.view',
                'hr.employee.manage',
                'hr.attendance.mark',
                'hr.payroll.run',
            ],
            'Production Supervisor' => [
                'company.view',
                'inventory.view',
                'production.bom.create',
                'production.order.create',
                'production.log',
                'reports.inventory',
            ],
            'Staff' => [
                'company.view',
                'reports.sales',
                'reports.inventory',
            ],
            'Employee' => [
                'hr.payslip.view',
            ],
        ];

        foreach ($roleMatrix as $roleName => $perms) {
            /** @var Role $role */
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $role->syncPermissions($perms);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
