<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\VacancyStatus;
use Tests\TestCase;

class VacancyStatusTest extends TestCase
{
    public function test_all_statuses_have_ukrainian_labels(): void
    {
        $this->assertSame('Чернетка',  VacancyStatus::Draft->label());
        $this->assertSame('Активна',   VacancyStatus::Active->label());
        $this->assertSame('Завершена', VacancyStatus::Expired->label());
        $this->assertSame('Архів',     VacancyStatus::Archived->label());
    }

    public function test_options_returns_array_for_filament_select(): void
    {
        $options = VacancyStatus::options();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('active', $options);
        $this->assertSame('Активна', $options['active']);
        $this->assertCount(4, $options);
    }

    public function test_each_status_has_unique_badge_class(): void
    {
        $classes = array_map(fn ($s) => $s->badgeClass(), VacancyStatus::cases());

        $this->assertSame($classes, array_unique($classes));
    }

    public function test_each_status_has_color(): void
    {
        $this->assertSame('gray',    VacancyStatus::Draft->color());
        $this->assertSame('success', VacancyStatus::Active->color());
        $this->assertSame('warning', VacancyStatus::Expired->color());
        $this->assertSame('danger',  VacancyStatus::Archived->color());
    }

    public function test_public_cases_returns_only_active_and_expired(): void
    {
        $cases = VacancyStatus::publicCases();

        $this->assertContains(VacancyStatus::Active,  $cases);
        $this->assertContains(VacancyStatus::Expired, $cases);
        $this->assertNotContains(VacancyStatus::Draft,    $cases);
        $this->assertNotContains(VacancyStatus::Archived, $cases);
    }
}
