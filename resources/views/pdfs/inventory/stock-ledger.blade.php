@extends('pdfs.layouts.master')

@section('content')
    <p>
        <strong>Item:</strong> {{ $item ? $item->display_label : 'All items' }}<br>
        @if ($warehouse)
            <strong>Warehouse:</strong> {{ $warehouse->code }} — {{ $warehouse->name }}<br>
        @endif
        <strong>Period:</strong> {{ $dateFrom }} to {{ $dateTo }}
    </p>

    <table class="section">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Batch/Serial</th>
                <th class="text-end">Qty In</th>
                <th class="text-end">Qty Out</th>
                <th class="text-end">Balance</th>
                <th class="text-end">Unit Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->created_at?->format('d-m-Y') }}</td>
                    <td>{{ $row->transaction_type }}</td>
                    <td>{{ $row->reference_type ? class_basename($row->reference_type).' #'.$row->reference_id : '—' }}</td>
                    <td>{{ $row->batch_no ?? $row->serial_no ?? '—' }}</td>
                    <td class="text-end">{{ $row->qty_in ?? '—' }}</td>
                    <td class="text-end">{{ $row->qty_out ?? '—' }}</td>
                    <td class="text-end">{{ $row->balance_qty }}</td>
                    <td class="text-end">{{ $row->unit_cost ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
