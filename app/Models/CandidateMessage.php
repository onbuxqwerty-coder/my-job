<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateMessage extends Model
{
    protected $fillable = [
        'application_id',
        'sender_id',
        'template_id',
        'type',
        'subject',
        'body',
        'status',
        'copy_to_sender',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'type'           => MessageType::class,
            'copy_to_sender' => 'boolean',
            'sent_at'        => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }
}
