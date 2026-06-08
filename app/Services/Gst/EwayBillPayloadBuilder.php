<?php

namespace App\Services\Gst;

use App\Models\Company;
use App\Models\Customer;
use App\Models\SalesDispatchChallan;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use InvalidArgumentException;

/**
 * Builds e-Way bill JSON for NIC / GSP generate APIs from dispatch challan data.
 */
class EwayBillPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(
        SalesDispatchChallan $challan,
        Company $company,
        Customer $customer,
        SalesOrder $order,
        ?Warehouse $warehouse
    ): array {
        $challan->loadMissing(['salesOrder.lines.item']);

        if (strlen((string) $company->gstin) !== 15) {
            throw new InvalidArgumentException('Company GSTIN must be 15 characters for e-Way bill.');
        }

        $fromPin = (int) $company->pincode;
        $fromAddr = $warehouse !== null
            ? trim($warehouse->name.($warehouse->city ? ', '.$warehouse->city : ''))
            : trim($company->address_line1.' '.$company->city);

        $itemList = [];
        $totalValue = 0.0;
        $slNo = 1;

        foreach ($order->lines as $line) {
            $item = $line->item;
            $taxable = (float) $line->taxable_value;
            $cgst = (float) $line->cgst;
            $sgst = (float) $line->sgst;
            $igst = (float) $line->igst;
            $lineTotal = $taxable + $cgst + $sgst + $igst;
            $totalValue += $lineTotal;

            $itemList[] = [
                'productName' => $item?->name ?? 'Goods',
                'productDesc' => $item?->name ?? 'Goods',
                'hsnCode' => (int) ($item?->hsn_code ?? 99999999),
                'quantity' => (float) $line->quantity,
                'qtyUnit' => $item?->uom ?? 'PCS',
                'taxableAmount' => $taxable,
                'cgstRate' => $taxable > 0 ? round($cgst / $taxable * 100, 2) : 0,
                'sgstRate' => $taxable > 0 ? round($sgst / $taxable * 100, 2) : 0,
                'igstRate' => $taxable > 0 ? round($igst / $taxable * 100, 2) : 0,
                'cessRate' => 0,
            ];
            $slNo++;
        }

        $isInterState = $company->state_code !== $customer->state_code;

        return [
            'supplyType' => 'O',
            'subSupplyType' => '1',
            'subSupplyDesc' => 'Supply',
            'docType' => 'CHL',
            'docNo' => $challan->challan_number,
            'docDate' => ($challan->dispatched_at ?? now())->format('d/m/Y'),
            'fromGstin' => $company->gstin,
            'fromTrdName' => $company->company_name,
            'fromAddr1' => $fromAddr,
            'fromPlace' => $warehouse?->city ?? $company->city,
            'fromPincode' => $fromPin,
            'fromStateCode' => (int) $company->state_code,
            'toGstin' => $customer->gstin ?: 'URP',
            'toTrdName' => $customer->name,
            'toAddr1' => $customer->address_line1,
            'toPlace' => $customer->city,
            'toPincode' => (int) $customer->pincode,
            'toStateCode' => (int) $customer->state_code,
            'totalValue' => round($totalValue, 2),
            'cgstValue' => (float) $order->cgst_amount,
            'sgstValue' => (float) $order->sgst_amount,
            'igstValue' => (float) $order->igst_amount,
            'transMode' => '1',
            'transDistance' => 0,
            'vehicleNo' => $order->transporter_name ?? 'NA',
            'vehicleType' => 'R',
            'itemList' => $itemList,
            'ErpMeta' => [
                'challan_id' => $challan->id,
                'sales_order_id' => $order->id,
                'inter_state' => $isInterState,
            ],
        ];
    }
}
