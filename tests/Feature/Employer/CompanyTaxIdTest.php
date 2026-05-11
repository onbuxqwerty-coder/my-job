<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Enums\BusinessType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyTaxIdTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employer = User::factory()->create(['role' => UserRole::Employer]);
        $this->company  = Company::factory()->create(['user_id' => $this->employer->id]);
    }

    #[Test]
    public function legal_company_can_save_edrpou(): void
    {
        $this->company->update([
            'business_type' => BusinessType::Legal,
            'edrpou'        => '12345678',
            'ipn'           => null,
        ]);

        $this->assertDatabaseHas('companies', [
            'id'            => $this->company->id,
            'business_type' => 'legal',
            'edrpou'        => '12345678',
            'ipn'           => null,
        ]);
    }

    #[Test]
    public function individual_company_can_save_ipn(): void
    {
        $this->company->update([
            'business_type' => BusinessType::Individual,
            'ipn'           => '1234567890',
            'edrpou'        => null,
        ]);

        $this->assertDatabaseHas('companies', [
            'id'            => $this->company->id,
            'business_type' => 'individual',
            'ipn'           => '1234567890',
            'edrpou'        => null,
        ]);
    }

    #[Test]
    public function tax_id_accessor_returns_correct_value_for_legal(): void
    {
        $this->company->update([
            'business_type' => BusinessType::Legal,
            'edrpou'        => '87654321',
        ]);

        $this->assertEquals('87654321', $this->company->fresh()->tax_id);
    }

    #[Test]
    public function tax_id_accessor_returns_correct_value_for_individual(): void
    {
        $this->company->update([
            'business_type' => BusinessType::Individual,
            'ipn'           => '9876543210',
        ]);

        $this->assertEquals('9876543210', $this->company->fresh()->tax_id);
    }

    #[Test]
    public function tax_id_label_is_correct_for_legal(): void
    {
        $this->company->update(['business_type' => BusinessType::Legal]);

        $this->assertEquals('ЄДРПОУ', $this->company->fresh()->tax_id_label);
    }

    #[Test]
    public function tax_id_label_is_correct_for_individual(): void
    {
        $this->company->update(['business_type' => BusinessType::Individual]);

        $this->assertEquals('ІПН', $this->company->fresh()->tax_id_label);
    }

    #[Test]
    public function switching_to_individual_clears_edrpou(): void
    {
        $this->company->update([
            'business_type' => BusinessType::Legal,
            'edrpou'        => '12345678',
        ]);

        $this->company->update([
            'business_type' => BusinessType::Individual,
            'ipn'           => '1234567890',
            'edrpou'        => null,
        ]);

        $this->assertNull($this->company->fresh()->edrpou);
        $this->assertEquals('1234567890', $this->company->fresh()->ipn);
    }
}
