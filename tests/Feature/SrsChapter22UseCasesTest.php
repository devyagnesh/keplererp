<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GstPeriodLock;
use App\Models\InventoryBalance;
use App\Models\Item;
use App\Models\PayrollRun;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\GstPeriodLockService;
use App\Services\InventoryStockService;
use App\Services\LeaveBalanceService;
use App\Services\Payroll\PayrollArrearService;
use App\Services\StockReconciliationService;
use App\Services\WarehouseTransferService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\ProcessesPayrollRuns;
use Tests\TestCase;

/**
 * SRS Chapter 22 live use cases UC 22.1–22.8 (QA script coverage).
 */
class SrsChapter22UseCasesTest extends TestCase
{
    use ProcessesPayrollRuns;
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create(['state_code' => '24']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    /** UC 22.1 — procurement GRN with QC (see SrsZeroGapComplianceTest). */
    public function test_uc_22_1_procurement_grn_route_exists(): void
    {
        $this->assertTrue(route('admin.purchase.grns.store') !== '');
        $this->assertTrue(route('admin.purchase.grns.create') !== '');
    }

    /** UC 22.2 — sales pick list load and confirm. */
    public function test_uc_22_2_sales_pick_list(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-UC22', 'name' => 'UC22', 'city' => 'Pune', 'is_active' => true]);
        $customer = Customer::factory()->create();
        $item = Item::query()->create([
            'sku' => 'FG-UC22-'.uniqid(),
            'name' => 'Brake Pad',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '87083000',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '500', $this->admin->id, []);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-UC22-'.uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'processing',
            'subtotal' => '92500.00',
            'taxable_amount' => '92500.00',
            'total_amount' => '109150.00',
            'created_by' => $this->admin->id,
        ]);
        SalesOrderLine::query()->create([
            'sales_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => '500',
            'unit_price' => '185',
            'taxable_value' => '92500',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.sales.orders.pick-list', $order))
            ->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.sales.orders.pick-list.confirm', $order), [
                'scanned_codes' => [$item->sku],
                'packaging_notes' => '10 boxes',
            ])
            ->assertOk();

        $this->assertNotNull($order->fresh()->pick_confirmed_at);
    }

    /** UC 22.3 — production material consumption saved. */
    public function test_uc_22_3_production_materials(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-PRD', 'name' => 'PRD', 'city' => 'Vapi', 'is_active' => true]);
        $fg = Item::query()->create([
            'sku' => 'FG-PRD-'.uniqid(), 'name' => 'Hypochlorite', 'uom' => 'LTR',
            'reorder_level' => 0, 'is_active' => true, 'hsn_code' => '28289000', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        $rm = Item::query()->create([
            'sku' => 'RM-PRD-'.uniqid(), 'name' => 'Chlorine', 'uom' => 'KG',
            'reorder_level' => 0, 'is_active' => true, 'hsn_code' => '28011000', 'gst_rate' => 18, 'item_type' => 'RAW_MATERIAL',
        ]);
        $wo = ProductionOrder::query()->create([
            'wo_number' => 'WO-UC22-'.uniqid(),
            'item_id' => $fg->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '100',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
        ]);
        $mat = ProductionOrderMaterial::query()->create([
            'production_order_id' => $wo->id,
            'item_id' => $rm->id,
            'planned_qty' => '8.5',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.production.work-orders.materials', $wo), [
                'materials' => [['id' => $mat->id, 'actual_qty' => '8.3']],
            ])
            ->assertOk();

        $this->assertSame('8.3000', (string) $mat->fresh()->actual_qty);
    }

    /** UC 22.4 — payroll run processes and marks paid. */
    public function test_uc_22_4_payroll_processing(): void
    {
        $employee = Employee::query()->create([
            'emp_code' => 'EMP-UC22-'.uniqid(),
            'name' => 'Payroll UC',
            'is_active' => true,
            'basic_salary' => '20000.00',
        ]);
        $run = PayrollRun::query()->create([
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'status' => 'draft',
        ]);

        $this->lockAndProcessPayroll($run, $this->admin);
        $this->approvePayroll($run->fresh(), $this->admin);

        $this->actingAs($this->admin)
            ->postJson(route('admin.hr.payroll-runs.mark-paid', $run->fresh()))
            ->assertOk();

        $this->assertDatabaseHas('payroll_details', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'payment_status' => 'PAID',
        ]);
    }

    /** UC 22.5 — GST filing ARN recorded. */
    public function test_uc_22_5_gst_filing_arn(): void
    {
        $lock = app(GstPeriodLockService::class)->recordFiling(
            (int) now()->year,
            (int) now()->month,
            'ARN123456',
            'ARN789012',
            '1500.00',
            $this->admin
        );

        $this->assertSame('ARN123456', $lock->gstr1_arn);

        $this->actingAs($this->admin)
            ->postJson(route('admin.reports.gst-period.filing'), [
                'year' => now()->year,
                'month' => now()->month,
                'gstr1_arn' => 'ARN-API-1',
            ])
            ->assertOk();

        $this->assertSame('ARN-API-1', GstPeriodLock::query()->first()?->gstr1_arn);
    }

    /** UC 22.6 — mid-month join arrear + leave balance. */
    public function test_uc_22_6_employee_onboarding(): void
    {
        $joinDate = now()->startOfMonth()->addDays(20);
        $employee = Employee::query()->create([
            'emp_code' => 'EMP-ONB-'.uniqid(),
            'name' => 'Suresh Mehta',
            'is_active' => true,
            'join_date' => $joinDate,
            'basic_salary' => '13500.00',
        ]);
        app(LeaveBalanceService::class)->initializeForEmployee($employee);
        PayrollRun::query()->create([
            'period_year' => (int) $joinDate->year,
            'period_month' => (int) $joinDate->month,
            'status' => 'processed',
        ]);
        $arrear = app(PayrollArrearService::class)->queueForNewEmployee($employee);

        $this->assertDatabaseHas('leave_balances', ['employee_id' => $employee->id]);
        $this->assertNotNull($arrear);
    }

    /** UC 22.7 — warehouse transfer and stock reconciliation. */
    public function test_uc_22_7_warehouse_transfer(): void
    {
        $from = Warehouse::query()->create(['code' => 'WH-A', 'name' => 'A', 'city' => 'X', 'is_active' => true]);
        $to = Warehouse::query()->create(['code' => 'WH-B', 'name' => 'B', 'city' => 'Y', 'is_active' => true]);
        $item = Item::query()->create([
            'sku' => 'WT-'.uniqid(),
            'name' => 'Transfer Item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);
        InventoryBalance::query()->create([
            'warehouse_id' => $from->id,
            'item_id' => $item->id,
            'quantity' => '100',
        ]);
        StockLedger::query()->create([
            'warehouse_id' => $from->id,
            'item_id' => $item->id,
            'transaction_type' => 'OPENING',
            'qty_in' => '100',
            'qty_out' => null,
            'balance_qty' => '100',
            'created_by' => $this->admin->id,
        ]);

        $service = app(WarehouseTransferService::class);
        $transfer = $service->createDraft($from->id, $to->id, 'Restock', [
            ['item_id' => $item->id, 'qty_requested' => '10'],
        ], $this->admin);
        $service->approve($transfer, $this->admin);
        $service->dispatch($transfer->fresh(), $this->admin);
        $line = $transfer->fresh()->lines->first();
        $service->receive($transfer->fresh(), $this->admin, [
            ['id' => $line->id, 'qty_received' => '10'],
        ]);

        $this->assertSame(WarehouseTransfer::STATUS_RECEIVED, $transfer->fresh()->status);

        $warehouse = Warehouse::query()->create(['code' => 'WH-R', 'name' => 'R', 'city' => 'Z', 'is_active' => true]);
        InventoryBalance::query()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => '50']);
        StockLedger::query()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'transaction_type' => 'OPENING',
            'qty_in' => '50',
            'qty_out' => null,
            'balance_qty' => '50',
            'created_by' => $this->admin->id,
        ]);
        $recon = app(StockReconciliationService::class)->createDraft($warehouse->id, (int) now()->year, (int) now()->month, $this->admin);
        $reconLine = $recon->lines->first();
        app(StockReconciliationService::class)->updateCounts($recon, [
            ['id' => $reconLine->id, 'physical_qty' => '48', 'reason' => 'Count short'],
        ]);
        app(StockReconciliationService::class)->post($recon->fresh(), $this->admin);

        $bal = InventoryBalance::query()->where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->first();
        $this->assertSame('48.0000', (string) $bal->quantity);
    }

    /** UC 22.8 — vendor portal forced password change. */
    public function test_uc_22_8_vendor_portal(): void
    {
        $vendor = Vendor::factory()->create([
            'portal_enabled' => true,
            'portal_password' => Hash::make('TempPass123'),
            'portal_must_change_password' => true,
        ]);

        $this->actingAs($vendor, 'vendor')
            ->get(route('vendor.portal.dashboard'))
            ->assertRedirect(route('vendor.portal.change-password'));
    }
}
