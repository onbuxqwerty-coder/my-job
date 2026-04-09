<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApplyDTO;
use App\Enums\ApplicationStatus;
use App\Jobs\SendApplicationStatusNotification;
use App\Jobs\SendNewApplicationNotification;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use DomainException;

final class ApplicationService
{
    /**
     * Submit a job application.
     *
     * @throws DomainException when the user has already applied
     */
    public function apply(User $user, Vacancy $vacancy, ApplyDTO $dto): Application
    {
        if ($this->alreadyApplied($user, $vacancy)) {
            throw new DomainException('You have already applied to this vacancy.');
        }

        $application = Application::create([
            'vacancy_id'   => $vacancy->id,
            'user_id'      => $user->id,
            'resume_url'   => $dto->resumeUrl,
            'cover_letter' => $dto->coverLetter,
            'status'       => ApplicationStatus::Pending,
        ]);

        SendNewApplicationNotification::dispatch($application->id);

        return $application;
    }

    /**
     * Change the status of an application and notify the candidate.
     *
     * @throws DomainException when the status is already set
     */
    public function changeStatus(Application $application, ApplicationStatus $status): Application
    {
        if ($application->status === $status) {
            throw new DomainException('Application is already in this status.');
        }

        $application->update(['status' => $status]);

        SendApplicationStatusNotification::dispatch($application->id);

        return $application->fresh();
    }

    /**
     * Check if the user has already applied to a given vacancy.
     */
    public function alreadyApplied(User $user, Vacancy $vacancy): bool
    {
        return Application::where('user_id', $user->id)
            ->where('vacancy_id', $vacancy->id)
            ->exists();
    }
}
