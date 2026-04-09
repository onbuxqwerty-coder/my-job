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

class InterviewInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Interview $interview,
    ) {}

    public function envelope(): Envelope
    {
        $vacancy = $this->interview->application->vacancy;

        return new Envelope(
            subject: 'Запрошення на співбесіду: ' . $vacancy->title,
        );
    }

    public function content(): Content
    {
        $interview = $this->interview;
        $vacancy   = $interview->application->vacancy;
        $company   = $vacancy->company;

        return new Content(
            markdown: 'emails.interview-invitation',
            with: [
                'candidateName' => $interview->application->user->name,
                'vacancyTitle'  => $vacancy->title,
                'companyName'   => $company?->name ?? '',
                'scheduledAt'   => $interview->scheduled_at->format('d.m.Y о H:i'),
                'duration'      => $interview->duration,
                'type'          => $interview->type->label(),
                'meetingLink'   => $interview->meeting_link,
                'officeAddress' => $interview->office_address,
                'notes'         => $interview->notes,
                'confirmUrl'    => $interview->confirmUrl(),
                'cancelUrl'     => $interview->cancelUrl(),
            ],
        );
    }
}
