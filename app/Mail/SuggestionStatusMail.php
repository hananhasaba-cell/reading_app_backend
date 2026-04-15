<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuggestionStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $suggestion;
    public $status;
    public $accepted;

    public function __construct($user, $suggestion, $status, $accepted)
    {
        $this->user = $user;
        $this->suggestion = $suggestion;
        $this->status = $status;
        $this->accepted = $accepted;
    }

    public function build()
    {
        return $this->subject('حالة اقتراح الكتاب')
            ->view('emails.suggestion_status');
    }
}
