<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'created_by',
        'scheduled_at',
        'duration',
        'type',
        'meeting_link',
        'office_address',
        'notes',
        'internal_notes',
        'status',
        'confirm_token',
        'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'type'         => InterviewType::class,
            'status'       => InterviewStatus::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmUrl(): string
    {
        return route('interview.confirm', $this->confirm_token);
    }

    public function cancelUrl(): string
    {
        return route('interview.cancel', $this->confirm_token);
    }
}
