<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['slug', 'name', 'region', 'is_region_center'];

    protected $casts = [
        'is_region_center' => 'boolean',
    ];
}
