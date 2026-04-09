<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MessageType;
use App\Mail\CandidateMessageMail;
use App\Models\Application;
use App\Models\CandidateMessage;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class CommunicationService
{
    /**
     * Send a message to a candidate and log it.
     */
    public function send(
        Application $application,
        User        $sender,
        MessageType $type,
        string      $subject,
        string      $body,
        bool        $copyToSender = false,
        ?int        $templateId = null,
    ): CandidateMessage {
        $message = CandidateMessage::create([
            'application_id' => $application->id,
            'sender_id'      => $sender->id,
            'template_id'    => $templateId,
            'type'           => $type,
            'subject'        => $subject,
            'body'           => $body,
            'status'         => 'sent',
            'copy_to_sender' => $copyToSender,
            'sent_at'        => now(),
        ]);

        $candidate = $application->user;

        Mail::to($candidate->email)->queue(new CandidateMessageMail($message));

        if ($copyToSender) {
            Mail::to($sender->email)->queue(new CandidateMessageMail($message));
        }

        return $message;
    }

    /**
     * Build variables map for template rendering.
     *
     * @return array<string, string>
     */
    public function buildVars(Application $application, User $sender): array
    {
        $vacancy = $application->vacancy;
        $company = $vacancy->company;

        return [
            'candidateName' => $application->user->name,
            'vacancyName'   => $vacancy->title,
            'companyName'   => $company?->name ?? '',
            'hrName'        => $sender->name,
            'hrEmail'       => $sender->email,
        ];
    }
}
