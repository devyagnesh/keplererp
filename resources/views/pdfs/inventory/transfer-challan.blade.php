@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>Transfer No:</strong> {{ $transfer->transfer_number }}<br>
                <strong>Status:</strong> {{ strtoupper($transfer->status) }}<br>
                @if ($transfer->dispatched_at)
                    <strong>Dispatched:</strong> {{ $transfer->dispatched_at->format('d M Y H:i') }}<br>
                @endif
                @if ($transfer->vehicle_no)
                    <strong>Vehicle:</strong> {{ $transfer->vehicle_no }}<br>
                @endif
                @if ($transfer->lr_number)
                    <strong>LR No:</strong> {{ $transfer->lr_number }}
                @endif
            </td>
            <td class="text-end">
                @if (!empty($barcodeSvg))
                    {!! $barcodeSvg !!}
                @endif
            </td>
        </tr>
    </table>

    <table class="no-border section">
        <tr>
            <td><strong>From:</strong> {{ $fromWarehouse?->name }} ({{ $fromWarehouse?->code }})</td>
            <td><strong>To:</strong> {{ $toWarehouse?->name }} ({{ $toWarehouse?->code }})</td>
        </tr>
    </table>

    <table class="section">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">Requested</th>
                <th class="text-end">Dispatched</th>
                <th class="text-end">Received</th>
                <th>Batch / Serial</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td class="text-end">{{ $line->qty_requested }}</td>
                    <td class="text-end">{{ $line->qty_dispatched ?? '—' }}</td>
                    <td class="text-end">{{ $line->qty_received ?? '—' }}</td>
                    <td>{{ trim(($line->batch_no ?? '').' '.($line->serial_no ?? '')) ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($transfer->reason)
        <p class="muted"><strong>Reason:</strong> {{ $transfer->reason }}</p>
    @endif
@endsection
