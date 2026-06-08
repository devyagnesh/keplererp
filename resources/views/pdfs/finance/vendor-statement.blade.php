@extends('pdfs.layouts.master')

@section('content')
    <p>
        <strong>Vendor:</strong> {{ $vendor->name }} · GSTIN: {{ $vendor->gstin ?? '—' }}<br>
        <strong>Period:</strong> {{ $dateFrom }} to {{ $dateTo }}
    </p>

    <table class="section">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th class="text-end">Debit (Dr)</th>
                <th class="text-end">Credit (Cr)</th>
                <th class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $entry)
                <tr>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ $entry['type'] }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td class="text-end">{{ $entry['debit'] }}</td>
                    <td class="text-end">{{ $entry['credit'] }}</td>
                    <td class="text-end">{{ $entry['balance'] }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="5">Closing Balance (payable to vendor)</th>
                <th class="text-end">{{ $closingBalance }}</th>
            </tr>
        </tbody>
    </table>
@endsection
