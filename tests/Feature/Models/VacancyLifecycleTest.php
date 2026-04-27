<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VacancyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-06-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── publish() ────────────────────────────────────────────────────────────

    public function test_publish_transitions_draft_to_active_with_expires_at(): void
    {
        $v = Vacancy::factory()->draft()->create();

        $v->publish(30);

        $fresh = $v->fresh();
        $this->assertSame(VacancyStatus::Active, $fresh->status);
        $this->assertTrue($fresh->published_at->equalTo(now()));
        $this->assertTrue($fresh->expires_at->equalTo(now()->addDays(30)));
    }

    public function test_publish_does_not_overwrite_existing_published_at(): void
    {
        $original = now()->subDays(60);
        $v = Vacancy::factory()->expired()->create(['published_at' => $original]);

        $v->publish(30);

        $this->assertSame(
            $original->toIso8601String(),
            $v->fresh()->published_at->toIso8601String()
        );
    }

    public function test_publish_archived_vacancy_throws_domain_exception(): void
    {
        $v = Vacancy::factory()->archived()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/архівовано/');

        $v->publish(30);
    }

    // ── extend() ─────────────────────────────────────────────────────────────

    public function test_extend_adds_days_to_expires_at_for_active_vacancy(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 5)->create();
        $oldExpires = $v->expires_at->copy();

        $v->extend(15);

        $this->assertSame(
            $oldExpires->addDays(15)->toIso8601String(),
            $v->fresh()->expires_at->toIso8601String()
        );
    }

    public function test_extend_expired_vacancy_makes_it_active_from_now(): void
    {
        $v = Vacancy::factory()->expired(daysAgo: 10)->create();

        $v->extend(30);

        $fresh = $v->fresh();
        $this->assertSame(VacancyStatus::Active, $fresh->status);
        $this->assertTrue($fresh->expires_at->equalTo(now()->addDays(30)));
    }

    public function test_extend_resets_expiry_notification_sent_at(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 1)->create([
            'expiry_notification_sent_at' => now()->subHours(2),
        ]);

        $v->extend(30);

        $this->assertNull($v->fresh()->expiry_notification_sent_at);
    }

    public function test_extend_does_not_change_published_at(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 5)->create();
        $original = $v->published_at->copy();

        $v->extend(15);

        $this->assertSame(
            $original->toIso8601String(),
            $v->fresh()->published_at->toIso8601String()
        );
    }

    public function test_extend_draft_vacancy_throws_domain_exception(): void
    {
        $v = Vacancy::factory()->draft()->create();

        $this->expectException(\DomainException::class);

        $v->extend(30);
    }

    public function test_extend_archived_vacancy_throws_domain_exception(): void
    {
        $v = Vacancy::factory()->archived()->create();

        $this->expectException(\DomainException::class);

        $v->extend(30);
    }

    // ── days_left / countdown_label ──────────────────────────────────────────

    public function test_days_left_returns_correct_value_for_active_vacancy(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 5)->create();

        $this->assertSame(5, $v->days_left);
    }

    public function test_days_left_returns_null_for_draft_and_archived(): void
    {
        $this->assertNull(Vacancy::factory()->draft()->create()->days_left);
        $this->assertNull(Vacancy::factory()->archived()->create()->days_left);
    }

    public function test_countdown_label_for_1_day(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 1)->create();

        $this->assertSame('Залишилось 1 день', $v->countdown_label);
    }

    public function test_countdown_label_for_2_days(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 2)->create();

        $this->assertSame('Залишилось 2 дні', $v->countdown_label);
    }

    public function test_countdown_label_for_11_days(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 11)->create();

        $this->assertSame('Залишилось 11 днів', $v->countdown_label);
    }

    public function test_countdown_label_for_expired_vacancy(): void
    {
        $v = Vacancy::factory()->expired()->create();

        $this->assertSame('Публікацію завершено', $v->countdown_label);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function test_scope_active_returns_only_active_with_future_expires_at(): void
    {
        Vacancy::factory()->active()->count(3)->create();
        Vacancy::factory()->draft()->count(2)->create();
        Vacancy::factory()->expired()->count(2)->create();
        Vacancy::factory()->archived()->count(1)->create();

        $this->assertSame(3, Vacancy::active()->count());
    }

    public function test_scope_expiring_soon_finds_vacancies_in_time_window(): void
    {
        Vacancy::factory()->expiringSoon(hours: 12)->create();   // у вікні
        Vacancy::factory()->expiringSoon(hours: 23)->create();   // у вікні
        Vacancy::factory()->active(daysLeft: 5)->create();        // поза вікном
        Vacancy::factory()->expired()->create();                  // не active

        $this->assertSame(2, Vacancy::expiringSoon(24)->count());
    }

    public function test_scope_pending_expiry_notification_excludes_already_notified(): void
    {
        Vacancy::factory()->expiringSoon()->create([
            'expiry_notification_sent_at' => now()->subHours(2),  // вже сповіщений
        ]);
        Vacancy::factory()->expiringSoon()->create([
            'expiry_notification_sent_at' => null,                // ще треба
        ]);

        $this->assertSame(1, Vacancy::pendingExpiryNotification(24)->count());
    }
}
