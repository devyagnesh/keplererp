<?php

namespace App\Services\Gst\Drivers;

use App\Contracts\Gst\EinvoiceDriverInterface;
use App\Exceptions\NicApiException;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Gst\EinvoicePayloadBuilder;
use App\Services\Gst\NicApiClient;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Live NIC / GSP e-Invoice IRN generation via configurable REST API.
 */
class NicEinvoiceDriver implements EinvoiceDriverInterface
{
    public function __construct(
        protected NicApiClient $client,
        protected EinvoicePayloadBuilder $payloadBuilder
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generate(Invoice $invoice): ?array
    {
        if (! $this->client->isConfigured('einvoice')) {
            Log::warning('einvoice.driver nic: missing NIC credentials in config.');

            return null;
        }

        $invoice->loadMissing(['customer', 'invoiceItems.item']);
        $customer = $invoice->customer;
        if ($customer === null || $customer->gstin === null || strlen((string) $customer->gstin) !== 15) {
            Log::info('einvoice: skipped — B2B customer GSTIN required.', ['invoice_id' => $invoice->id]);

            return null;
        }

        $company = Company::query()->orderBy('id')->first();
        if ($company === null) {
            return null;
        }

        try {
            $payload = $this->payloadBuilder->build($invoice, $company, $customer);
            $path = (string) config('einvoice.nic.generate_path', '/einvoice/generate');
            $response = $this->client->postAuthenticated('einvoice', $path, $payload);

            $irn = $this->client->extractScalar($response, ['Irn', 'irn', 'IRN']);
            $ackNo = $this->client->extractScalar($response, ['AckNo', 'ack_no', 'AckNo', 'acknowledgement_number']);
            $qr = $this->client->extractScalar($response, ['SignedQRCode', 'QRCode', 'qr_code', 'SignedQrCode', 'einvoice_qr']);

            if ($irn === null || $irn === '') {
                throw new NicApiException('e-Invoice API response did not contain IRN.', 0, ['response' => $response]);
            }

            return [
                'irn' => $irn,
                'ack_no' => $ackNo ?? '',
                'qr' => $qr ?? '',
            ];
        } catch (InvalidArgumentException|NicApiException $e) {
            Log::error('einvoice NIC generation failed', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (Throwable $e) {
            report($e);
            Log::error('einvoice NIC unexpected error', ['invoice_id' => $invoice->id]);

            return null;
        }
    }
}
