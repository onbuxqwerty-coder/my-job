<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Mail\VerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\Resume;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Component;

class EmailStep extends Component
{
    public Resume $resume;
    public array  $formData = [];
    public array  $errors   = [];

    public string $email            = '';
    public string $verificationCode = '';
    public bool   $codeSent         = false;
    public bool   $isVerified       = false;
    public bool   $isVerifying      = false;
    public int    $countdown        = 0;

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume     = $resume;
        $this->formData   = $formData;
        $this->email      = $formData['personal_info']['email'] ?? '';
        $this->isVerified = !empty($formData['personal_info']['email_verified_at'] ?? '');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.email-step');
    }

    public function sendVerificationCode(): void
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Невірний формат email';
            return;
        }

        try {
            $verification = EmailVerification::updateOrCreate(
                ['email' => $this->email],
                [
                    'code'            => EmailVerification::generateCode(),
                    'code_expires_at' => now()->addMinutes(10),
                    'is_verified'     => false,
                    'verified_at'     => null,
                ]
            );

            Mail::to($this->email)->send(new VerificationCodeMail($verification->code));

            $this->codeSent  = true;
            $this->countdown = 32;
            $this->dispatch('start-countdown', duration: 32);
            unset($this->errors['email']);
        } catch (\Exception) {
            $this->errors['email'] = 'Помилка при надіслані коду. Спробуйте ще раз.';
        }
    }

    #[On('tick-countdown')]
    public function tickCountdown(int $remaining): void
    {
        $this->countdown = $remaining;
    }

    public function verifyEmail(): void
    {
        if (empty($this->verificationCode)) {
            $this->errors['code'] = 'Введіть код';
            return;
        }

        $this->isVerifying = true;

        try {
            $verification = EmailVerification::where('email', $this->email)->first();

            if (!$verification || !$verification->verifyCode($this->verificationCode)) {
                $this->errors['code'] = 'Невірний код або код скінчився';
                return;
            }

            $verifiedAt = now()->toIso8601String();

            $this->resume->updatePersonalInfo([
                'email'             => $this->email,
                'email_verified_at' => $verifiedAt,
            ]);

            $this->isVerified       = true;
            $this->codeSent         = false;
            $this->verificationCode = '';
            unset($this->errors['code']);

            $this->dispatch('step-updated',
                section: 'personal_info',
                data: ['email' => $this->email, 'email_verified_at' => $verifiedAt],
            );
        } catch (\Exception) {
            $this->errors['code'] = 'Помилка верифікації. Спробуйте ще раз.';
        } finally {
            $this->isVerifying = false;
        }
    }

    public function changeEmail(): void
    {
        $this->isVerified       = false;
        $this->codeSent         = false;
        $this->verificationCode = '';
        unset($this->errors['code']);
    }
}
