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
                                <td colspan="4" class="text-muted text-center py-4">No processed payslips yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.employee>
