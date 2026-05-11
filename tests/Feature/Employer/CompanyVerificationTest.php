<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Enums\CompanyVerificationStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => UserRole::Admin]);
        $employer      = User::factory()->create(['role' => UserRole::Employer]);
        $this->company = Company::factory()->create([
            'user_id'             => $employer->id,
            'name'                => 'ТОВ "Тестова Компанія"',
            'verification_status' => CompanyVerificationStatus::Unverified,
        ]);
    }

    #[Test]
    public function company_defaults_to_unverified_status(): void
    {
        $this->assertEquals(CompanyVerificationStatus::Unverified, $this->company->verification_status);
        $this->assertFalse($this->company->isVerified());
    }

    #[Test]
    public function admin_can_verify_company(): void
    {
        $this->company->update([
            'verification_status' => CompanyVerificationStatus::Verified,
            'verified_name'       => 'ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ "ТЕСТОВА КОМПАНІЯ"',
            'verified_at'         => now(),
            'verified_by'         => $this->admin->id,
        ]);

        $this->assertTrue($this->company->fresh()->isVerified());
        $this->assertDatabaseHas('companies', [
            'id'                  => $this->company->id,
            'verification_status' => 'verified',
            'verified_by'         => $this->admin->id,
        ]);
    }

    #[Test]
    public function admin_can_reject_company(): void
    {
        $this->company->update([
            'verification_status' => CompanyVerificationStatus::Rejected,
        ]);

        $this->assertEquals(CompanyVerificationStatus::Rejected, $this->company->fresh()->verification_status);
        $this->assertFalse($this->company->fresh()->isVerified());
    }

    #[Test]
    public function reset_clears_all_verification_fields(): void
    {
        $this->company->update([
            'verification_status' => CompanyVerificationStatus::Verified,
            'verified_name'       => 'Якась назва',
            'verified_at'         => now(),
            'verified_by'         => $this->admin->id,
        ]);

        $this->company->update([
            'verification_status' => CompanyVerificationStatus::Unverified,
            'verified_name'       => null,
            'verified_at'         => null,
            'verified_by'         => null,
        ]);

        $fresh = $this->company->fresh();
        $this->assertEquals(CompanyVerificationStatus::Unverified, $fresh->verification_status);
        $this->assertNull($fresh->verified_name);
        $this->assertNull($fresh->verified_at);
        $this->assertNull($fresh->verified_by);
    }
}
