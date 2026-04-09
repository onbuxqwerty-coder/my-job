<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSubscription extends Model
{
    protected $fillable = [
        'telegram_id',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
