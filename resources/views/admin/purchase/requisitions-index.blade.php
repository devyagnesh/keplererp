<x-layouts.app title="Purchase requisitions">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Purchase requisitions</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">PR</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\PurchaseRequisition::class)
            <a href="{{ route('admin.purchase.requisitions.create') }}" class="btn btn-primary btn-wave">New PR</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="prTable">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Required</th>
                            <th>Status</th>
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
                tableSelector: '#prTable',
                dataUrl: @json(route('admin.purchase.requisitions.data')),
                deleteSelector: '.js-pr-delete',
                postActionSelector: '.js-pr-post,.js-pr-convert',
                columns: [
                    { data: 'pr_number', name: 'pr_number' },
                    { data: 'required_date', name: 'required_date', orderable: false },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[3, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script>
            $(function () {
                $(document).on('click', '.js-pr-reject', function () {
                    var url = $(this).data('url');
                    if (!url) {
                        return;
                    }
                    var reason = window.prompt('Reason for rejection?');
                    if (!reason) {
                        return;
                    }
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            rejected_reason: reason,
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        success: function (response) {
                            if (response.message) {
                                notifySuccess(response.message);
                            }
                            var $tbl = $('#prTable');
                            if ($tbl.length && $.fn.DataTable && $tbl.DataTable) {
                                $tbl.DataTable().ajax.reload(null, false);
                            }
                        },
                        error: function (xhr) {
                            var msg =
                                xhr.responseJSON && xhr.responseJSON.message
                                    ? xhr.responseJSON.message
                                    : 'Could not reject.';
                            notifyError(msg);
                        },
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>
