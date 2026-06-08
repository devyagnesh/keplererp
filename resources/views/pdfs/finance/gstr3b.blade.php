@extends('pdfs.layouts.master')

@section('content')
    <p class="text-center"><strong>Period:</strong> {{ sprintf('%04d-%02d', $year, $month) }}</p>

    <table class="section" style="width: 60%; margin: 24px auto;">
        <thead>
            <tr><th>Description</th><th class="text-end">Amount (INR)</th></tr>
        </thead>
        <tbody>
            <tr><td>Taxable outward supplies</td><td class="text-end">{{ $totals['taxable'] }}</td></tr>
            <tr><td>CGST liability</td><td class="text-end">{{ $totals['cgst'] }}</td></tr>
            <tr><td>SGST liability</td><td class="text-end">{{ $totals['sgst'] }}</td></tr>
            <tr><td>IGST liability</td><td class="text-end">{{ $totals['igst'] }}</td></tr>
            <tr><th>Total tax liability (output)</th><th class="text-end">{{ bcadd(bcadd($totals['cgst'], $totals['sgst'], 2), $totals['igst'], 2) }}</th></tr>
            <tr><th>Total invoice value</th><th class="text-end">{{ $totals['total'] }}</th></tr>
        </tbody>
    </table>

    <p class="muted text-center">Review copy for GSTR-3B filing. File via GST portal JSON export.</p>
@endsection
