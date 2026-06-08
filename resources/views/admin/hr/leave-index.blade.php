<x-layouts.app title="Leave applications">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Leave applications</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leave</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Apply for leave</div>
                </div>
                <div class="card-body">
                    <form id="leaveApplyForm" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">—</option>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}">{{ $e->emp_code }} — {{ $e->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Leave type</label>
                                <select name="leave_type" class="form-select" required>
                                    <option value="CASUAL">Casual</option>
                                    <option value="SICK">Sick</option>
                                    <option value="EARNED">Earned</option>
                                    <option value="UNPAID">Unpaid</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reason" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3" id="leaveApplySubmit">Submit</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">Applications</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100" id="leaveTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Period</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
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
            window.leaveDataUrl = @json(route('admin.hr.leave.data'));
            window.leaveStoreUrl = @json(route('admin.hr.leave.store'));
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/leave-index.js') }}"></script>
        <script src="{{ asset('js/modules/erp/leave-apply-form.js') }}"></script>
    @endpush
</x-layouts.app>
