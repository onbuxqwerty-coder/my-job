<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    protected $fillable = ['name', 'slug', 'position'];

    public function subsectors(): HasMany
    {
        return $this->hasMany(IndustrySubsector::class);
    }
}
