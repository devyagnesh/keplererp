<x-layouts.app title="Payroll runs">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Payroll runs</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payroll</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\PayrollRun::class)
            <a href="{{ route('admin.hr.payroll-runs.create') }}" class="btn btn-primary btn-wave">New run</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="payrollTable">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Processed</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    @endpush

    @push('scripts')
        <script>
            window.erpTableConfig = {
                tableSelector: '#payrollTable',
                dataUrl: @json(route('admin.hr.payroll-runs.data')),
                postActionSelector: '.js-payroll-process',
                postConfirm: 'Mark this payroll run as processed?',
                columns: [
                    { data: 'period', name: 'period', orderable: false },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'processed_at', name: 'processed_at', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[3, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
    @endpush
</x-layouts.app>
