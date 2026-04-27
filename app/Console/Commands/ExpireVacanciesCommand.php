<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireVacanciesCommand extends Command
{
    protected $signature = 'vacancies:expire
                            {--dry-run : Не змінює БД, лише виводить статистику}
                            {--batch=500 : Розмір батча для оновлення}
                            {--max-runtime=55 : Максимальний час виконання у секундах}';

    protected $description = 'Переводить активні вакансії в статус expired, якщо expires_at вже минув';

    public function handle(): int
    {
        $startedAt  = microtime(true);
        $isDryRun   = (bool) $this->option('dry-run');
        $batchSize  = (int) $this->option('batch');
        $maxRuntime = (int) $this->option('max-runtime');

        $logContext = [
            'command'    => 'vacancies:expire',
            'dry_run'    => $isDryRun,
            'batch'      => $batchSize,
            'started_at' => now()->toIso8601String(),
        ];

        $this->info($isDryRun ? 'РЕЖИМ DRY-RUN: змін у БД не буде.' : 'Запуск expire-команди.');
        Log::channel('vacancies')->info('Expire command started', $logContext);

        try {
            $totalExpired = 0;
            $totalScanned = 0;

            Vacancy::query()
                ->where('status', VacancyStatus::Active)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->orderBy('id')
                ->chunkById($batchSize, function ($batch) use (
                    &$totalExpired,
                    &$totalScanned,
                    $isDryRun,
                    $startedAt,
                    $maxRuntime
                ) {
                    $totalScanned += $batch->count();

                    if (! $isDryRun) {
                        DB::transaction(function () use ($batch, &$totalExpired) {
                            $ids = $batch->pluck('id')->all();

                            $affected = Vacancy::query()
                                ->whereIn('id', $ids)
                                ->where('status', VacancyStatus::Active)
                                ->where('expires_at', '<', now())
                                ->update(['status' => VacancyStatus::Expired->value]);

                            $totalExpired += $affected;
                        });
                    } else {
                        $totalExpired += $batch->count();
                    }

                    $elapsed = microtime(true) - $startedAt;
                    if ($elapsed > ($maxRuntime - 5)) {
                        $this->warn("Досягнуто max-runtime ({$maxRuntime}s), зупиняюсь.");
                        Log::channel('vacancies')->warning('Expire command stopped by max-runtime', [
                            'elapsed_seconds' => round($elapsed, 2),
                            'expired_so_far'  => $totalExpired,
                        ]);
                        return false;
                    }
                });

            $elapsedMs = (int) ((microtime(true) - $startedAt) * 1000);

            $message = $isDryRun
                ? "Знайдено {$totalScanned} вакансій для завершення (dry-run, БД не змінено) за {$elapsedMs} мс."
                : "Завершено {$totalExpired} вакансій з {$totalScanned} просканованих за {$elapsedMs} мс.";

            $this->info($message);
            Log::channel('vacancies')->info('Expire command finished', array_merge($logContext, [
                'expired_count' => $totalExpired,
                'scanned_count' => $totalScanned,
                'elapsed_ms'    => $elapsedMs,
            ]));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Помилка: {$e->getMessage()}");
            Log::channel('vacancies')->error('Expire command failed', array_merge($logContext, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            report($e);

            return self::FAILURE;
        }
    }
}
