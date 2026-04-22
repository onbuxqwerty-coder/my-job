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
        {{-- Telegram --}}
        <div class="space-y-3">
            <button
                id="tg-auth-btn"
                onclick="startTelegramAuth()"
                class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg font-semibold text-white transition"
                style="background: #229ED9;"
                onmouseover="this.style.background='#1a8cbf'"
                onmouseout="this.style.background='#229ED9'"
            >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                </svg>
                Увійти через Telegram
            </button>

            <div class="text-center text-xs text-gray-400">або</div>

            <a href="{{ route('login') }}?redirect={{ urlencode(request()->url()) }}"
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
