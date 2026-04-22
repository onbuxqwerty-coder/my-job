<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Resume;
use App\Policies\ResumePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, AppleExtendSocialite::class);

        Gate::policy(Resume::class, ResumePolicy::class);
    }
}
