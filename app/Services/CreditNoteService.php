<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Sales credit notes with GST reversal journals (SRS US-07 / returns).
 */
class CreditNoteService
{
    public function __construct(
        protected GstCalculationService $gst,
        protected AccountingJournalService $journal,
        protected DocumentNumberService $documentNumbers,
        protected GstPeriodLockService $gstPeriodLock
    ) {}

    /**
     * @param  list<array{item_id: int, quantity: string, unit_price: string}>  $lines
     *
     * @throws Throwable
     */
    public function createPosted(
        Customer $customer,
        array $lines,
        ?int $invoiceId,
        ?string $reason,
        User $user,
        ?Carbon $creditNoteDate = null
    ): CreditNote {
        $date = $creditNoteDate ?? Carbon::today();
        $this->gstPeriodLock->assertNotLocked($date);

        $company = Company::query()->first();
        if ($company === null) {
            throw new InvalidArgumentException('Company profile is required.');
        }

        return DB::transaction(function () use ($customer, $lines, $invoiceId, $reason, $user, $date, $company): CreditNote {
            $taxableTotal = '0.00';
            $cgstTotal = '0.00';
            $sgstTotal = '0.00';
            $igstTotal = '0.00';

            $note = CreditNote::query()->create([
                'credit_note_number' => $this->documentNumbers->next('credit_notes', 'CN-'),
                'customer_id' => $customer->id,
                'invoice_id' => $invoiceId,
                'credit_note_date' => $date->toDateString(),
                'reason' => $reason,
                'status' => 'posted',
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $item = Item::query()->findOrFail((int) $line['item_id']);
                $lineTaxable = $this->gst->lineTaxable((string) $line['quantity'], (string) $line['unit_price']);
                $split = $this->gst->splitLineTax(
                    $lineTaxable,
                    (string) $item->gst_rate,
                    (string) $customer->state_code,
                    (string) $company->state_code
                );
                $note->lines()->create([
                    'item_id' => $item->id,
                    'quantity' => (string) $line['quantity'],
                    'unit_price' => (string) $line['unit_price'],
                    'taxable_value' => $split['taxable'],
                    'gst_rate' => (string) $item->gst_rate,
                ]);
                $taxableTotal = bcadd($taxableTotal, $split['taxable'], 2);
                $cgstTotal = bcadd($cgstTotal, $split['cgst'], 2);
                $sgstTotal = bcadd($sgstTotal, $split['sgst'], 2);
                $igstTotal = bcadd($igstTotal, $split['igst'], 2);
            }

            $total = bcadd(bcadd(bcadd($taxableTotal, $cgstTotal, 2), $sgstTotal, 2), $igstTotal, 2);
            $note->update([
                'subtotal' => $taxableTotal,
                'taxable_amount' => $taxableTotal,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'total_amount' => $total,
            ]);

            $this->journal->post(
                CreditNote::class,
                $note->id,
                'Credit note '.$note->credit_note_number,
                $user->id,
                [
                    ['code' => 'AR-TRADE', 'debit' => '0.00', 'credit' => $total],
                    ['code' => 'SALES-REV', 'debit' => $taxableTotal, 'credit' => '0.00'],
                    ['code' => 'CGST-OUT', 'debit' => $cgstTotal, 'credit' => '0.00'],
                    ['code' => 'SGST-OUT', 'debit' => $sgstTotal, 'credit' => '0.00'],
                    ['code' => 'IGST-OUT', 'debit' => $igstTotal, 'credit' => '0.00'],
                ]
            );

            return $note;
        });
    }
}
