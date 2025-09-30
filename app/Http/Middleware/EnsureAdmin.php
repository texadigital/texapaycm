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
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login.show')->with('error', 'Please log in to access the admin panel.');
        }

        // Check if user is admin
        $user = Auth::user();
        if (!(bool) ($user->is_admin ?? false)) {
            return redirect()->route('dashboard')->with('error', 'You are not authorized to access the admin panel.');
        }

        return $next($request);
    }
}
