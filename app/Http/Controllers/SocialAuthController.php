<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
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

        return Socialite::driver($provider)->redirect();
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

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            $user = User::query()
                ->where('email', $socialUser->getEmail())
                ->first();

            if ($user) {
                $user->update([
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            } else {
                $oauthRole = session()->pull('oauth_role', 'candidate');
                $role = $oauthRole === 'employer' ? UserRole::Employer : UserRole::Candidate;

                $user = User::create([
                    'name'        => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Користувач',
                    'email'       => $socialUser->getEmail() ?? $socialUser->getId() . '@' . $provider . '.oauth',
                    'password'    => bcrypt(Str::random(32)),
                    'role'        => $role,
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }
        }

        Auth::login($user, remember: true);

        $redirect = $user->role === UserRole::Employer
            ? route('employer.dashboard')
            : route('home');

        return redirect()->intended($redirect);
    }
}
