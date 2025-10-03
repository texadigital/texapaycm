<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.limits' => \App\Http\Middleware\CheckUserLimits::class,
            'redirect.admins' => \App\Http\Middleware\RedirectAdminsToFilament::class,
            'mobile.feature' => \App\Http\Middleware\MobileFeatureGate::class,
            'force.json' => \App\Http\Middleware\ForceJson::class,
            'idempotency' => \App\Http\Middleware\EnforceIdempotency::class,
            'auth.jwt' => \App\Http\Middleware\RequireAccessToken::class,

        ]);

        // Trust ngrok (and other) reverse proxies to honor X-Forwarded-* headers
        // This ensures request()->isSecure() and URL generation use https when tunneled
        $middleware->trustProxies('*');
    })
    ->withProviders([
        \App\Providers\RefundServiceProvider::class,
        \App\Providers\RouteServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
