<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class TokenAuthController extends Controller
{
    public function __construct(private JwtService $jwt)
    {
    }

    private function makeRefreshToken(User $user, Request $request, int $ttlDays = 60): array
    {
        $raw = Str::random(64);
        $hash = hash('sha256', $raw);

        $record = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'device_name' => (string) $request->header('X-Device-Name', ''),
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'expires_at' => now()->addDays($ttlDays),
        ]);

        return [$raw, $record];
    }

    private function setRefreshCookie(string $rawToken)
    {
        // HttpOnly, Secure, Strict; scoped to refresh endpoint path
        $cookie = cookie(
            name: 'refresh_token',
            value: $rawToken,
            minutes: 60 * 24 * 60, // 60 days
            path: '/api/mobile/auth/refresh',
            domain: config('session.domain'),
            secure: true,
            httpOnly: true,
            sameSite: 'strict'
        );
        cookie()->queue($cookie);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required','string','max:32'],
            'password' => ['required','string','max:190'],
            'pin' => ['nullable','string','regex:/^\d{4,6}$/'],
        ]);

        // Reuse existing logic from AuthController for phone normalization and pin gate
        $phone = \App\Services\PhoneNumberService::normalize($data['phone']);
        $user = User::where('phone', $phone)->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid phone or password',
            ], 401);
        }
        $settings = $user->securitySettings;
        if ($settings && $settings->pin_enabled && !empty($settings->pin_hash)) {
            $providedPin = (string) ($data['pin'] ?? '');
            if ($providedPin === '' || !Hash::check($providedPin, $settings->pin_hash)) {
                return response()->json([
                    'success' => false,
                    'code' => 'PIN_REQUIRED',
                    'message' => 'PIN is required for this account',
                ], 403);
            }
        }

        $access = $this->jwt->makeAccessToken($user, []);
        [$rawRefresh, $rec] = $this->makeRefreshToken($user, $request);
        $this->setRefreshCookie($rawRefresh);

        return response()->json([
            'success' => true,
            'accessToken' => $access,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'kycStatus' => (string) ($user->kyc_status ?? 'unverified'),
                'kycLevel' => (int) ($user->kyc_level ?? 0),
            ],
        ]);
    }

    public function refresh(Request $request)
    {
        $raw = (string) $request->cookie('refresh_token', '');
        if ($raw === '') {
            return response()->json(['success' => false, 'message' => 'No refresh token'], 401);
        }
        $hash = hash('sha256', $raw);
        $rec = RefreshToken::where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
        if (!$rec) {
            return response()->json(['success' => false, 'message' => 'Invalid refresh token'], 401);
        }

        $user = $rec->user;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 401);
        }

        // Rotate token
        $rec->revoked_at = now();
        $rec->save();
        [$newRaw, $newRec] = $this->makeRefreshToken($user, $request);
        $rec->replaced_by_id = $newRec->id;
        $rec->save();

        $this->setRefreshCookie($newRaw);
        $access = $this->jwt->makeAccessToken($user, []);

        return response()->json(['success' => true, 'accessToken' => $access]);
    }

    public function logout(Request $request)
    {
        $raw = (string) $request->cookie('refresh_token', '');
        if ($raw !== '') {
            $hash = hash('sha256', $raw);
            RefreshToken::where('token_hash', $hash)->update(['revoked_at' => now()]);
        }
        // Clear cookie
        cookie()->queue(cookie('refresh_token', '', -60, '/api/mobile/auth/refresh', config('session.domain'), true, true, false, 'strict'));
        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        if (!$u) return response()->json(['success' => false], 401);
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'kycStatus' => (string) ($u->kyc_status ?? 'unverified'),
                'kycLevel' => (int) ($u->kyc_level ?? 0),
            ]
        ]);
    }
}
