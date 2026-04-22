<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PendingVacancyService;
use App\Services\TelegramAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TelegramAuthController extends Controller
{
    public function __construct(
        private readonly TelegramAuthService $authService,
    ) {}

    /**
     * POST /api/telegram/auth/init
     * Генерує сесію та повертає deep link.
     */
    public function init(Request $request): JsonResponse
    {
        $role = in_array($request->input('role'), ['candidate', 'employer'], true)
            ? $request->input('role')
            : 'candidate';

        $data = $this->authService->generateSession($role);

        return response()->json($data);
    }

    /**
     * GET /api/telegram/auth/status/{token}
     * Polling — перевірка статусу сесії.
     */
    public function status(string $token): JsonResponse
    {
        return response()->json($this->authService->getStatus($token));
    }

    /**
     * GET /telegram/auth/login/{token}
     * Одноразовий вхід після підтвердження через бота.
     */
    public function login(string $token): RedirectResponse
    {
        $user = $this->authService->loginWithToken($token);

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'telegram' => 'Посилання для входу недійсне або прострочене.',
            ]);
        }

        Auth::login($user, remember: true);
        Session::regenerate();

        $vacancy = app(PendingVacancyService::class)->createFromSession($user);

        if ($vacancy) {
            return redirect()->route('employer.dashboard')
                ->with('vacancy_published_id', $vacancy->id);
        }

        // Якщо прийшли з resume wizard — повертаємо назад
        if (request()->query('resume_redirect')) {
            $resumeId = session('pending_resume_id');
            if ($resumeId) {
                return redirect()->route('resumes.create');
            }
        }

        $redirect = match($user->role->value) {
            'employer'  => route('employer.dashboard'),
            'candidate' => route('seeker.dashboard'),
            default     => route('home'),
        };

        return redirect()->intended($redirect);
    }
}
