<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DebitNote;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PostsGoodsReceipts;
use Tests\TestCase;

class AdminListingModulesTest extends TestCase
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

    public function test_sales_invoice_index_and_data(): void
    {
        $customer = Customer::factory()->create();
        $order = SalesOrder::query()->create([
            'order_number' => 'SO-LST-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'dispatched',
            'subtotal' => '1000',
            'taxable_amount' => '1000',
            'total_amount' => '1180',
            'created_by' => $this->admin->id,
        ]);
        Invoice::query()->create([
            'invoice_number' => 'INV-LST-1',
            'sales_order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'place_of_supply' => '24',
            'subtotal' => '1000',
            'discount_amount' => '0',
            'taxable_amount' => '1000',
            'cgst_amount' => '90',
            'sgst_amount' => '90',
            'igst_amount' => '0',
            'total_amount' => '1180',
            'status' => 'posted',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.sales.invoices.index'))
            ->assertOk()
            ->assertSee('Sales Invoices');

        $this->actingAs($this->admin)
            ->postJson(route('admin.sales.invoices.data'))
            ->assertOk()
            ->assertJsonPath('data.0.invoice_number', 'INV-LST-1')
            ->assertJsonPath('data.0.customer', $customer->name);
    }

    public function test_debit_note_index_and_data(): void
    {
        $vendor = Vendor::factory()->create();
        DebitNote::query()->create([
            'debit_note_number' => 'DN-LST-1',
            'vendor_id' => $vendor->id,
            'amount' => '500.00',
            'status' => 'posted',
            'reason' => 'Damaged goods',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.purchase.debit-notes.index'))
            ->assertOk()
            ->assertSee('Debit Notes');

        $this->actingAs($this->admin)
            ->postJson(route('admin.purchase.debit-notes.data'))
            ->assertOk()
            ->assertJsonPath('data.0.debit_note_number', 'DN-LST-1')
            ->assertJsonPath('data.0.vendor', $vendor->name);
    }

    public function test_audit_log_index_and_data(): void
    {
        AuditLog::query()->create([
            'action' => 'sales_order.dispatched',
            'description' => 'Sales order SO-001 dispatched.',
            'user_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Log');

        $this->actingAs($this->admin)
            ->postJson(route('admin.audit-logs.data'))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'sales_order.dispatched');
    }

    public function test_debit_note_listing_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales Manager');

        $this->actingAs($user)
            ->get(route('admin.purchase.debit-notes.index'))
            ->assertForbidden();
    }

    public function test_audit_log_listing_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Accountant');

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_grn_return_creates_debit_note_visible_in_listing(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-DN', 'name' => 'DN WH', 'city' => 'Pune', 'is_active' => true]);
        $vendor = Vendor::factory()->create();
        $item = Item::query()->create([
            'sku' => 'SKU-DN-'.uniqid(),
            'name' => 'Test Item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'RAW',
        ]);
        app(InventoryStockService::class)->adjust($warehouse->id, $item->id, '20', $this->admin->id, []);
        $grn = GoodsReceipt::query()->create([
            'grn_number' => 'GRN-DN-1',
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

        $this->actingAs($this->admin)->postJson(route('admin.purchase.grn-returns.store'), [
            'goods_receipt_id' => $grn->id,
            'reason' => 'Damaged',
            'debit_amount' => '500.00',
            'lines' => [['item_id' => $item->id, 'quantity' => '2']],
        ])->assertCreated();

        $this->actingAs($this->admin)
            ->postJson(route('admin.purchase.debit-notes.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
