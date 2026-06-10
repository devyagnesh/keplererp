<x-layouts.employee title="My attendance">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">My attendance</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Attendance</li>
                </ol>
            </nav>
            <p class="text-muted mb-0 mt-1">{{ $monthLabel }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="attendanceMonth" class="form-label mb-0 text-muted fs-13">Month</label>
            <input type="month" id="attendanceMonth" class="form-control form-control-sm w-auto"
                value="{{ $selectedMonth }}">
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Present</div>
                    <div class="fs-20 fw-semibold text-success">{{ $presentDays }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Absent</div>
                    <div class="fs-20 fw-semibold text-danger">{{ $absentDays }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Half day</div>
                    <div class="fs-20 fw-semibold text-warning">{{ $halfDays }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Leave</div>
                    <div class="fs-20 fw-semibold text-info">{{ $leaveDays }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header">
            <div class="card-title mb-0">Attendance log</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="employeeAttendanceTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Recorded at</th>
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
            window.employeeAttendanceDataUrl = @json(route('employee.attendance.data'));
            window.employeeAttendanceMonth = @json($selectedMonth);
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/employee/attendance-index.js') }}"></script>
    @endpush
</x-layouts.employee>
