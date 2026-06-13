<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect vendor portal users who must change their initial password.
 */
class EnsureVendorPasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = auth('vendor')->user();
        if (! $vendor instanceof Vendor) {
            return $next($request);
        }

        if (! $vendor->portal_must_change_password) {
            return $next($request);
        }

        $allowed = [
            'vendor.portal.change-password',
            'vendor.portal.change-password.submit',
            'vendor.portal.logout',
        ];

        if ($request->routeIs(...$allowed)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'You must change your password before continuing.',
                'redirect' => route('vendor.portal.change-password'),
            ], 403);
        }

        return redirect()->route('vendor.portal.change-password');
    }
}
