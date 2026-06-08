<?php

namespace App\Services\Gst\Drivers;

use App\Contracts\Gst\EwayBillDriverInterface;
use App\Models\SalesDispatchChallan;

/**
 * Stub e-Way driver for local / shared-hosting without NIC credentials.
 */
class LogEwayBillDriver implements EwayBillDriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(SalesDispatchChallan $challan): ?array
    {
        $challan->loadMissing('salesOrder');

        return [
            'eway_bill_no' => 'STUB-EWB-'.$challan->challan_number,
            'eway_qr' => 'eway:stub:'.$challan->challan_number,
        ];
    }
}
