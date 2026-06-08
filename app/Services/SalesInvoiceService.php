<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Enums\PdfDocumentType;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Creates a posted sales invoice from a confirmed sales order and books AR + revenue + GST output.
 */
class SalesInvoiceService
{
    public function __construct(
        protected AccountingJournalService $journal,
        protected WhatsAppNotificationService $whatsappNotifications,
        protected EinvoiceService $einvoice,
        protected AuditLogService $auditLog,
        protected PdfGeneratorService $pdfGenerator,
        protected GstPeriodLockService $gstPeriodLock
    ) {}

    /**
     * @throws Throwable
     */
    public function createPostedFromSalesOrder(SalesOrder $salesOrder, User $user, string $invoiceNumber): Invoice
    {
        $this->gstPeriodLock->assertNotLocked(Carbon::today());

        $invoice = DB::transaction(function () use ($salesOrder, $user, $invoiceNumber): Invoice {
            if ($salesOrder->status !== 'confirmed' && $salesOrder->status !== 'dispatched') {
                throw new InvalidArgumentException('Invoice can only be raised for a confirmed or dispatched order.');
            }
            if (Invoice::query()->where('sales_order_id', $salesOrder->id)->where('status', 'posted')->exists()) {
                throw new InvalidArgumentException('A posted invoice already exists for this order.');
            }

            $salesOrder->loadMissing(['lines.item', 'customer']);
            $customer = $salesOrder->customer;
            if ($customer === null) {
                throw new InvalidArgumentException('Customer is required.');
            }

            $invoice = Invoice::query()->create([
                'invoice_number' => $invoiceNumber,
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $customer->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays((int) $salesOrder->payment_terms_days)->toDateString(),
                'place_of_supply' => $customer->state_code,
                'subtotal' => $salesOrder->subtotal,
                'discount_amount' => $salesOrder->discount_amount,
                'taxable_amount' => $salesOrder->taxable_amount,
                'cgst_amount' => $salesOrder->cgst_amount,
                'sgst_amount' => $salesOrder->sgst_amount,
                'igst_amount' => $salesOrder->igst_amount,
                'total_amount' => $salesOrder->total_amount,
                'status' => 'posted',
                'created_by' => $user->id,
            ]);

            foreach ($salesOrder->lines as $line) {
                $invoice->invoiceItems()->create([
                    'item_id' => $line->item_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'taxable_value' => $line->taxable_value,
                    'cgst' => $line->cgst,
                    'sgst' => $line->sgst,
                    'igst' => $line->igst,
                ]);
            }

            $taxable = (string) $salesOrder->taxable_amount;
            $cgst = (string) $salesOrder->cgst_amount;
            $sgst = (string) $salesOrder->sgst_amount;
            $igst = (string) $salesOrder->igst_amount;
            $total = (string) $salesOrder->total_amount;

            $this->journal->post(
                Invoice::class,
                $invoice->id,
                'Invoice '.$invoice->invoice_number.' for SO '.$salesOrder->order_number,
                $user->id,
                [
                    ['code' => 'AR-TRADE', 'debit' => $total, 'credit' => '0.00'],
                    ['code' => 'SALES-REV', 'debit' => '0.00', 'credit' => $taxable],
                    ['code' => 'CGST-OUT', 'debit' => '0.00', 'credit' => $cgst],
                    ['code' => 'SGST-OUT', 'debit' => '0.00', 'credit' => $sgst],
                    ['code' => 'IGST-OUT', 'debit' => '0.00', 'credit' => $igst],
                ]
            );

            Customer::query()->whereKey($customer->id)->update([
                'credit_used' => bcadd((string) $customer->credit_used, $total, 2),
            ]);

            return $invoice;
        });

        $this->einvoice->generateForInvoice($invoice);

        $invoice->loadMissing('customer');
        $postedCustomer = $invoice->customer;
        if ($postedCustomer instanceof Customer) {
            $this->whatsappNotifications->notifyInvoicePosted($invoice, $postedCustomer);
            if ($postedCustomer->email !== null && $postedCustomer->email !== '') {
                \Illuminate\Support\Facades\Mail::to($postedCustomer->email)->queue(
                    new \App\Mail\InvoicePostedMail($invoice)
                );
            }
        }

        $this->auditLog->record(
            'invoice.posted',
            'Posted invoice '.$invoice->invoice_number,
            $invoice,
            $user
        );

        $this->pdfGenerator->queue(PdfDocumentType::TaxInvoice, $invoice, $user->id);

        return $invoice;
    }
}
