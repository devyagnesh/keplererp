<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Carbon;

/**
 * Aggregates real-time KPIs for the Super Admin dashboard (SRS US-10).
 */
class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $today = Carbon::today();

        $salesToday = SalesOrder::query()
            ->whereDate('order_date', $today)
            ->whereIn('status', ['confirmed', 'dispatched', 'invoiced'])
            ->sum('total_amount');

        $pendingPos = PurchaseOrder::query()
            ->whereIn('status', ['draft', 'pending_approval', 'pending_finance', 'approved', 'sent'])
            ->count();

        $lowStockItems = Item::query()
            ->where('is_active', true)
            ->whereRaw('CAST(reorder_level AS DECIMAL(14,4)) > 0')
            ->withSum('inventoryBalances', 'quantity')
            ->get(['id', 'sku', 'name', 'reorder_level'])
            ->filter(function (Item $item): bool {
                $qty = (string) ($item->inventory_balances_sum_quantity ?? '0');

                return bccomp($qty, (string) $item->reorder_level, 4) < 0;
            })
            ->take(10)
            ->map(fn (Item $item): array => [
                'item_label' => $item->display_label,
                'qty' => (string) ($item->inventory_balances_sum_quantity ?? '0'),
                'reorder_level' => (string) $item->reorder_level,
            ])
            ->values()
            ->all();

        $overdueReceivables = Invoice::query()
            ->whereIn('status', ['posted', 'partially_paid'])
            ->whereDate('due_date', '<', $today)
            ->whereRaw('total_amount > amount_paid')
            ->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(total_amount - amount_paid), 0) as outstanding')
            ->first();

        $productionInProgress = ProductionOrder::query()
            ->whereIn('status', ['planned', 'released', 'in_progress'])
            ->count();

        return [
            'sales_today' => number_format((float) $salesToday, 2, '.', ''),
            'pending_purchase_orders' => $pendingPos,
            'low_stock_count' => count($lowStockItems),
            'low_stock_items' => $lowStockItems,
            'overdue_invoice_count' => (int) ($overdueReceivables->invoice_count ?? 0),
            'overdue_receivables' => number_format((float) ($overdueReceivables->outstanding ?? 0), 2, '.', ''),
            'production_in_progress' => $productionInProgress,
        ];
    }
}
