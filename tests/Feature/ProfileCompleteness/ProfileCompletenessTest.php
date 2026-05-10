<?php

declare(strict_types=1);

namespace Tests\Feature\ProfileCompleteness;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Experience;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\SkillTag;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\ProfileCompletenessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private ProfileCompletenessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProfileCompletenessService::class);
    }

    #[Test]
    public function empty_candidate_profile_has_low_score(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        Resume::factory()->create(['user_id' => $user->id]);

        $result = $this->service->candidateScore($user);

        $this->assertLessThan(30, $result['score']);
        $this->assertNotNull($result['next_step']);
        $this->assertNotEmpty($result['missing']);
    }

    #[Test]
    public function fully_filled_candidate_profile_has_score_100(): void
    {
        $user   = User::factory()->create(['role' => UserRole::Candidate]);
        $resume = Resume::factory()->create([
            'user_id'      => $user->id,
            'personal_info' => [
                'first_name' => 'Іван',
                'last_name'  => 'Петренко',
                'phone'      => '+38 (099) 123-45-67',
                'position'   => 'PHP Developer',
            ],
            'location' => ['city' => 'Київ', 'city_id' => 1],
        ]);

        Experience::create([
            'resume_id'    => $resume->id,
            'position'     => 'Developer',
            'company_name' => 'Acme',
            'start_date'   => '2020-01-01',
            'is_current'   => true,
        ]);

        for ($i = 0; $i < 3; $i++) {
            Skill::create(['resume_id' => $resume->id, 'skill_name' => "Skill {$i}"]);
        }

        $result = $this->service->candidateScore($user);

        $this->assertSame(100, $result['score']);
        $this->assertNull($result['next_step']);
        $this->assertEmpty($result['missing']);
    }

    #[Test]
    public function next_step_is_highest_weight_missing_field(): void
    {
        $user   = User::factory()->create(['role' => UserRole::Candidate]);
        $resume = Resume::factory()->create([
            'user_id'      => $user->id,
            'personal_info' => [
                'first_name' => 'Іван',
                'last_name'  => 'Петренко',
                'phone'      => '+38 (099) 123-45-67',
                'position'   => 'PHP Developer',
            ],
            'location' => ['city' => 'Київ', 'city_id' => 1],
        ]);

        // skills missing (20), experience missing (30) — experience has highest weight
        $result = $this->service->candidateScore($user);

        $this->assertSame('experience', $result['next_step']['field'] ?? $result['missing'][0]['field']);

        // After sorting missing by weight desc, experience (30) should be next_step
        $this->assertSame(30, collect($result['missing'])->firstWhere('field', 'experience')['weight'] ?? null);

        $topMissing = collect($result['missing'])->sortByDesc('weight')->first();
        $this->assertSame('experience', $topMissing['field']);
        $this->assertSame($topMissing['label'], $result['next_step']['label']);
    }

    #[Test]
    public function employer_score_counts_logo_and_description(): void
    {
        $employer = User::factory()->employer()->create(['phone' => '+380991234567']);

        // Without logo → logo field missing
        Company::factory()->create([
            'user_id'     => $employer->id,
            'logo'        => null,
            'description' => str_repeat('a', 10),
            'location'    => 'Київ',
        ]);

        $resultWithout = $this->service->employerScore($employer);
        $missingFields = array_column($resultWithout['missing'], 'field');

        $this->assertContains('logo', $missingFields);

        // With logo → logo counted
        $employer->company->update(['logo' => 'logos/test.png']);
        $employer->refresh();

        $resultWith      = $this->service->employerScore($employer);
        $missingFieldsNow = array_column($resultWith['missing'], 'field');

        $this->assertNotContains('logo', $missingFieldsNow);
        $this->assertGreaterThan($resultWithout['score'], $resultWith['score']);
    }

    #[Test]
    public function vacancy_score_requires_min_description_length(): void
    {
        $employer = User::factory()->employer()->create();
        $company  = Company::factory()->create(['user_id' => $employer->id]);

        $shortVacancy = Vacancy::factory()->create([
            'company_id'  => $company->id,
            'description' => 'Коротко',
        ]);

        $longVacancy = Vacancy::factory()->create([
            'company_id'  => $company->id,
            'description' => str_repeat('Довгий опис вакансії. ', 15),
        ]);

        $shortResult = $this->service->vacancyScore($shortVacancy);
        $longResult  = $this->service->vacancyScore($longVacancy);

        $shortMissing = array_column($shortResult['missing'], 'field');
        $longMissing  = array_column($longResult['missing'], 'field');

        $this->assertContains('description', $shortMissing);
        $this->assertNotContains('description', $longMissing);
        $this->assertGreaterThan($shortResult['score'], $longResult['score']);
    }

    #[Test]
    public function vacancy_score_includes_skill_tags(): void
    {
        $employer = User::factory()->employer()->create();
        $company  = Company::factory()->create(['user_id' => $employer->id]);

        $vacancy = Vacancy::factory()->create([
            'company_id' => $company->id,
        ]);

        $resultBefore = $this->service->vacancyScore($vacancy);
        $missingBefore = array_column($resultBefore['missing'], 'field');
        $this->assertContains('skills', $missingBefore);

        $skillTag = SkillTag::firstOrCreate(['name' => 'PHP', 'slug' => 'php']);
        $vacancy->skills()->attach($skillTag->id, ['is_required' => true]);

        $resultAfter = $this->service->vacancyScore($vacancy);
        $missingAfter = array_column($resultAfter['missing'], 'field');
        $this->assertNotContains('skills', $missingAfter);
        $this->assertGreaterThan($resultBefore['score'], $resultAfter['score']);
    }
}
