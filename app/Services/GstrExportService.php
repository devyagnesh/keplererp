<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports GSTR-1 style outward supply data for a calendar month (SRS US-07).
 */
class GstrExportService
{
    /**
     * @return array{invoices: \Illuminate\Support\Collection, totals: array<string, string>}
     */
    public function reportData(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $invoices = Invoice::query()
            ->with(['customer:id,name,gstin,state_code', 'invoiceItems.item:id,hsn_code,gst_rate'])
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->orderBy('invoice_date')
            ->get();

        $totals = [
            'taxable' => '0.00',
            'cgst' => '0.00',
            'sgst' => '0.00',
            'igst' => '0.00',
            'total' => '0.00',
        ];

        foreach ($invoices as $invoice) {
            $totals['taxable'] = bcadd($totals['taxable'], (string) $invoice->taxable_amount, 2);
            $totals['cgst'] = bcadd($totals['cgst'], (string) $invoice->cgst_amount, 2);
            $totals['sgst'] = bcadd($totals['sgst'], (string) $invoice->sgst_amount, 2);
            $totals['igst'] = bcadd($totals['igst'], (string) $invoice->igst_amount, 2);
            $totals['total'] = bcadd($totals['total'], (string) $invoice->total_amount, 2);
        }

        return [
            'invoices' => $invoices,
            'totals' => $totals,
        ];
    }

    /**
     * Stream a CSV suitable for GSTR-1 B2B outward supplies.
     */
    public function downloadGstr1Csv(int $year, int $month): StreamedResponse
    {
        $invoices = $this->reportData($year, $month)['invoices'];

        $filename = sprintf('gstr1-%04d-%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($invoices): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, [
                'Invoice Number',
                'Invoice Date',
                'Customer',
                'Customer GSTIN',
                'Place of Supply',
                'Taxable Value',
                'CGST',
                'SGST',
                'IGST',
                'Invoice Value',
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->invoice_date?->format('d-m-Y'),
                    $invoice->customer?->name ?? '',
                    $invoice->customer?->gstin ?? '',
                    $invoice->place_of_supply,
                    $invoice->taxable_amount,
                    $invoice->cgst_amount,
                    $invoice->sgst_amount,
                    $invoice->igst_amount,
                    $invoice->total_amount,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Stream a summary CSV for GSTR-3B tax liability snapshot.
     */
    public function downloadGstr3bSummaryCsv(int $year, int $month): StreamedResponse
    {
        $totals = $this->reportData($year, $month)['totals'];
        $filename = sprintf('gstr3b-summary-%04d-%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($totals, $year, $month): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Period', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Invoice Value']);
            fputcsv($handle, [
                sprintf('%04d-%02d', $year, $month),
                $totals['taxable'],
                $totals['cgst'],
                $totals['sgst'],
                $totals['igst'],
                $totals['total'],
            ]);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * JSON export for GST portal upload helpers (SRS US-07).
     */
    public function downloadGstr1Json(int $year, int $month): StreamedResponse
    {
        $data = $this->reportData($year, $month);
        $payload = [
            'period' => sprintf('%04d-%02d', $year, $month),
            'b2b' => $data['invoices']->map(fn (Invoice $invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                'customer_gstin' => $invoice->customer?->gstin,
                'place_of_supply' => $invoice->place_of_supply,
                'taxable_value' => (string) $invoice->taxable_amount,
                'cgst' => (string) $invoice->cgst_amount,
                'sgst' => (string) $invoice->sgst_amount,
                'igst' => (string) $invoice->igst_amount,
                'total' => (string) $invoice->total_amount,
            ])->values()->all(),
            'totals' => $data['totals'],
        ];

        $filename = sprintf('gstr1-%04d-%02d.json', $year, $month);

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }
}
