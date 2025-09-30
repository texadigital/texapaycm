<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJson
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure JSON is preferred to avoid HTML redirects
        $request->headers->set('Accept', 'application/json');

        /** @var Response $response */
        $response = $next($request);

        // Convert 302 redirects to JSON 401 to avoid HTML for API clients
        if (in_array($response->getStatusCode(), [301,302,303,307,308])) {
            return new JsonResponse([
                'success' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required',
            ], 401);
        }

        return $response;
    }
}
