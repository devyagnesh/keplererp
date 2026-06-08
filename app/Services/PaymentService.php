<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorPayable;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Records vendor payments and customer receipts with auto journal entries (SRS US-06).
 */
class PaymentService
{
    public function __construct(
        protected AccountingJournalService $journal,
        protected WhatsAppNotificationService $whatsapp
    ) {}

    /**
     * @param  array{vendor_payable_id: int, amount: string, payment_method: string, utr_reference?: string|null, payment_date: string}  $data
     *
     * @throws Throwable
     */
    public function recordVendorPayment(array $data, User $user, string $paymentNumber): Payment
    {
        return DB::transaction(function () use ($data, $user, $paymentNumber): Payment {
            $payable = VendorPayable::query()
                ->whereKey((int) $data['vendor_payable_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($payable->status === 'paid') {
                throw new InvalidArgumentException('Payable is already fully paid.');
            }

            $amount = (string) $data['amount'];
            if (bccomp($amount, '0', 2) <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $balance = bcsub((string) $payable->amount, (string) $payable->amount_paid, 2);
            if (bccomp($balance, '0', 2) <= 0) {
                throw new InvalidArgumentException('No outstanding balance on this payable.');
            }
            if (bccomp($amount, $balance, 2) > 0) {
                throw new InvalidArgumentException('Payment exceeds outstanding payable balance.');
            }

            $payment = Payment::query()->create([
                'payment_number' => $paymentNumber,
                'payment_type' => 'vendor_payment',
                'vendor_payable_id' => $payable->id,
                'vendor_id' => $payable->vendor_id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'utr_reference' => $data['utr_reference'] ?? null,
                'payment_date' => $data['payment_date'],
                'created_by' => $user->id,
            ]);

            $newPaid = bcadd((string) $payable->amount_paid, $amount, 2);
            $payable->update([
                'amount_paid' => $newPaid,
                'status' => bccomp($newPaid, (string) $payable->amount, 2) >= 0 ? 'paid' : 'partial',
            ]);

            $this->journal->post(
                Payment::class,
                $payment->id,
                'Vendor payment '.$payment->payment_number,
                $user->id,
                [
                    ['code' => 'AP-TRADE', 'debit' => $amount, 'credit' => '0.00'],
                    ['code' => 'BANK-MAIN', 'debit' => '0.00', 'credit' => $amount],
                ]
            );

            $payable->loadMissing('vendor');
            if ($payable->vendor !== null) {
                $this->whatsapp->notifyVendorPaymentSent($payment, $payable->vendor);
            }

            return $payment;
        });
    }

    /**
     * @param  array{invoice_id: int, amount: string, payment_method: string, utr_reference?: string|null, payment_date: string}  $data
     *
     * @throws Throwable
     */
    public function recordCustomerReceipt(array $data, User $user, string $paymentNumber): Payment
    {
        return DB::transaction(function () use ($data, $user, $paymentNumber): Payment {
            $invoice = Invoice::query()
                ->whereKey((int) $data['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($invoice->status, ['posted', 'partially_paid'], true)) {
                throw new InvalidArgumentException('Invoice must be posted with an outstanding balance.');
            }

            $amount = (string) $data['amount'];
            if (bccomp($amount, '0', 2) <= 0) {
                throw new InvalidArgumentException('Receipt amount must be greater than zero.');
            }

            $balance = bcsub((string) $invoice->total_amount, (string) $invoice->amount_paid, 2);
            if (bccomp($balance, '0', 2) <= 0) {
                throw new InvalidArgumentException('Invoice has no outstanding balance.');
            }
            if (bccomp($amount, $balance, 2) > 0) {
                throw new InvalidArgumentException('Receipt exceeds invoice balance.');
            }

            $payment = Payment::query()->create([
                'payment_number' => $paymentNumber,
                'payment_type' => 'customer_receipt',
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'utr_reference' => $data['utr_reference'] ?? null,
                'payment_date' => $data['payment_date'],
                'created_by' => $user->id,
            ]);

            $newPaid = bcadd((string) $invoice->amount_paid, $amount, 2);
            $invoice->update([
                'amount_paid' => $newPaid,
                'status' => bccomp($newPaid, (string) $invoice->total_amount, 2) >= 0 ? 'paid' : 'partially_paid',
            ]);

            $customer = Customer::query()->whereKey($invoice->customer_id)->lockForUpdate()->first();
            if ($customer instanceof Customer) {
                $newCreditUsed = bcsub((string) $customer->credit_used, $amount, 2);
                if (bccomp($newCreditUsed, '0', 2) < 0) {
                    $newCreditUsed = '0.00';
                }
                $customer->update(['credit_used' => $newCreditUsed]);
            }

            $this->journal->post(
                Payment::class,
                $payment->id,
                'Customer receipt '.$payment->payment_number.' for '.$invoice->invoice_number,
                $user->id,
                [
                    ['code' => 'BANK-MAIN', 'debit' => $amount, 'credit' => '0.00'],
                    ['code' => 'AR-TRADE', 'debit' => '0.00', 'credit' => $amount],
                ]
            );

            $invoice->loadMissing('customer');
            $postedCustomer = $invoice->customer;
            if ($postedCustomer instanceof Customer) {
                $this->whatsapp->notifyCustomerPaymentReceived($payment, $invoice, $postedCustomer);
            }

            return $payment;
        });
    }
}
