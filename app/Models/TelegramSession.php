<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_token',
        'user_id',
        'telegram_id',
        'phone',
        'status',
        'login_token',
        'role',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'telegram_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'authorized';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
