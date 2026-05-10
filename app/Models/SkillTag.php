<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SkillTag extends Model
{
    protected $table = 'skill_tags';

    protected $fillable = [
        'name',
        'slug',
        'category',
    ];

    public function candidateUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidate_skills', 'skill_id', 'user_id')
            ->withPivot('level');
    }

    public function vacancies(): BelongsToMany
    {
        return $this->belongsToMany(Vacancy::class, 'vacancy_skills', 'skill_id', 'vacancy_id')
            ->withPivot('is_required');
    }
}
