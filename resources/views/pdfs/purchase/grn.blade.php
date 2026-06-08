@extends('pdfs.layouts.master')

@section('content')
    <table class="no-border section">
        <tr>
            <td>
                <strong>GRN:</strong> {{ $grn->grn_number }} · {{ $grn->received_at?->format('d M Y H:i') }}<br>
                <strong>Warehouse:</strong> {{ $warehouse?->name ?? '—' }} ({{ $warehouse?->code ?? '—' }})
            </td>
            <td style="text-align: right;">
                <strong>PO Ref:</strong> {{ $purchaseOrder?->po_number ?? '—' }}<br>
                <strong>Vendor:</strong> {{ $vendor?->name ?? '—' }}
                @if (!empty($barcodeSvg))
                    <div style="margin-top: 8px;">{!! $barcodeSvg !!}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="section">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">PO Qty</th>
                <th class="text-end">Received</th>
                <th class="text-end">Accepted</th>
                <th class="text-end">Rejected</th>
                <th>Batch/Serial</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                @php $variance = bccomp((string) $line->quantity, (string) $line->accepted_qty, 4) !== 0; @endphp
                <tr @if($variance) style="color: #dc3545;" @endif>
                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td class="text-end">{{ $line->quantity }}</td>
                    <td class="text-end">{{ $line->accepted_qty }}</td>
                    <td class="text-end">{{ $line->rejected_qty ?? '0' }}</td>
                    <td>{{ $line->batch_no ?? $line->serial_no ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="section muted">
        Posted by user #{{ $grn->created_by }} at {{ $grn->posted_at?->format('d M Y H:i') ?? $grn->received_at?->format('d M Y H:i') }}
    </p>
    <p class="muted">GRN {{ $grn->grn_number }}</p>
@endsection
