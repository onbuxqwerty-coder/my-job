<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceTest extends TestCase
{
    use RefreshDatabase;

    private Resume $resume;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resume = Resume::factory()->for(User::factory()->create())->create();
    }

    #[Test]
    public function it_validates_experience_with_end_date(): void
    {
        $experience = $this->resume->experiences()->create([
            'position'     => 'Senior Developer',
            'company_name' => 'TechCorp',
            'start_date'   => now()->subYears(2),
            'end_date'     => now()->subYear(),
        ]);

        $this->assertTrue($experience->isValid());
    }

    #[Test]
    public function it_validates_current_job(): void
    {
        $experience = $this->resume->experiences()->create([
            'position'     => 'Developer',
            'company_name' => 'TechCorp',
            'start_date'   => now()->subYear(),
            'is_current'   => true,
        ]);

        $this->assertTrue($experience->isValid());
    }

    #[Test]
    public function it_fails_validation_with_invalid_dates(): void
    {
        $experience = $this->resume->experiences()->create([
            'position'     => 'Developer',
            'company_name' => 'TechCorp',
            'start_date'   => now(),
            'end_date'     => now()->subDays(10),
        ]);

        $this->assertFalse($experience->isValid());
    }

    #[Test]
    public function it_belongs_to_resume(): void
    {
        $experience = $this->resume->experiences()->create([
            'position'     => 'Developer',
            'company_name' => 'TechCorp',
            'start_date'   => now()->subYear(),
            'is_current'   => true,
        ]);

        $this->assertEquals($this->resume->id, $experience->resume->id);
    }

    #[Test]
    public function it_cascades_delete_with_resume(): void
    {
        $this->resume->experiences()->create([
            'position'     => 'Developer',
            'company_name' => 'TechCorp',
            'start_date'   => now()->subYear(),
            'is_current'   => true,
        ]);

        $resumeId = $this->resume->id;
        $this->resume->delete();

        $this->assertDatabaseMissing('experiences', ['resume_id' => $resumeId]);
    }
}
