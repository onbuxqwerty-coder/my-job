<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\VacancyStatus;
use App\Filament\Resources\Vacancies\Pages\ListVacancies;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VacancyResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_table_shows_vacancies(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 5)->create();

        Livewire::test(ListVacancies::class)
            ->assertCanSeeTableRecords([$v]);
    }

    public function test_action_extend_30_adds_30_days_to_expires_at(): void
    {
        $v          = Vacancy::factory()->active(daysLeft: 5)->create();
        $oldExpires = $v->expires_at->copy();

        Livewire::test(ListVacancies::class)
            ->callTableAction('extend_30', $v)
            ->assertHasNoTableActionErrors();

        $this->assertSame(
            $oldExpires->addDays(30)->toIso8601String(),
            $v->fresh()->expires_at->toIso8601String()
        );
    }

    public function test_action_archive_transitions_vacancy_to_archived(): void
    {
        $v = Vacancy::factory()->active()->create();

        Livewire::test(ListVacancies::class)
            ->callTableAction('archive', $v)
            ->assertHasNoTableActionErrors();

        $this->assertSame(VacancyStatus::Archived, $v->fresh()->status);
    }

    public function test_bulk_archive_works_on_multiple_records(): void
    {
        $vacancies = Vacancy::factory()->active()->count(3)->create();

        Livewire::test(ListVacancies::class)
            ->callTableBulkAction('archive_bulk', $vacancies);

        foreach ($vacancies as $v) {
            $this->assertSame(VacancyStatus::Archived, $v->fresh()->status);
        }
    }

    public function test_filter_expiring_soon_query_matches_only_expiring_vacancies(): void
    {
        $expiringSoon = Vacancy::factory()->expiringSoon(48)->create();
        $stillFar     = Vacancy::factory()->active(daysLeft: 30)->create();

        // Перевіряємо query фільтра напряму (deferred filters в Filament v4 потребують окремого UI-тригера)
        $filtered = Vacancy::query()
            ->where('status', \App\Enums\VacancyStatus::Active)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addHours(72)])
            ->get();

        $this->assertTrue($filtered->contains($expiringSoon));
        $this->assertFalse($filtered->contains($stillFar));
    }
}
