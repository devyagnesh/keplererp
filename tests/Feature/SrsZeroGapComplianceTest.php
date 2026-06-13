<?php

namespace Tests\Feature;

use App\Mail\InvoicePostedMail;
use App\Mail\PayslipProcessedMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\Item;
use App\Models\PayrollArrear;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use App\Services\Payroll\PayrollArrearService;
use App\Services\PayrollRunService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\PostsGoodsReceipts;
use Tests\TestCase;

/**
 * SRS v1.1 zero-gap compliance: arrear, QC, pick list, materials, PDF log, email attachments.
 */
class SrsZeroGapComplianceTest extends TestCase
{
    use PostsGoodsReceipts;
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

    public function test_payroll_arrear_settles_on_next_run(): void
    {
        $joinDate = now()->startOfMonth()->addDays(20);
        $employee = Employee::query()->create([
            'emp_code' => 'EMP-ARR-'.uniqid(),
            'name' => 'Arrear Employee',
            'email' => 'arrear@example.test',
            'is_active' => true,
            'join_date' => $joinDate,
            'basic_salary' => '13500.00',
        ]);

        PayrollRun::query()->create([
            'period_year' => (int) $joinDate->year,
            'period_month' => (int) $joinDate->month,
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        $arrear = app(PayrollArrearService::class)->queueForNewEmployee($employee);
        $this->assertNotNull($arrear);
        $this->assertSame(PayrollArrear::STATUS_PENDING, $arrear->status);

        $nextMonth = $joinDate->copy()->addMonth();
        $run = PayrollRun::query()->create([
            'period_year' => (int) $nextMonth->year,
            'period_month' => (int) $nextMonth->month,
            'status' => 'draft',
            'attendance_locked' => true,
        ]);

        app(PayrollRunService::class)->process($run, $this->admin);

        $detail = PayrollDetail::query()
            ->where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->first();

        $this->assertNotNull($detail);
        $this->assertGreaterThan(0, (float) $detail->arrear_amount);
        $this->assertSame(PayrollArrear::STATUS_SETTLED, $arrear->fresh()->status);
    }

    public function test_sales_pick_list_confirm_sets_timestamp(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-PK', 'name' => 'Pick WH', 'city' => 'Pune', 'is_active' => true]);
        $customer = Customer::factory()->create();
        $item = Item::query()->create([
            'sku' => 'FG-PICK-'.uniqid(),
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
            'order_number' => 'SO-PK-'.uniqid(),
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
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data.lines');

        $this->actingAs($this->admin)
            ->postJson(route('admin.sales.orders.pick-list.confirm', $order), [
                'scanned_codes' => [$item->sku],
                'packaging_notes' => '10 boxes × 50 pcs',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $order->refresh();
        $this->assertNotNull($order->pick_confirmed_at);
        $this->assertSame('10 boxes × 50 pcs', $order->packaging_notes);
    }

    public function test_production_materials_update_persists_actual_qty(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-PR', 'name' => 'Prod WH', 'city' => 'Vapi', 'is_active' => true]);
        $fg = Item::query()->create([
            'sku' => 'FG-PR-'.uniqid(),
            'name' => 'Hypochlorite',
            'uom' => 'LTR',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '28289000',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);
        $rm = Item::query()->create([
            'sku' => 'RM-PR-'.uniqid(),
            'name' => 'Chlorine',
            'uom' => 'KG',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '28011000',
            'gst_rate' => 18,
            'item_type' => 'RAW',
        ]);
        $wo = ProductionOrder::query()->create([
            'wo_number' => 'WO-PR-'.uniqid(),
            'item_id' => $fg->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '100',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
        ]);
        $material = ProductionOrderMaterial::query()->create([
            'production_order_id' => $wo->id,
            'item_id' => $rm->id,
            'planned_qty' => '8.5',
            'actual_qty' => null,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.production.work-orders.materials', $wo), [
                'materials' => [
                    ['id' => $material->id, 'actual_qty' => '8.3'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertSame('8.3000', (string) $material->fresh()->actual_qty);
    }

    public function test_pdf_activity_log_datatable_returns_rows(): void
    {
        GeneratedDocument::query()->create([
            'document_type' => 'tax_invoice',
            'documentable_type' => SalesOrder::class,
            'documentable_id' => 1,
            'module' => 'sales',
            'file_path' => 'documents/sales/test.pdf',
            'download_name' => 'INV-test.pdf',
            'generated_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.reports.pdf-log.data'), ['draw' => 1, 'start' => 0, 'length' => 10])
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'data']);
    }

    public function test_invoice_posted_mail_includes_pdf_attachment(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);
        $user = $this->admin;
        Company::factory()->create(['state_code' => '24']);
        $customer = Customer::factory()->create([
            'state_code' => '24',
            'email' => 'buyer@example.test',
        ]);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-MAIL-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100.00',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
            'payment_terms_days' => 30,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))->assertOk();

        Mail::assertQueued(InvoicePostedMail::class, function (InvoicePostedMail $mail): bool {
            return count($mail->attachments()) === 1;
        });
    }

    public function test_payslip_processed_mail_queued_with_attachment(): void
    {
        Mail::fake();

        $employee = Employee::query()->create([
            'emp_code' => 'EMP-MAIL-'.uniqid(),
            'name' => 'Mail Employee',
            'email' => 'payslip@example.test',
            'is_active' => true,
            'basic_salary' => '20000.00',
        ]);
        $run = PayrollRun::query()->create([
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'status' => 'draft',
            'attendance_locked' => true,
        ]);

        app(PayrollRunService::class)->process($run, $this->admin);
        app(PayrollRunService::class)->approve($run->fresh(), $this->admin);

        Mail::assertQueued(PayslipProcessedMail::class, function (PayslipProcessedMail $mail): bool {
            return count($mail->attachments()) === 1;
        });
    }

    public function test_grn_store_accepts_qc_officer_name(): void
    {
        Company::factory()->create(['state_code' => '24']);
        $vendor = Vendor::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create(['code' => 'WH-QC', 'name' => 'QC WH', 'city' => 'Ahd', 'is_active' => true]);
        $item = Item::query()->create([
            'sku' => 'RM-QC-'.uniqid(),
            'name' => 'Paracetamol API',
            'uom' => 'KG',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '29420090',
            'gst_rate' => 18,
            'item_type' => 'RAW_MATERIAL',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.purchase.orders.store'), [
                'vendor_id' => $vendor->id,
                'pr_id' => null,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->toDateString(),
                'expected_delivery' => null,
                'payment_terms_days' => 30,
                'notes' => null,
                'lines' => [
                    ['item_id' => $item->id, 'quantity' => 200, 'unit_cost' => 420],
                ],
            ])
            ->assertCreated();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $approver = User::factory()->create();
        $approver->assignRole('Super Admin');
        $this->actingAs($approver)
            ->postJson(route('admin.purchase.orders.approve', $po))
            ->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.purchase.grns.store'), [
                'purchase_order_id' => $po->id,
                'vendor_id' => $vendor->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->format('Y-m-d H:i:s'),
                'qc_officer_name' => 'Ravi Storekeeper',
                'lines' => [
                    [
                        'item_id' => $item->id,
                        'quantity' => '195',
                        'accepted_qty' => '190',
                        'rejected_qty' => '5',
                        'qc_status' => 'pass',
                        'qc_remarks' => '5 kg moisture rejection',
                    ],
                ],
            ])
            ->assertCreated();

        $grn = $this->postLatestGrn($this->admin);
        $this->assertSame('posted', $grn->status);
        $this->assertSame('Ravi Storekeeper', $grn->qc_officer_name);
    }
}
