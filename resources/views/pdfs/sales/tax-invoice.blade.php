@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td style="width: 50%;">
                <strong>Bill To</strong><br>
                {{ $customer?->name }}<br>
                GSTIN: {{ $customer?->gstin ?? '—' }}<br>
                Place of supply: {{ $invoice->place_of_supply }}
            </td>
            <td style="width: 50%;">
                <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->invoice_date?->format('d M Y') }}<br>
                <strong>Due Date:</strong> {{ $invoice->due_date?->format('d M Y') }}<br>
                @if ($invoice->salesOrder)
                    <strong>SO Ref:</strong> {{ $invoice->salesOrder->order_number }}
                @endif
            </td>
        </tr>
    </table>

    @if ($invoice->irn)
        <p class="muted">IRN: {{ $invoice->irn }} · ACK: {{ $invoice->ack_no ?? '—' }} · {{ $invoice->irn_generated_at?->format('d M Y H:i') }}</p>
    @endif

    <table class="section">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>HSN</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Taxable</th>
                <th class="text-end">CGST</th>
                <th class="text-end">SGST</th>
                <th class="text-end">IGST</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td>{{ $line->item?->hsn_code ?? '—' }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td class="text-end">{{ $line->unit_price }}</td>
                    <td class="text-end">{{ $line->taxable_value }}</td>
                    <td class="text-end">{{ $line->cgst }}</td>
                    <td class="text-end">{{ $line->sgst }}</td>
                    <td class="text-end">{{ $line->igst }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-end">{{ $invoice->subtotal }}</td></tr>
        <tr><td>Discount</td><td class="text-end">{{ $invoice->discount_amount }}</td></tr>
        <tr><td>Taxable Total</td><td class="text-end">{{ $invoice->taxable_amount }}</td></tr>
        <tr><td>CGST</td><td class="text-end">{{ $invoice->cgst_amount }}</td></tr>
        <tr><td>SGST</td><td class="text-end">{{ $invoice->sgst_amount }}</td></tr>
        <tr><td>IGST</td><td class="text-end">{{ $invoice->igst_amount }}</td></tr>
        <tr><th>Grand Total</th><th class="text-end">{{ $invoice->total_amount }} {{ $company?->currency ?? 'INR' }}</th></tr>
    </table>

    <p class="section"><strong>Amount in words:</strong> {{ $amountInWords }}</p>

    @if ($company?->bank_name)
        <p class="section muted">
            <strong>Bank details:</strong> {{ $company->bank_name }},
            A/c {{ $company->bank_account_number }}, IFSC {{ $company->bank_ifsc }}
        </p>
    @endif

    @if ($invoice->einvoice_qr)
        <p class="muted">e-Invoice QR: {{ $invoice->einvoice_qr }}</p>
    @endif

    <p class="section" style="margin-top: 24px;">For {{ $company?->legal_name ?? $company?->company_name }}<br><br>Authorised Signatory</p>
@endsection
