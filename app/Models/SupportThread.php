<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactRole;
use App\Enums\SupportThreadStatus;
use Database\Factories\SupportThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportThread extends Model
{
    /** @use HasFactory<SupportThreadFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'subject', 'role', 'status', 'last_message_at',
    ];

    protected $casts = [
        'role'            => ContactRole::class,
        'status'          => SupportThreadStatus::class,
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'thread_id');
    }
}
