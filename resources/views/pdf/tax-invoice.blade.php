<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .header { margin-bottom: 16px; }
        .muted { color: #666; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $company?->legal_name ?? $company?->company_name ?? 'Company' }}</strong><br>
        @if ($company)
            GSTIN: {{ $company->gstin }}<br>
            {{ $company->address_line1 }}, {{ $company->city }} — {{ $company->pincode }}<br>
            {{ $company->phone }} · {{ $company->email }}
        @endif
    </div>

    <h2 style="margin: 0;">Tax Invoice</h2>
    <p class="muted">Invoice {{ $invoice->invoice_number }} · Date {{ $invoice->invoice_date?->format('d M Y') }}</p>

    <p>
        <strong>Bill to:</strong> {{ $customer?->name }}<br>
        @if ($customer)
            GSTIN: {{ $customer->gstin ?? '—' }} · Place of supply: {{ $invoice->place_of_supply }}
        @endif
    </p>

    @if ($invoice->irn)
        <p class="muted">IRN: {{ $invoice->irn }} · ACK: {{ $invoice->ack_no ?? '—' }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
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

    <table style="width: 40%; margin-left: auto; margin-top: 16px;">
        <tr><td>Subtotal</td><td class="text-end">{{ $invoice->subtotal }}</td></tr>
        <tr><td>CGST</td><td class="text-end">{{ $invoice->cgst_amount }}</td></tr>
        <tr><td>SGST</td><td class="text-end">{{ $invoice->sgst_amount }}</td></tr>
        <tr><td>IGST</td><td class="text-end">{{ $invoice->igst_amount }}</td></tr>
        <tr><th>Total</th><th class="text-end">{{ $invoice->total_amount }} {{ $company?->currency ?? 'INR' }}</th></tr>
        <tr><td>Paid</td><td class="text-end">{{ $invoice->amount_paid ?? '0.00' }}</td></tr>
    </table>

    @if ($invoice->einvoice_qr)
        <p class="muted" style="margin-top: 20px;">e-Invoice QR: {{ $invoice->einvoice_qr }}</p>
    @endif
</body>
</html>
