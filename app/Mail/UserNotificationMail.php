<?php

namespace App\Mail;

use App\Models\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserNotification $notification)
    {
    }

    public function build()
    {
        return $this->subject($this->notification->title)
            ->view('emails.user_notification')
            ->with([
                'notification' => $this->notification,
                'user' => $this->notification->user,
            ]);
    }
}


