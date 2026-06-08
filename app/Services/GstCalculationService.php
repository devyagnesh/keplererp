<?php

namespace App\Services;

/**
 * GST split for India: intra-state CGST+SGST vs inter-state IGST.
 */
class GstCalculationService
{
    /**
     * @return array{cgst: string, sgst: string, igst: string, taxable: string}
     */
    public function splitLineTax(string $taxableValue, string $gstRatePercent, string $buyerStateCode, string $sellerStateCode): array
    {
        $taxable = $taxableValue;
        $rate = bcdiv($gstRatePercent, '100', 6);
        $tax = bcmul($taxable, $rate, 4);
        $tax = bcadd($tax, '0', 2);

        if ($buyerStateCode === $sellerStateCode) {
            $half = bcdiv($tax, '2', 2);

            return [
                'taxable' => $taxable,
                'cgst' => $half,
                'sgst' => bcsub($tax, $half, 2),
                'igst' => '0.00',
            ];
        }

        return [
            'taxable' => $taxable,
            'cgst' => '0.00',
            'sgst' => '0.00',
            'igst' => $tax,
        ];
    }

    /**
     * Taxable value from quantity and pre-tax unit price.
     */
    public function lineTaxable(string $quantity, string $unitPricePreTax): string
    {
        return bcmul($quantity, $unitPricePreTax, 2);
    }
}
