<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class RequireAccessToken
{
    public function __construct(private JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $auth = $request->headers->get('Authorization', '');
        if (!str_starts_with($auth, 'Bearer ')) {
            throw new UnauthorizedHttpException('Bearer', 'Missing bearer token');
        }

        $token = substr($auth, 7);
        try {
            $payload = $this->jwt->parse($token);
        } catch (\Throwable $e) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token');
        }

        if (($payload->typ ?? '') !== 'access') {
            throw new UnauthorizedHttpException('Bearer', 'Wrong token type');
        }

        $userId = (int) ($payload->sub ?? 0);
        $user = $userId ? User::find($userId) : null;
        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'User not found');
        }

        // Set authenticated user for the request lifecycle (stateless)
        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
