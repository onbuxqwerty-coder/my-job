<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\InterviewStatus;
use App\Mail\InterviewReminderMail;
use App\Models\Interview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInterviewReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int    $interviewId,
        public readonly string $reminderLabel,
    ) {}

    public function handle(): void
    {
        $interview = Interview::with(['application.user', 'application.vacancy.company'])
            ->find($this->interviewId);

        if (!$interview) {
            return;
        }

        // Skip if cancelled
        if ($interview->status === InterviewStatus::Cancelled) {
            return;
        }

        $candidate = $interview->application->user;

        Mail::to($candidate->email)->send(
            new InterviewReminderMail($interview, $this->reminderLabel)
        );
    }
}
