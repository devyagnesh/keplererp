<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PostsGoodsReceipts;
use Tests\TestCase;

class BatchSerialTrackingTest extends TestCase
{
    use PostsGoodsReceipts;
    use RefreshDatabase;

    public function test_batch_tracked_grn_requires_batch_and_dispatch_allocates_batch(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $approver = $this->makeSuperAdmin();
        $ctx = $this->seedBatchSalesContext();

        $this->actingAs($user)->postJson(route('admin.items.store'), [
            'sku' => 'BATCH-'.uniqid(),
            'name' => 'Batch item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => 1,
            'is_batch_tracked' => 1,
        ])->assertCreated();

        $batchItem = Item::query()->where('is_batch_tracked', true)->latest('id')->firstOrFail();

        $po = $this->createApprovedPo($user, $approver, $ctx, $batchItem);

        $this->actingAs($user)->postJson(route('admin.purchase.grns.store'), [
            'purchase_order_id' => $po->id,
            'vendor_id' => $ctx['vendor']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'received_at' => '2026-04-19 10:00:00',
            'lines' => [
                ['item_id' => $batchItem->id, 'quantity' => 5],
            ],
        ])->assertStatus(422);

        $this->actingAs($user)->postJson(route('admin.purchase.grns.store'), [
            'purchase_order_id' => $po->id,
            'vendor_id' => $ctx['vendor']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'received_at' => '2026-04-19 10:00:00',
            'lines' => [
                [
                    'item_id' => $batchItem->id,
                    'quantity' => 5,
                    'batch_no' => 'LOT-A',
                    'expiry_date' => now()->addYear()->toDateString(),
                ],
            ],
        ])->assertCreated();

        $this->postLatestGrn($user);

        $this->assertDatabaseHas('stock_ledger', [
            'item_id' => $batchItem->id,
            'batch_no' => 'LOT-A',
        ]);

        $this->actingAs($user)->postJson(route('admin.sales.orders.store'), [
            'customer_id' => $ctx['customer']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'order_date' => '2026-04-20',
            'lines' => [
                ['item_id' => $batchItem->id, 'quantity' => 2, 'unit_price' => 50],
            ],
        ])->assertCreated();

        $order = SalesOrder::query()->latest('id')->firstOrFail();
        $line = $order->lines()->firstOrFail();

        $this->actingAs($user)->getJson(route('admin.sales.orders.dispatch-data', $order))
            ->assertOk()
            ->assertJsonPath('data.lines.0.is_batch_tracked', true);

        $this->actingAs($user)->postJson(route('admin.sales.orders.dispatch', $order), [
            'lines' => [
                ['line_id' => $line->id, 'batch_no' => 'LOT-A'],
            ],
        ])->assertOk();

        $line->refresh();
        $this->assertSame('LOT-A', $line->batch_no);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $batchItem->id,
            'movement_type' => 'sales_dispatch',
            'batch_no' => 'LOT-A',
        ]);
    }

    public function test_tracking_map_endpoint_returns_flags(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $item = Item::query()->create([
            'sku' => 'TRK-'.uniqid(),
            'name' => 'Tracked',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'is_batch_tracked' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'RAW_MATERIAL',
        ]);

        $this->actingAs($user)->getJson(route('admin.inventory.tracking-map'))
            ->assertOk()
            ->assertJsonPath('items.'.$item->id.'.is_batch_tracked', true);
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    /**
     * @return array{warehouse: Warehouse, vendor: Vendor, customer: Customer}
     */
    private function seedBatchSalesContext(): array
    {
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-B'.uniqid(),
            'name' => 'Batch WH',
            'city' => 'Ahmedabad',
            'is_active' => true,
        ]);
        $vendor = Vendor::factory()->create(['state_code' => '24']);
        $customer = Customer::factory()->create([
            'state_code' => '24',
            'credit_limit' => 100000,
            'credit_used' => 0,
        ]);

        return compact('warehouse', 'vendor', 'customer');
    }

    /**
     * @param  array{warehouse: Warehouse, vendor: Vendor}  $ctx
     */
    private function createApprovedPo(User $user, User $approver, array $ctx, Item $item): PurchaseOrder
    {
        $this->actingAs($user)->postJson(route('admin.purchase.orders.store'), [
            'vendor_id' => $ctx['vendor']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'order_date' => '2026-04-20',
            'payment_terms_days' => 30,
            'lines' => [
                ['item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 10],
            ],
        ])->assertCreated();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->actingAs($approver)->postJson(route('admin.purchase.orders.approve', $po))->assertOk();

        return $po->fresh();
    }
}
