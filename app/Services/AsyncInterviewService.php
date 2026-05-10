<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewRequestStatus;
use App\Events\InterviewRequestSent;
use App\Events\InterviewResponseSubmitted;
use App\Models\Application;
use App\Models\InterviewRequest;
use App\Models\InterviewResponse;
use App\Models\User;
use Carbon\Carbon;

final class AsyncInterviewService
{
    /**
     * Send an async text interview request for an application.
     *
     * @param  array<string>  $questions
     * @throws \RuntimeException if an active interview request already exists
     */
    public function send(Application $application, array $questions, ?Carbon $deadline): InterviewRequest
    {
        $existing = InterviewRequest::where('application_id', $application->id)
            ->whereIn('status', [
                InterviewRequestStatus::Pending->value,
                InterviewRequestStatus::Answered->value,
            ])
            ->first();

        if ($existing) {
            throw new \RuntimeException('Active interview request already exists for this application.');
        }

        $application->loadMissing('vacancy.company');

        $request = InterviewRequest::create([
            'application_id'   => $application->id,
            'employer_user_id' => $application->vacancy->company->user_id,
            'questions'        => $questions,
            'deadline_at'      => $deadline,
            'status'           => InterviewRequestStatus::Pending,
        ]);

        $application->update(['status' => ApplicationStatus::Interview]);

        event(new InterviewRequestSent($request));

        return $request;
    }

    /**
     * Save or update a candidate's response to an interview request.
     *
     * @param  array<array{question_index: int, text: string}>  $answers
     * @throws \RuntimeException if the response has already been submitted
     */
    public function saveResponse(
        InterviewRequest $request,
        User             $candidate,
        array            $answers,
        bool             $submit = false,
    ): InterviewResponse {
        $existing = InterviewResponse::where('interview_request_id', $request->id)->first();

        if ($existing?->isSubmitted()) {
            throw new \RuntimeException('Cannot edit a submitted interview response.');
        }

        $data = [
            'interview_request_id' => $request->id,
            'user_id'              => $candidate->id,
            'answers'              => $answers,
        ];

        if ($submit) {
            $data['submitted_at'] = now();
        }

        $response = InterviewResponse::updateOrCreate(
            ['interview_request_id' => $request->id],
            $data,
        );

        if ($submit) {
            $request->update(['status' => InterviewRequestStatus::Answered]);

            event(new InterviewResponseSubmitted($response));
        }

        return $response;
    }

    /**
     * Mark all pending interview requests past their deadline as expired.
     *
     * @return int Number of updated records
     */
    public function markExpired(): int
    {
        return InterviewRequest::pending()
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->update(['status' => InterviewRequestStatus::Expired]);
    }
}
