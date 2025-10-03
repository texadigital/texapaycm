<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSecuritySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        $sec = UserSecuritySetting::firstOrCreate(['user_id' => $u->id]);
        return response()->json([
            'pinEnabled' => (bool) $sec->pin_enabled,
            'twoFactorEnabled' => (bool) $sec->two_factor_enabled,
            'lastSecurityUpdate' => $sec->last_security_update?->toISOString(),
        ]);
    }

    public function updatePin(Request $request)
    {
        $data = $request->validate([
            'currentPin' => ['nullable','string','min:4','max:6'],
            'newPin' => ['required','string','regex:/^\d{4,6}$/'],
        ]);
        $u = $request->user();
        $sec = UserSecuritySetting::firstOrCreate(['user_id' => $u->id]);
        if ($sec->pin_enabled && $sec->pin_hash) {
            if (empty($data['currentPin']) || !Hash::check($data['currentPin'], $sec->pin_hash)) {
                return response()->json(['success' => false, 'code' => 'INVALID_PIN', 'message' => 'Current PIN is incorrect.'], 400);
            }
        }
        $sec->pin_hash = Hash::make($data['newPin']);
        $sec->pin_enabled = true;
        $sec->last_security_update = now();
        $sec->save();
        return response()->json(['success' => true]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'currentPassword' => ['required','string','min:6'],
            'newPassword' => ['required','string','min:8'],
        ]);
        $u = $request->user();
        if (!Hash::check($data['currentPassword'], $u->password)) {
            return response()->json(['success' => false, 'code' => 'INVALID_PASSWORD', 'message' => 'Current password is incorrect.'], 400);
        }
        $u->password = Hash::make($data['newPassword']);
        $u->save();
        return response()->json(['success' => true]);
    }

    public function updateToggles(Request $request)
    {
        $data = $request->validate([
            'pinEnabled' => ['nullable','boolean'],
            'twoFactorEnabled' => ['nullable','boolean'],
        ]);
        $u = $request->user();
        $sec = \App\Models\UserSecuritySetting::firstOrCreate(['user_id' => $u->id]);
        if (array_key_exists('pinEnabled', $data)) {
            $sec->pin_enabled = (bool) $data['pinEnabled'];
        }
        if (array_key_exists('twoFactorEnabled', $data)) {
            $sec->two_factor_enabled = (bool) $data['twoFactorEnabled'];
        }
        $sec->last_security_update = now();
        $sec->save();
        return response()->json(['success' => true, 'settings' => [
            'pinEnabled' => (bool) $sec->pin_enabled,
            'twoFactorEnabled' => (bool) $sec->two_factor_enabled,
            'lastSecurityUpdate' => $sec->last_security_update?->toISOString(),
        ]]);
    }

    /**
     * Verify PIN by phone (no auth) for flows like password reset pre-check.
     */
    public function verifyPin(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required','string','max:32'],
            'pin' => ['required','string','regex:/^\d{4,6}$/'],
        ]);
        $phone = \App\Services\PhoneNumberService::normalize($data['phone']);
        $user = \App\Models\User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json(['success' => false, 'code' => 'USER_NOT_FOUND', 'message' => 'No account found.'], 404);
        }
        $sec = \App\Models\UserSecuritySetting::firstOrCreate(['user_id' => $user->id]);
        if (!$sec->pin_enabled || empty($sec->pin_hash)) {
            return response()->json(['success' => false, 'code' => 'PIN_NOT_ENABLED', 'message' => 'PIN is not enabled for this account.'], 400);
        }
        if (!\Illuminate\Support\Facades\Hash::check($data['pin'], $sec->pin_hash)) {
            return response()->json(['success' => false, 'code' => 'INVALID_PIN', 'message' => 'PIN is incorrect.'], 400);
        }
        return response()->json(['success' => true]);
    }
}
