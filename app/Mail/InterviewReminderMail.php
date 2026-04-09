<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Interview $interview,
        public readonly string   $reminderLabel, // "24 години", "1 година", "15 хвилин"
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Нагадування про співбесіду через ' . $this->reminderLabel,
        );
    }

    public function content(): Content
    {
        $interview = $this->interview;
        $vacancy   = $interview->application->vacancy;

        return new Content(
            markdown: 'emails.interview-reminder',
            with: [
                'candidateName' => $interview->application->user->name,
                'vacancyTitle'  => $vacancy->title,
                'scheduledAt'   => $interview->scheduled_at->format('d.m.Y о H:i'),
                'type'          => $interview->type->label(),
                'meetingLink'   => $interview->meeting_link,
                'officeAddress' => $interview->office_address,
                'reminderLabel' => $this->reminderLabel,
                'cancelUrl'     => $interview->cancelUrl(),
            ],
        );
    }
}
