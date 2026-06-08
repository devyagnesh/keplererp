<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Converts an accepted quotation into a confirmed sales order (SRS US-05).
 */
class SalesQuotationConversionService
{
    public function __construct(
        protected GstCalculationService $gst,
        protected InventoryStockService $stockService,
        protected CustomerCreditService $credit
    ) {}

    /**
     * @throws Throwable
     */
    public function convert(SalesQuotation $quotation, User $user, int $warehouseId, string $orderNumber, bool $creditLimitOverride = false): SalesOrder
    {
        return DB::transaction(function () use ($quotation, $user, $warehouseId, $orderNumber, $creditLimitOverride): SalesOrder {
            $locked = SalesQuotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'converted') {
                throw new InvalidArgumentException('Quotation was already converted.');
            }
            if (! in_array($locked->status, ['draft', 'sent', 'accepted'], true)) {
                throw new InvalidArgumentException('Only draft, sent, or accepted quotations can be converted.');
            }
            if (in_array($locked->status, ['expired', 'rejected', 'converted'], true)) {
                throw new InvalidArgumentException('This quotation cannot be converted.');
            }
            if ($locked->valid_until !== null && $locked->valid_until->lt(Carbon::today())) {
                throw new InvalidArgumentException('Quotation has expired. Run erp:expire-sales-quotations or wait for the nightly job.');
            }
            if (SalesOrder::query()->where('quotation_id', $locked->id)->exists()) {
                throw new InvalidArgumentException('A sales order already exists for this quotation.');
            }

            if (! Warehouse::query()->whereKey($warehouseId)->where('is_active', true)->exists()) {
                throw new InvalidArgumentException('Invalid or inactive warehouse.');
            }

            $locked->loadMissing(['lines.item', 'customer']);
            $customer = $locked->customer;
            if (! $customer instanceof Customer) {
                throw new InvalidArgumentException('Customer is required.');
            }

            $company = Company::query()->orderBy('id')->firstOrFail();
            $computed = $this->computeFromQuotation($locked, $customer, $company);

            if ($computed['lines'] === []) {
                throw new InvalidArgumentException('Quotation has no valid line items.');
            }
            if (bccomp($computed['total_amount'], '0', 2) <= 0) {
                throw new InvalidArgumentException('Quotation total must be greater than zero.');
            }

            $this->credit->assertWithinLimit($customer, $computed['total_amount'], $user, $creditLimitOverride);

            $so = SalesOrder::query()->create([
                'order_number' => $orderNumber,
                'quotation_id' => $locked->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouseId,
                'order_date' => now()->toDateString(),
                'expected_dispatch' => $locked->valid_until,
                'payment_terms_days' => (int) ($customer->payment_terms_days ?? 30),
                'status' => 'confirmed',
                'created_by' => $user->id,
                'notes' => $locked->notes,
                'subtotal' => $computed['subtotal'],
                'discount_amount' => '0.00',
                'taxable_amount' => $computed['taxable_amount'],
                'cgst_amount' => $computed['cgst_amount'],
                'sgst_amount' => $computed['sgst_amount'],
                'igst_amount' => $computed['igst_amount'],
                'total_amount' => $computed['total_amount'],
            ]);

            foreach ($computed['lines'] as $line) {
                $so->lines()->create($line);
            }

            $so->refresh();
            $this->stockService->reserveForSalesOrder($so, $user->id);
            $locked->update(['status' => 'converted']);

            return $so;
        });
    }

    /**
     * @return array{lines: list<array<string, mixed>>, subtotal: string, taxable_amount: string, cgst_amount: string, sgst_amount: string, igst_amount: string, total_amount: string}
     */
    protected function computeFromQuotation(SalesQuotation $quotation, Customer $customer, Company $company): array
    {
        $subtotal = '0.00';
        $cgstTot = '0.00';
        $sgstTot = '0.00';
        $igstTot = '0.00';
        $total = '0.00';
        $linesOut = [];

        foreach ($quotation->lines as $line) {
            $item = $line->item;
            if ($item === null || ! $item->is_active) {
                continue;
            }
            $qty = (string) $line->quantity;
            if (bccomp($qty, '0', 4) <= 0) {
                continue;
            }
            $unitPrice = (string) $line->unit_price;
            $taxable = $this->gst->lineTaxable($qty, $unitPrice);
            $split = $this->gst->splitLineTax($taxable, (string) $item->gst_rate, $customer->state_code, $company->state_code);
            $lineTotal = bcadd(bcadd(bcadd($split['taxable'], $split['cgst'], 2), $split['sgst'], 2), $split['igst'], 2);
            $subtotal = bcadd($subtotal, $split['taxable'], 2);
            $cgstTot = bcadd($cgstTot, $split['cgst'], 2);
            $sgstTot = bcadd($sgstTot, $split['sgst'], 2);
            $igstTot = bcadd($igstTot, $split['igst'], 2);
            $total = bcadd($total, $lineTotal, 2);
            $linesOut[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'gst_rate' => (string) $item->gst_rate,
                'taxable_value' => $split['taxable'],
                'cgst' => $split['cgst'],
                'sgst' => $split['sgst'],
                'igst' => $split['igst'],
                'line_total' => $lineTotal,
            ];
        }

        return [
            'lines' => $linesOut,
            'subtotal' => $subtotal,
            'taxable_amount' => $subtotal,
            'cgst_amount' => $cgstTot,
            'sgst_amount' => $sgstTot,
            'igst_amount' => $igstTot,
            'total_amount' => $total,
        ];
    }
}
