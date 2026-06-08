@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>Customer</strong><br>
                {{ $customer?->name }}<br>
                GSTIN: {{ $customer?->gstin ?? '—' }}
            </td>
            <td>
                <strong>Quote No:</strong> {{ $quotation->quote_number }}<br>
                <strong>Date:</strong> {{ $quotation->quote_date?->format('d M Y') }}<br>
                <strong>Valid Until:</strong> {{ $quotation->valid_until?->format('d M Y') ?? '—' }}
            </td>
        </tr>
    </table>

    <table class="section">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>HSN</th>
                <th class="text-end">Qty</th>
                <th>UOM</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $i => $line)
                @php $amt = bcmul((string) $line->quantity, (string) $line->unit_price, 2); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td>{{ $line->item?->hsn_code ?? '—' }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td>{{ $line->item?->uom ?? '—' }}</td>
                    <td class="text-end">{{ $line->unit_price }}</td>
                    <td class="text-end">{{ $amt }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><th>Total</th><th class="text-end">{{ $totalAmount }} {{ $company?->currency ?? 'INR' }}</th></tr>
    </table>

    <p class="section"><strong>Amount in words:</strong> {{ $amountInWords }}</p>

    @if ($quotation->notes)
        <p class="section"><strong>Terms:</strong> {{ $quotation->notes }}</p>
    @endif

    <p class="muted">Valid until {{ $quotation->valid_until?->format('d M Y') ?? '—' }}. Subject to availability. Prices exclusive of freight unless stated.</p>
@endsection
