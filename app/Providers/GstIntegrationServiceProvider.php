<?php

namespace App\Providers;

use App\Contracts\Gst\EinvoiceDriverInterface;
use App\Contracts\Gst\EwayBillDriverInterface;
use App\Services\Gst\Drivers\LogEinvoiceDriver;
use App\Services\Gst\Drivers\LogEwayBillDriver;
use App\Services\Gst\Drivers\NicEinvoiceDriver;
use App\Services\Gst\Drivers\NicEwayBillDriver;
use Illuminate\Support\ServiceProvider;

/**
 * Binds GST e-Invoice / e-Way drivers from config (log stub vs live NIC/GSP).
 */
class GstIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EinvoiceDriverInterface::class, function (): EinvoiceDriverInterface {
            return match ((string) config('einvoice.driver', 'log')) {
                'nic' => $this->app->make(NicEinvoiceDriver::class),
                default => $this->app->make(LogEinvoiceDriver::class),
            };
        });

        $this->app->bind(EwayBillDriverInterface::class, function (): EwayBillDriverInterface {
            return match ((string) config('eway.driver', 'log')) {
                'nic' => $this->app->make(NicEwayBillDriver::class),
                default => $this->app->make(LogEwayBillDriver::class),
            };
        });
    }
}
