@extends('pdfs.layouts.master')

@section('content')
    <p class="text-center" style="font-size: 13px; font-weight: bold; margin-bottom: 12px;">{{ $periodLabel }}</p>

    <table class="no-border section">
        <tr>
            <td>
                <strong>{{ $employee?->name }}</strong> ({{ $employee?->emp_code }})<br>
                {{ $employee?->designation ?? '—' }} · {{ $employee?->department ?? '—' }}<br>
                DOJ: {{ $employee?->join_date?->format('d M Y') ?? '—' }}
            </td>
            <td>
                PF No: {{ $employee?->pf_number ?? '—' }}<br>
                ESI No: {{ $employee?->esi_number ?? '—' }}
            </td>
        </tr>
    </table>

    <table class="section" style="width: 48%; float: left;">
        <thead><tr><th colspan="2">Earnings</th></tr></thead>
        <tbody>
            @foreach ($earningLines as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="text-end">{{ $line['amount'] }}</td>
                </tr>
            @endforeach
            <tr><th>Gross Earnings</th><th class="text-end">{{ $detail->gross_salary }}</th></tr>
        </tbody>
    </table>

    <table class="section" style="width: 48%; float: right;">
        <thead><tr><th colspan="2">Deductions</th></tr></thead>
        <tbody>
            <tr><td>PF Employee ({{ $pfEmployeeLabel }})</td><td class="text-end">{{ $detail->pf_deduction }}</td></tr>
            <tr><td>ESI Employee ({{ $esiEmployeeLabel }})</td><td class="text-end">{{ $detail->esi_deduction }}</td></tr>
            <tr><td>Professional Tax</td><td class="text-end">{{ $detail->professional_tax }}</td></tr>
            <tr><th>Total Deductions</th><th class="text-end">{{ bcadd(bcadd((string) $detail->pf_deduction, (string) $detail->esi_deduction, 2), (string) $detail->professional_tax, 2) }}</th></tr>
        </tbody>
    </table>

    <div style="clear: both;"></div>

    <table class="no-border section" style="margin-top: 16px; border: 2px solid #333; padding: 8px;">
        <tr>
            <td><strong>NET PAY</strong></td>
            <td class="text-end" style="font-size: 16px; font-weight: bold;">{{ $detail->net_salary }} {{ $company?->currency ?? 'INR' }}</td>
        </tr>
    </table>

    <p class="section"><strong>In words:</strong> {{ $netPayWords }}</p>

    <p class="muted section">
        Employer PF (ref): {{ $employerPf }} · Employer ESI (ref): {{ $employerEsi }}<br>
        This is a computer-generated payslip. No signature required.
    </p>
@endsection
