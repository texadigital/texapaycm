<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        // Only enforce for write methods
        if (!in_array($request->getMethod(), ['POST','PUT','PATCH','DELETE'])) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName() ?: $request->path();
        // Require header for specific mobile routes
        $requires = [
            'api.mobile.transfers.name_enquiry',
            'api.mobile.transfers.quote',
            'api.mobile.transfers.confirm',
            'api.mobile.support.contact',
            'api.mobile.support.tickets.reply',
            'api.mobile.profile.security.pin',
            'api.mobile.profile.security.password',
            'api.mobile.profile.update',
            'api.mobile.profile.notifications.update',
        ];

        $header = $request->header('Idempotency-Key');
        if (in_array($routeName, $requires, true) && empty($header)) {
            return response()->json([
                'success' => false,
                'code' => 'IDEMPOTENCY_KEY_REQUIRED',
                'message' => 'Please provide Idempotency-Key header for this request.',
            ], 400);
        }

        if ($header) {
            $userId = (int) (Auth::id() ?: 0);
            $bodyHash = sha1($request->getContent() ?? '');
            $cacheKey = 'idem:mobile:' . $userId . ':' . $routeName . ':' . sha1($header) . ':' . $bodyHash;
            if (Cache::has($cacheKey)) {
                return new JsonResponse(Cache::get($cacheKey));
            }
            /** @var Response $response */
            $response = $next($request);
            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                Cache::put($cacheKey, $data, now()->addHours(6));
            }
            return $response;
        }

        return $next($request);
    }
}
