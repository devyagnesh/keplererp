<?php

namespace App\Services\Gst;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use InvalidArgumentException;

/**
 * Builds GST e-Invoice JSON (schema v1.1 subset) for NIC / GSP generate-IRN APIs.
 */
class EinvoicePayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Invoice $invoice, Company $company, Customer $customer): array
    {
        $invoice->loadMissing(['invoiceItems.item']);

        if (strlen((string) $company->gstin) !== 15) {
            throw new InvalidArgumentException('Company GSTIN must be 15 characters for e-Invoice.');
        }

        if (strlen((string) $customer->gstin) !== 15) {
            throw new InvalidArgumentException('Customer GSTIN is required for B2B e-Invoice.');
        }

        $invoiceDate = $invoice->invoice_date->format('d/m/Y');
        $isInterState = $company->state_code !== $customer->state_code;
        $items = [];
        $slNo = 1;

        foreach ($invoice->invoiceItems as $line) {
            $item = $line->item;
            $gstRate = $item !== null ? (float) $item->gst_rate : 0.0;
            $taxable = (float) $line->taxable_value;
            $cgst = (float) $line->cgst;
            $sgst = (float) $line->sgst;
            $igst = (float) $line->igst;
            $total = $taxable + $cgst + $sgst + $igst;

            $items[] = [
                'SlNo' => (string) $slNo,
                'PrdDesc' => $item?->name ?? 'Item',
                'IsServc' => 'N',
                'HsnCd' => $item?->hsn_code ?? '99999999',
                'Qty' => (float) $line->quantity,
                'Unit' => $item?->uom ?? 'PCS',
                'UnitPrice' => (float) $line->unit_price,
                'TotAmt' => $taxable,
                'Discount' => 0,
                'PreTaxVal' => $taxable,
                'AssAmt' => $taxable,
                'GstRt' => $gstRate,
                'IgstAmt' => $igst,
                'CgstAmt' => $cgst,
                'SgstAmt' => $sgst,
                'TotItemVal' => round($total, 2),
            ];
            $slNo++;
        }

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => 'B2B',
                'RegRev' => 'N',
                'IgstOnIntra' => 'N',
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => $invoice->invoice_number,
                'Dt' => $invoiceDate,
            ],
            'SellerDtls' => [
                'Gstin' => $company->gstin,
                'LglNm' => $company->legal_name,
                'TrdNm' => $company->company_name,
                'Addr1' => $company->address_line1,
                'Addr2' => $company->address_line2 ?? '',
                'Loc' => $company->city,
                'Pin' => (int) $company->pincode,
                'Stcd' => $company->state_code,
            ],
            'BuyerDtls' => [
                'Gstin' => $customer->gstin,
                'LglNm' => $customer->name,
                'TrdNm' => $customer->name,
                'Pos' => $customer->state_code,
                'Addr1' => $customer->address_line1,
                'Addr2' => $customer->address_line2 ?? '',
                'Loc' => $customer->city,
                'Pin' => (int) $customer->pincode,
                'Stcd' => $customer->state_code,
            ],
            'ItemList' => $items,
            'ValDtls' => [
                'AssVal' => (float) $invoice->taxable_amount,
                'CgstVal' => (float) $invoice->cgst_amount,
                'SgstVal' => (float) $invoice->sgst_amount,
                'IgstVal' => (float) $invoice->igst_amount,
                'Discount' => (float) $invoice->discount_amount,
                'TotInvVal' => (float) $invoice->total_amount,
            ],
            'ErpMeta' => [
                'invoice_id' => $invoice->id,
                'place_of_supply' => $invoice->place_of_supply,
                'inter_state' => $isInterState,
            ],
        ];
    }
}
