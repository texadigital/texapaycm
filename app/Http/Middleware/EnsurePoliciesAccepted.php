<?php

namespace App\Http\Middleware;

use App\Models\PolicyAcceptance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePoliciesAccepted
{
    /**
     * Handle an incoming request.
     * Returns 409 JSON if the authenticated user has not accepted current policy versions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Define current policy versions (keep in sync with PoliciesController)
        $versions = [
            'terms' => [
                'version' => '1.0.0',
            ],
            'privacy' => [
                'version' => '1.0.0',
            ],
        ];

        $last = PolicyAcceptance::query()
            ->where('user_id', $user->id)
            ->latest('accepted_at')
            ->first();

        $accepted = false;
        if ($last) {
            $accepted = ($last->terms_version === ($versions['terms']['version'] ?? null))
                && ($last->privacy_version === ($versions['privacy']['version'] ?? null));
        }

        if (!$accepted) {
            return response()->json([
                'code' => 'POLICIES_NOT_ACCEPTED',
                'message' => 'You must review and accept the latest Terms & Privacy to continue.',
                'versions' => [
                    'terms' => $versions['terms']['version'] ?? null,
                    'privacy' => $versions['privacy']['version'] ?? null,
                ],
            ], 409);
        }

        return $next($request);
    }
}
