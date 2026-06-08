<x-layouts.app title="New payroll run">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New payroll run</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hr.payroll-runs.index') }}">Payroll</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body">
                    <form id="payrollRunForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" name="period_year" class="form-control" required min="2000" max="2100"
                                value="{{ now()->year }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Month (1–12)</label>
                            <input type="number" name="period_month" class="form-control" required min="1" max="12"
                                value="{{ now()->month }}">
                        </div>
                        <button type="submit" class="btn btn-primary" id="payrollRunSubmit">Create</button>
                        <a href="{{ route('admin.hr.payroll-runs.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.payrollRunSubmitUrl = @json(route('admin.hr.payroll-runs.store'));
            window.payrollRunsIndexUrl = @json(route('admin.hr.payroll-runs.index'));
        </script>
        <script src="{{ asset('js/modules/erp/payroll-run-form.js') }}"></script>
    @endpush
</x-layouts.app>
