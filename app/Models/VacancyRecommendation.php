<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyRecommendation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'vacancy_id',
        'score',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at' => 'datetime',
            'score'         => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }
}
