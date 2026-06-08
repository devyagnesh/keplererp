<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchSerialTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceability_dashboard_and_fefo_data_with_expiry(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $ctx = $this->seedContext();

        $item = Item::query()->create([
            'sku' => 'BATCH-T'.uniqid(),
            'name' => 'Batch FG',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'is_batch_tracked' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'FINISHED_GOOD',
        ]);

        $po = $this->createApprovedPo($user, $this->makeSuperAdmin(), $ctx, $item);

        $this->actingAs($user)->postJson(route('admin.purchase.grns.store'), [
            'purchase_order_id' => $po->id,
            'vendor_id' => $ctx['vendor']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'received_at' => '2026-04-19 10:00:00',
            'lines' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 5,
                    'batch_no' => 'LOT-EXP',
                    'expiry_date' => now()->addDays(10)->toDateString(),
                ],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('stock_ledger', [
            'item_id' => $item->id,
            'batch_no' => 'LOT-EXP',
        ]);

        $this->actingAs($user)->get(route('admin.inventory.traceability.index'))->assertOk();

        $this->actingAs($user)->postJson(route('admin.inventory.traceability.fefo-data'), [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.batch_no', 'LOT-EXP')
            ->assertJsonPath('data.0.status', 'Expiring soon');

        $this->actingAs($user)->get(route('admin.inventory.traceability.export-fefo'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_movement_history_lists_batch_ledger_rows(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = $this->makeSuperAdmin();
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-H',
            'name' => 'Hist WH',
            'city' => 'Test',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'HIST-'.uniqid(),
            'name' => 'Hist item',
            'uom' => 'PCS',
            'reorder_level' => 0,
            'is_active' => true,
            'is_batch_tracked' => true,
            'hsn_code' => '12345678',
            'gst_rate' => 18,
            'item_type' => 'RAW_MATERIAL',
        ]);

        StockLedger::query()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'batch_no' => 'B-HIST',
            'transaction_type' => 'GRN_IN',
            'qty_in' => '3',
            'qty_out' => null,
            'balance_qty' => '3',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->postJson(route('admin.inventory.traceability.history-data'), [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.batch_no', 'B-HIST');
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    /**
     * @return array{warehouse: Warehouse, vendor: Vendor}
     */
    private function seedContext(): array
    {
        Company::factory()->create(['state_code' => '24']);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-TR'.uniqid(),
            'name' => 'Trace WH',
            'city' => 'Ahmedabad',
            'is_active' => true,
        ]);
        $vendor = Vendor::factory()->create(['state_code' => '24']);

        return compact('warehouse', 'vendor');
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
