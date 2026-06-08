@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>Order No:</strong> {{ $order->wo_number }}<br>
                <strong>Date:</strong> {{ $order->planned_start?->format('d M Y') ?? now()->format('d M Y') }}<br>
                <strong>Status:</strong> {{ $order->status }}
            </td>
            <td>
                <strong>Product:</strong> {{ $item?->display_label ?? '—' }}<br>
                <strong>Planned Qty:</strong> {{ $order->qty_planned }} {{ $item?->uom ?? '' }}<br>
                <strong>Warehouse:</strong> {{ $warehouse?->name ?? '—' }}
            </td>
        </tr>
    </table>

    @if ($order->planned_start || $order->planned_end)
        <p><strong>Schedule:</strong> {{ $order->planned_start?->format('d M Y') ?? '—' }} to {{ $order->planned_end?->format('d M Y') ?? '—' }}</p>
    @endif

    @if ($bomLines->isNotEmpty())
        <table class="section">
            <thead>
                <tr>
                    <th>#</th>
                    <th>RM Code</th>
                    <th>Material</th>
                    <th class="text-end">Qty/Unit</th>
                    <th class="text-end">Total Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bomLines as $i => $bomLine)
                    @php $totalQty = bcmul((string) $bomLine->quantity_per, (string) $order->qty_planned, 4); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $bomLine->componentItem?->display_label ?? '—' }}</td>
                        <td>{{ $bomLine->componentItem?->name }}</td>
                        <td class="text-end">{{ $bomLine->quantity_per }}</td>
                        <td class="text-end">{{ $totalQty }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($order->notes)
        <p class="section"><strong>Special Instructions:</strong> {{ $order->notes }}</p>
    @endif

    <table class="no-border section" style="margin-top: 32px;">
        <tr>
            <td style="width: 33%; border-top: 1px solid #999; padding-top: 8px;">Supervisor</td>
            <td style="width: 33%; border-top: 1px solid #999; padding-top: 8px;">Released By</td>
            <td style="width: 33%; border-top: 1px solid #999; padding-top: 8px;">Completed By</td>
        </tr>
    </table>
@endsection
