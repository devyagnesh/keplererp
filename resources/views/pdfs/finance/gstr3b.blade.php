@extends('pdfs.layouts.master')

@section('content')
    <p class="text-center"><strong>Period:</strong> {{ sprintf('%04d-%02d', $year, $month) }}</p>

    <table class="section" style="width: 70%; margin: 24px auto;">
        <thead>
            <tr><th>Description</th><th class="text-end">Amount (INR)</th></tr>
        </thead>
        <tbody>
            <tr><td colspan="2"><strong>Outward supplies (tax liability)</strong></td></tr>
            <tr><td>Taxable outward supplies</td><td class="text-end">{{ $totals['taxable'] }}</td></tr>
            <tr><td>CGST liability</td><td class="text-end">{{ $totals['cgst'] }}</td></tr>
            <tr><td>SGST liability</td><td class="text-end">{{ $totals['sgst'] }}</td></tr>
            <tr><td>IGST liability</td><td class="text-end">{{ $totals['igst'] }}</td></tr>
            <tr><th>Total output tax</th><th class="text-end">{{ $net_tax['output'] ?? '0.00' }}</th></tr>
            <tr><td colspan="2"><strong>Input tax credit (purchases)</strong></td></tr>
            <tr><td>ITC CGST</td><td class="text-end">{{ $itc['cgst'] ?? '0.00' }}</td></tr>
            <tr><td>ITC SGST</td><td class="text-end">{{ $itc['sgst'] ?? '0.00' }}</td></tr>
            <tr><td>ITC IGST</td><td class="text-end">{{ $itc['igst'] ?? '0.00' }}</td></tr>
            <tr><th>Total ITC available</th><th class="text-end">{{ $net_tax['itc'] ?? '0.00' }}</th></tr>
            <tr><th>Net tax payable</th><th class="text-end">{{ $net_tax['payable'] ?? '0.00' }}</th></tr>
        </tbody>
    </table>

    <p class="muted text-center">GSTR-3B summary for internal review before GST portal filing.</p>
@endsection
