<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VacancyStatsWidget extends BaseWidget
{
    protected static ?int $sort            = 1;
    protected ?string     $pollingInterval = '60s';

    protected function getStats(): array
    {
        $counts = Vacancy::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $active   = $counts[VacancyStatus::Active->value]   ?? 0;
        $expired  = $counts[VacancyStatus::Expired->value]  ?? 0;
        $draft    = $counts[VacancyStatus::Draft->value]    ?? 0;
        $archived = $counts[VacancyStatus::Archived->value] ?? 0;

        $criticalCount = Vacancy::expiringSoon(24)->count();

        return [
            Stat::make('Активні вакансії', $active)
                ->description($criticalCount > 0
                    ? "{$criticalCount} завершуються < 24 год ⚠"
                    : 'Публікуються зараз'
                )
                ->descriptionIcon($criticalCount > 0
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-check-circle'
                )
                ->color($criticalCount > 0 ? 'warning' : 'success')
                ->chart($this->getActiveVacanciesChart()),

            Stat::make('Завершені', $expired)
                ->description('Доступні за URL (noindex)')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Чернетки', $draft)
                ->description('Не опубліковані')
                ->descriptionIcon('heroicon-m-pencil')
                ->color('gray'),

            Stat::make('В архіві', $archived)
                ->description('404 за прямим URL')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('danger'),
        ];
    }

    private function getActiveVacanciesChart(): array
    {
        return Vacancy::query()
            ->where('status', VacancyStatus::Active)
            ->where('published_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(published_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->values()
            ->toArray();
    }
}
