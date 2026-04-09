<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PhoneOtpService
{
    private const OTP_TTL     = 300; // 5 хвилин
    private const OTP_LENGTH  = 6;
    private const CACHE_PREFIX = 'phone_otp:';

    public function __construct(
        private readonly TelegramService $telegramService,
    ) {}

    /**
     * Генерує та надсилає OTP на вказаний номер.
     */
    public function sendOtp(string $phone): void
    {
        $otp  = $this->generateOtp();
        $key  = self::CACHE_PREFIX . $this->normalizePhone($phone);

        Cache::put($key, $otp, self::OTP_TTL);

        $text = "🔐 Ваш код підтвердження: <b>{$otp}</b>\n\nКод дійсний 5 хвилин.";

        $user = User::where('phone', $this->normalizePhone($phone))->first();

        // Відправка через Telegram якщо акаунт прив'язаний
        if ($user?->telegram_id) {
            $this->telegramService->sendMessage((int) $user->telegram_id, $text);
            return;
        }

        // Відправка через SMS
        $this->sendSms($this->normalizePhone($phone), "Ваш код підтвердження: {$otp}. Дійсний 5 хвилин.");
    }

    /**
     * Перевіряє OTP-код.
     */
    public function verifyOtp(string $phone, string $code): bool
    {
        $key      = self::CACHE_PREFIX . $this->normalizePhone($phone);
        $cached   = Cache::get($key);

        if (!$cached || $cached !== $code) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    /**
     * Знаходить або створює користувача за номером телефону.
     */
    public function findOrCreateUser(string $phone, string $role = 'candidate'): User
    {
        $normalized = $this->normalizePhone($phone);

        return User::firstOrCreate(
            ['phone' => $normalized],
            [
                'name'     => $normalized,
                'email'    => $normalized . '@phone.local',
                'password' => bcrypt(str()->random(32)),
                'role'     => $role,
            ]
        );
    }

    /**
     * Нормалізує номер телефону до формату +380XXXXXXXXX.
     */
    public function normalizePhone(string $phone): string
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

    /**
     * Відправляє SMS через налаштованого провайдера.
     */
    private function sendSms(string $phone, string $message): void
    {
        $provider = config('services.sms.provider', 'turbosms');

        try {
            match ($provider) {
                'turbosms' => $this->sendViaTurboSms($phone, $message),
                default    => Log::info("SMS [{$phone}]: {$message}"),
            };
        } catch (\Throwable $e) {
            Log::error('SMS send failed', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Відправка через TurboSMS API.
     */
    private function sendViaTurboSms(string $phone, string $message): void
    {
        $token  = config('services.sms.turbosms_token');
        $sender = config('services.sms.sender', 'MyJob');

        if (!$token) {
            Log::info("TurboSMS token not set. SMS [{$phone}]: {$message}");
            return;
        }

        Http::withToken($token)
            ->post('https://api.turbosms.ua/message/send.json', [
                'recipients' => [$phone],
                'sms'        => ['sender' => $sender, 'text' => $message],
            ]);
    }

    /**
     * Генерує 6-значний OTP.
     */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
}
