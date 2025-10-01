<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/mobile/auth/register
     * Body: { name: string, phone: string, password: string, pin: 4-6 digits }
     * Creates a new user and logs them in (session cookie), returning JSON.
     */
    public function register(Request $request)
    {
        if (!(bool) \App\Models\AdminSetting::getValue('mobile_api_enabled', false)) {
            return response()->json([
                'success' => false,
                'code' => 'FEATURE_DISABLED',
                'message' => 'Mobile API is disabled',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'phone' => ['required','string','max:32','unique:users,phone'],
            'password' => ['required','string','min:6','max:190'],
            'pin' => ['required','string','regex:/^\d{4,6}$/'],
        ]);

        // Normalize phone number to international format
        $phone = PhoneNumberService::normalize($data['phone']);
        
        // Validate Cameroon phone number
        $validation = PhoneNumberService::validateCameroon($phone);
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_PHONE',
                'message' => $validation['error'],
            ], 400);
        }
        
        $email = $phone . '@local';

        $user = User::create([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($data['password']),
            'pin_hash' => Hash::make($data['pin']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'kycStatus' => (string) ($user->kyc_status ?? 'unverified'),
                'kycLevel' => (int) ($user->kyc_level ?? 0),
            ],
        ], 201);
    }
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

        // Normalize phone number to international format
        $phone = PhoneNumberService::normalize($data['phone']);
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
