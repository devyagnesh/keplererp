<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GSTR-1 / GSTR-3B exports with ITC and sectioned JSON (SRS §21.10).
 */
class GstrExportService
{
    public function __construct(
        protected GoodsReceiptAccountingService $grnAccounting
    ) {}

    /**
     * @return array{
     *     invoices: Collection,
     *     credit_notes: Collection,
     *     totals: array<string, string>,
     *     itc: array<string, string>,
     *     net_tax: array<string, string>
     * }
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

        $creditNotes = CreditNote::query()
            ->with('customer:id,name,gstin')
            ->whereBetween('credit_note_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'posted')
            ->orderBy('credit_note_date')
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

        $itc = $this->purchaseItcTotals($start, $end);
        $outputTax = bcadd(bcadd($totals['cgst'], $totals['sgst'], 2), $totals['igst'], 2);
        $itcTotal = bcadd(bcadd($itc['cgst'], $itc['sgst'], 2), $itc['igst'], 2);
        $netTax = bcsub($outputTax, $itcTotal, 2);
        if (bccomp($netTax, '0', 2) < 0) {
            $netTax = '0.00';
        }

        return [
            'invoices' => $invoices,
            'credit_notes' => $creditNotes,
            'totals' => $totals,
            'itc' => $itc,
            'net_tax' => [
                'output' => $outputTax,
                'itc' => $itcTotal,
                'payable' => $netTax,
            ],
        ];
    }

    /**
     * @return array{taxable: string, cgst: string, sgst: string, igst: string, total: string}
     */
    public function purchaseItcTotals(Carbon $start, Carbon $end): array
    {
        $grns = GoodsReceipt::query()
            ->with(['lines', 'purchaseOrder.lines'])
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        $taxable = '0.00';
        $cgst = '0.00';
        $sgst = '0.00';
        $igst = '0.00';
        $total = '0.00';

        foreach ($grns as $grn) {
            try {
                $row = $this->grnAccounting->computeTaxTotals($grn);
                $taxable = bcadd($taxable, $row['taxable'], 2);
                $cgst = bcadd($cgst, $row['cgst'], 2);
                $sgst = bcadd($sgst, $row['sgst'], 2);
                $igst = bcadd($igst, $row['igst'], 2);
                $total = bcadd($total, $row['total'], 2);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        return compact('taxable', 'cgst', 'sgst', 'igst', 'total');
    }

    public function downloadGstr1Csv(int $year, int $month): StreamedResponse
    {
        $data = $this->reportData($year, $month);
        $filename = sprintf('gstr1-%04d-%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Section', 'Invoice Number', 'Date', 'Party', 'GSTIN', 'Taxable', 'CGST', 'SGST', 'IGST', 'Total']);
            foreach ($data['invoices'] as $invoice) {
                $section = $invoice->customer?->gstin ? 'B2B' : 'B2C';
                fputcsv($handle, [
                    $section,
                    $invoice->invoice_number,
                    $invoice->invoice_date?->format('d-m-Y'),
                    $invoice->customer?->name ?? '',
                    $invoice->customer?->gstin ?? '',
                    $invoice->taxable_amount,
                    $invoice->cgst_amount,
                    $invoice->sgst_amount,
                    $invoice->igst_amount,
                    $invoice->total_amount,
                ]);
            }
            foreach ($data['credit_notes'] as $note) {
                fputcsv($handle, [
                    'CDN',
                    $note->credit_note_number,
                    $note->credit_note_date?->format('d-m-Y'),
                    $note->customer?->name ?? '',
                    $note->customer?->gstin ?? '',
                    $note->taxable_amount,
                    $note->cgst_amount,
                    $note->sgst_amount,
                    $note->igst_amount,
                    $note->total_amount,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function downloadGstr3bSummaryCsv(int $year, int $month): StreamedResponse
    {
        $data = $this->reportData($year, $month);
        $filename = sprintf('gstr3b-summary-%04d-%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($data, $year, $month): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Period', 'Output CGST', 'Output SGST', 'Output IGST', 'ITC CGST', 'ITC SGST', 'ITC IGST', 'Net Tax Payable']);
            fputcsv($handle, [
                sprintf('%04d-%02d', $year, $month),
                $data['totals']['cgst'],
                $data['totals']['sgst'],
                $data['totals']['igst'],
                $data['itc']['cgst'],
                $data['itc']['sgst'],
                $data['itc']['igst'],
                $data['net_tax']['payable'],
            ]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function downloadGstr1Json(int $year, int $month): StreamedResponse
    {
        $data = $this->reportData($year, $month);
        $b2b = [];
        $b2cLarge = [];
        $b2cSmall = [];
        $cdn = [];
        $nilRated = [];

        foreach ($data['invoices'] as $invoice) {
            $row = [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                'place_of_supply' => $invoice->place_of_supply,
                'taxable_value' => (string) $invoice->taxable_amount,
                'cgst' => (string) $invoice->cgst_amount,
                'sgst' => (string) $invoice->sgst_amount,
                'igst' => (string) $invoice->igst_amount,
                'total' => (string) $invoice->total_amount,
            ];
            $gstin = $invoice->customer?->gstin;
            if ($gstin !== null && $gstin !== '') {
                $row['customer_gstin'] = $gstin;
                $b2b[] = $row;
            } elseif (bccomp((string) $invoice->total_amount, '250000', 2) > 0) {
                $b2cLarge[] = $row;
            } else {
                $b2cSmall[] = $row;
            }
            if (bccomp((string) $invoice->igst_amount, '0', 2) === 0
                && bccomp((string) $invoice->cgst_amount, '0', 2) === 0
                && bccomp((string) $invoice->taxable_amount, '0', 2) > 0) {
                $nilRated[] = $row;
            }
        }

        foreach ($data['credit_notes'] as $note) {
            $cdn[] = [
                'credit_note_number' => $note->credit_note_number,
                'credit_note_date' => $note->credit_note_date?->format('Y-m-d'),
                'customer_gstin' => $note->customer?->gstin,
                'taxable_value' => (string) $note->taxable_amount,
                'cgst' => (string) $note->cgst_amount,
                'sgst' => (string) $note->sgst_amount,
                'igst' => (string) $note->igst_amount,
                'total' => (string) $note->total_amount,
            ];
        }

        $payload = [
            'gstin' => Company::query()->orderBy('id')->value('gstin'),
            'period' => sprintf('%04d-%02d', $year, $month),
            'b2b' => $b2b,
            'b2c_large' => $b2cLarge,
            'b2c_small' => $b2cSmall,
            'cdn' => $cdn,
            'nil_rated' => $nilRated,
            'totals' => $data['totals'],
            'itc' => $data['itc'],
            'net_tax_payable' => $data['net_tax']['payable'],
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
