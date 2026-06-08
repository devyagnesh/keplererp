<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Console\Command;

/**
 * Daily low-stock WhatsApp alerts (ManufactureERP SRS §4 / WA-03, communication map).
 */
class SendLowStockWhatsAppAlerts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'whatsapp:send-low-stock-alerts';

    /**
     * @var string
     */
    protected $description = 'Queue WhatsApp template alerts for SKUs below reorder level (Purchase Managers).';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppNotificationService $whatsapp): int
    {
        if (! $whatsapp->isEnabledForCompany()) {
            $this->components->info('WhatsApp is off (company switch) or Cloud API is not configured.');

            return self::SUCCESS;
        }

        $items = Item::query()
            ->where('is_active', true)
            ->whereRaw('CAST(reorder_level AS DECIMAL(14,4)) > 0')
            ->withSum('inventoryBalances', 'quantity')
            ->get(['id', 'sku', 'name', 'reorder_level']);

        $count = 0;
        foreach ($items as $item) {
            $qty = (string) ($item->inventory_balances_sum_quantity ?? '0');
            if (bccomp($qty, (string) $item->reorder_level, 4) >= 0) {
                continue;
            }
            $whatsapp->notifyLowStockItem($item, $qty);
            $count++;
        }

        $this->components->info("Queued low-stock notifications for {$count} SKU(s).");

        return self::SUCCESS;
    }
}
