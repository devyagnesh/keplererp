<x-layouts.guest title="Sign in">
    <div class="container">
        <div class="row justify-content-center authentication authentication-basic align-items-center h-100">
            <div class="col-md-4">
                <div class="mb-3 d-flex justify-content-center auth-logo">
                    @if (! empty($companyLogoUrl))
                        <img src="{{ $companyLogoUrl }}" alt="{{ $companyDisplayName }}" class="desktop-logo img-fluid"
                            height="72">
                    @else
                        <span class="fw-bold text-primary fs-24">{{ $companyDisplayName }}</span>
                    @endif
                </div>
                <div class="card custom-card my-4 border z-3 position-relative">
                    <div class="card-body p-0">
                        <div class="p-5">
                            <p class="h4 fw-semibold mb-2 text-center">Sign in</p>
                            <p class="mb-4 text-muted fw-normal text-center fs-13">On-premise manufacturing ERP</p>

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form id="loginForm" method="POST" action="{{ route('login.attempt') }}" novalidate>
                                @csrf
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label for="email" class="form-label text-default">Email</label>
                                        <input type="email" class="form-control form-control-lg" id="email"
                                            name="email" value="{{ old('email') }}" placeholder="admin@example.com"
                                            autocomplete="username" required>
                                        <span class="invalid-feedback d-block" id="email-error"></span>
                                    </div>
                                    <div class="col-xl-12 mb-2">
                                        <label for="password" class="form-label text-default">Password</label>
                                        <input type="password" class="form-control form-control-lg" id="password"
                                            name="password" placeholder="Password" autocomplete="current-password"
                                            required>
                                        <span class="invalid-feedback d-block" id="password-error"></span>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember"
                                                id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label text-muted fw-normal fs-12" for="remember">
                                                Remember me
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2 w-100" id="loginSubmit">
                                        <i class="ri-login-circle-line fs-18"></i> Sign in
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('js/modules/auth/login.js') }}"></script>
    @endpush
</x-layouts.guest>
