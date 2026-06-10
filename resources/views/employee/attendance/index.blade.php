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
    </div>

    @can('hr.attendance.self_mark')
        <div class="row gy-4 mb-4">
            <div class="col-lg-5">
                <div class="card custom-card h-100">
                    <div class="card-header">
                        <div class="card-title mb-0">Today — GPS check-in / check-out</div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <div>
                                <div class="text-muted fs-12">Status</div>
                                <div class="fw-semibold" id="todayStatusLabel">{{ strtoupper($todayStatus['status'] ?? 'NOT MARKED') }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-12">Check-in</div>
                                <div class="fw-semibold" id="todayCheckInTime">{{ $todayStatus['check_in_at'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-12">Check-out</div>
                                <div class="fw-semibold" id="todayCheckOutTime">{{ $todayStatus['check_out_at'] ?? '—' }}</div>
                            </div>
                        </div>
                        <p class="text-muted fs-12" id="employeeGpsStatus">Enable location permission for high-accuracy GPS.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-success btn-wave" id="employeeCheckInBtn"
                                @disabled($todayStatus['has_checked_in'])>Check in</button>
                            <button type="button" class="btn btn-outline-danger btn-wave" id="employeeCheckOutBtn"
                                @disabled(! $todayStatus['has_checked_in'] || $todayStatus['has_checked_out'])>Check out</button>
                        </div>
                        <p class="text-muted fs-11 mt-3 mb-0">Uses device GPS (8 decimal places). Readings worse than ±{{ (int) $gpsMaxAccuracyM }} m are rejected.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card custom-card h-100">
                    <div class="card-header">
                        <div class="card-title mb-0">Today's location</div>
                    </div>
                    <div class="card-body p-0">
                        <div id="employeeTodayMap" style="height: 280px; border-radius: 0 0 8px 8px;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

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

    <div class="card custom-card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="attendanceMonth" class="form-label">Month</label>
                    <input type="month" id="attendanceMonth" class="form-control" value="{{ $selectedMonth }}">
                </div>
                <div class="col-md-3">
                    <label for="attendanceStatusFilter" class="form-label">Status</label>
                    <select id="attendanceStatusFilter" class="form-select">
                        <option value="">All statuses</option>
                        <option value="present" @selected($selectedStatus === 'present')>Present</option>
                        <option value="absent" @selected($selectedStatus === 'absent')>Absent</option>
                        <option value="half" @selected($selectedStatus === 'half')>Half day</option>
                        <option value="leave" @selected($selectedStatus === 'leave')>Leave</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-wave w-100" id="attendanceFilterApply">Apply filters</button>
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
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Check-in GPS</th>
                            <th>Check-out GPS</th>
                            <th>Source</th>
                            <th>Recorded</th>
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
        @can('hr.attendance.self_mark')
            <link rel="stylesheet" href="{{ asset('libs/leaflet/leaflet.css') }}">
        @endcan
    @endpush

    @push('scripts')
        <script>
            window.employeeAttendanceDataUrl = @json(route('employee.attendance.data'));
            window.employeeAttendanceMonth = @json($selectedMonth);
            window.employeeTodayStatus = @json($todayStatus);
            window.employeeCheckInUrl = @json(route('employee.attendance.check-in'));
            window.employeeCheckOutUrl = @json(route('employee.attendance.check-out'));
            window.employeeGpsWarnAccuracyM = @json($gpsWarnAccuracyM);
            window.employeeTodayMap = true;
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        @can('hr.attendance.self_mark')
            <script src="{{ asset('libs/leaflet/leaflet.js') }}"></script>
            <script src="{{ asset('js/modules/employee/geolocation-capture.js') }}"></script>
            <script src="{{ asset('js/modules/employee/attendance-check.js') }}"></script>
        @endcan
        <script src="{{ asset('js/modules/employee/attendance-index.js') }}"></script>
    @endpush
</x-layouts.employee>
