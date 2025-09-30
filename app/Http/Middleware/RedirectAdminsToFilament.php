<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectAdminsToFilament
{
    /**
     * If the authenticated user is an admin, redirect them to the Filament panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (bool) (Auth::user()->is_admin ?? false)) {
            // Allow requests that are already under /admin
            if ($request->is('admin') || $request->is('admin/*')) {
                return $next($request);
            }
            return redirect('/admin');
        }

        return $next($request);
    }
}
