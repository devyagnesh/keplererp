<?php

use App\Http\Middleware\EnsureValidLicense;
use App\Http\Middleware\EnsureVendorPasswordChanged;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('erp:expire-sales-quotations')->dailyAt('00:15');
        $schedule->command('whatsapp:send-license-expiry-alerts')->dailyAt('07:30');
        $schedule->command('whatsapp:send-low-stock-alerts')->dailyAt('08:00');
        $schedule->command('whatsapp:send-overdue-invoice-alerts')->dailyAt('09:00');
        $schedule->command('license:refresh')->dailyAt('06:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'license' => EnsureValidLicense::class,
            'vendor.password' => EnsureVendorPasswordChanged::class,
        ]);
        $middleware->appendToGroup('web', [
            EnsureValidLicense::class,
            SecurityHeaders::class,
        ]);
        $middleware->redirectUsersTo(fn () => route('admin.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
