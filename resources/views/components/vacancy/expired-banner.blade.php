@props(['vacancy'])

<div class="mj-expired-banner">
    <div class="mj-expired-banner-inner">
        <svg class="mj-expired-banner-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div class="mj-expired-banner-body">
            <h3 class="mj-expired-banner-title">Ця вакансія неактивна</h3>
            <p class="mj-expired-banner-text">
                Публікація закрита {{ $vacancy->expires_at?->locale('uk')->isoFormat('D MMMM YYYY') }}.
                Перегляньте схожі активні вакансії нижче.
            </p>
        </div>
    </div>
</div>

<style>
.mj-expired-banner {
    background: #fefce8;
    border: 1px solid #fde047;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 20px;
}
[data-theme="dark"] .mj-expired-banner {
    background: #422006;
    border-color: #854d0e;
}
.mj-expired-banner-inner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.mj-expired-banner-icon {
    width: 20px;
    height: 20px;
    color: #ca8a04;
    flex-shrink: 0;
    margin-top: 2px;
}
[data-theme="dark"] .mj-expired-banner-icon { color: #fbbf24; }
.mj-expired-banner-title {
    font-size: 15px;
    font-weight: 600;
    color: #713f12;
    margin: 0 0 4px;
}
[data-theme="dark"] .mj-expired-banner-title { color: #fde68a; }
.mj-expired-banner-text {
    font-size: 13px;
    color: #854d0e;
    margin: 0;
}
[data-theme="dark"] .mj-expired-banner-text { color: #fcd34d; }
</style>
