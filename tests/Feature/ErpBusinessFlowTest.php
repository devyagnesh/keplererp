<?php

namespace Tests\Feature;

use App\Models\AccountingJournalEntry;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SalesOrder;
use App\Models\StockLedger;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayable;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PR → approve → PO → approve → GRN posts stock, ledger, vendor payable, and balanced journal.
     */
    public function test_purchase_flow_pr_po_grn_creates_stock_ledger_payable_and_journal(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $approver = $this->makeSuperAdmin();
        $ctx = $this->seedPurchaseContext();

        $this->actingAs($user)->postJson(route('admin.purchase.requisitions.store'), [
            'required_date' => '2026-05-01',
            'warehouse_id' => $ctx['warehouse']->id,
            'notes' => null,
            'lines' => [
                ['item_id' => $ctx['item']->id, 'quantity' => 10],
            ],
        ])->assertCreated();

        $pr = PurchaseRequisition::query()->latest('id')->firstOrFail();
        $this->assertSame('draft', $pr->status);

        $this->actingAs($user)->postJson(route('admin.purchase.requisitions.submit', $pr))->assertOk();
        $pr->refresh();
        $this->assertSame('pending_approval', $pr->status);

        $this->actingAs($user)->postJson(route('admin.purchase.requisitions.approve', $pr))->assertOk();
        $pr->refresh();
        $this->assertSame('approved', $pr->status);

        $this->actingAs($user)->postJson(route('admin.purchase.orders.store'), [
            'vendor_id' => $ctx['vendor']->id,
            'pr_id' => $pr->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'order_date' => '2026-04-20',
            'expected_delivery' => '2026-04-25',
            'payment_terms_days' => 30,
            'notes' => null,
            'lines' => [
                ['item_id' => $ctx['item']->id, 'quantity' => 10, 'unit_cost' => 100],
            ],
        ])->assertCreated();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('draft', $po->status);
        $this->assertGreaterThan('0', (string) $po->total_amount);

        $this->actingAs($approver)->postJson(route('admin.purchase.orders.approve', $po))->assertOk();
        $po->refresh();
        $this->assertSame('approved', $po->status);

        $this->actingAs($user)->postJson(route('admin.purchase.grns.store'), [
            'purchase_order_id' => $po->id,
            'vendor_id' => $ctx['vendor']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'received_at' => '2026-04-19 10:00:00',
            'notes' => null,
            'lines' => [
                ['item_id' => $ctx['item']->id, 'quantity' => 10],
            ],
        ])->assertCreated();

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $ctx['warehouse']->id)
            ->where('item_id', $ctx['item']->id)
            ->first();
        $this->assertNotNull($balance);
        $this->assertEquals(10.0, (float) $balance->quantity);

        $this->assertDatabaseHas('stock_ledger', [
            'warehouse_id' => $ctx['warehouse']->id,
            'item_id' => $ctx['item']->id,
            'transaction_type' => 'GRN_IN',
        ]);

        $ledger = StockLedger::query()->where('item_id', $ctx['item']->id)->firstOrFail();
        $this->assertEquals(10.0, (float) $ledger->balance_qty);

        $this->assertSame(1, VendorPayable::query()->count());
        $payable = VendorPayable::query()->firstOrFail();
        $this->assertSame(0, bccomp((string) $po->total_amount, (string) $payable->amount, 2));

        $this->assertSame(1, AccountingJournalEntry::query()->count());
        $entry = AccountingJournalEntry::query()->with('lines')->firstOrFail();
        $debit = '0.00';
        $credit = '0.00';
        foreach ($entry->lines as $line) {
            $debit = bcadd($debit, (string) $line->debit, 2);
            $credit = bcadd($credit, (string) $line->credit, 2);
        }
        $this->assertSame(0, bccomp($debit, $credit, 2));

        $this->assertSame(1, StockMovement::query()->where('movement_type', 'grn')->count());
    }

    public function test_grn_rejects_when_accepted_and_rejected_qty_mismatch(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $creator = $this->makeSuperAdmin();
        $approver = $this->makeSuperAdmin();
        $ctx = $this->seedPurchaseContext();

        $this->actingAs($creator)->postJson(route('admin.purchase.orders.store'), [
            'vendor_id' => $ctx['vendor']->id,
            'pr_id' => null,
            'warehouse_id' => $ctx['warehouse']->id,
            'order_date' => '2026-04-20',
            'expected_delivery' => null,
            'payment_terms_days' => 30,
            'notes' => null,
            'lines' => [
                ['item_id' => $ctx['item']->id, 'quantity' => 10, 'unit_cost' => 100],
            ],
        ])->assertCreated();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->actingAs($approver)->postJson(route('admin.purchase.orders.approve', $po))->assertOk();

        $this->actingAs($creator)->postJson(route('admin.purchase.grns.store'), [
            'purchase_order_id' => $po->id,
            'vendor_id' => $ctx['vendor']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'received_at' => '2026-04-19 10:00:00',
            'notes' => null,
            'lines' => [
                [
                    'item_id' => $ctx['item']->id,
                    'quantity' => 10,
                    'accepted_qty' => 6,
                    'rejected_qty' => 2,
                ],
            ],
        ])->assertUnprocessable();
    }

    /**
     * SO reserves stock, dispatch deducts, invoice posts AR journal and increases credit_used.
     */
    public function test_sales_flow_reserve_dispatch_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $ctx = $this->seedSalesContext();

        app(InventoryStockService::class)->adjust(
            $ctx['warehouse']->id,
            $ctx['item']->id,
            '100.0000',
            $user->id,
            ['notes' => 'Test seed stock']
        );

        $this->actingAs($user)->postJson(route('admin.sales.orders.store'), [
            'customer_id' => $ctx['customer']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'order_date' => '2026-04-20',
            'notes' => null,
            'lines' => [
                ['item_id' => $ctx['item']->id, 'quantity' => 5, 'unit_price' => 100],
            ],
        ])->assertCreated();

        $this->assertSame(1, StockReservation::query()->where('status', 'reserved')->count());
        $res = StockReservation::query()->firstOrFail();
        $this->assertEquals(5.0, (float) $res->quantity);

        $order = SalesOrder::query()->latest('id')->firstOrFail();
        $orderTotal = (string) $order->total_amount;

        $this->actingAs($user)->postJson(route('admin.sales.orders.dispatch', $order))->assertOk();

        $order->refresh();
        $this->assertSame('dispatched', $order->status);
        $this->assertSame(0, StockReservation::query()->where('status', 'reserved')->count());
        $this->assertSame(1, StockReservation::query()->where('status', 'consumed')->count());

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $ctx['warehouse']->id)
            ->where('item_id', $ctx['item']->id)
            ->firstOrFail();
        $this->assertEquals(95.0, (float) $balance->quantity);

        $this->assertDatabaseHas('stock_ledger', [
            'transaction_type' => 'SALES_OUT',
            'item_id' => $ctx['item']->id,
        ]);

        $creditBefore = (string) $ctx['customer']->fresh()->credit_used;

        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))->assertOk();

        $this->assertSame(1, Invoice::query()->where('status', 'posted')->count());
        $this->assertSame(1, AccountingJournalEntry::query()->count());

        $ctx['customer']->refresh();
        $this->assertSame(
            bcadd($creditBefore, $orderTotal, 2),
            (string) $ctx['customer']->credit_used
        );

        $this->actingAs($user)->postJson(route('admin.sales.orders.invoice', $order))
            ->assertStatus(422);
    }

    public function test_sales_order_credit_limit_override_allowed_for_company_editors(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $ctx = $this->seedSalesContext();

        app(InventoryStockService::class)->adjust(
            $ctx['warehouse']->id,
            $ctx['item']->id,
            '100.0000',
            $user->id,
            ['notes' => 'Test seed stock']
        );

        $ctx['customer']->update([
            'credit_limit' => '100.00',
            'credit_used' => '0',
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.store'), [
            'customer_id' => $ctx['customer']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'order_date' => '2026-04-20',
            'notes' => null,
            'credit_limit_override' => true,
            'lines' => [
                ['item_id' => $ctx['item']->id, 'quantity' => 5, 'unit_price' => 100],
            ],
        ])->assertCreated();
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    /**
     * @return array{warehouse: Warehouse, item: Item, vendor: Vendor}
     */
    private function seedPurchaseContext(): array
    {
        Company::factory()->create([
            'state_code' => '24',
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-T'.uniqid(),
            'name' => 'Test WH',
            'city' => 'Ahmedabad',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-P-'.uniqid(),
            'name' => 'Raw material',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'RAW_MATERIAL',
        ]);
        $vendor = Vendor::factory()->create([
            'state_code' => '24',
        ]);

        return compact('warehouse', 'item', 'vendor');
    }

    /**
     * @return array{warehouse: Warehouse, item: Item, customer: Customer}
     */
    private function seedSalesContext(): array
    {
        Company::factory()->create([
            'state_code' => '24',
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-S'.uniqid(),
            'name' => 'Test WH Sales',
            'city' => 'Ahmedabad',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'SKU-S-'.uniqid(),
            'name' => 'Finished good',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'hsn_code' => '87654321',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);
        $customer = Customer::factory()->create([
            'state_code' => '24',
            'credit_limit' => 100000,
            'credit_used' => 0,
        ]);

        return compact('warehouse', 'item', 'customer');
    }
}
