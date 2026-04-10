<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentType;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'city_id',
        'title',
        'slug',
        'description',
        'salary_from',
        'salary_to',
        'currency',
        'employment_type',
        'is_active',
        'published_at',
        'is_featured',
        'featured_until',
        'languages',
        'suitability',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'is_active'       => 'boolean',
            'is_featured'     => 'boolean',
            'published_at'    => 'datetime',
            'featured_until'  => 'datetime',
            'salary_from'     => 'integer',
            'salary_to'       => 'integer',
            'languages'       => 'array',
            'suitability'     => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
