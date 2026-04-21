<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google'];

    /**
     * Redirect to OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        $role = in_array(request('role'), ['candidate', 'employer'], true)
            ? request('role')
            : 'candidate';

        session(['oauth_role' => $role]);

        return Socialite::driver($provider)->with(['prompt' => 'select_account'])->redirect();
    }

    /**
     * Handle OAuth provider callback.
     */
    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'Не вдалося авторизуватися через ' . ucfirst($provider) . '. Спробуйте ще раз.',
            ]);
        }

        $oauthRole = session()->pull('oauth_role', 'candidate');
        $requestedRole = $oauthRole === 'employer' ? UserRole::Employer : UserRole::Candidate;

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            $user = User::query()
                ->where('email', $socialUser->getEmail())
                ->first();

            if ($user) {
                // Existing account found by email — check role mismatch
                if ($user->role !== $requestedRole) {
                    $existingRole = $user->role === UserRole::Employer ? 'роботодавець' : 'кандидат';
                    $requestedRoleLabel = $requestedRole === UserRole::Employer ? 'роботодавця' : 'кандидата';

                    return redirect()->route('login')->withErrors([
                        'email' => "Цей Google-акаунт вже зареєстрований як {$existingRole}. Увійдіть як {$existingRole} або зареєструйтесь з іншим акаунтом Google.",
                    ]);
                }

                $user->update([
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            } else {
                $user = User::create([
                    'name'        => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Користувач',
                    'email'       => $socialUser->getEmail() ?? $socialUser->getId() . '@' . $provider . '.oauth',
                    'password'    => bcrypt(Str::random(32)),
                    'role'        => $requestedRole,
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }
        } else {
            // Account found by provider_id — check role mismatch
            if ($user->role !== $requestedRole) {
                $existingRole = $user->role === UserRole::Employer ? 'роботодавець' : 'кандидат';

                return redirect()->route('login')->withErrors([
                    'email' => "Цей Google-акаунт вже зареєстрований як {$existingRole}. Увійдіть як {$existingRole} або зареєструйтесь з іншим акаунтом Google.",
                ]);
            }
        }

        Auth::login($user, remember: true);

        if ($vacancyRedirect = $this->handlePendingVacancy($user)) {
            return $vacancyRedirect;
        }

        $redirect = $user->role === UserRole::Employer
            ? route('employer.dashboard')
            : route('home');

        return redirect()->intended($redirect);
    }

    private function handlePendingVacancy(User $user): ?RedirectResponse
    {
        if (! session()->has('pending_vacancy') || $user->role !== UserRole::Employer) {
            return null;
        }

        if ($user->company === null) {
            return redirect()->route('employer.profile')
                ->with('info', 'Спочатку налаштуйте профіль компанії — після цього вакансія буде створена автоматично.');
        }

        $data    = session()->pull('pending_vacancy');
        $company = $user->company;

        $vacancy = Vacancy::create([
            'company_id'      => $company->id,
            'category_id'     => $data['category_id'],
            'city_id'         => $data['city_id'],
            'title'           => $data['title'],
            'slug'            => Str::slug($data['title']) . '-' . Str::random(6),
            'salary_from'     => $data['salary_from'] ?? null,
            'salary_to'       => null,
            'currency'        => 'UAH',
            'employment_type' => ['full-time'],
            'is_active'       => false,
            'is_featured'     => false,
            'is_top'          => false,
            'languages'       => [],
            'suitability'     => [],
        ]);

        return redirect()->route('employer.vacancies.edit', ['vacancyId' => $vacancy->id]);
    }
}
