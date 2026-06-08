@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td style="width: 50%;">
                <strong>Vendor</strong><br>
                {{ $vendor?->name }}<br>
                GSTIN: {{ $vendor?->gstin ?? '—' }}<br>
                {{ $vendor?->address_line1 ?? '' }}, {{ $vendor?->city ?? '' }}
            </td>
            <td style="width: 50%;">
                <strong>PO No:</strong> {{ $purchaseOrder->po_number }}<br>
                <strong>Date:</strong> {{ $purchaseOrder->order_date?->format('d M Y') }}<br>
                <strong>Expected Delivery:</strong> {{ $purchaseOrder->expected_delivery?->format('d M Y') ?? '—' }}<br>
                <strong>Payment Terms:</strong> {{ $purchaseOrder->payment_terms_days }} days
            </td>
        </tr>
    </table>

    @if ($warehouse)
        <p><strong>Deliver To:</strong> {{ $warehouse->name }} ({{ $warehouse->code }})</p>
    @endif

    <table class="section">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>HSN</th>
                <th class="text-end">Qty</th>
                <th>UOM</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Taxable</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td>{{ $line->item?->hsn_code ?? '—' }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td>{{ $line->item?->uom ?? '—' }}</td>
                    <td class="text-end">{{ $line->unit_cost }}</td>
                    <td class="text-end">{{ $line->taxable_value ?? $line->quantity }}</td>
                    <td class="text-end">{{ $line->line_total ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-end">{{ $purchaseOrder->subtotal }}</td></tr>
        <tr><td>CGST</td><td class="text-end">{{ $purchaseOrder->cgst_amount }}</td></tr>
        <tr><td>SGST</td><td class="text-end">{{ $purchaseOrder->sgst_amount }}</td></tr>
        <tr><td>IGST</td><td class="text-end">{{ $purchaseOrder->igst_amount }}</td></tr>
        <tr><th>Grand Total</th><th class="text-end">{{ $purchaseOrder->total_amount }}</th></tr>
    </table>

    <p class="section"><strong>Amount in words:</strong> {{ $amountInWords }}</p>

    @if ($purchaseOrder->notes)
        <p class="section"><strong>Terms:</strong> {{ $purchaseOrder->notes }}</p>
    @endif

    <p class="section muted">Approved · {{ $purchaseOrder->finance_approved_at?->format('d M Y') ?? now()->format('d M Y') }}</p>
    <p class="muted">This PO is subject to terms and conditions on reverse.</p>
@endsection
