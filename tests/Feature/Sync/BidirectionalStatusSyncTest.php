<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Events\ApplicationStatusChanged;
use App\Exceptions\UnauthorizedStatusChangeException;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Services\ApplicationStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BidirectionalStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeApplication(ApplicationStatus $status = ApplicationStatus::Pending): array
    {
        $seeker   = User::factory()->create(['role' => UserRole::Candidate]);
        $employer = User::factory()->create(['role' => UserRole::Employer]);
        $company  = Company::factory()->for($employer, 'user')->create();
        $vacancy  = Vacancy::factory()->for($company)->create();
        $app      = Application::factory()
            ->for($seeker, 'user')
            ->for($vacancy)
            ->create(['status' => $status]);

        return [$seeker, $employer, $app];
    }

    #[Test]
    public function employer_can_change_status_to_interview(): void
    {
        [, $employer, $app] = $this->makeApplication();
        $this->actingAs($employer);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Interview, $employer, 'employer');

        $this->assertSame(ApplicationStatus::Interview, $app->refresh()->status);
    }

    #[Test]
    public function seeker_can_withdraw_application(): void
    {
        [$seeker, , $app] = $this->makeApplication();
        $this->actingAs($seeker);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Withdrawn, $seeker, 'seeker');

        $this->assertSame(ApplicationStatus::Withdrawn, $app->refresh()->status);
    }

    #[Test]
    public function employer_cannot_withdraw_application(): void
    {
        [, $employer, $app] = $this->makeApplication();
        $this->actingAs($employer);

        $this->expectException(UnauthorizedStatusChangeException::class);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Withdrawn, $employer, 'employer');
    }

    #[Test]
    public function seeker_cannot_set_status_to_hired(): void
    {
        [$seeker, , $app] = $this->makeApplication();
        $this->actingAs($seeker);

        $this->expectException(UnauthorizedStatusChangeException::class);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Hired, $seeker, 'seeker');
    }

    #[Test]
    public function status_change_is_recorded_in_history(): void
    {
        [, $employer, $app] = $this->makeApplication();
        $this->actingAs($employer);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Screening, $employer, 'employer', 'Переглянули резюме');

        $history = $app->statusHistory()->first();
        $this->assertNotNull($history);
        $this->assertSame(ApplicationStatus::Screening, $history->to_status);
        $this->assertSame('employer', $history->actor_role);
        $this->assertSame('Переглянули резюме', $history->comment);
    }

    #[Test]
    public function status_change_dispatches_event(): void
    {
        Event::fake([ApplicationStatusChanged::class]);

        [, $employer, $app] = $this->makeApplication();
        $this->actingAs($employer);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Screening, $employer, 'employer');

        Event::assertDispatched(ApplicationStatusChanged::class, function (ApplicationStatusChanged $e) use ($app): bool {
            return $e->application->id === $app->id
                && $e->newStatus === ApplicationStatus::Screening;
        });
    }

    #[Test]
    public function both_sides_receive_notification_on_change(): void
    {
        Notification::fake();

        [$seeker, $employer, $app] = $this->makeApplication();
        $this->actingAs($employer);

        $service = $this->app->make(ApplicationStatusService::class);
        $service->changeStatus($app, ApplicationStatus::Interview, $employer, 'employer');

        Notification::assertSentTo($seeker, ApplicationStatusChangedNotification::class);
        Notification::assertSentTo($employer, ApplicationStatusChangedNotification::class);
    }
}
