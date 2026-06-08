<x-layouts.app title="New voucher">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New journal voucher</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.finance.vouchers.index') }}">Vouchers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <p class="text-muted fs-13">Each line must have either debit or credit (not both). Total debits must equal
                total credits.</p>
            <form id="lineDocumentForm" novalidate>
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Voucher date</label>
                        <input type="date" name="voucher_date" class="form-control" required
                            value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Narration</label>
                        <input type="text" name="narration" class="form-control" maxlength="5000">
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Account code</th>
                                <th>Account name</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 4; $i++)
                                <tr>
                                    <td><input type="text" name="lines[{{ $i }}][account_code]" class="form-control"
                                            maxlength="32"></td>
                                    <td><input type="text" name="lines[{{ $i }}][account_name]" class="form-control"
                                            maxlength="120"></td>
                                    <td><input type="number" step="0.01" name="lines[{{ $i }}][debit]"
                                            class="form-control" min="0" value="0"></td>
                                    <td><input type="number" step="0.01" name="lines[{{ $i }}][credit]"
                                            class="form-control" min="0" value="0"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Save draft</button>
                <a href="{{ route('admin.finance.vouchers.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.finance.vouchers.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.finance.vouchers.index'));
        </script>
        <script src="{{ asset('js/modules/erp/line-document-form.js') }}"></script>
    @endpush
</x-layouts.app>
