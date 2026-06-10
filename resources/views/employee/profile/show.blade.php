<x-layouts.employee title="My profile">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">My profile</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-lg-6">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <div class="card-title mb-0">Employment details</div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Employee code</dt>
                        <dd class="col-sm-8">{{ $employee->emp_code }}</dd>
                        <dt class="col-sm-4 text-muted">Name</dt>
                        <dd class="col-sm-8">{{ $employee->name }}</dd>
                        <dt class="col-sm-4 text-muted">Department</dt>
                        <dd class="col-sm-8">{{ $employee->department ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Designation</dt>
                        <dd class="col-sm-8">{{ $employee->designation ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Join date</dt>
                        <dd class="col-sm-8">{{ $employee->join_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Employment type</dt>
                        <dd class="col-sm-8">{{ $employee->employment_type ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <div class="card-title mb-0">Contact</div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Email</dt>
                        <dd class="col-sm-8">{{ $employee->email ?? auth()->user()?->email ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Phone</dt>
                        <dd class="col-sm-8">{{ $employee->phone ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">WhatsApp</dt>
                        <dd class="col-sm-8">{{ $employee->whatsapp ?? '—' }}</dd>
                    </dl>
                    <p class="text-muted fs-12 mb-0 mt-3">Contact HR to update your profile information.</p>
                </div>
            </div>
        </div>
        @if ($employee->employeeAllowances->isNotEmpty())
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title mb-0">Allowances</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Allowance</th>
                                        <th>Amount (monthly)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->employeeAllowances as $allowance)
                                        <tr>
                                            <td>{{ $allowance->allowanceType?->name ?? 'Allowance' }}</td>
                                            <td>₹ {{ number_format((float) $allowance->monthly_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.employee>
