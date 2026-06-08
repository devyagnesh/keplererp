<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\GstPeriodLock;
use App\Models\InventoryBalance;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\GstPeriodLockService;
use App\Services\InventoryStockService;
use Database\Seeders\DepartmentDesignationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SrsComplianceModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_grn_return_reduces_stock_and_creates_debit_note(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $warehouse = Warehouse::query()->create(['code' => 'WH-R', 'name' => 'R', 'city' => 'Ahd', 'is_active' => true]);
        $vendor = Vendor::factory()->create();
        $item = Item::query()->create([
            'sku' => 'SKU-R', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'RAW',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '20', $user->id, []);
        $grn = GoodsReceipt::query()->create([
            'grn_number' => 'GRN-R1',
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'received_at' => now(),
            'status' => 'posted',
            'posted_at' => now(),
        ]);
        GoodsReceiptLine::query()->create([
            'goods_receipt_id' => $grn->id,
            'item_id' => $item->id,
            'quantity' => '20',
            'accepted_qty' => '20',
            'rejected_qty' => '0',
        ]);

        $this->actingAs($user)->postJson(route('admin.purchase.grn-returns.store'), [
            'goods_receipt_id' => $grn->id,
            'reason' => 'Damaged',
            'debit_amount' => '500.00',
            'lines' => [['item_id' => $item->id, 'quantity' => '2']],
        ])->assertCreated();

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->value('quantity');
        $this->assertSame(0, bccomp('18.0000', (string) $balance, 4));
        $this->assertDatabaseHas('debit_notes', ['vendor_id' => $vendor->id, 'amount' => '500.00']);
    }

    public function test_credit_note_posts_with_journal(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create(['state_code' => '24']);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $customer = Customer::factory()->create(['state_code' => '24']);
        $item = Item::query()->create([
            'sku' => 'SKU-CN', 'name' => 'Item', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.credit-notes.store'), [
            'customer_id' => $customer->id,
            'reason' => 'Return',
            'lines' => [['item_id' => $item->id, 'quantity' => '1', 'unit_price' => '100']],
        ])->assertCreated();

        $this->assertDatabaseHas('credit_notes', ['customer_id' => $customer->id, 'status' => 'posted']);
        $this->assertDatabaseHas('accounting_journal_entries', [
            'reference_type' => \App\Models\CreditNote::class,
        ]);
    }

    public function test_gst_period_lock_blocks_new_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Company::factory()->create(['state_code' => '24']);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        app(GstPeriodLockService::class)->lock((int) now()->year, (int) now()->month, $user);
        $customer = Customer::factory()->create(['state_code' => '24']);
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-LOCK',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100',
            'taxable_amount' => '100',
            'total_amount' => '118',
            'payment_terms_days' => 30,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))->assertUnprocessable();
    }

    public function test_gstr1_json_export(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.reports.gstr1.json', [
            'year' => now()->year,
            'month' => now()->month,
        ]))->assertOk()->assertHeader('content-type', 'application/json');
    }

    public function test_production_release_creates_materials(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $warehouse = Warehouse::query()->create(['code' => 'WH-P', 'name' => 'P', 'city' => 'Ahd', 'is_active' => true]);
        $fg = Item::query()->create([
            'sku' => 'FG-1', 'name' => 'FG', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'FINISHED_GOOD',
        ]);
        $rm = Item::query()->create([
            'sku' => 'RM-1', 'name' => 'RM', 'uom' => 'PCS', 'reorder_level' => 0,
            'is_active' => true, 'hsn_code' => '12345678', 'gst_rate' => 18, 'item_type' => 'RAW',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $rm->id, '100', $user->id, []);
        $bom = \App\Models\BillOfMaterial::query()->create([
            'parent_item_id' => $fg->id,
            'version' => 1,
            'is_active' => true,
        ]);
        \App\Models\BillOfMaterialLine::query()->create([
            'bill_of_material_id' => $bom->id,
            'component_item_id' => $rm->id,
            'quantity_per' => '2',
        ]);
        $wo = ProductionOrder::query()->create([
            'wo_number' => 'WO-MAT',
            'item_id' => $fg->id,
            'bom_id' => $bom->id,
            'warehouse_id' => $warehouse->id,
            'qty_planned' => '5',
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->putJson(route('admin.production.work-orders.update', $wo), [
            'status' => 'in_progress',
        ])->assertOk();

        $this->assertDatabaseHas('production_order_materials', [
            'production_order_id' => $wo->id,
            'item_id' => $rm->id,
        ]);
        $this->assertGreaterThan(0, ProductionOrderMaterial::query()->where('production_order_id', $wo->id)->count());
    }

    public function test_sales_order_pick_and_pack_processing(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $customer = Customer::factory()->create();
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-PP',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => '100',
            'taxable_amount' => '100',
            'total_amount' => '118',
            'payment_terms_days' => 30,
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.process', $order), [
            'courier_name' => 'BlueDart',
            'tracking_number' => 'TRK1',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('processing', $order->status);
        $this->assertNotNull($order->processing_at);
    }

    public function test_profit_loss_csv_export(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.reports.profit-loss'))->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_department_seeder_creates_masters(): void
    {
        $this->seed(DepartmentDesignationSeeder::class);
        $this->assertDatabaseHas('departments', ['code' => 'PROD']);
        $this->assertDatabaseHas('designations', ['code' => 'MGR']);
    }

    public function test_gst_period_lock_endpoint(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->postJson(route('admin.reports.gst-period.lock'), [
            'year' => now()->year,
            'month' => now()->month,
        ])->assertOk();

        $this->assertInstanceOf(GstPeriodLock::class, GstPeriodLock::query()->first());
    }
}
