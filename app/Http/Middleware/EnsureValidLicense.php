<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks mutating requests when license is expired (SRS §18 read-only lockout).
 */
class EnsureValidLicense
{
    public function __construct(
        protected LicenseService $license
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(
            'login',
            'login.attempt',
            'vendor.portal.login',
            'vendor.portal.login.attempt',
            'admin.license.expired'
        )) {
            return $next($request);
        }

        if ($this->license->isValid()) {
            return $next($request);
        }

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        if (str_ends_with($routeName, '.data')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'License expired. Renew AMC to continue. Read-only mode is active.',
            ], 403);
        }

        if ($request->routeIs('admin.license.expired')) {
            return $next($request);
        }

        return redirect()->route('admin.license.expired');
    }
}
