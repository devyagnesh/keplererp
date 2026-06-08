<x-layouts.app title="Payroll payslips">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">
                Payslips — {{ $run->period_year }}-{{ str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT) }}
            </h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hr.payroll-runs.index') }}">Payroll</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payslips</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hr.payroll-runs.pdf', $run) }}" class="btn btn-outline-secondary btn-wave" target="_blank" rel="noopener">Summary PDF</a>
            <a href="{{ route('admin.hr.payroll-runs.index') }}" class="btn btn-light btn-wave">Back</a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Net salary</th>
                            <th class="text-end">Payslip</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($details as $detail)
                            <tr>
                                <td>{{ $detail->employee?->name ?? '—' }}</td>
                                <td>{{ $detail->employee?->emp_code ?? '—' }}</td>
                                <td>{{ number_format((float) $detail->net_salary, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.hr.payroll-details.pdf', $detail) }}"
                                        class="btn btn-sm btn-outline-secondary btn-wave" target="_blank" rel="noopener">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center">No payroll lines found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
