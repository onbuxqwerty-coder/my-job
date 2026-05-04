<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanType;
use App\Enums\VacancyStatus;
use App\Services\SubscriptionService;
use Carbon\CarbonInterface;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory, SoftDeletes;

    public const DEFAULT_PUBLICATION_DAYS = 30;

    protected $fillable = [
        'company_id',
        'category_id',
        'city_id',
        'title',
        'slug',
        'description',
        'salary_from',
        'salary_to',
        'currency',
        'employment_type',
        'is_active',
        'is_featured',
        'is_top',
        'is_hot',
        'featured_until',
        'promoted_until',
        'languages',
        'suitability',
        'published_at',
        'expires_at',
        'status',
        'expiry_notification_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_type'             => 'array',
            'is_active'                   => 'boolean',
            'is_featured'                 => 'boolean',
            'is_top'                      => 'boolean',
            'is_hot'                      => 'boolean',
            'featured_until'              => 'datetime',
            'promoted_until'              => 'datetime',
            'hot_until'                   => 'datetime',
            'top_until'                   => 'datetime',
            'salary_from'                 => 'integer',
            'salary_to'                   => 'integer',
            'languages'                   => 'array',
            'suitability'                 => 'array',
            'published_at'                => 'datetime',
            'expires_at'                  => 'datetime',
            'expiry_notification_sent_at' => 'datetime',
            'status'                      => VacancyStatus::class,
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', VacancyStatus::Active)
            ->whereNotNull('published_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', VacancyStatus::Expired);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', VacancyStatus::Draft);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', VacancyStatus::Archived);
    }

    /** @param  int  $hours */
    public function scopeExpiringSoon(Builder $query, int $hours = 24): Builder
    {
        return $query
            ->where('status', VacancyStatus::Active)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addHours($hours)]);
    }

    /** @param  int  $hours */
    public function scopePendingExpiryNotification(Builder $query, int $hours = 24): Builder
    {
        return $query->expiringSoon($hours)
            ->whereNull('expiry_notification_sent_at');
    }

    // -------------------------------------------------------------------------
    // Computed accessors
    // -------------------------------------------------------------------------

    protected function daysLeft(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->is_active || ! $this->expires_at) {
                return null;
            }

            return max((int) now()->diffInDays($this->expires_at, absolute: false), 0);
        });
    }

    protected function hoursLeft(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->is_active || ! $this->expires_at) {
                return null;
            }

            return max((int) now()->diffInHours($this->expires_at, absolute: false), 0);
        });
    }

    protected function countdownLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status === VacancyStatus::Expired) {
                return 'Публікацію завершено';
            }

            if ($this->status === VacancyStatus::Archived) {
                return 'В архіві';
            }

            if ($this->status === VacancyStatus::Draft) {
                return 'Чернетка';
            }

            if (! $this->expires_at) {
                return 'Безстрокова публікація';
            }

            $hoursLeft = $this->hours_left ?? 0;

            if ($hoursLeft < 1) {
                $minutes = max((int) now()->diffInMinutes($this->expires_at, absolute: false), 0);
                return "Залишилось {$minutes} " . self::pluralizeUk($minutes, 'хвилина', 'хвилини', 'хвилин');
            }

            if ($hoursLeft < 24) {
                return "Залишилось {$hoursLeft} " . self::pluralizeUk($hoursLeft, 'година', 'години', 'годин');
            }

            $days = $this->days_left;
            return "Залишилось {$days} " . self::pluralizeUk($days, 'день', 'дні', 'днів');
        });
    }

    private static function pluralizeUk(int $n, string $one, string $few, string $many): string
    {
        $mod10  = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $few;
        }

        return $many;
    }

    // -------------------------------------------------------------------------
    // State transitions
    // -------------------------------------------------------------------------

    /** @throws \DomainException */
    public function publish(int $days = self::DEFAULT_PUBLICATION_DAYS): void
    {
        if ($this->status === VacancyStatus::Archived) {
            throw new \DomainException(
                "Вакансію #{$this->id} архівовано. Спочатку відновіть її."
            );
        }

        $this->forceFill([
            'status'                      => VacancyStatus::Active,
            'published_at'                => $this->published_at ?? now(),
            'expires_at'                  => now()->addDays($days),
            'expiry_notification_sent_at' => null,
        ])->save();

        $this->maybeActivateFreePlan();
    }

    private function maybeActivateFreePlan(): void
    {
        $employer = $this->company?->user;

        if (! $employer || $employer->currentPlan() !== null) {
            return;
        }

        $freePlan = SubscriptionPlan::where('type', PlanType::Free)->first();

        if ($freePlan) {
            app(SubscriptionService::class)->activate($employer, $freePlan);
        }
    }

    /** @throws \DomainException */
    public function extend(int $days): void
    {
        if (! in_array($this->status, [VacancyStatus::Active, VacancyStatus::Expired], true)) {
            throw new \DomainException(
                "Вакансію #{$this->id} не можна продовжити зі статусу {$this->status->value}."
            );
        }

        $newExpiresAt = $this->status === VacancyStatus::Expired
            ? now()->addDays($days)
            : ($this->expires_at ?? now())->addDays($days);

        $this->forceFill([
            'status'                      => VacancyStatus::Active,
            'expires_at'                  => $newExpiresAt,
            'expiry_notification_sent_at' => null,
        ])->save();
    }

    public function archive(): void
    {
        $this->forceFill(['status' => VacancyStatus::Archived])->save();
    }

    public function expire(): void
    {
        $this->forceFill(['status' => VacancyStatus::Expired])->save();
    }

    public function markExpiryNotificationSent(): void
    {
        $this->forceFill(['expiry_notification_sent_at' => now()])->save();
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_vacancies')->withPivot('created_at');
    }
}
