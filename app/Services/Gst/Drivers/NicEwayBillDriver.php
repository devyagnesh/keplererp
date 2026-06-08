<?php

namespace App\Services\Gst\Drivers;

use App\Contracts\Gst\EwayBillDriverInterface;
use App\Exceptions\NicApiException;
use App\Models\Company;
use App\Models\SalesDispatchChallan;
use App\Services\Gst\EwayBillPayloadBuilder;
use App\Services\Gst\NicApiClient;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Live NIC / GSP e-Way bill generation via configurable REST API.
 */
class NicEwayBillDriver implements EwayBillDriverInterface
{
    public function __construct(
        protected NicApiClient $client,
        protected EwayBillPayloadBuilder $payloadBuilder
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generate(SalesDispatchChallan $challan): ?array
    {
        if (! $this->client->isConfigured('eway')) {
            Log::warning('eway.driver nic: missing NIC credentials in config.');

            return null;
        }

        $challan->loadMissing(['salesOrder.lines.item', 'salesOrder.customer', 'warehouse', 'customer']);
        $order = $challan->salesOrder;
        $customer = $challan->customer ?? $order?->customer;
        if ($order === null || $customer === null) {
            return null;
        }

        $company = Company::query()->orderBy('id')->first();
        if ($company === null) {
            return null;
        }

        try {
            $payload = $this->payloadBuilder->build(
                $challan,
                $company,
                $customer,
                $order,
                $challan->warehouse
            );
            $path = (string) config('eway.nic.generate_path', '/ewaybill/generate');
            $response = $this->client->postAuthenticated('eway', $path, $payload);

            $ewbNo = $this->client->extractScalar($response, [
                'ewayBillNo',
                'EwbNo',
                'ewbNo',
                'eway_bill_no',
                'EWAYBILL',
            ]);
            $qr = $this->client->extractScalar($response, [
                'SignedQRCode',
                'QRCode',
                'qr_code',
                'eway_qr',
            ]);

            if ($ewbNo === null || $ewbNo === '') {
                throw new NicApiException('e-Way API response did not contain bill number.', 0, ['response' => $response]);
            }

            return [
                'eway_bill_no' => $ewbNo,
                'eway_qr' => $qr ?? 'eway:'.$ewbNo,
            ];
        } catch (InvalidArgumentException|NicApiException $e) {
            Log::error('eway NIC generation failed', [
                'challan_id' => $challan->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (Throwable $e) {
            report($e);
            Log::error('eway NIC unexpected error', ['challan_id' => $challan->id]);

            return null;
        }
    }
}
