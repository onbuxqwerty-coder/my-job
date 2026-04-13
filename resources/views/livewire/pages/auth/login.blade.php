<?php

declare(strict_types=1);

use App\Livewire\Forms\LoginForm;
use App\Services\PhoneOtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    // Крок: 'phone' | 'otp' | 'email'
    public string $step = 'phone';

    // Роль: 'candidate' | 'employer'
    public string $role = 'candidate';

    // Phone flow
    public string $phone = '';
    public string $otp   = '';

    // Email flow
    public LoginForm $form;

    public function setRole(string $role): void
    {
        $this->role = $role;
        $this->step = 'phone';
        $this->resetErrorBag();
    }

    public function sendOtp(PhoneOtpService $otpService): void
    {
        $this->validate(['phone' => ['required', 'string', 'min:10']]);

        $otpService->sendOtp($this->phone);

        $this->step = 'otp';
    }

    public function verifyOtp(PhoneOtpService $otpService): void
    {
        $this->validate(['otp' => ['required', 'string', 'size:6']]);

        if (!$otpService->verifyOtp($this->phone, $this->otp)) {
            $this->addError('otp', 'Невірний або прострочений код. Спробуйте ще раз.');
            return;
        }

        $user = $otpService->findOrCreateUser($this->phone, $this->role);

        Auth::login($user, remember: true);
        Session::regenerate();

        $default = match($user->role->value) {
            'employer'  => route('employer.dashboard', absolute: false),
            'candidate' => route('seeker.dashboard', absolute: false),
            default     => route('home', absolute: false),
        };

        $this->redirectIntended(default: $default, navigate: true);
    }

    public function backToPhone(): void
    {
        $this->step = 'phone';
        $this->otp  = '';
        $this->resetErrorBag();
    }

    public function showEmailLogin(): void
    {
        $this->step = 'email';
        $this->resetErrorBag();
    }

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();

        $user = Auth::user();
        $default = match($user->role->value) {
            'employer'  => route('employer.dashboard', absolute: false),
            'candidate' => route('seeker.dashboard', absolute: false),
            default     => route('home', absolute: false),
        };

        $this->redirectIntended(default: $default, navigate: true);
    }
}; ?>

<div class="login-wrapper" style="text-align: center;">

    {{-- Title --}}
    <h1 style="font-size: 28px; font-weight: 800; color: #1a1a1a; margin-bottom: 12px; line-height: 1.2;">
        @if($role === 'employer')
            Вхід або реєстрація роботодавця
        @else
            Вхід або реєстрація шукача
        @endif
    </h1>

    {{-- Subtitle --}}
    <p style="font-size: 15px; color: #555; margin-bottom: 20px; line-height: 1.6;">
        @if($role === 'employer')
            Увійдіть, щоб розміщувати вакансії<br>та знаходити кращих кандидатів.
        @else
            Увійдіть, щоб керувати розміщенням вашого резюме,<br>відгукуватися на вакансії та отримувати пропозиції від роботодавців.
        @endif
    </p>

    {{-- Role switcher --}}
    @if($step === 'phone' || $step === 'email')
        <div class="role-switcher" style="display: inline-flex; gap: 12px; width: 340px; background: #e8eaed; border-radius: 10px; padding: 4px; margin-bottom: 20px;">
    
    <!-- Кнопка Шукач -->
    <button wire:click="setRole('candidate')"
            style="flex: 1; padding: 10px; font-size: 16px; font-weight: 600; border-radius: 7px; cursor: pointer; transition: all 0.3s ease;
                   border: 1px solid {{ $role === 'candidate' ? '#2d323b' : 'transparent' }};
                   background: {{ $role === 'candidate' ? '#2d323b' : 'transparent' }};
                   color: {{ $role === 'candidate' ? '#ffffff' : '#5f6368' }};
                   box-shadow: {{ $role === 'candidate' ? '0 4px 8px rgba(45, 50, 59, 0.2)' : 'none' }};">
        Шукач
    </button>

    <!-- Кнопка Роботодавець -->
    <button wire:click="setRole('employer')"
            style="flex: 1; padding: 10px; font-size: 16px; font-weight: 600; border-radius: 7px; cursor: pointer; transition: all 0.3s ease;
                   border: 1px solid {{ $role === 'employer' ? '#2d323b' : 'transparent' }};
                   background: {{ $role === 'employer' ? '#2d323b' : 'transparent' }};
                   color: {{ $role === 'employer' ? '#ffffff' : '#5f6368' }};
                   box-shadow: {{ $role === 'employer' ? '0 4px 8px rgba(45, 50, 59, 0.2)' : 'none' }};">
        Роботодавець
    </button>

</div>

    @endif

    {{-- ======= SOCIAL AUTH ======= --}}
    @if($step === 'phone')
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">

            {{-- Google --}}
            <a href="{{ route('social.redirect', 'google') }}"
               class="google-btn"
               style="display:flex; align-items:center; justify-content:center; gap:12px;
                      height:48px; border:1px solid #dadce0; border-radius:8px;
                      font-size:15px; font-weight:600; color:#3c4043;
                      background:#fff; text-decoration:none; transition:background-color 0.2s;"
               onmouseover="this.style.backgroundColor='#f8f9fa'"
               onmouseout="this.style.backgroundColor='#fff'">
                <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.2l6.8-6.8C35.8 2.2 30.2 0 24 0 14.7 0 6.7 5.4 2.7 13.3l7.9 6.1C12.5 13 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.4c-.5 2.8-2.1 5.1-4.4 6.7l7 5.4C43.2 36.8 46.1 31.1 46.1 24.5z"/><path fill="#FBBC05" d="M10.6 28.6A14.8 14.8 0 0 1 9.5 24c0-1.6.3-3.2.8-4.6l-7.9-6.1A23.9 23.9 0 0 0 0 24c0 3.9.9 7.5 2.6 10.7l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7-5.4c-2 1.4-4.6 2.2-8.2 2.2-6.2 0-11.5-4.2-13.4-9.8l-7.9 6.1C6.7 42.6 14.7 48 24 48z"/></svg>
                Продовжити з Google
            </a>

        </div>

        <div class="login-divider" style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <hr style="flex:1; border:none; border-top:1px solid #e0e0e0;">
            <span style="font-size:13px; color:#888;">або</span>
            <hr style="flex:1; border:none; border-top:1px solid #e0e0e0;">
        </div>
    @endif

    {{-- ======= КРОК 1: ТЕЛЕФОН ======= --}}
    @if($step === 'phone')
        <div class="login-card" style="background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:24px;">
            <form wire:submit="sendOtp">
                <input
                    class="login-input"
                    wire:model="phone"
                    type="tel"
                    placeholder="+380"
                    required autofocus
                    style="width:100%; height:48px; padding:0 16px; font-size:15px;
                           border:1px solid #d0d5dd; border-radius:8px; outline:none;
                           color:#1a1a1a; background:#fff; margin-bottom:12px;
                           box-sizing:border-box; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#1a73e8'"
                    onblur="this.style.borderColor='#d0d5dd'"
                />
                <x-input-error :messages="$errors->get('phone')" class="mb-3" style="text-align:left;" />

                <button type="submit"
                        style="width:100%; height:48px; font-size:16px; font-weight:600;
                               background-color:#1a73e8; color:#fff; border:none;
                               border-radius:8px; cursor:pointer; transition:background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#1557b0'"
                        onmouseout="this.style.backgroundColor='#1a73e8'">
                    <span wire:loading.remove wire:target="sendOtp">Отримати код</span>
                    <span wire:loading wire:target="sendOtp">Надсилання...</span>
                </button>

                <p style="font-size:12px; color:#888; margin-top:14px; line-height:1.5;">
                    Продовжуючи, ви приймаєте
                    <a href="#" style="color:#1a73e8; text-decoration:underline;">правила сервісу</a>
                    та
                    <a href="#" style="color:#1a73e8; text-decoration:underline;">політику конфіденційності</a>.
                </p>
            </form>
        </div>

        <div style="margin-top:16px;">
            <button wire:click="showEmailLogin"
                    class="login-email-btn"
                    style="display:flex; align-items:center; justify-content:center; gap:10px;
                           padding:14px; font-size:15px; font-weight:600; color:#1a1a1a;
                           background:transparent; border:none; cursor:pointer; border-radius:8px;
                           transition:background-color 0.2s; width:100%;"
                    onmouseover="this.style.backgroundColor='#e8eaed'"
                    onmouseout="this.style.backgroundColor='transparent'">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#555; flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Увійти за допомогою ел. пошти
            </button>
        </div>
    @endif

    {{-- ======= КРОК 2: OTP КОД ======= --}}
    @if($step === 'otp')
        <div class="login-card" style="background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:24px;">
            <div class="otp-info-box" style="margin-bottom:16px; padding:12px; background:#f0f7ff; border-radius:8px;">
                <p style="font-size:14px; color:#555; margin:0;">
                    Код надіслано на номер <strong style="color:#1a1a1a;">{{ $phone }}</strong><br>
                    через SMS, Viber або Telegram
                </p>
            </div>

            <form wire:submit="verifyOtp">
                <input
                    class="login-input"
                    wire:model="otp"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="• • • • • •"
                    required autofocus
                    style="width:100%; height:56px; padding:0 16px; font-size:24px; letter-spacing:10px;
                           text-align:center; border:1px solid #d0d5dd; border-radius:8px; outline:none;
                           color:#1a1a1a; background:#fff; margin-bottom:12px;
                           box-sizing:border-box; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#1a73e8'"
                    onblur="this.style.borderColor='#d0d5dd'"
                />
                <x-input-error :messages="$errors->get('otp')" class="mb-3" style="text-align:left;" />

                <button type="submit"
                        style="width:100%; height:48px; font-size:16px; font-weight:600;
                               background-color:#1a73e8; color:#fff; border:none;
                               border-radius:8px; cursor:pointer; transition:background-color 0.2s; margin-bottom:12px;"
                        onmouseover="this.style.backgroundColor='#1557b0'"
                        onmouseout="this.style.backgroundColor='#1a73e8'">
                    <span wire:loading.remove wire:target="verifyOtp">Підтвердити</span>
                    <span wire:loading wire:target="verifyOtp">Перевірка...</span>
                </button>

                <button type="button" wire:click="sendOtp"
                        style="width:100%; height:40px; font-size:14px; font-weight:500; color:#1a73e8;
                               background:transparent; border:none; cursor:pointer;"
                        onmouseover="this.style.opacity='0.7'"
                        onmouseout="this.style.opacity='1'">
                    <span wire:loading.remove wire:target="sendOtp">Надіслати код повторно</span>
                    <span wire:loading wire:target="sendOtp">Надсилання...</span>
                </button>
            </form>
        </div>

        <button wire:click="backToPhone"
                class="login-back-btn"
                style="margin-top:16px; display:flex; align-items:center; justify-content:center;
                       gap:6px; font-size:14px; color:#555; background:transparent;
                       border:none; cursor:pointer; width:100%; padding:10px;"
                onmouseover="this.style.color='#1a1a1a'"
                onmouseout="this.style.color='#555'">
            ← Змінити номер
        </button>
    @endif

    {{-- ======= EMAIL ЛОГІН ======= --}}
    @if($step === 'email')
        <div class="login-card" style="background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:24px;">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form wire:submit="login">
                <input
                    class="login-input"
                    wire:model="form.email"
                    type="email"
                    placeholder="Електронна пошта"
                    required autofocus autocomplete="username"
                    style="width:100%; height:48px; padding:0 16px; font-size:15px;
                           border:1px solid #d0d5dd; border-radius:8px; outline:none;
                           color:#1a1a1a; background:#fff; margin-bottom:12px;
                           box-sizing:border-box; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#1a73e8'"
                    onblur="this.style.borderColor='#d0d5dd'"
                />
                <x-input-error :messages="$errors->get('form.email')" class="mb-2" style="text-align:left;" />

                <input
                    class="login-input"
                    wire:model="form.password"
                    type="password"
                    placeholder="Пароль"
                    required autocomplete="current-password"
                    style="width:100%; height:48px; padding:0 16px; font-size:15px;
                           border:1px solid #d0d5dd; border-radius:8px; outline:none;
                           color:#1a1a1a; background:#fff; margin-bottom:16px;
                           box-sizing:border-box; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#1a73e8'"
                    onblur="this.style.borderColor='#d0d5dd'"
                />
                <x-input-error :messages="$errors->get('form.password')" class="mb-2" style="text-align:left;" />

                <button type="submit"
                        style="width:100%; height:48px; font-size:16px; font-weight:600;
                               background-color:#1a73e8; color:#fff; border:none;
                               border-radius:8px; cursor:pointer; transition:background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#1557b0'"
                        onmouseout="this.style.backgroundColor='#1a73e8'">
                    Увійти
                </button>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                       style="display:block; margin-top:12px; font-size:13px; color:#1a73e8; text-decoration:underline;">
                        Забули пароль?
                    </a>
                @endif
            </form>
        </div>

        <button wire:click="backToPhone"
                class="login-back-btn"
                style="margin-top:16px; display:flex; align-items:center; justify-content:center;
                       gap:6px; font-size:14px; color:#555; background:transparent;
                       border:none; cursor:pointer; width:100%; padding:10px;"
                onmouseover="this.style.color='#1a1a1a'"
                onmouseout="this.style.color='#555'">
            ← Увійти за номером телефону
        </button>
    @endif

</div>
