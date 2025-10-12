<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyProviderHmac
{
    public function handle(Request $request, Closure $next, string $provider = 'safehaven') : Response
    {
        // Raw body and provided signature
        $payload = $request->getContent();
        $provided = $request->header('X-Signature')
            ?? $request->header('X-' . ucfirst($provider) . '-Signature')
            ?? '';

        // Choose secret per provider
        $secret = match ($provider) {
            'safehaven' => config('services.safehaven.webhook_secret'),
            'paystack' => config('services.paystack.webhook_secret'),
            default => null,
        };

        if (empty($secret)) {
            // If no secret configured, fail closed in production; allow in local
            if (!app()->environment('local', 'testing')) {
                return response('Webhook secret not configured', 403);
            }
            return $next($request);
        }

        $computed = hash_hmac('sha256', $payload, $secret);
        // Some providers prefix with sha256=...
        if (str_starts_with((string) $provided, 'sha256=')) {
            $provided = substr((string) $provided, 7);
        }

        if (!hash_equals($computed, (string) $provided)) {
            return response('Invalid signature', 401);
        }

        return $next($request);
    }
}
