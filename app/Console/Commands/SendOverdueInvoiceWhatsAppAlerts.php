<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily overdue receivable WhatsApp alerts (SRS WA-06).
 */
class SendOverdueInvoiceWhatsAppAlerts extends Command
{
    protected $signature = 'whatsapp:send-overdue-invoice-alerts';

    protected $description = 'Queue WhatsApp reminders for overdue customer invoices.';

    public function handle(WhatsAppNotificationService $whatsapp): int
    {
        if (! $whatsapp->isEnabledForCompany()) {
            $this->components->info('WhatsApp is disabled or not configured.');

            return self::SUCCESS;
        }

        $today = Carbon::today();
        $count = 0;

        $invoices = Invoice::query()
            ->with('customer:id,name,phone')
            ->whereIn('status', ['posted', 'partially_paid'])
            ->whereDate('due_date', '<', $today)
            ->whereRaw('total_amount > amount_paid')
            ->get();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            if ($customer === null) {
                continue;
            }
            $days = (int) $invoice->due_date->diffInDays($today);
            $whatsapp->notifyPaymentOverdue($invoice, $customer, $days);
            $count++;
        }

        $this->components->info("Queued overdue reminders for {$count} invoice(s).");

        return self::SUCCESS;
    }
}
