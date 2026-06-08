<?php

namespace App\Contracts\Gst;

use App\Models\SalesDispatchChallan;

/**
 * Pluggable e-Way bill generation (NIC / GSP).
 */
interface EwayBillDriverInterface
{
    /**
     * Generate e-Way bill for a dispatch challan.
     *
     * @return array{eway_bill_no: string, eway_qr: string}|null Null when skipped or failed.
     */
    public function generate(SalesDispatchChallan $challan): ?array;
}
