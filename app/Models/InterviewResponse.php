<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewResponse extends Model
{
    protected $fillable = [
        'interview_request_id',
        'user_id',
        'answers',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers'      => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function interviewRequest(): BelongsTo
    {
        return $this->belongsTo(InterviewRequest::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
