<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SupportThread $thread,
        public readonly SupportMessage $message,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Відповідь на звернення: {$this->thread->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-message',
            with: [
                'subject'       => $this->thread->subject,
                'body'          => $this->message->body,
                'recipientName' => $this->recipientName,
                'threadUrl'     => url('/seeker/messages/' . $this->thread->id),
            ],
        );
    }
}
