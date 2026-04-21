<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $fillable = [
        'resume_id',
        'position',
        'company_name',
        'company_industry',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
    ];

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function isValid(): bool
    {
        if ($this->is_current) {
            return true;
        }

        return $this->end_date !== null && $this->end_date->greaterThan($this->start_date);
    }
}
