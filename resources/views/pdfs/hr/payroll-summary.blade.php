@extends('pdfs.layouts.master')

@section('content')
    <p class="text-center" style="font-size: 13px; font-weight: bold;">Payroll Period: {{ $periodLabel }}</p>

    <table class="section">
        <thead>
            <tr>
                <th>Emp Code</th>
                <th>Name</th>
                <th>Department</th>
                <th class="text-end">Gross</th>
                <th class="text-end">PF</th>
                <th class="text-end">ESI</th>
                <th class="text-end">PT</th>
                <th class="text-end">Net Salary</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $row)
                <tr>
                    <td>{{ $row->employee?->emp_code }}</td>
                    <td>{{ $row->employee?->name }}</td>
                    <td>{{ $row->employee?->department ?? '—' }}</td>
                    <td class="text-end">{{ $row->gross_salary }}</td>
                    <td class="text-end">{{ $row->pf_deduction }}</td>
                    <td class="text-end">{{ $row->esi_deduction }}</td>
                    <td class="text-end">{{ $row->professional_tax }}</td>
                    <td class="text-end">{{ $row->net_salary }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3">Company Total</th>
                <th class="text-end">{{ $totals['gross'] }}</th>
                <th class="text-end">{{ $totals['pf'] }}</th>
                <th class="text-end">{{ $totals['esi'] }}</th>
                <th class="text-end">{{ $totals['pt'] }}</th>
                <th class="text-end">{{ $totals['net'] }}</th>
            </tr>
        </tbody>
    </table>

    <p class="section muted">
        Statutory summary — Total PF payable: {{ $totals['pf'] }} · Total ESI payable: {{ $totals['esi'] }}
    </p>
@endsection
