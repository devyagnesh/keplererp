<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\GstPeriodLock;
use App\Models\InventoryBalance;
use App\Models\Item;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\GstPeriodLockService;
use App\Services\LeaveBalanceService;
use App\Services\StockReconciliationService;
use App\Services\WarehouseTransferService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SRS Chapter 22 integration scenarios (UC 22.5–22.7).
 */
class SrsChapter22IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_warehouse_transfer_workflow_draft_to_received(): void
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
        $this->seedLedgerBalance($from->id, $item->id, '100');

        $service = app(WarehouseTransferService::class);
        $transfer = $service->createDraft($from->id, $to->id, 'Restock', [
            ['item_id' => $item->id, 'qty_requested' => '10'],
        ], $this->admin);

        $this->assertSame(WarehouseTransfer::STATUS_DRAFT, $transfer->status);

        $service->approve($transfer, $this->admin);
        $service->dispatch($transfer->fresh(), $this->admin);

        $fromBal = InventoryBalance::query()->where('warehouse_id', $from->id)->where('item_id', $item->id)->first();
        $this->assertSame('90.0000', (string) $fromBal->quantity);

        $line = $transfer->fresh()->lines->first();
        $service->receive($transfer->fresh(), $this->admin, [
            ['id' => $line->id, 'qty_received' => '10'],
        ]);

        $toBal = InventoryBalance::query()->where('warehouse_id', $to->id)->where('item_id', $item->id)->first();
        $this->assertSame('10.0000', (string) $toBal->quantity);
        $this->assertSame(WarehouseTransfer::STATUS_RECEIVED, $transfer->fresh()->status);
    }

    public function test_leave_balance_initialized_and_deducted_on_approval(): void
    {
        $employee = Employee::query()->create([
            'emp_code' => 'EMP-'.uniqid(),
            'name' => 'Test Employee',
            'is_active' => true,
            'join_date' => now()->startOfYear(),
            'basic_salary' => '20000.00',
        ]);
        app(LeaveBalanceService::class)->initializeForEmployee($employee);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type' => 'CL',
            'balance_days' => 12,
        ]);

        $leave = LeaveApplication::query()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->startOfMonth()->addDays(1),
            'leave_type' => 'CL',
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.hr.leave.approve', $leave))
            ->assertOk()
            ->assertJsonPath('status', true);

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type', 'CL')
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(10.0, (float) $balance->balance_days);
    }

    public function test_stock_reconciliation_posts_variance_adjustment(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-R', 'name' => 'R', 'city' => 'Z', 'is_active' => true]);
        $item = Item::query()->create([
            'sku' => 'SR-'.uniqid(),
            'name' => 'Recon Item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);
        InventoryBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => '50',
        ]);
        $this->seedLedgerBalance($warehouse->id, $item->id, '50');

        $service = app(StockReconciliationService::class);
        $recon = $service->createDraft($warehouse->id, (int) now()->year, (int) now()->month, $this->admin);
        $line = $recon->lines->first();

        $service->updateCounts($recon, [
            ['id' => $line->id, 'physical_qty' => '48', 'reason' => 'Count short'],
        ]);
        $service->post($recon->fresh(), $this->admin);

        $bal = InventoryBalance::query()->where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->first();
        $this->assertSame('48.0000', (string) $bal->quantity);
    }

    public function test_gst_filing_arn_is_recorded(): void
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
        $this->assertSame('ARN789012', $lock->gstr3b_arn);

        $this->actingAs($this->admin)
            ->postJson(route('admin.reports.gst-period.filing'), [
                'year' => now()->year,
                'month' => now()->month,
                'gstr1_arn' => 'ARN-API-1',
            ])
            ->assertOk();

        $this->assertSame('ARN-API-1', GstPeriodLock::query()->first()?->gstr1_arn);
    }

    public function test_vendor_portal_forces_password_change(): void
    {
        $vendor = Vendor::factory()->create([
            'portal_enabled' => true,
            'portal_password' => Hash::make('TempPass123'),
            'portal_must_change_password' => true,
        ]);

        $this->actingAs($vendor, 'vendor')
            ->get(route('vendor.portal.dashboard'))
            ->assertRedirect(route('vendor.portal.change-password'));

        $this->actingAs($vendor, 'vendor')
            ->postJson(route('vendor.portal.change-password.submit'), [
                'current_password' => 'TempPass123',
                'password' => 'NewSecure99',
                'password_confirmation' => 'NewSecure99',
            ])
            ->assertOk();

        $vendor->refresh();
        $this->assertFalse($vendor->portal_must_change_password);
        $this->assertNotNull($vendor->portal_password_changed_at);
    }

    public function test_payroll_mark_paid_updates_details(): void
    {
        $run = PayrollRun::query()->create([
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $employee = Employee::query()->create([
            'emp_code' => 'EMP-PAY-'.uniqid(),
            'name' => 'Payroll Employee',
            'is_active' => true,
            'basic_salary' => '20000.00',
        ]);
        PayrollDetail::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'net_salary' => '25000.00',
            'payment_status' => 'PENDING',
            'gross_salary' => '30000.00',
            'basic_salary' => '20000.00',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.hr.payroll-runs.mark-paid', $run))
            ->assertOk();

        $this->assertDatabaseHas('payroll_details', [
            'payroll_run_id' => $run->id,
            'payment_status' => 'PAID',
        ]);
        $this->assertNotNull($run->fresh()->paid_at);
    }

    protected function seedLedgerBalance(int $warehouseId, int $itemId, string $qty): void
    {
        StockLedger::query()->create([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'transaction_type' => 'OPENING',
            'qty_in' => $qty,
            'qty_out' => null,
            'balance_qty' => $qty,
            'created_by' => $this->admin->id,
        ]);
    }
}
