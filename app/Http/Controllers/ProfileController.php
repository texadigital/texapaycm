<?php

namespace App\Http\Controllers;

use App\Services\LimitCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    protected $limitCheckService;

    public function __construct(LimitCheckService $limitCheckService)
    {
        $this->limitCheckService = $limitCheckService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $limitStatus = $this->limitCheckService->getUserLimitStatus($user);
        $limitWarnings = $this->limitCheckService->getLimitWarnings($user);
        $userStats = $this->limitCheckService->getUserStatistics($user, 30);

        return view('profile.index', compact('user', 'limitStatus', 'limitWarnings', 'userStats'));
    }

    public function limits(Request $request)
    {
        $user = $request->user();
        $limitStatus = $this->limitCheckService->getUserLimitStatus($user);
        $limitWarnings = $this->limitCheckService->getLimitWarnings($user);
        $userStats7Days = $this->limitCheckService->getUserStatistics($user, 7);
        $userStats30Days = $this->limitCheckService->getUserStatistics($user, 30);
        $userStats90Days = $this->limitCheckService->getUserStatistics($user, 90);

        return view('profile.limits', compact(
            'user', 
            'limitStatus', 
            'limitWarnings', 
            'userStats7Days',
            'userStats30Days',
            'userStats90Days'
        ));
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->withInput();
        }

        // Log the account deletion
        \Log::info('User account deletion initiated', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Logout the user
        auth()->logout();

        // Delete the user (this will cascade delete related records due to foreign key constraints)
        $user->delete();

        // Redirect to home with success message
        return redirect()->route('login.show')
            ->with('success', 'Your account has been successfully deleted.');
    }

    public function personalInfo(Request $request)
    {
        $user = $request->user();
        return view('profile.personal-info', compact('user'));
    }

    public function updatePersonalInfo(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'full_name' => ['nullable','string','max:190'],
            'notification_email' => ['nullable','email','max:190'],
            'avatar' => ['nullable','image','max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = $path;
        }

        $user->fill([
            'full_name' => $validated['full_name'] ?? $user->full_name,
            'notification_email' => $validated['notification_email'] ?? $user->notification_email,
            'avatar_path' => $validated['avatar_path'] ?? $user->avatar_path,
        ])->save();

        return back()->with('success', 'Profile updated.');
    }

    public function notifications(Request $request)
    {
        $user = $request->user();
        return view('profile.notifications', compact('user'));
    }

    public function updateNotifications(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'email_notifications' => ['nullable','boolean'],
            'sms_notifications' => ['nullable','boolean'],
        ]);

        // Persist via AdminSetting or user preferences table in future; for now store on user model if columns exist
        $user->fill([
            'email_notifications' => (bool) $request->boolean('email_notifications'),
            'sms_notifications' => (bool) $request->boolean('sms_notifications'),
        ])->save();

        return back()->with('success', 'Notification preferences saved.');
    }
}
