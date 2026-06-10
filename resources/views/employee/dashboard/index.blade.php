<x-layouts.employee title="Dashboard">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Employee dashboard</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
            <p class="text-muted mb-0 mt-1">{{ $employee->name }} ({{ $employee->emp_code }})</p>
        </div>
        <form method="get" action="{{ route('employee.dashboard') }}" class="d-flex align-items-center gap-2">
            <label for="dashboardMonth" class="form-label mb-0 text-muted fs-13">Month</label>
            <input type="month" name="month" id="dashboardMonth" class="form-control form-control-sm w-auto"
                value="{{ $selectedMonth }}">
            <button type="submit" class="btn btn-sm btn-primary btn-wave">Apply</button>
        </form>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Present ({{ $summary['month_label'] }})</div>
                    <div class="fs-24 fw-semibold text-success">{{ $summary['present_days'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Absent</div>
                    <div class="fs-24 fw-semibold text-danger">{{ $summary['absent_days'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Half days</div>
                    <div class="fs-24 fw-semibold text-warning">{{ $summary['half_days'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Pending leave requests</div>
                    <div class="fs-24 fw-semibold">{{ $summary['pending_leaves'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-xl-6">
            <div class="card custom-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">Recent attendance</div>
                    <a href="{{ route('employee.attendance.index', ['month' => $selectedMonth]) }}"
                        class="btn btn-sm btn-outline-primary btn-wave">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary['recent_attendance'] as $entry)
                                    <tr>
                                        <td>{{ $entry->work_date?->format('d M Y') }}</td>
                                        <td>
                                            @include('employee.partials.attendance-status-badge', ['status' => $entry->status])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">No attendance recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card custom-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">Recent leave applications</div>
                    <a href="{{ route('employee.leave.index') }}" class="btn btn-sm btn-outline-primary btn-wave">Apply / view</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary['recent_leaves'] as $leave)
                                    <tr>
                                        <td>{{ $leave->start_date?->format('d M') }} – {{ $leave->end_date?->format('d M Y') }}</td>
                                        <td>{{ $leave->leave_type }}</td>
                                        <td>
                                            @include('employee.partials.leave-status-badge', ['status' => $leave->status])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No leave applications yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="card-title mb-0">Latest payslip</div>
                    <a href="{{ route('employee.payslips.index') }}" class="btn btn-sm btn-outline-primary btn-wave">All payslips</a>
                </div>
                <div class="card-body">
                    @if ($summary['last_payslip'])
                        @php
                            $run = $summary['last_payslip']->payrollRun;
                            $period = $run
                                ? $run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT)
                                : '—';
                        @endphp
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">Period {{ $period }}</div>
                                <div class="text-muted fs-13">Net salary: ₹ {{ number_format((float) $summary['last_payslip']->net_salary, 2) }}</div>
                                <div class="text-muted fs-12">Processed {{ $run?->processed_at?->format('d M Y') ?? '—' }}</div>
                            </div>
                            <a href="{{ route('employee.payslips.pdf', $summary['last_payslip']) }}"
                                class="btn btn-primary btn-wave" target="_blank" rel="noopener">Download PDF</a>
                        </div>
                    @else
                        <p class="text-muted mb-0">No processed payslips available yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.employee>
