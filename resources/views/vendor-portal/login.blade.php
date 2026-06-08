<x-layouts.guest title="Vendor portal">
    <div class="container py-5" style="max-width:420px;">
        <h1 class="mb-4 text-center">Vendor portal</h1>
        <form id="vendorLoginForm" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
    </div>
    @push('scripts')
        <script>
            $(function () {
                $('#vendorLoginForm').validate({
                    submitHandler: function (form) {
                        $.ajax({
                            url: @json(route('vendor.portal.login.attempt')),
                            type: 'POST',
                            data: $(form).serialize(),
                            dataType: 'json',
                            success: function (r) { window.location = r.redirect || @json(route('vendor.portal.dashboard')); },
                            error: function (xhr) {
                                var r = xhr.responseJSON;
                                alert(r && r.message ? r.message : 'Login failed');
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
</x-layouts.guest>
