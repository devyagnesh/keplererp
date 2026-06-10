<x-layouts.employee title="My payslips">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">My payslips</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payslips</li>
                </ol>
            </nav>
            <p class="text-muted mb-0 mt-1">{{ $employee->name }} ({{ $employee->emp_code }})</p>
        </div>
    </div>

    <div class="card custom-card mb-4">
        <div class="card-header">
            <div class="card-title mb-0">Filter payslips</div>
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('employee.payslips.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="payslipYear" class="form-label">Year</label>
                    <select name="year" id="payslipYear" class="form-select">
                        <option value="">All years</option>
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" @selected($selectedYear === (int) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="payslipMonthFrom" class="form-label">Month from</label>
                    <select name="month_from" id="payslipMonthFrom" class="form-select">
                        <option value="">Any</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($selectedMonthFrom === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="payslipMonthTo" class="form-label">Month to</label>
                    <select name="month_to" id="payslipMonthTo" class="form-select">
                        <option value="">Any</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($selectedMonthTo === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-wave flex-grow-1">Apply</button>
                    <a href="{{ route('employee.payslips.index') }}" class="btn btn-outline-secondary btn-wave">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Processed</th>
                            <th>Net salary</th>
                            <th class="text-end">Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($details as $detail)
                            @php
                                $run = $detail->payrollRun;
                                $period = $run
                                    ? $run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT)
                                    : '—';
                            @endphp
                            <tr>
                                <td>{{ $period }}</td>
                                <td>{{ $run?->processed_at?->format('Y-m-d') ?? '—' }}</td>
                                <td>₹ {{ number_format((float) $detail->net_salary, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('employee.payslips.pdf', $detail) }}"
                                        class="btn btn-sm btn-primary btn-wave" target="_blank" rel="noopener">Payslip PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center py-4">No payslips match your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.employee>
