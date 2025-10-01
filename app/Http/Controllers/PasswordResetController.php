<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
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
            // Clean and format phone number
            $phone = preg_replace('/\D+/', '', $request->phone);
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

            return back()->with('status', 'Password reset code has been sent to your phone number.');

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
    public function showResetPassword(Request $request)
    {
        $phone = $request->session()->get('reset_phone');
        $expires = $request->session()->get('reset_expires');

        if (!$phone || !$expires || now()->greaterThan($expires)) {
            return redirect()->route('password.forgot')->withErrors(['phone' => 'Reset session expired. Please request a new code.']);
        }

        return view('auth.reset-password', [
            'phone' => $phone,
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'password' => 'required|min:6|confirmed',
        ], [
            'code.required' => 'Reset code is required.',
            'code.size' => 'Reset code must be 6 digits.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
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

            // Update user password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Delete used reset code
            DB::table('password_resets')->where('email', $user->email)->delete();

            // Clear session
            $request->session()->forget(['reset_phone', 'reset_expires']);

            // Send password reset success notification
            $this->notificationService->dispatchUserNotification(
                'auth.password.reset.success',
                $user,
                [
                    'title' => 'Password Reset Successful',
                    'message' => 'Your password has been successfully reset. You can now login with your new password.',
                    'reset_at' => now()->toISOString(),
                ],
                ['sms', 'push'] // Send via SMS and push (no email since it's not real)
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
        ]);

        try {
            // Clean and format phone number
            $phone = preg_replace('/\D+/', '', $request->phone);
            $user = User::where('phone', $phone)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this phone number.',
                ], 404);
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
            // Clean and format phone number
            $phone = preg_replace('/\D+/', '', $request->phone);
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
