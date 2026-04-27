<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Resume;
use App\Models\Vacancy;
use App\Policies\ResumePolicy;
use App\Policies\VacancyPolicy;
use App\Events\VacancyExtended;
use App\Listeners\NotifyEmployerOfExtension;
use App\Notifications\Channels\TelegramChannel;
use Carbon\Carbon;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Carbon::setLocale('uk');

        Notification::extend('telegram', fn ($app) => $app->make(TelegramChannel::class));

        Event::listen(VacancyExtended::class, NotifyEmployerOfExtension::class);
        Event::listen(SocialiteWasCalled::class, AppleExtendSocialite::class);

        Gate::policy(Resume::class, ResumePolicy::class);
        Gate::policy(Vacancy::class, VacancyPolicy::class);
    }
}
