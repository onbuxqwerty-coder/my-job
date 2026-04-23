<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustrySubsector extends Model
{
    protected $fillable = ['industry_id', 'name', 'slug'];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
