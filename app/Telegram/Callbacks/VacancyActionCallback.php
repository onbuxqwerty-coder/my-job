<?php

declare(strict_types=1);

namespace App\Telegram\Callbacks;

use App\Enums\VacancyStatus;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

final class VacancyActionCallback
{
    public function __invoke(Nutgram $bot): void
    {
        $data = $bot->callbackQuery()?->data ?? '';

        // Парсимо: vac:ext:123:30 | vac:arc:123 | vac:mut:123
        if (! preg_match('/^vac:([a-z]+):(\d+)(?::(\d+))?$/', $data, $m)) {
            $bot->answerCallbackQuery(text: '❌ Невідома дія.');
            return;
        }

        $action    = $m[1];
        $vacancyId = (int) $m[2];
        $days      = isset($m[3]) ? (int) $m[3] : null;

        // Авторизація через telegram_id
        $telegramId = $bot->userId();
        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
            $bot->answerCallbackQuery(text: '❌ Спочатку увійдіть на сайт через /start.', show_alert: true);
            return;
        }

        $vacancy = Vacancy::find($vacancyId);

        if (! $vacancy) {
            $bot->answerCallbackQuery(text: '❌ Вакансію не знайдено.', show_alert: true);
            return;
        }

        // Перевірка прав власника
        if ($vacancy->company?->user_id !== $user->id) {
            $bot->answerCallbackQuery(text: '❌ Немає доступу до цієї вакансії.', show_alert: true);
            Log::channel('vacancies')->warning('VacancyActionCallback: unauthorized', [
                'telegram_id' => $telegramId,
                'vacancy_id'  => $vacancyId,
                'user_id'     => $user->id,
            ]);
            return;
        }

        match ($action) {
            'ext' => $this->handleExtend($bot, $vacancy, $days),
            'arc' => $this->handleArchive($bot, $vacancy),
            'mut' => $this->handleMute($bot, $vacancy),
            default => $bot->answerCallbackQuery(text: '❌ Невідома дія.'),
        };
    }

    private function handleExtend(Nutgram $bot, Vacancy $vacancy, ?int $days): void
    {
        if (! in_array($days, [15, 30, 90], true)) {
            $bot->answerCallbackQuery(text: '❌ Неприпустима кількість днів.', show_alert: true);
            return;
        }

        try {
            // TODO: замінити на CheckoutService з модуля 11A (платіжна абстракція)
            $url = app(PaymentService::class)->createVacancyExtensionCheckout($vacancy, $days);

            $bot->editMessageText(
                text: "💳 <b>Оплата продовження на {$days} днів</b>\n\n"
                    . "Перейдіть для оплати:\n{$url}\n\n"
                    . "Після успішної оплати вакансія автоматично продовжиться.",
                parse_mode: 'HTML',
                reply_markup: null,
            );
        } catch (\Throwable $e) {
            Log::channel('vacancies')->error('VacancyActionCallback: checkout failed', [
                'vacancy_id' => $vacancy->id,
                'error'      => $e->getMessage(),
            ]);
            $bot->answerCallbackQuery(text: '❌ Помилка створення оплати. Спробуйте через сайт.', show_alert: true);
        }
    }

    private function handleArchive(Nutgram $bot, Vacancy $vacancy): void
    {
        $vacancy->archive();

        $bot->answerCallbackQuery(text: '✅ Вакансію архівовано.');
        $bot->editMessageText(
            text: "📦 Вакансію «{$vacancy->title}» переміщено в архів.",
            reply_markup: null,
        );
    }

    private function handleMute(Nutgram $bot, Vacancy $vacancy): void
    {
        // Позначаємо як надіслане зараз → наступний scheduler-цикл пропустить
        $vacancy->markExpiryNotificationSent();

        $bot->answerCallbackQuery(text: '🔕 Нагадування вимкнено для цієї вакансії.');
        $bot->editMessageText(
            text: "🔕 Більше не нагадуватиму про «{$vacancy->title}».",
            reply_markup: null,
        );
    }
}
