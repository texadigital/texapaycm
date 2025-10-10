<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComplianceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $subjectLine, public array $data = [])
    {
        $this->subject($subjectLine);
    }

    public function build()
    {
        return $this->view('emails.compliance_alert')
            ->with(['subjectLine' => $this->subjectLine, 'data' => $this->data]);
    }
}
