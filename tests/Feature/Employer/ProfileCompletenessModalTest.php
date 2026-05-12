<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProfileCompletenessModalTest extends TestCase
{
    use RefreshDatabase;

    /** Створює роботодавця з мінімально заповненою компанією (score < 100%) */
    private function makeIncompleteEmployer(array $userAttrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'role'        => UserRole::Employer,
            'phone'       => null,
            'telegram_id' => null,
        ], $userAttrs));

        Company::factory()->create([
            'user_id'     => $user->id,
            'name'        => 'Test Company',
            'logo'        => null,
            'description' => '',
            'website'     => null,
            'location'    => '',
            'city_id'     => null,
        ]);

        return $user;
    }

    /** Створює роботодавця з повністю заповненим профілем (score = 100%) */
    private function makeCompleteEmployer(): User
    {
        $user = User::factory()->create([
            'role'        => UserRole::Employer,
            'phone'       => '0991234567',
            'telegram_id' => 123456789,
        ]);

        Company::factory()->create([
            'user_id'       => $user->id,
            'name'          => 'Acme Corp',
            'logo'          => 'logos/acme.png',
            'description'   => str_repeat('a', 300),
            'website'       => 'https://acme.com',
            'location'      => 'Kyiv',
            'city_id'       => null,
            'business_type' => 'legal',
            'edrpou'        => '12345678',
        ]);

        return $user;
    }

    #[Test]
    public function modal_shows_on_first_login_when_profile_incomplete(): void
    {
        $user = $this->makeIncompleteEmployer(['profile_completeness_modal_shown_at' => null]);

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->assertSet('show', true);
    }

    #[Test]
    public function modal_does_not_show_when_profile_complete(): void
    {
        $user = $this->makeCompleteEmployer();

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->assertSet('show', false);
    }

    #[Test]
    public function modal_does_not_show_twice_same_day(): void
    {
        $user = $this->makeIncompleteEmployer([
            'profile_completeness_modal_shown_at' => now(),
        ]);

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->assertSet('show', false);
    }

    #[Test]
    public function modal_shows_again_next_day(): void
    {
        $user = $this->makeIncompleteEmployer([
            'profile_completeness_modal_shown_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->assertSet('show', true);
    }

    #[Test]
    public function modal_not_shown_to_candidate(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->assertSet('show', false);
    }

    #[Test]
    public function dismiss_action_closes_modal(): void
    {
        $user = $this->makeIncompleteEmployer(['profile_completeness_modal_shown_at' => null]);

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->assertSet('show', true)
            ->call('dismiss')
            ->assertSet('show', false);
    }

    #[Test]
    public function go_fill_action_redirects_to_company_edit(): void
    {
        $user = $this->makeIncompleteEmployer(['profile_completeness_modal_shown_at' => null]);

        $this->actingAs($user);

        Volt::test('employer.profile-completeness-modal')
            ->call('goFill')
            ->assertRedirect(route('employer.profile'));
    }
}
