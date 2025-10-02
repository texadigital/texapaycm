<?php

namespace App\Mail;

use App\Models\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class UserNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserNotification $notification)
    {
    }

    public function build()
    {
        // Determine specific template per notification type (non-breaking; fallback to generic)
        $type = (string) ($this->notification->type ?? '');
        $baseView = 'emails.user_notification';
        $view = $baseView;
        $textView = null;

        // Map known transfer-related types to specific views
        $map = [
            'transfer.initiated' => 'emails.transfers.initiated',
            'transfer.payin.success' => 'emails.transfers.payin_success',
            'transfer.payin.failed' => 'emails.transfers.payin_failed',
            'transfer.payout.success' => 'emails.transfers.payout_success',
            'transfer.payout.failed' => 'emails.transfers.payout_failed',
            'transfer.refund.initiated' => 'emails.transfers.refund_initiated',
            'transfer.refund.completed' => 'emails.transfers.refund_completed',
        ];

        if (isset($map[$type]) && View::exists($map[$type])) {
            $view = $map[$type];
            // Attach plain text alternative if available
            $candidateText = $map[$type] . '.text';
            if (View::exists($candidateText)) {
                $textView = $candidateText;
            }
        }

        $this->subject($this->notification->title)
            ->view($view)
            ->with([
                'notification' => $this->notification,
                'user' => $this->notification->user,
            ]);

        if ($textView) {
            $this->text($textView);
        }

        return $this;
    }
}


