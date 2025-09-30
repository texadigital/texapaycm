<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/mobile/auth/login
     * Body: { phone: string, password: string, pin?: string }
     * Mirrors web login + optional PIN challenge. Uses session cookies.
     */
    public function login(Request $request)
    {
        // Feature flag
        if (!(bool) \App\Models\AdminSetting::getValue('mobile_api_enabled', false)) {
            return response()->json([
                'success' => false,
                'code' => 'FEATURE_DISABLED',
                'message' => 'Mobile API is disabled',
            ], 403);
        }

        $data = $request->validate([
            'phone' => ['required','string','max:32'],
            'password' => ['required','string','max:190'],
            'pin' => ['nullable','string','regex:/^\d{4,6}$/'],
        ]);

        $phone = preg_replace('/\D+/', '', $data['phone']);
        $user = User::where('phone', $phone)->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            try { LoginHistory::create([
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'login_method' => 'password',
                'status' => 'failed',
                'device_info' => 'mobile_api',
            ]); } catch (\Throwable $e) {}
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid phone or password',
            ], 401);
        }

        // PIN gating mirrors web flow if enabled
        $settings = $user->securitySettings;
        if ($settings && $settings->pin_enabled && !empty($settings->pin_hash)) {
            $providedPin = (string) ($data['pin'] ?? '');
            if ($providedPin === '' || !Hash::check($providedPin, $settings->pin_hash)) {
                try { LoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'login_method' => 'password',
                    'status' => 'challenge',
                    'device_info' => 'mobile_api',
                ]); } catch (\Throwable $e) {}
                return response()->json([
                    'success' => false,
                    'code' => 'PIN_REQUIRED',
                    'message' => 'PIN is required for this account',
                ], 403);
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        try { LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'login_method' => 'password',
            'status' => 'success',
            'device_info' => 'mobile_api',
        ]); } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'kycStatus' => (string) ($user->kyc_status ?? 'unverified'),
                'kycLevel' => (int) ($user->kyc_level ?? 0),
            ],
        ]);
    }

    /**
     * POST /api/mobile/auth/logout
     */
    public function logout(Request $request)
    {
        if (!(bool) \App\Models\AdminSetting::getValue('mobile_api_enabled', false)) {
            return response()->json([
                'success' => false,
                'code' => 'FEATURE_DISABLED',
                'message' => 'Mobile API is disabled',
            ], 403);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['success' => true]);
    }
}
