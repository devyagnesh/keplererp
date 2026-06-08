<?php

namespace App\Services\Gst\Drivers;

use App\Contracts\Gst\EinvoiceDriverInterface;
use App\Models\Invoice;

/**
 * Stub e-Invoice driver for local / shared-hosting without NIC credentials.
 */
class LogEinvoiceDriver implements EinvoiceDriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(Invoice $invoice): ?array
    {
        return [
            'irn' => 'STUB-IRN-'.$invoice->invoice_number,
            'ack_no' => 'ACK-'.now()->format('YmdHis'),
            'qr' => 'einvoice:stub:'.$invoice->invoice_number,
        ];
    }
}
