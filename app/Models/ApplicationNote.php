<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_id',
        'author_id',
        'text',
        'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
