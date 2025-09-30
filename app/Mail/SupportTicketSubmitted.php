<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket)
    {
    }

    public function build()
    {
        return $this->subject('New Support Ticket: '.$this->ticket->subject)
            ->view('emails.support_ticket_submitted')
            ->with([
                'ticket' => $this->ticket,
                'user' => $this->ticket->user,
            ]);
    }
}
