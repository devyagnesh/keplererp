@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>Challan No:</strong> {{ $challan->challan_number }}<br>
                <strong>Date:</strong> {{ $challan->dispatched_at?->format('d M Y H:i') }}<br>
                <strong>SO Ref:</strong> {{ $order?->order_number ?? '—' }}<br>
                <strong>Invoice Ref:</strong> {{ $invoice?->invoice_number ?? '—' }}
            </td>
            <td>
                <strong>Ship From:</strong> {{ $warehouse?->name ?? '—' }}<br>
                <strong>Ship To:</strong> {{ $customer?->name ?? '—' }}
            </td>
        </tr>
    </table>

    @if ($challan->eway_bill_no)
        <p class="muted"><strong>e-Way Bill:</strong> {{ $challan->eway_bill_no }}</p>
    @endif

    <table class="section">
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>HSN</th>
                <th class="text-end">Qty</th>
                <th>UOM</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td>{{ $line->item?->name }}</td>
                    <td>{{ $line->item?->hsn_code ?? '—' }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td>{{ $line->item?->uom ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="section muted">Goods dispatched for delivery only. No commercial transaction.</p>

    <table class="no-border section" style="margin-top: 32px;">
        <tr>
            <td style="width: 33%; border-top: 1px solid #999; padding-top: 8px;">Driver Signature</td>
            <td style="width: 33%; border-top: 1px solid #999; padding-top: 8px;">Receiver Signature</td>
            <td style="width: 33%; border-top: 1px solid #999; padding-top: 8px;">For {{ $company?->company_name ?? 'Company' }}</td>
        </tr>
    </table>
@endsection
