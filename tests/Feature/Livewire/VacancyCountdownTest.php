<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class VacancyCountdownTest extends TestCase
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

    public function test_shows_days_left_for_active_vacancy(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 3)->create();

        Livewire::test('employer.vacancy-countdown', ['vacancyId' => $v->id])
            ->assertSee('Залишилось 3 дні')
            ->assertSee('Активна');
    }

    public function test_shows_singular_day_correctly(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 1)->create();

        Livewire::test('employer.vacancy-countdown', ['vacancyId' => $v->id])
            ->assertSee('Залишилось 1 день')
            ->assertDontSee('1 дні')
            ->assertDontSee('1 днів');
    }

    public function test_expired_vacancy_shows_renewal_button(): void
    {
        $v = Vacancy::factory()->expired()->create();

        Livewire::test('employer.vacancy-countdown', ['vacancyId' => $v->id])
            ->assertSee('Публікацію завершено')
            ->assertSee('Поновити публікацію');
    }

    public function test_refresh_method_updates_state_after_db_change(): void
    {
        $v = Vacancy::factory()->active(daysLeft: 5)->create();

        $component = Livewire::test('employer.vacancy-countdown', ['vacancyId' => $v->id])
            ->assertSee('Залишилось 5 днів');

        $v->update(['expires_at' => now()->addDays(2)]);

        $component->call('refresh')
            ->assertSee('Залишилось 2 дні');
    }
}
