@extends('pdfs.layouts.master')

@section('content')
    <p class="text-center"><strong>Period:</strong> {{ sprintf('%04d-%02d', $year, $month) }}</p>

    <table class="section">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Customer</th>
                <th>GSTIN</th>
                <th>POS</th>
                <th class="text-end">Taxable</th>
                <th class="text-end">CGST</th>
                <th class="text-end">SGST</th>
                <th class="text-end">IGST</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->invoice_date?->format('d-m-Y') }}</td>
                    <td>{{ $invoice->customer?->name }}</td>
                    <td>{{ $invoice->customer?->gstin ?? '—' }}</td>
                    <td>{{ $invoice->place_of_supply }}</td>
                    <td class="text-end">{{ $invoice->taxable_amount }}</td>
                    <td class="text-end">{{ $invoice->cgst_amount }}</td>
                    <td class="text-end">{{ $invoice->sgst_amount }}</td>
                    <td class="text-end">{{ $invoice->igst_amount }}</td>
                    <td class="text-end">{{ $invoice->total_amount }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="5">Total</th>
                <th class="text-end">{{ $totals['taxable'] }}</th>
                <th class="text-end">{{ $totals['cgst'] }}</th>
                <th class="text-end">{{ $totals['sgst'] }}</th>
                <th class="text-end">{{ $totals['igst'] }}</th>
                <th class="text-end">{{ $totals['total'] }}</th>
            </tr>
        </tbody>
    </table>
@endsection
