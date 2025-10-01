<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transfer;
use App\Models\LoginHistory;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function showPinChallenge(Request $request)
    {
        $userId = $request->session()->get('pin_challenge_user');
        if (!$userId) {
            return redirect()->route('login.show');
        }
        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login.show');
        }
        return view('auth.pin', ['user' => $user]);
    }

    public function verifyPinChallenge(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => ['required','digits_between:4,6'],
        ]);
        $userId = $request->session()->get('pin_challenge_user');
        $expires = $request->session()->get('pin_challenge_expires');
        if (!$userId || ($expires && now()->greaterThan($expires))) {
            return redirect()->route('login.show')->withErrors(['pin' => 'PIN challenge expired. Please login again.']);
        }
        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login.show')->withErrors(['pin' => 'Session invalid.']);
        }
        $settings = $user->securitySettings;
        $hash = $settings?->pin_hash ?: $user->pin_hash;
        if (!$hash || !Hash::check($request->string('pin'), $hash)) {
            return back()->withErrors(['pin' => 'Invalid PIN']);
        }
        // Success: complete login
        Auth::login($user, true);
        try {
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'login_method' => 'password+pin',
                'status' => 'success',
                'device_info' => null,
            ]);
        } catch (\Throwable $e) { /* swallow */ }
        
        // Send login success notification
        $this->notificationService->dispatchUserNotification('auth.login.success', $user, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_method' => 'password+pin'
        ]);
        
        $request->session()->forget(['pin_challenge_user','pin_challenge_expires']);
        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'phone' => ['required','string','max:32','unique:users,phone'],
            'password' => ['required','string','min:6','max:190','confirmed'],
            'pin' => ['required','string','regex:/^\d{4,6}$/'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);

        // Email is mandatory in the current schema; use a placeholder derived from phone
        $email = $phone . '@local';

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'pin_hash' => Hash::make($validated['pin']),
        ]);

        Auth::login($user);
        
        // Send registration success notification
        $this->notificationService->dispatchUserNotification('user.registered', $user, [
            'registration_method' => 'phone',
            'phone' => $phone
        ]);
        
        return redirect()->route('dashboard')->with('success', 'Welcome to TexaPay!');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required','string','max:32'],
            'password' => ['required','string','max:190'],
        ]);
        $phone = preg_replace('/\D+/', '', $credentials['phone']);

        $user = User::where('phone', $phone)->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Record failed login
            try {
                LoginHistory::create([
                    'user_id' => $user?->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'login_method' => 'password',
                    'status' => 'failed',
                    'device_info' => null,
                ]);
            } catch (\Throwable $e) { /* swallow */ }
            
            // Send failed login notification if user exists
            if ($user) {
                $this->notificationService->dispatchUserNotification('auth.login.failed', $user, [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'login_method' => 'password'
                ]);
            }
            
            return back()->withInput()->withErrors(['phone' => 'Invalid phone or password']);
        }

        // If PIN is enabled for this user, redirect to PIN challenge instead of logging in now
        $settings = $user->securitySettings;
        if ($settings && $settings->pin_enabled && !empty($settings->pin_hash)) {
            // Store challenge state
            $request->session()->put('pin_challenge_user', $user->id);
            $request->session()->put('pin_challenge_expires', now()->addMinutes(10));
            // Log challenge event
            try {
                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'login_method' => 'password',
                    'status' => 'challenge',
                    'device_info' => null,
                ]);
            } catch (\Throwable $e) { /* swallow */ }
            return redirect()->route('login.pin.show');
        }

        // Otherwise, log in directly
        Auth::login($user, true);
        try {
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'login_method' => 'password',
                'status' => 'success',
                'device_info' => null,
            ]);
        } catch (\Throwable $e) { /* swallow */ }
        
        // Send login success notification
        $this->notificationService->dispatchUserNotification('auth.login.success', $user, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_method' => 'password'
        ]);
        
        // If the user is an admin, send them to the Filament admin panel
        if ((bool) ($user->is_admin ?? false)) {
            return redirect('/admin');
        }
        // Otherwise, go to the normal user dashboard
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required','string'],
        ]);
        $user = $request->user();
        if (!$user || !Hash::check($request->string('password'), $user->password)) {
            return back()->with('error', 'Password is incorrect.');
        }
        // Soft delete user
        $user->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show')->with('success', 'Account deleted.');
    }
}
