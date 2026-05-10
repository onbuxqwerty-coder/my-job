<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterviewRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InterviewRequest extends Model
{
    protected $fillable = [
        'application_id',
        'employer_user_id',
        'questions',
        'deadline_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'      => InterviewRequestStatus::class,
            'questions'   => 'array',
            'deadline_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function response(): HasOne
    {
        return $this->hasOne(InterviewResponse::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InterviewRequestStatus::Pending);
    }

    public function scopeAnswered(Builder $query): Builder
    {
        return $query->where('status', InterviewRequestStatus::Answered);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', InterviewRequestStatus::Expired);
    }

    public function isExpired(): bool
    {
        return $this->deadline_at !== null && $this->deadline_at->isPast();
    }
}
