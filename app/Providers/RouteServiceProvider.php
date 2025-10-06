<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Define the 'api' rate limiter used by throttle:api
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(60)->by($key);
        });

        // Auth-specific limiters. Keep tight to protect OTP endpoints.
        RateLimiter::for('signup', function (Request $request) {
            $id = strtolower(trim((string)$request->input('phone') ?: (string)$request->input('identifier')));
            $key = 'signup:' . ($id ?: 'unknown') . ':' . $request->ip();
            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many signup attempts. Please try again shortly.',
                    'retryAfterSeconds' => 60,
                ], 429);
            });
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $id = strtolower(trim((string)$request->input('phone') ?: (string)$request->input('identifier')));
            $key = 'otp:' . ($id ?: 'unknown') . ':' . $request->ip();
            return Limit::perMinute(10)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many verification attempts. Please wait before retrying.',
                    'retryAfterSeconds' => 60,
                ], 429);
            });
        });

        RateLimiter::for('reset-init', function (Request $request) {
            $id = strtolower(trim((string)$request->input('identifier') ?: (string)$request->input('phone')));
            $key = 'reset-init:' . ($id ?: 'unknown') . ':' . $request->ip();
            return Limit::perMinutes(60, 3)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many reset requests. Please try again later.',
                    'retryAfterSeconds' => 3600,
                ], 429);
            });
        });

        RateLimiter::for('reset-verify', function (Request $request) {
            $id = strtolower(trim((string)$request->input('identifier') ?: (string)$request->input('phone')));
            $key = 'reset-verify:' . ($id ?: 'unknown') . ':' . $request->ip();
            return Limit::perMinute(10)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many attempts. Please wait before retrying.',
                    'retryAfterSeconds' => 60,
                ], 429);
            });
        });

        RateLimiter::for('resend-otp', function (Request $request) {
            $id = strtolower(trim((string)$request->input('phone') ?: (string)$request->input('identifier')));
            $key = 'resend:' . ($id ?: 'unknown') . ':' . $request->ip();
            return Limit::perMinute(2)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Please wait a few seconds before requesting another code.',
                    'retryAfterSeconds' => 30,
                ], 429);
            });
        });
    }
}
