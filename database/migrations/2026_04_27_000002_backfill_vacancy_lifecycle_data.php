<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Активні вакансії: статус 'active', термін +30 днів від created_at
        if ($driver === 'mysql') {
            DB::table('vacancies')
                ->where(function ($q) {
                    $q->where('is_active', true)
                      ->whereNull('status')
                      ->orWhere(function ($q2) {
                          $q2->where('is_active', true)->where('status', 'draft');
                      });
                })
                ->update([
                    'expires_at' => DB::raw('DATE_ADD(created_at, INTERVAL 30 DAY)'),
                    'status'     => 'active',
                ]);
        } else {
            // SQLite / інші: оновлюємо через PHP
            DB::table('vacancies')
                ->where(function ($q) {
                    $q->where('is_active', true)
                      ->whereNull('status')
                      ->orWhere(function ($q2) {
                          $q2->where('is_active', true)->where('status', 'draft');
                      });
                })
                ->get(['id', 'created_at'])
                ->each(function ($row) {
                    $expiresAt = \Carbon\Carbon::parse($row->created_at)->addDays(30);
                    DB::table('vacancies')
                        ->where('id', $row->id)
                        ->update([
                            'expires_at' => $expiresAt,
                            'status'     => 'active',
                        ]);
                });
        }

        // Ті, у кого expires_at вже минув — позначаємо як expired
        DB::table('vacancies')
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        // Неактивні без статусу — draft
        DB::table('vacancies')
            ->where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'draft');
            })
            ->update(['status' => 'draft']);
    }

    public function down(): void
    {
        // Backfill незворотній
    }
};
