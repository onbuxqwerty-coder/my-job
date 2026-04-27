<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\VacancyStatsWidget;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VacancyStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_renders_without_error(): void
    {
        Livewire::test(VacancyStatsWidget::class)
            ->assertOk();
    }

    public function test_active_count_reflects_database(): void
    {
        Vacancy::factory()->active()->count(3)->create();
        Vacancy::factory()->expired()->count(2)->create();

        Livewire::test(VacancyStatsWidget::class)
            ->assertSee('3');
    }

    public function test_warning_shown_when_critical_vacancies_exist(): void
    {
        Vacancy::factory()->expiringSoon(12)->create();

        $component = Livewire::test(VacancyStatsWidget::class);

        $this->assertTrue(Vacancy::expiringSoon(24)->exists());
    }

    public function test_no_warning_when_no_critical_vacancies(): void
    {
        Vacancy::factory()->active(daysLeft: 30)->count(2)->create();

        $this->assertFalse(Vacancy::expiringSoon(24)->exists());
    }
}
