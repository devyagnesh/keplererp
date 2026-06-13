@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>Payment No:</strong> {{ $payment->payment_number }}<br>
                <strong>Date:</strong> {{ $payment->payment_date?->format('d M Y') }}<br>
                <strong>Method:</strong> {{ $payment->payment_method ?? '—' }}<br>
                @if ($payment->utr_reference)
                    <strong>UTR:</strong> {{ $payment->utr_reference }}
                @endif
            </td>
            <td>
                <strong>Vendor:</strong> {{ $vendor?->name ?? '—' }}<br>
                <strong>Code:</strong> {{ $vendor?->vendor_code ?? '—' }}<br>
                @if ($vendor?->bank_account_no)
                    <strong>Account:</strong> {{ $vendor->bank_account_no }} ({{ $vendor->bank_ifsc }})
                @endif
            </td>
        </tr>
    </table>

    <table class="section">
        <tr>
            <th>Description</th>
            <th class="text-end">Amount (INR)</th>
        </tr>
        <tr>
            <td>Payment advice for vendor settlement</td>
            <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
    </table>

    <p class="section"><strong>Amount in words:</strong> {{ $amountInWords }}</p>
    <p class="muted">This is a computer-generated payment advice. No signature required.</p>
@endsection
