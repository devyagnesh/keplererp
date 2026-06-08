<x-layouts.guest title="My payslips">
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h4 mb-1">My payslips</h1>
                <p class="text-muted mb-0">{{ $employee->name }} ({{ $employee->emp_code }})</p>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
            </form>
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
                                    <td>{{ number_format((float) $detail->net_salary, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('employee.payslips.pdf', $detail) }}"
                                            class="btn btn-sm btn-primary btn-wave" target="_blank" rel="noopener">Payslip PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center">No processed payslips yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
