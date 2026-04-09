<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Jobs\SendInterviewReminder;
use App\Mail\InterviewInvitationMail;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class InterviewService
{
    /**
     * Schedule an interview and dispatch invitation + reminders.
     */
    public function schedule(
        Application   $application,
        User          $creator,
        \Carbon\Carbon $scheduledAt,
        int           $duration,
        InterviewType $type,
        ?string       $meetingLink,
        ?string       $officeAddress,
        ?string       $notes,
        ?string       $internalNotes,
    ): Interview {
        $interview = Interview::create([
            'application_id' => $application->id,
            'created_by'     => $creator->id,
            'scheduled_at'   => $scheduledAt,
            'duration'       => $duration,
            'type'           => $type,
            'meeting_link'   => $meetingLink ?: null,
            'office_address' => $officeAddress ?: null,
            'notes'          => $notes ?: null,
            'internal_notes' => $internalNotes ?: null,
            'status'         => InterviewStatus::Scheduled,
            'confirm_token'  => Str::uuid()->toString(),
        ]);

        // Send invitation immediately (queued)
        Mail::to($application->user->email)
            ->queue(new InterviewInvitationMail($interview));

        // Schedule reminders
        $this->scheduleReminders($interview);

        return $interview;
    }

    /**
     * Confirm interview by candidate token.
     */
    public function confirm(string $token): Interview
    {
        $interview = Interview::where('confirm_token', $token)->firstOrFail();

        $interview->update(['status' => InterviewStatus::Confirmed]);

        return $interview;
    }

    /**
     * Cancel interview by candidate token or by employer.
     */
    public function cancel(Interview $interview, ?string $reason = null): void
    {
        $interview->update([
            'status'           => InterviewStatus::Cancelled,
            'cancelled_reason' => $reason,
        ]);
    }

    /**
     * Dispatch delayed reminder jobs.
     */
    private function scheduleReminders(Interview $interview): void
    {
        $scheduled = $interview->scheduled_at;

        $reminders = [
            ['label' => '24 години', 'delay' => $scheduled->copy()->subHours(24)],
            ['label' => '1 годину',  'delay' => $scheduled->copy()->subHour()],
            ['label' => '15 хвилин', 'delay' => $scheduled->copy()->subMinutes(15)],
        ];

        foreach ($reminders as $r) {
            if ($r['delay']->isFuture()) {
                SendInterviewReminder::dispatch($interview->id, $r['label'])
                    ->delay($r['delay']);
            }
        }
    }
}
