<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Resend reset code with cooldown
     */
    public function resendResetCode(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('reset_phone');
        $expires = $request->session()->get('reset_expires');
        if (!$phone || !$expires || now()->greaterThan($expires)) {
            return redirect()->route('password.forgot')->withErrors(['phone' => 'Reset session expired. Please request a new code.']);
        }

        $cooldown = 60; // seconds
        $lastSent = $request->session()->get('reset_last_sent_at');
        if ($lastSent && now()->diffInSeconds($lastSent) < $cooldown) {
            $remaining = $cooldown - now()->diffInSeconds($lastSent);
            return back()->withErrors(['resend' => 'Please wait ' . $remaining . ' seconds before resending.']);
        }

        try {
            $user = User::where('phone', $phone)->first();
            if (!$user) {
                return redirect()->route('password.forgot')->withErrors(['phone' => 'User not found.']);
            }

            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(15);

            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            $this->notificationService->dispatchUserNotification(
                'auth.password.reset.requested',
                $user,
                [
                    'title' => 'Password Reset Code',
                    'message' => "Your password reset code is: {$code}. This code expires in 15 minutes.",
                    'reset_code' => $code,
                    'expires_at' => $expiresAt->toISOString(),
                ],
                ['sms']
            );

            // refresh session timers
            $request->session()->put('reset_expires', $expiresAt);
            $request->session()->put('reset_last_sent_at', now());

            return back()->with('status', 'A new code has been sent.');

        } catch (\Throwable $e) {
            Log::error('Password reset resend failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['resend' => 'Unable to resend code. Please try again.']);
        }
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset code via SMS
     */
    public function sendResetCode(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string|max:32',
        ], [
            'phone.required' => 'Phone number is required.',
        ]);

        try {
            // Normalize phone number to international format
            $phone = PhoneNumberService::normalize($request->phone);
            $user = User::where('phone', $phone)->first();
            
            if (!$user) {
                return back()->withErrors(['phone' => 'No account found with this phone number.']);
            }

            // Generate reset code (6 digits)
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(15); // Code expires in 15 minutes

            // Store reset code in database (using email field for compatibility)
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email], // Using email field to store user identifier
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            // Send password reset notification via SMS
            $this->notificationService->dispatchUserNotification(
                'auth.password.reset.requested',
                $user,
                [
                    'title' => 'Password Reset Code',
                    'message' => "Your password reset code is: {$code}. This code expires in 15 minutes.",
                    'reset_code' => $code,
                    'expires_at' => $expiresAt->toISOString(),
                ],
                ['sms'] // Send via SMS only
            );

            Log::info('Password reset code sent', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);

            // Store phone in session for verification step
            $request->session()->put('reset_phone', $phone);
            $request->session()->put('reset_expires', $expiresAt);
            $request->session()->put('reset_last_sent_at', now());

            return redirect()->route('password.verify')
                ->with('status', 'Password reset code has been sent to your phone number.');

        } catch (\Exception $e) {
            Log::error('Password reset code failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['phone' => 'Unable to send reset code. Please try again.']);
        }
    }

    /**
     * Show password reset form
     */
    public function showVerifyCode(Request $request)
    {
        $phone = $request->session()->get('reset_phone');
        $expires = $request->session()->get('reset_expires');

        if (!$phone || !$expires || now()->greaterThan($expires)) {
            return redirect()->route('password.forgot')->withErrors(['phone' => 'Reset session expired. Please request a new code.']);
        }

        $lastSent = $request->session()->get('reset_last_sent_at');
        $cooldown = 60;
        $resendWait = 0;
        if ($lastSent) {
            $diff = now()->diffInSeconds($lastSent);
            $resendWait = max(0, $cooldown - $diff);
        }

        return view('auth.verify-reset-code', [
            'phone' => $phone,
            'resend_wait' => $resendWait,
            'cooldown' => $cooldown,
        ]);
    }

    /**
     * Reset password
     */
    public function verifyResetCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Reset code is required.',
            'code.size' => 'Reset code must be 6 digits.',
        ]);

        try {
            $phone = $request->session()->get('reset_phone');
            $expires = $request->session()->get('reset_expires');

            if (!$phone || !$expires || now()->greaterThan($expires)) {
                return redirect()->route('password.forgot')->withErrors(['phone' => 'Reset session expired. Please request a new code.']);
            }

            // Get user
            $user = User::where('phone', $phone)->first();
            if (!$user) {
                return redirect()->route('password.forgot')->withErrors(['phone' => 'User not found.']);
            }

            // Verify reset code
            $passwordReset = DB::table('password_resets')
                ->where('email', $user->email)
                ->where('created_at', '>', now()->subMinutes(15))
                ->first();

            if (!$passwordReset || !Hash::check($request->code, $passwordReset->token)) {
                return back()->withErrors(['code' => 'Invalid or expired reset code.']);
            }

            // Mark session as verified and proceed to reset form
            $request->session()->put('reset_verified', true);
            return redirect()->route('password.reset')->with('status', 'Code verified. Set your new password.');

        } catch (\Exception $e) {
            Log::error('Password reset verification failed', [
                'phone' => $phone ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['code' => 'Unable to verify code. Please try again.']);
        }
    }

    public function showResetPassword(Request $request)
    {
        $phone = $request->session()->get('reset_phone');
        $expires = $request->session()->get('reset_expires');
        $verified = (bool) $request->session()->get('reset_verified');

        if (!$phone || !$expires || now()->greaterThan($expires)) {
            return redirect()->route('password.forgot')->withErrors(['phone' => 'Reset session expired. Please request a new code.']);
        }
        if (!$verified) {
            return redirect()->route('password.verify');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ], [
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            $phone = $request->session()->get('reset_phone');
            $expires = $request->session()->get('reset_expires');
            $verified = (bool) $request->session()->get('reset_verified');

            if (!$phone || !$expires || now()->greaterThan($expires)) {
                return redirect()->route('password.forgot')->withErrors(['phone' => 'Reset session expired. Please request a new code.']);
            }
            if (!$verified) {
                return redirect()->route('password.verify');
            }

            // Get user
            $user = User::where('phone', $phone)->first();
            if (!$user) {
                return redirect()->route('password.forgot')->withErrors(['phone' => 'User not found.']);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Delete used reset token
            DB::table('password_resets')->where('email', $user->email)->delete();

            // Clear session
            $request->session()->forget(['reset_phone', 'reset_expires', 'reset_verified']);

            // Send success notification
            $this->notificationService->dispatchUserNotification(
                'auth.password.reset.success',
                $user,
                [
                    'title' => 'Password Reset Successful',
                    'message' => 'Your password has been successfully reset. You can now login with your new password.',
                    'reset_at' => now()->toISOString(),
                ],
                ['sms', 'push']
            );

            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);

            return redirect()->route('login.show')->with('status', 'Password has been reset successfully. You can now login with your new password.');

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'phone' => $phone ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['password' => 'Unable to reset password. Please try again.']);
        }
    }

    /**
     * API endpoint for mobile app password reset
     */
    public function apiSendResetCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:32',
            'pin' => ['nullable','string','regex:/^\d{4,6}$/'],
        ]);

        try {
            // Normalize phone number to international format
            $phone = PhoneNumberService::normalize($request->phone);
            $user = User::where('phone', $phone)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this phone number.',
                ], 404);
            }

            // If user has PIN enabled, require correct PIN before sending reset code
            try {
                $settings = $user->securitySettings;
                if ($settings && $settings->pin_enabled && !empty($settings->pin_hash)) {
                    $providedPin = (string) ($request->input('pin') ?? '');
                    if ($providedPin === '' || !\Illuminate\Support\Facades\Hash::check($providedPin, $settings->pin_hash)) {
                        return response()->json([
                            'success' => false,
                            'code' => 'PIN_REQUIRED',
                            'message' => 'PIN is required to reset this account password.',
                        ], 403);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback: check legacy user pin_hash if present
                if (!empty($user->pin_hash)) {
                    $providedPin = (string) ($request->input('pin') ?? '');
                    if ($providedPin === '' || !\Illuminate\Support\Facades\Hash::check($providedPin, $user->pin_hash)) {
                        return response()->json([
                            'success' => false,
                            'code' => 'PIN_REQUIRED',
                            'message' => 'PIN is required to reset this account password.',
                        ], 403);
                    }
                }
            }

            // Generate reset code (6 digits)
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(15);

            // Store reset code
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            // Send notification
            $this->notificationService->dispatchUserNotification(
                'auth.password.reset.requested',
                $user,
                [
                    'title' => 'Password Reset Code',
                    'message' => "Your password reset code is: {$code}. This code expires in 15 minutes.",
                    'reset_code' => $code,
                    'expires_at' => $expiresAt->toISOString(),
                ],
                ['sms', 'push'] // Send via SMS and push
            );

            return response()->json([
                'success' => true,
                'message' => 'Password reset code has been sent to your phone.',
                'expires_at' => $expiresAt->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('API password reset failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send reset code. Please try again.',
            ], 500);
        }
    }

    /**
     * API endpoint for mobile app password reset verification
     */
    public function apiResetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:32',
            'code' => 'required|string|size:6',
            'password' => 'required|min:6',
        ]);

        try {
            // Normalize phone number to international format
            $phone = PhoneNumberService::normalize($request->phone);
            $user = User::where('phone', $phone)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this phone number.',
                ], 404);
            }

            // Verify reset code
            $passwordReset = DB::table('password_resets')
                ->where('email', $user->email)
                ->where('created_at', '>', now()->subMinutes(15))
                ->first();

            if (!$passwordReset || !Hash::check($request->code, $passwordReset->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset code.',
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Delete used token
            DB::table('password_resets')->where('email', $user->email)->delete();

            // Send success notification
            $this->notificationService->dispatchUserNotification(
                'auth.password.reset.success',
                $user,
                [
                    'title' => 'Password Reset Successful',
                    'message' => 'Your password has been successfully reset.',
                ],
                ['sms', 'push'] // Send via SMS and push
            );

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('API password reset failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to reset password. Please try again.',
            ], 500);
        }
    }
}
