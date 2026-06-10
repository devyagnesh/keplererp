<x-layouts.app title="Attendance">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Attendance</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Attendance</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Mark attendance (HR manual)</div>
                </div>
                <div class="card-body">
                    <form id="attendanceMarkForm" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Work date</label>
                                <input type="date" name="work_date" id="attendanceWorkDate" class="form-control" required
                                    value="{{ $workDate }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">—</option>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}">{{ $e->emp_code }} — {{ $e->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="leave">Leave</option>
                                    <option value="half">Half day</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3" id="attendanceMarkSubmit">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="card-title mb-0">GPS map — check-in / check-out</div>
                    <span class="badge bg-primary-transparent" id="attendanceMapCount">0 GPS point(s)</span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" id="attendanceFilterDate" class="form-control"
                                value="{{ $workDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employee</label>
                            <select id="attendanceFilterEmployee" class="form-select">
                                <option value="">All employees</option>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->emp_code }} — {{ $e->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Source</label>
                            <select id="attendanceFilterSource" class="form-select">
                                <option value="">All</option>
                                <option value="self_service">Self-service GPS</option>
                                <option value="hr_manual">HR manual</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select id="attendanceFilterStatus" class="form-select">
                                <option value="">All</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="leave">Leave</option>
                                <option value="half">Half day</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary w-100 btn-wave" id="attendanceMapRefresh"
                                onclick="window.reloadAttendanceMap && window.reloadAttendanceMap()">Refresh map</button>
                        </div>
                    </div>
                    <div id="attendanceMap" style="height: 420px; border-radius: 8px;"></div>
                    <p class="text-muted fs-12 mt-2 mb-0">Green = check-in, red = check-out. Circles show GPS accuracy radius in metres.</p>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">Entries</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Code</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Check-in GPS</th>
                                    <th>Check-out GPS</th>
                                    <th>Logged</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/leaflet/leaflet.css') }}">
    @endpush

    @push('scripts')
        <script>
            window.attendanceWorkDate = @json($workDate);
            window.attendanceDataUrl = @json(route('admin.hr.attendance.data'));
            window.attendanceMapDataUrl = @json(route('admin.hr.attendance.map-data'));
            window.attendanceMarkSubmitUrl = @json(route('admin.hr.attendance.store'));
            window.attendanceIndexBaseUrl = @json(route('admin.hr.attendance.index'));
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('libs/leaflet/leaflet.js') }}"></script>
        <script src="{{ asset('js/modules/erp/attendance-index.js') }}"></script>
        <script src="{{ asset('js/modules/erp/attendance-map.js') }}"></script>
        <script src="{{ asset('js/modules/erp/attendance-mark-form.js') }}"></script>
    @endpush
</x-layouts.app>
