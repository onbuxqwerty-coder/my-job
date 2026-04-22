<div class="px-6 py-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Вхід або реєстрація</h2>
        <p class="text-sm text-gray-600 mt-1">Увійдіть або створіть акаунт, щоб продовжити</p>
    </div>

    @if ($isAuthenticated)
        <div class="p-4 rounded-lg" style="background-color: #1F2937; border: 1px solid #4B5563;">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" style="color: #34D399;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-semibold" style="color: #D1FAE5;">Ви авторизовані</p>
                    <p class="text-sm" style="color: #A7F3D0;">{{ auth()->user()->name }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-3">
            {{-- Google --}}
            <a href="{{ route('social.redirect', ['provider' => 'google', 'role' => 'candidate']) }}"
               style="display:flex; align-items:center; justify-content:center; gap:12px;
                      height:52px; border:1px solid #a7a7a7; border-radius:8px; font-size:15px;
                      font-weight:600; color:#3c4043; background:#fff; text-decoration:none; transition:background 0.2s;"
               onmouseover="this.style.background='#f5f5f5'"
               onmouseout="this.style.background='#fff'"
            >
                <svg width="24" height="24" viewBox="0 0 48 48">
                    <path fill="#4285F4" d="M47.5 24.6c0-1.6-.1-3.1-.4-4.6H24v8.7h13.2c-.6 3-2.3 5.5-4.9 7.2l7.9 6.1C44.5 37.7 47.5 31.6 47.5 24.6z"/>
                    <path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7-5.4c-2 1.4-4.6 2.2-8.2 2.2-6.2 0-11.5-4.2-13.4-9.8l-7.9 6.1C6.7 42.6 14.7 48 24 48z"/>
                    <path fill="#FBBC05" d="M10.6 28.6A14.8 14.8 0 0 1 9.5 24c0-1.6.3-3.2.8-4.6l-7.9-6.1A23.9 23.9 0 0 0 0 24c0 3.9.9 7.5 2.6 10.7l7.9-6.1z"/>
                    <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.5l6.7-6.7C35.4 2.4 30.1 0 24 0 14.7 0 6.7 5.4 2.6 13.3l7.9 6.1C12.5 13.7 17.8 9.5 24 9.5z"/>
                </svg>
                Продовжити з Google
            </a>

            {{-- Telegram --}}
            <button
                id="tg-auth-btn"
                onclick="startTelegramAuth()"
                class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg font-semibold text-white transition"
                style="height:52px; background: #229ED9; border-radius:8px;"
                onmouseover="this.style.background='#1a8cbf'"
                onmouseout="this.style.background='#229ED9'"
            >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                </svg>
                Увійти через Telegram
            </button>

            <div class="text-center text-xs text-gray-400">або</div>

            <a href="{{ route('login') }}"
               class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                Увійти через email / телефон
            </a>
        </div>

        {{-- Telegram modal --}}
        <div id="tg-resume-modal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60" onclick="closeTgResumeModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center space-y-4">
                <p id="tg-resume-title" class="text-lg font-bold text-gray-900">Відкрийте Telegram</p>
                <p id="tg-resume-text" class="text-sm text-gray-600">Натисніть "Start" у боті та поділіться номером телефону</p>
                <div id="tg-resume-spinner" class="flex justify-center">
                    <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <a id="tg-resume-link" href="#" target="_blank"
                   class="block w-full px-4 py-3 rounded-lg text-white font-semibold"
                   style="background:#229ED9;">
                    Відкрити Telegram
                </a>
                <button onclick="closeTgResumeModal()" class="text-sm text-gray-400 hover:text-gray-600">Скасувати</button>
            </div>
        </div>
    @endif

    <div class="mj-alert p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Навіщо це потрібно?</strong> Акаунт дозволяє зберегти резюме та отримувати пропозиції від роботодавців.
        </p>
    </div>
</div>

@script
<script>
    let _tgRPollInterval = null;
    let _tgRToken        = null;
    let _tgRPolling      = false;
    let _visRTimer       = null;

    async function startTelegramAuth() {
        const btn = document.getElementById('tg-auth-btn');
        try {
            if (btn) { btn.disabled = true; btn.textContent = 'Завантаження...'; }

            const res = await fetch('/api/telegram/auth/init', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ role: 'candidate' }),
            });

            if (!res.ok) throw new Error();
            const { token, deep_link } = await res.json();

            _tgRToken = token;
            document.getElementById('tg-resume-link').href = deep_link;
            document.getElementById('tg-resume-modal').style.display = 'flex';

            startRPolling(token);
            setTimeout(() => { stopRPolling(); closeTgResumeModal(); }, 300000);
        } catch {
            alert('Помилка. Спробуйте ще раз.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/></svg> Увійти через Telegram`;
            }
        }
    }

    function startRPolling(token) {
        stopRPolling();
        _tgRPollInterval = setInterval(() => pollRStatus(token), 3000);
    }

    function stopRPolling() {
        clearInterval(_tgRPollInterval);
        _tgRPollInterval = null;
    }

    async function pollRStatus(token) {
        if (_tgRPolling) return;
        _tgRPolling = true;
        try {
            const res = await fetch(`/api/telegram/auth/status/${token}`);
            if (res.status === 429) { stopRPolling(); setTimeout(() => startRPolling(token), 10000); return; }
            if (!res.ok) return;
            const data = await res.json();

            if (data.status === 'authorized' && data.login_url) {
                stopRPolling();
                document.getElementById('tg-resume-title').textContent = '✅ Авторизовано!';
                document.getElementById('tg-resume-text').textContent = 'Перенаправляємо...';
                document.getElementById('tg-resume-spinner').style.display = 'none';
                setTimeout(() => { window.location.href = data.login_url + '?resume_redirect=1'; }, 500);
            } else if (data.status === 'expired' || data.status === 'not_found') {
                stopRPolling(); _tgRToken = null; closeTgResumeModal();
                alert('Сесія прострочена. Спробуйте ще раз.');
            }
        } catch {
        } finally {
            _tgRPolling = false;
        }
    }

    function closeTgResumeModal() {
        stopRPolling();
        _tgRToken = null;
        document.getElementById('tg-resume-modal').style.display = 'none';
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && _tgRToken) {
            clearTimeout(_visRTimer);
            _visRTimer = setTimeout(() => startRPolling(_tgRToken), 300);
        } else if (document.visibilityState === 'hidden') {
            stopRPolling();
        }
    });

    // Auto-proceed if already authenticated
    @if($isAuthenticated)
        $wire.dispatch('auth-completed');
    @endif
</script>
@endscript
