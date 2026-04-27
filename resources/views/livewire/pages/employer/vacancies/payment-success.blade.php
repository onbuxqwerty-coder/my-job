<?php

declare(strict_types=1);

use App\Models\Vacancy;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Vacancy $vacancy;

    public function mount(Vacancy $vacancy): void
    {
        abort_unless($vacancy->company_id === auth()->user()->company?->id, 403);
        $this->vacancy = $vacancy;
    }
}
?>

<div>
    <x-employer-tabs />

    <div class="max-w-lg mx-auto px-4 py-16 text-center">

        <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Оплату прийнято!</h1>

        <p class="text-gray-600 mb-6">
            Ми обробляємо підтвердження від платіжного провайдера.
            Вакансія оновиться протягом хвилини — ця сторінка оновиться автоматично.
        </p>

        <livewire:employer.payment-status-poller :vacancy="$vacancy" />

        <div class="mt-8 text-sm text-gray-400">
            Якщо через 5 хвилин нічого не змінилось —
            <a href="mailto:support@myjob.co.ua" class="underline">напишіть нам</a>.
        </div>
    </div>
</div>
