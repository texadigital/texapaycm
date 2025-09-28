<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !(bool) (Auth::user()->is_admin ?? false)) {
            // If not admin, redirect to home or abort.
            return redirect()->route('dashboard')->with('error', 'You are not authorized to access the admin panel.');
        }

        return $next($request);
    }
}
