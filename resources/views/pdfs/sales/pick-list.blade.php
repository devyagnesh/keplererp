@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>Order:</strong> {{ $order->order_number }}<br>
                <strong>Customer:</strong> {{ $customer?->name ?? '—' }}<br>
                <strong>Warehouse:</strong> {{ $warehouse?->name ?? '—' }}
            </td>
            <td>
                <strong>Date:</strong> {{ now()->format('d M Y H:i') }}<br>
                @if ($order->packaging_notes)
                    <strong>Packaging:</strong> {{ $order->packaging_notes }}
                @endif
            </td>
        </tr>
    </table>

    <table class="section">
        <thead>
            <tr>
                <th>SKU / Barcode</th>
                <th>Item</th>
                <th class="text-end">Qty</th>
                <th>Pick ✓</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>
                        {{ $line['item']?->sku ?? '—' }}<br>
                        @if (!empty($line['barcodeSvg']))
                            <div style="margin-top:4px;">{!! $line['barcodeSvg'] !!}</div>
                        @endif
                    </td>
                    <td>{{ $line['item']?->name ?? '—' }}</td>
                    <td class="text-end">{{ $line['quantity'] }}</td>
                    <td style="width:40px; border:1px solid #999;">&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted section">Warehouse pick list — scan SKU barcode to confirm each line before dispatch.</p>
@endsection
