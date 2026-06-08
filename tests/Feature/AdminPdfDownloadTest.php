<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\PurchaseOrder;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_pdf_route_returns_pdf_when_approved(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Purchase Manager');
        Company::factory()->create();
        $vendor = Vendor::factory()->create();
        $po = PurchaseOrder::query()->create([
            'po_number' => 'PO-PDF-1',
            'vendor_id' => $vendor->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => '100',
            'taxable_amount' => '100',
            'total_amount' => '118',
        ]);

        $this->actingAs($user)
            ->get(route('admin.purchase.orders.pdf', $po))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_employee_can_view_payslip_index_when_linked(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Employee');
        $employee = Employee::query()->create([
            'emp_code' => 'E-001',
            'name' => 'Test Staff',
            'user_id' => $user->id,
            'is_active' => true,
            'basic_salary' => '10000',
            'hra' => '0',
        ]);
        $run = PayrollRun::query()->create([
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        PayrollDetail::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => '10000',
            'hra' => '0',
            'gross_salary' => '10000',
            'pf_deduction' => '0',
            'esi_deduction' => '0',
            'professional_tax' => '0',
            'net_salary' => '10000',
        ]);

        $this->actingAs($user)
            ->get(route('employee.payslips.index'))
            ->assertOk()
            ->assertSee('My payslips', false);
    }

    public function test_quotation_pdf_route_returns_pdf(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Sales Manager');
        Company::factory()->create();
        $customer = Customer::factory()->create();
        $qt = SalesQuotation::query()->create([
            'quote_number' => 'QT-PDF',
            'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('admin.sales.quotations.pdf', $qt))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
