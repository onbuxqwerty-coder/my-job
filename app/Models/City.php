<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['slug', 'name', 'region', 'is_region_center', 'latitude', 'longitude', 'population'];

    protected $casts = [
        'is_region_center' => 'boolean',
        'latitude'         => 'float',
        'longitude'        => 'float',
    ];

    /** Scope: великі/популярні міста (обласні центри), сортування за населенням */
    public function scopePopular(Builder $query): Builder
    {
        return $query->where('is_region_center', true)
                     ->orderByDesc('population')
                     ->orderBy('name');
    }

    /** Scope: пошук за назвою або регіоном */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('region', 'like', "%{$term}%");
        })->orderByRaw('is_region_center DESC')
          ->orderBy('name');
    }
}
