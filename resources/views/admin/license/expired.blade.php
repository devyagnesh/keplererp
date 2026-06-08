<x-layouts.guest title="License expired">
    <div class="container py-5 text-center">
        <h1 class="mb-3">License expired</h1>
        <p class="text-muted">Your ManufactureERP license or AMC has expired. Contact your vendor to renew, then run <code>php artisan license:refresh</code>.</p>
        <a href="{{ route('login') }}" class="btn btn-primary mt-3">Back to login</a>
    </div>
</x-layouts.guest>
