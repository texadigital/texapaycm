<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = $user->getOrCreateSecuritySettings();
        $recentLogins = LoginHistory::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('profile.security', compact('user', 'settings', 'recentLogins'));
    }

    public function updateToggles(Request $request)
    {
        $user = $request->user();
        $settings = $user->getOrCreateSecuritySettings();

        $validated = $request->validate([
            'sms_login_enabled' => ['nullable','boolean'],
            'face_id_enabled' => ['nullable','boolean'],
            'pin_enabled' => ['nullable','boolean'],
        ]);

        $settings->fill([
            'sms_login_enabled' => $request->boolean('sms_login_enabled'),
            'face_id_enabled' => $request->boolean('face_id_enabled'),
            'pin_enabled' => $request->boolean('pin_enabled') && !empty($settings->pin_hash),
            'last_security_update' => now(),
        ])->save();

        return back()->with('success', 'Security settings updated.');
    }

    public function updatePin(Request $request)
    {
        $user = $request->user();
        $settings = $user->getOrCreateSecuritySettings();

        $validated = $request->validate([
            'pin' => ['required','digits_between:4,6','confirmed'],
        ]);

        $settings->fill([
            'pin_hash' => Hash::make($validated['pin']),
            'pin_enabled' => true,
            'last_security_update' => now(),
        ])->save();

        return back()->with('success', 'PIN updated.');
    }
}
