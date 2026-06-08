<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('erp:expire-sales-quotations')->dailyAt('00:15');
        $schedule->command('whatsapp:send-license-expiry-alerts')->dailyAt('07:30');
        $schedule->command('whatsapp:send-low-stock-alerts')->dailyAt('08:00');
        $schedule->command('whatsapp:send-overdue-invoice-alerts')->dailyAt('09:00');
        $schedule->command('license:refresh')->dailyAt('06:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'license' => \App\Http\Middleware\EnsureValidLicense::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureValidLicense::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->redirectUsersTo(fn () => route('admin.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
