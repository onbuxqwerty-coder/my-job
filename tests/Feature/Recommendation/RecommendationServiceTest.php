<?php

declare(strict_types=1);

namespace Tests\Feature\Recommendation;

use App\Enums\UserRole;
use App\Jobs\RecalculateRecommendationsJob;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Resume;
use App\Models\SkillTag;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyRecommendation;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecommendationService::class);
    }

    private function makeCandidate(array $resumeOverrides = []): User
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);

        Resume::factory()->published()->create(array_merge(
            ['user_id' => $candidate->id],
            $resumeOverrides,
        ));

        return $candidate;
    }

    private function makeActiveVacancy(array $overrides = []): Vacancy
    {
        $company = Company::factory()->create();

        return Vacancy::factory()->active()->create(array_merge([
            'company_id' => $company->id,
            'city_id'    => null,
        ], $overrides));
    }

    private function makeSkill(string $name): SkillTag
    {
        return SkillTag::create(['name' => $name, 'slug' => str($name)->slug()->value()]);
    }

    private function makeCity(string $slug = 'kyiv'): City
    {
        static $counter = 0;
        $counter++;
        return City::create([
            'name'   => "City {$counter}",
            'slug'   => "{$slug}-{$counter}",
            'region' => 'Test',
        ]);
    }

    #[Test]
    public function score_is_100_when_all_skills_match(): void
    {
        $category  = Category::factory()->create();
        $candidate = $this->makeCandidate([
            'location'        => ['city_id' => null],
            'additional_info' => [
                'salary_expected_from'  => 30_000,
                'salary_expected_to'    => 60_000,
                'preferred_category_id' => $category->id,
            ],
        ]);

        $vacancy = $this->makeActiveVacancy([
            'city_id'     => null,
            'salary_from' => 20_000,
            'salary_to'   => 70_000,
            'category_id' => $category->id,
        ]);

        $skill1 = $this->makeSkill('Laravel');
        $skill2 = $this->makeSkill('Vue.js');

        $vacancy->skills()->attach([$skill1->id => ['is_required' => true], $skill2->id => ['is_required' => true]]);
        $candidate->candidateSkills()->attach([$skill1->id => ['level' => 3], $skill2->id => ['level' => 4]]);

        $score = $this->service->calculateScore($candidate, $vacancy);

        $this->assertEquals(100, $score);
    }

    #[Test]
    public function score_is_0_when_no_skills_match(): void
    {
        $cat1      = Category::factory()->create();
        $cat2      = Category::factory()->create();
        $cityA     = $this->makeCity('city-a');
        $cityB     = $this->makeCity('city-b');

        $candidate = $this->makeCandidate([
            'location'        => ['city_id' => $cityA->id],
            'additional_info' => [
                'salary_expected_from'  => 5_000,
                'salary_expected_to'    => 10_000,
                'preferred_category_id' => $cat2->id,
            ],
        ]);

        $vacancy = $this->makeActiveVacancy([
            'city_id'     => $cityB->id,
            'salary_from' => 100_000,
            'salary_to'   => 200_000,
            'category_id' => $cat1->id,
        ]);

        $skill = $this->makeSkill('Docker');
        $vacancy->skills()->attach([$skill->id => ['is_required' => true]]);
        // Candidate has NO skills

        $score = $this->service->calculateScore($candidate, $vacancy);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function score_considers_city_match(): void
    {
        $cityA = $this->makeCity('city-match-a');
        $cityB = $this->makeCity('city-match-b');

        $candidateMatchingCity = $this->makeCandidate(['location' => ['city_id' => $cityA->id]]);
        $candidateWrongCity    = $this->makeCandidate(['location' => ['city_id' => $cityB->id]]);

        // Vacancy remote (city_id=null): everyone gets +10
        $remoteVacancy = $this->makeActiveVacancy(['city_id' => null]);
        $this->assertEquals(10, $this->service->calculateScore($candidateMatchingCity, $remoteVacancy));
        $this->assertEquals(10, $this->service->calculateScore($candidateWrongCity, $remoteVacancy));

        // Vacancy with cityA: only candidate with cityA gets +10
        $cityVacancy = $this->makeActiveVacancy(['city_id' => $cityA->id]);
        $this->assertEquals(10, $this->service->calculateScore($candidateMatchingCity, $cityVacancy));
        $this->assertEquals(0, $this->service->calculateScore($candidateWrongCity, $cityVacancy));
    }

    #[Test]
    public function score_considers_salary_range(): void
    {
        // Use matching city to isolate salary criterion (+10 city for both)
        $city = $this->makeCity('salary-city');

        $candidate = $this->makeCandidate([
            'location'        => ['city_id' => $city->id],
            'additional_info' => [
                'salary_expected_from' => 30_000,
                'salary_expected_to'   => 60_000,
            ],
        ]);

        // Overlapping salary: 50k-100k vs candidate 30k-60k → overlap at 50k-60k → +10 salary
        $vacancyOverlap = $this->makeActiveVacancy([
            'city_id'     => $city->id,
            'salary_from' => 50_000,
            'salary_to'   => 100_000,
        ]);

        // Non-overlapping salary: 100k-200k vs candidate 30k-60k → no overlap → 0 salary
        $vacancyNoOverlap = $this->makeActiveVacancy([
            'city_id'     => $city->id,
            'salary_from' => 100_000,
            'salary_to'   => 200_000,
        ]);

        $scoreOverlap   = $this->service->calculateScore($candidate, $vacancyOverlap);
        $scoreNoOverlap = $this->service->calculateScore($candidate, $vacancyNoOverlap);

        // City match (+10) + salary (+10/0) = 20/10
        $this->assertEquals(20, $scoreOverlap);
        $this->assertEquals(10, $scoreNoOverlap);
    }

    #[Test]
    public function recommendations_are_saved_to_database(): void
    {
        $category  = Category::factory()->create();
        $candidate = $this->makeCandidate([
            'location'        => ['city_id' => null],
            'additional_info' => ['preferred_category_id' => $category->id],
        ]);

        $vacancy = $this->makeActiveVacancy(['city_id' => null, 'category_id' => $category->id]);

        $skill = $this->makeSkill('Redis');
        $vacancy->skills()->attach([$skill->id => ['is_required' => true]]);
        $candidate->candidateSkills()->attach([$skill->id => ['level' => 2]]);

        $this->service->recalculateForUser($candidate);

        $this->assertDatabaseHas('vacancy_recommendations', [
            'user_id'    => $candidate->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    #[Test]
    public function low_score_vacancies_are_excluded(): void
    {
        $cityA = $this->makeCity('low-score-a');
        $cityB = $this->makeCity('low-score-b');

        $candidate = $this->makeCandidate([
            'location'        => ['city_id' => $cityA->id],
            'additional_info' => ['salary_expected_from' => 5_000, 'salary_expected_to' => 10_000],
        ]);

        // Vacancy: city mismatch + salary mismatch + required skill not present → score < 50
        $vacancy = $this->makeActiveVacancy([
            'city_id'     => $cityB->id,
            'salary_from' => 100_000,
            'salary_to'   => 200_000,
        ]);
        $skill = $this->makeSkill('Kubernetes');
        $vacancy->skills()->attach([$skill->id => ['is_required' => true]]);

        $this->service->recalculateForUser($candidate);

        $this->assertDatabaseMissing('vacancy_recommendations', [
            'user_id'    => $candidate->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    #[Test]
    public function recalculation_is_triggered_on_profile_update(): void
    {
        Queue::fake();

        $candidate = User::factory()->create(['role' => UserRole::Candidate]);

        Queue::assertPushed(RecalculateRecommendationsJob::class, fn ($job) =>
            $job->target instanceof User && $job->target->id === $candidate->id
        );
    }
}
