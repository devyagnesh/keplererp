<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Change password — Vendor portal</title>
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
</head>
<body class="authentication-background">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card custom-card">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Change your password</h4>
                        <p class="text-muted">You must set a new password before accessing the vendor portal.</p>
                        <form id="vendorChangePasswordForm" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Current password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New password</label>
                                <input type="password" name="password" class="form-control" required minlength="8">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm new password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-wave w-100">Update password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        $(function () {
            $('#vendorChangePasswordForm').on('submit', function (e) {
                e.preventDefault();
                var $btn = $(this).find('[type="submit"]');
                $btn.prop('disabled', true);
                $.ajax({
                    url: @json(route('vendor.portal.change-password.submit')),
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                }).done(function (r) {
                    notifySuccess(r.message);
                    if (r.redirect) { window.location.href = r.redirect; }
                }).fail(function (x) {
                    notifyError(x.responseJSON?.message || 'Could not change password.');
                }).always(function () { $btn.prop('disabled', false); });
            });
        });
    </script>
</body>
</html>
