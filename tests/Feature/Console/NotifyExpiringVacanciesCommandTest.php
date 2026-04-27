<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\VacancyExpiringSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyExpiringVacanciesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createVacancyForUser(User $user, array $vacancyState = []): Vacancy
    {
        $company = Company::factory()->create(['user_id' => $user->id]);

        return Vacancy::factory()->expiringSoon(hours: 12)->create(
            array_merge(['company_id' => $company->id], $vacancyState)
        );
    }

    public function test_command_sends_notification_for_vacancies_expiring_within_24h(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'telegram_id'                    => 12345,
            'telegram_notifications_enabled' => true,
        ]);
        $v = $this->createVacancyForUser($user);

        $this->artisan('vacancies:notify-expiring')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            VacancyExpiringSoonNotification::class,
            fn ($n) => $n->vacancy->id === $v->id
        );

        $this->assertNotNull($v->fresh()->expiry_notification_sent_at);
    }

    public function test_does_not_send_twice_for_already_notified_vacancy(): void
    {
        Notification::fake();

        $user = User::factory()->create(['telegram_id' => 12345]);
        $this->createVacancyForUser($user, [
            'expiry_notification_sent_at' => now()->subHours(2),
        ]);

        $this->artisan('vacancies:notify-expiring')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_skips_users_without_telegram_id(): void
    {
        Notification::fake();

        $user = User::factory()->create(['telegram_id' => null]);
        $this->createVacancyForUser($user);

        $this->artisan('vacancies:notify-expiring')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_skips_users_with_notifications_disabled(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'telegram_id'                    => 12345,
            'telegram_notifications_enabled' => false,
        ]);
        $this->createVacancyForUser($user);

        $this->artisan('vacancies:notify-expiring')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
