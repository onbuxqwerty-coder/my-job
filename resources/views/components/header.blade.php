<style>
    .site-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 120px;
        background-color: #2d323b;
        color: #ffffff;
        font-size: 1.2rem;
        z-index: 9999;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .site-header.is-hidden {
        transform: translateY(-100%);
    }
    .site-header.is-solid {
        box-shadow: 0 2px 12px rgba(0,0,0,0.3);
    }
    .site-header__inner {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 24px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }
    .site-header__nav {
        display: flex;
        align-items: center;
        gap: 24px;
        flex: 1;
        justify-content: center;
    }
    .site-header__nav a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 600;
        white-space: nowrap;
        transition: opacity 0.2s;
    }
    .site-header__nav a:hover {
        opacity: 0.75;
    }
    .site-header__nav a.active {
        color: #818cf8;
    }
    .site-header__auth {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .site-header__btn-login {
        padding: 8px 20px;
        font-size: 1.2rem;
        font-weight: 600;
        color: #ffffff;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 0.2s;
    }
    .site-header__btn-login:hover {
        background-color: rgba(255,255,255,0.1);
    }

    .header-burger {
        display: none;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: #2d323b;
        border: 2px solid #a7a7a7;
        border-radius: 6px;
        cursor: pointer;
        flex-shrink: 0;
        gap: 0;
        flex-direction: column;
        padding: 6px 7px;
    }
    .header-burger__bar {
        display: block;
        width: 100%;
        height: 2px;
        background: #a7a7a7;
        border-radius: 2px;
        margin: 2px 0;
    }
    @media (max-width: 1023px) {
        .header-burger { display: flex; }
    }

    @media (max-width: 767px) {
        .site-header {
            height: 64px;
            font-size: 1rem;
        }
        .site-header__inner {
            padding: 0 16px;
            gap: 12px;
        }
        .site-header__nav {
            display: none;
        }
        .site-header__logo img {
            height: 48px !important;
        }
        .site-header__btn-login {
            padding: 6px 14px;
            font-size: 0.95rem;
        }
    }
</style>

<nav class="site-header">
    <div class="site-header__inner">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="site-header__logo" style="flex-shrink:0; display:flex; align-items:center;">
            <img src="{{ asset('img/logo/mj-logo-100x100-dark-theme.webp') }}"
                 alt="My Job"
                 style="height:100px; width:auto; display:block;">
        </a>

        {{-- Burger (mobile only) --}}
        <button class="header-burger" onclick="window.toggleMobileNav()" aria-label="Меню">
            <span class="header-burger__bar"></span>
            <span class="header-burger__bar"></span>
            <span class="header-burger__bar"></span>
        </button>

        {{-- Nav links --}}
        <div class="site-header__nav">
            <a href="{{ route('resumes.create') }}" {{ request()->routeIs('resumes.*') ? 'class=active' : '' }}>Розмістити Резюме</a>
            <button
                onclick="Livewire.dispatch('open-quick-publish')"
                style="background:none; border:none; cursor:pointer; font-size:inherit; font-weight:600;
                       color:#ffffff; padding:0; transition:opacity 0.2s; white-space:nowrap;"
                onmouseover="this.style.opacity='0.75'"
                onmouseout="this.style.opacity='1'"
            >
                Розмістити вакансію
            </button>

            @auth
                @if(auth()->user()->role === \App\Enums\UserRole::Employer)
                    <a href="{{ route('employer.dashboard') }}"
                       {{ request()->routeIs('employer.*') ? 'class=active' : '' }}>
                        Мої вакансії
                    </a>
                @endif
                @if(auth()->user()->role === \App\Enums\UserRole::Candidate)
                    <a href="{{ route('seeker.dashboard') }}"
                       {{ request()->routeIs('seeker.*') ? 'class=active' : '' }}>
                        Мій кабінет
                    </a>
                @endif
                @if(auth()->user()->role === \App\Enums\UserRole::Admin)
                    <a href="{{ route('filament.admin.pages.dashboard') }}">Адмін-панель</a>
                @endif
            @endauth
        </div>

        {{-- Dark mode toggle --}}
        <button class="dark-toggle" onclick="window.toggleDarkMode()" aria-label="Перемкнути тему" title="Перемкнути тему">
            <span id="darkToggleIcon">🌙</span>
        </button>

        {{-- Auth --}}
        <div class="site-header__auth">
            @auth
                <span style="color:#ffffff; font-weight:600;">
                    {{ auth()->user()->name }}
                </span>
                @if(\Illuminate\Support\Facades\Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="site-header__btn-login">Вийти</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('filament.admin.auth.logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="site-header__btn-login">Вийти</button>
                    </form>
                @endif
            @else
                @if(\Illuminate\Support\Facades\Route::has('login'))
                    <a href="{{ route('login') }}" class="site-header__btn-login">Увійти</a>
                @endif
            @endauth
        </div>

    </div>
</nav>

{{-- Mobile nav drawer --}}
<div id="mobile-nav-overlay"
     onclick="window.toggleMobileNav()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9998;"></div>

<div id="mobile-nav-drawer"
     style="display:none; position:fixed; top:0; left:0; bottom:0; width:280px;
            background:#2d323b; z-index:9999; padding:24px 0; overflow-y:auto;
            box-shadow:4px 0 16px rgba(0,0,0,0.4);">

    <div style="display:flex; align-items:center; justify-content:space-between; padding:0 20px 20px;">
        <span style="color:#fff; font-weight:700; font-size:1.1rem;">Меню</span>
        <button onclick="window.toggleMobileNav()"
                style="background:none; border:none; color:#a7a7a7; cursor:pointer; font-size:1.5rem; line-height:1;">✕</button>
    </div>

    <nav style="display:flex; flex-direction:column;">
        <a href="{{ route('home') }}"
           style="padding:14px 20px; color:#fff; text-decoration:none; font-weight:600; font-size:1rem;
                  border-bottom:1px solid rgba(255,255,255,0.08); transition:background 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.08)'"
           onmouseout="this.style.background=''"
        >Знайти вакансії</a>

        <a href="{{ route('resumes.create') }}"
           style="padding:14px 20px; color:#fff; text-decoration:none; font-weight:600; font-size:1rem;
                  border-bottom:1px solid rgba(255,255,255,0.08); transition:background 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.08)'"
           onmouseout="this.style.background=''"
        >Розмістити Резюме</a>

        <button onclick="Livewire.dispatch('open-quick-publish'); window.toggleMobileNav();"
                style="padding:14px 20px; color:#fff; text-align:left; font-weight:600; font-size:1rem;
                       background:none; border:none; border-bottom:1px solid rgba(255,255,255,0.08);
                       cursor:pointer; transition:background 0.2s; width:100%;"
                onmouseover="this.style.background='rgba(255,255,255,0.08)'"
                onmouseout="this.style.background=''"
        >Розмістити вакансію</button>

        @auth
            @if(auth()->user()->role === \App\Enums\UserRole::Employer)
                <a href="{{ route('employer.dashboard') }}"
                   style="padding:14px 20px; color:#fff; text-decoration:none; font-weight:600; font-size:1rem;
                          border-bottom:1px solid rgba(255,255,255,0.08); transition:background 0.2s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'"
                   onmouseout="this.style.background=''"
                >Мої вакансії</a>
            @endif
            @if(auth()->user()->role === \App\Enums\UserRole::Candidate)
                <a href="{{ route('seeker.dashboard') }}"
                   style="padding:14px 20px; color:#fff; text-decoration:none; font-weight:600; font-size:1rem;
                          border-bottom:1px solid rgba(255,255,255,0.08); transition:background 0.2s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'"
                   onmouseout="this.style.background=''"
                >Мій кабінет</a>
            @endif
        @endauth
    </nav>
</div>

<script>
    function initHeaderScroll() {
        const header = document.querySelector('.site-header');
        if (!header) return;

        let lastScrollY = window.pageYOffset;
        const THRESHOLD = 80;

        function onScroll() {
            const scrollY = window.pageYOffset;
            header.classList.toggle('is-solid', scrollY > THRESHOLD);
            header.classList.toggle('is-hidden', scrollY > lastScrollY && scrollY > THRESHOLD);
            lastScrollY = scrollY <= 0 ? 0 : scrollY;
        }

        window.removeEventListener('scroll', window._headerScrollHandler);
        window._headerScrollHandler = onScroll;
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    document.addEventListener('DOMContentLoaded', initHeaderScroll);
    document.addEventListener('livewire:navigated', initHeaderScroll);

    // Глобальна функція — викликається через inline onclick, без дублювання listeners
    window.toggleDarkMode = function() {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', next);
        document.documentElement.setAttribute('data-theme', next);
        document.documentElement.classList.toggle('dark', next === 'dark');
        const icon = document.getElementById('darkToggleIcon');
        if (icon) icon.textContent = next === 'dark' ? '☀️' : '🌙';
    };

    function syncDarkIcon() {
        const icon  = document.getElementById('darkToggleIcon');
        const theme = document.documentElement.getAttribute('data-theme') || 'light';
        if (icon) icon.textContent = theme === 'dark' ? '☀️' : '🌙';
    }

    document.addEventListener('DOMContentLoaded', syncDarkIcon);
    document.addEventListener('livewire:navigated', syncDarkIcon);

    window.toggleMobileFilters = function() {
        const aside = document.querySelector('aside.mj-filters');
        if (aside) aside.classList.toggle('is-open');
    };

    window.toggleMobileNav = function() {
        const drawer  = document.getElementById('mobile-nav-drawer');
        const overlay = document.getElementById('mobile-nav-overlay');
        const open    = drawer.style.display === 'none';
        drawer.style.display  = open ? 'block' : 'none';
        overlay.style.display = open ? 'block' : 'none';
        document.body.style.overflow = open ? 'hidden' : '';
    };
</script>
