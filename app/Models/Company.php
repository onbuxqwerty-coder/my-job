<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BusinessType;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'description',
        'website',
        'location',
        'city_id',
        'is_verified',
        'business_type',
        'edrpou',
        'ipn',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'   => 'boolean',
            'business_type' => BusinessType::class,
        ];
    }

    public function getTaxIdAttribute(): ?string
    {
        return match($this->business_type) {
            BusinessType::Legal      => $this->edrpou,
            BusinessType::Individual => $this->ipn,
            default                  => null,
        };
    }

    public function getTaxIdLabelAttribute(): string
    {
        return $this->business_type?->taxIdLabel() ?? 'Код';
    }

    /**
     * Returns a normalized public URL for the logo,
     * handling both stored path ("logos/file.jpg") and legacy full URL ("/storage/logos/file.jpg").
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http') || str_starts_with($this->logo, '/storage')) {
            return $this->logo;
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function isProfileComplete(): bool
    {
        return filled($this->description) && $this->name !== 'Компанія';
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }
}
