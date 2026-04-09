<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CandidateMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CandidateMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->message->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.candidate-message',
            with: [
                'body'       => $this->message->body,
                'senderName' => $this->message->sender->name,
                'company'    => $this->message->application->vacancy->company->name ?? '',
            ],
        );
    }
}
