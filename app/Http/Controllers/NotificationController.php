<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);
        $type = $request->get('type');
        $unreadOnly = $request->boolean('unread_only', false);

        $query = $user->notifications();

        if ($type) {
            $query->ofType($type);
        }

        if ($unreadOnly) {
            $query->unread();
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Get notification summary
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount($user),
            'recent_notifications' => $this->notificationService->getRecentNotifications($user, 5),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, UserNotification $notification): JsonResponse
    {
        $user = $request->user();

        // Ensure user owns the notification
        if ($notification->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'count' => $count,
        ]);
    }

    /**
     * Get notification preferences
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get all notification types and their preferences
        $notificationTypes = [
            'auth.login.success',
            'auth.login.failed',
            'profile.updated',
            'security.settings.updated',
            'kyc.started',
            'kyc.completed',
            'kyc.failed',
            'transfer.quote.created',
            'transfer.quote.expired',
            'transfer.initiated',
            'transfer.payin.success',
            'transfer.payin.failed',
            'transfer.payout.success',
            'transfer.payout.failed',
            'transfer.refund.initiated',
            'transfer.refund.completed',
            'support.ticket.created',
            'support.ticket.replied',
            'support.ticket.closed',
            'limits.warning.daily',
            'limits.warning.monthly',
            'limits.exceeded',
        ];

        $preferences = [];
        foreach ($notificationTypes as $type) {
            $preference = \App\Models\NotificationPreference::getOrCreateDefault($user, $type);
            $preferences[$type] = [
                'email_enabled' => $preference->email_enabled,
                'sms_enabled' => $preference->sms_enabled,
                'push_enabled' => $preference->push_enabled,
                'in_app_enabled' => $preference->in_app_enabled,
            ];
        }

        return response()->json([
            'preferences' => $preferences,
            'global_settings' => [
                'email_notifications' => $user->email_notifications,
                'sms_notifications' => $user->sms_notifications,
            ],
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.email_enabled' => 'boolean',
            'preferences.*.sms_enabled' => 'boolean',
            'preferences.*.push_enabled' => 'boolean',
            'preferences.*.in_app_enabled' => 'boolean',
        ]);

        foreach ($validated['preferences'] as $type => $settings) {
            $preference = \App\Models\NotificationPreference::getOrCreateDefault($user, $type);
            $preference->update($settings);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
        ]);
    }
}


