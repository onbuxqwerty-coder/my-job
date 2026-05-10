<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resume;
use App\Models\SkillTag;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyRecommendation;
use Illuminate\Support\Collection;

final class RecommendationService
{
    private const MIN_SCORE = 50;

    public function calculateScore(User $candidate, Vacancy $vacancy): int
    {
        $resume = $candidate->resumes()->where('status', 'published')->latest()->first();

        $score = 0;
        $score += $this->scoreCityMatch($resume, $vacancy);
        $score += $this->scoreSalaryMatch($resume, $vacancy);
        $score += $this->scoreCategoryMatch($resume, $vacancy);
        $score += $this->scoreSkillsMatch($candidate, $vacancy);

        return min(100, $score);
    }

    public function recalculateForUser(User $candidate): void
    {
        $vacancies = Vacancy::active()->with(['skills'])->get();

        $records = $vacancies
            ->map(fn (Vacancy $v) => [
                'user_id'       => $candidate->id,
                'vacancy_id'    => $v->id,
                'score'         => $this->calculateScore($candidate, $v),
                'calculated_at' => now()->toDateTimeString(),
            ])
            ->filter(fn (array $r) => $r['score'] >= self::MIN_SCORE)
            ->values()
            ->all();

        VacancyRecommendation::where('user_id', $candidate->id)->delete();

        if (! empty($records)) {
            VacancyRecommendation::insert($records);
        }
    }

    public function recalculateForVacancy(Vacancy $vacancy): void
    {
        $candidates = User::whereHas('candidateSkills')->get();

        $records = $candidates
            ->map(fn (User $c) => [
                'user_id'       => $c->id,
                'vacancy_id'    => $vacancy->id,
                'score'         => $this->calculateScore($c, $vacancy),
                'calculated_at' => now()->toDateTimeString(),
            ])
            ->filter(fn (array $r) => $r['score'] >= self::MIN_SCORE)
            ->values()
            ->all();

        VacancyRecommendation::where('vacancy_id', $vacancy->id)->delete();

        if (! empty($records)) {
            VacancyRecommendation::insert($records);
        }
    }

    private function scoreCityMatch(?Resume $resume, Vacancy $vacancy): int
    {
        // Remote vacancy (no city) matches everyone
        if ($vacancy->city_id === null) {
            return 10;
        }

        if (! $resume) {
            return 0;
        }

        $location        = $resume->location ?? [];
        $candidateCityId = isset($location['city_id']) ? (int) $location['city_id'] : null;

        return $candidateCityId === $vacancy->city_id ? 10 : 0;
    }

    private function scoreSalaryMatch(?Resume $resume, Vacancy $vacancy): int
    {
        if (! $vacancy->salary_from && ! $vacancy->salary_to) {
            return 0;
        }

        if (! $resume) {
            return 0;
        }

        $info         = $resume->additional_info ?? [];
        $candidateMin = isset($info['salary_expected_from']) ? (int) $info['salary_expected_from'] : null;
        $candidateMax = isset($info['salary_expected_to'])   ? (int) $info['salary_expected_to']   : null;

        if (! $candidateMin && ! $candidateMax) {
            return 0;
        }

        $vacancyMin = $vacancy->salary_from ?? 0;
        $vacancyMax = $vacancy->salary_to   ?? PHP_INT_MAX;
        $cMin       = $candidateMin ?? 0;
        $cMax       = $candidateMax ?? PHP_INT_MAX;

        return ($cMin <= $vacancyMax && $vacancyMin <= $cMax) ? 10 : 0;
    }

    private function scoreCategoryMatch(?Resume $resume, Vacancy $vacancy): int
    {
        if (! $resume) {
            return 0;
        }

        $info                = $resume->additional_info ?? [];
        $preferredCategoryId = isset($info['preferred_category_id']) ? (int) $info['preferred_category_id'] : null;

        if (! $preferredCategoryId) {
            return 0;
        }

        return $preferredCategoryId === $vacancy->category_id ? 10 : 0;
    }

    private function scoreSkillsMatch(User $candidate, Vacancy $vacancy): int
    {
        $vacancySkills  = $vacancy->skills()->withPivot('is_required')->get();
        $requiredSkills = $vacancySkills->filter(fn ($s) => (bool) $s->pivot->is_required);
        $optionalSkills = $vacancySkills->filter(fn ($s) => ! $s->pivot->is_required);

        if ($requiredSkills->isEmpty()) {
            return 0;
        }

        $candidateSkillIds = $candidate->candidateSkills()->pluck('skill_tags.id')->all();

        $weight        = 70.0 / $requiredSkills->count();
        $requiredScore = 0.0;

        foreach ($requiredSkills as $skill) {
            if (in_array($skill->id, $candidateSkillIds, true)) {
                $requiredScore += $weight;
            }
        }

        $optionalScore = 0.0;
        if ($optionalSkills->isNotEmpty()) {
            $matched       = $optionalSkills->filter(fn ($s) => in_array($s->id, $candidateSkillIds, true))->count();
            $optionalScore = ($matched / $optionalSkills->count()) * 7.0;
        }

        return (int) round($requiredScore + $optionalScore);
    }
}
