<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelegramSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TelegramAuthService
{
    private const SESSION_TTL_MINUTES = 5;

    /**
     * Генерує сесію та повертає токен + deep link.
     *
     * @return array{token: string, deep_link: string}
     */
    public function generateSession(string $role = 'candidate'): array
    {
        $token = Str::random(48);
        $botUsername = env('TELEGRAM_BOT_USERNAME', 'myjob_in_bot');

        TelegramSession::create([
            'session_token' => $token,
            'role'          => $role,
            'status'        => 'pending',
            'expires_at'    => now()->addMinutes(self::SESSION_TTL_MINUTES),
        ]);

        return [
            'token'     => $token,
            'deep_link' => "https://t.me/{$botUsername}?start=auth_{$token}",
        ];
    }

    /**
     * Обробляє контакт від бота та авторизує сесію.
     */
    public function processContact(string $token, int $telegramId, string $phone): bool
    {
        $session = TelegramSession::where('session_token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            Log::warning('TelegramAuth: session not found or expired', ['token' => substr($token, 0, 8)]);
            return false;
        }

        $phone = $this->normalizePhone($phone);
        $role  = $session->role ?? 'candidate';

        $user = User::where('telegram_id', $telegramId)->first()
            ?? User::where('phone', $phone)->first()
            ?? User::create([
                'name'        => 'Користувач',
                'email'       => $telegramId . '@telegram.local',
                'password'    => bcrypt(Str::random(32)),
                'phone'       => $phone,
                'telegram_id' => $telegramId,
                'role'        => $role,
            ]);

        // Оновлюємо telegram_id / phone якщо ще не встановлені
        $updates = [];
        if (! $user->telegram_id) $updates['telegram_id'] = $telegramId;
        if (! $user->phone)       $updates['phone']       = $phone;
        if ($updates)             $user->update($updates);

        $loginToken = Str::random(64);

        $session->update([
            'user_id'     => $user->id,
            'telegram_id' => $telegramId,
            'phone'       => $phone,
            'status'      => 'authorized',
            'login_token' => $loginToken,
        ]);

        return true;
    }

    /**
     * Повертає поточний статус сесії для polling.
     *
     * @return array{status: string, login_url?: string}
     */
    public function getStatus(string $token): array
    {
        $session = TelegramSession::where('session_token', $token)->first();

        if (! $session) {
            return ['status' => 'not_found'];
        }

        if ($session->isExpired()) {
            return ['status' => 'expired'];
        }

        if ($session->isAuthorized()) {
            return [
                'status'    => 'authorized',
                'login_url' => route('telegram.auth.login', ['token' => $session->login_token]),
            ];
        }

        return ['status' => 'pending'];
    }

    /**
     * Авторизує користувача по одноразовому login_token.
     */
    public function loginWithToken(string $loginToken): ?User
    {
        $session = TelegramSession::where('login_token', $loginToken)
            ->where('status', 'authorized')
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();

        if (! $session || ! $session->user) {
            return null;
        }

        // Одноразовий токен — відразу анулюємо
        $session->update(['login_token' => null]);

        return $session->user;
    }

    /**
     * Нормалізує телефонний номер до формату +380XXXXXXXXX.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '380')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+38' . $digits;
        }

        return '+' . $digits;
    }
}
