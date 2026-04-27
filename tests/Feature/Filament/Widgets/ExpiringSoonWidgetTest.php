<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\ExpiringSoonWidget;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpiringSoonWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_can_view_returns_false_when_no_expiring_vacancies(): void
    {
        Vacancy::factory()->active(daysLeft: 30)->create();

        $this->assertFalse(ExpiringSoonWidget::canView());
    }

    public function test_can_view_returns_true_when_expiring_vacancies_exist(): void
    {
        Vacancy::factory()->expiringSoon(48)->create();

        $this->assertTrue(ExpiringSoonWidget::canView());
    }

    public function test_table_shows_expiring_vacancy(): void
    {
        $expiring = Vacancy::factory()->expiringSoon(48)->create();
        Vacancy::factory()->active(daysLeft: 30)->create();

        Livewire::test(ExpiringSoonWidget::class)
            ->assertCanSeeTableRecords([$expiring]);
    }

    public function test_table_excludes_far_future_vacancies(): void
    {
        Vacancy::factory()->expiringSoon(48)->create();
        $far = Vacancy::factory()->active(daysLeft: 30)->create();

        $query = Vacancy::expiringSoon(72);
        $this->assertFalse($query->get()->contains($far));
    }

    public function test_extend_30_action_adds_30_days(): void
    {
        $vacancy    = Vacancy::factory()->expiringSoon(48)->create();
        $oldExpires = $vacancy->expires_at->copy();

        Livewire::test(ExpiringSoonWidget::class)
            ->callTableAction('extend_30', $vacancy)
            ->assertHasNoTableActionErrors();

        $this->assertSame(
            $oldExpires->addDays(30)->toIso8601String(),
            $vacancy->fresh()->expires_at->toIso8601String()
        );
    }

    public function test_archive_action_archives_vacancy(): void
    {
        $vacancy = Vacancy::factory()->expiringSoon(48)->create();

        Livewire::test(ExpiringSoonWidget::class)
            ->callTableAction('archive', $vacancy)
            ->assertHasNoTableActionErrors();

        $this->assertSame(\App\Enums\VacancyStatus::Archived, $vacancy->fresh()->status);
    }
}
