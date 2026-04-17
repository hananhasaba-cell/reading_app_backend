<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReaderLevelUpMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $oldNickname;
    public $newNickname;
    /**
     * Create a new message instance.
     */
    public function __construct($user, $oldNickname, $newNickname)
    {
        $this->user = $user;
        $this->oldNickname = $oldNickname;
        $this->newNickname = $newNickname;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reader Level Up Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
    public function build()
    {
        return $this->view('emails.level_up')
            ->with([
                'name' => $this->user->name,
                'old_nickname' => $this->oldNickname,
                'new_nickname' => $this->newNickname,
            ])
            ->subject('تهانينا! لقد تم ترقيتك إلى لقب جديد');
}
}