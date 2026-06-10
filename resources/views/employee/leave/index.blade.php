<x-layouts.employee title="Leave">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Leave applications</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leave</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Apply for leave</div>
                </div>
                <div class="card-body">
                    <form id="employeeLeaveApplyForm" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Leave type</label>
                                <select name="leave_type" class="form-select" required>
                                    <option value="">— Select —</option>
                                    <option value="CASUAL">Casual</option>
                                    <option value="SICK">Sick</option>
                                    <option value="EARNED">Earned</option>
                                    <option value="UNPAID">Unpaid</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reason" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3" id="employeeLeaveApplySubmit">Submit application</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">My applications</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100" id="employeeLeaveTable">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Submitted</th>
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
    @endpush

    @push('scripts')
        <script>
            window.employeeLeaveDataUrl = @json(route('employee.leave.data'));
            window.employeeLeaveStoreUrl = @json(route('employee.leave.store'));
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/employee/leave-index.js') }}"></script>
        <script src="{{ asset('js/modules/employee/leave-apply-form.js') }}"></script>
    @endpush
</x-layouts.employee>
