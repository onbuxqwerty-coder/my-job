<div
    class="relative"
    x-data="{
        geoLoading: false,
        geoError: null,

        requestGeo() {
            if (!navigator.geolocation) {
                this.geoError = 'Геолокація не підтримується браузером';
                return;
            }
            this.geoLoading = true;
            this.geoError   = null;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    fetch('/api/cities/nearest', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                        body: JSON.stringify({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.data) {
                            $wire.selectCity(String(data.data.id), data.data.name);
                        } else {
                            this.geoError = 'Місто не знайдено поблизу';
                        }
                    })
                    .catch(() => { this.geoError = 'Помилка запиту до сервера'; })
                    .finally(() => { this.geoLoading = false; });
                },
                () => {
                    this.geoError   = 'Не вдалося визначити місцезнаходження';
                    this.geoLoading = false;
                },
                { timeout: 8000 }
            );
        },
    }"
    @click.outside="$wire.closeDropdown()"
    @keydown.escape.window="$wire.closeDropdown()"
>
    {{-- Input row --}}
    <div style="position:relative; display:flex; align-items:center; height:100%;">

        {{-- Location icon --}}
        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </span>

        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            @focus="$wire.openDropdown()"
            @keydown.arrow-down.prevent="$wire.navigateDown({{ $showPopular ? $popular->count() + 2 : $results->count() + 2 }})"
            @keydown.arrow-up.prevent="$wire.navigateUp()"
            value="{{ $displayName ?: $query }}"
            placeholder="{{ $value ? $displayName : 'Виберіть місто або регіон...' }}"
            style="width:100%; height:48px; padding:0 44px 0 38px; font-size:15px;
                   border:1px solid #000; border-radius:var(--radius-lg);
                   color:var(--color-text-dark); background:#fff;
                   outline:none; transition:box-shadow .15s;"
            onfocus="this.style.boxShadow='0 0 0 3px rgba(0,0,0,.1)'"
            onblur="this.style.boxShadow='none'"
        />

        {{-- Geo button --}}
        <button
            type="button"
            @click.stop="requestGeo()"
            :disabled="geoLoading"
            title="Визначити місто за геолокацією"
            style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                   background:none; border:none; cursor:pointer; color:#6b7280; padding:4px;
                   display:flex; align-items:center; transition:color .15s;"
            onmouseover="this.style.color='#2563eb'"
            onmouseout="this.style.color='#6b7280'"
        >
            <span x-show="!geoLoading">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="2" fill="none"/>
                </svg>
            </span>
            <span x-show="geoLoading">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </span>
        </button>
    </div>

    {{-- Geo error --}}
    <p x-show="geoError" x-text="geoError" style="font-size:12px;color:#dc2626;margin-top:4px;padding-left:4px;"></p>

    {{-- Dropdown --}}
    @if($isOpen)
    <div
        class="city-dropdown"
        style="position:absolute; z-index:9999; top:calc(100% + 4px); left:0; right:0;
               background:#fff; border:1px solid #d1d5db; border-radius:8px;
               box-shadow:0 10px 25px rgba(0,0,0,.12); max-height:400px; overflow-y:auto;"
        wire:loading.class="opacity-50"
        wire:target="query"
    >
        @php $idx = 0; @endphp

        {{-- Спеціальні опції --}}
        <div style="border-bottom:1px solid #f3f4f6;">
            {{-- Вся Україна --}}
            <button wire:click="clearCity" type="button"
                    style="width:100%; text-align:left; padding:10px 16px; font-size:14px; border:none; cursor:pointer;
                           background:{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }}; color:#111827;
                           display:flex; align-items:center; gap:8px;"
                    onmouseover="this.style.background='#eff6ff'"
                    onmouseout="this.style.background='{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }}'">
                <span>☑</span><span>Вся Україна</span>
            </button>
            @php $idx++; @endphp

            {{-- Дистанційно --}}
            <button wire:click="selectRemote" type="button"
                    style="width:100%; text-align:left; padding:10px 16px; font-size:14px; border:none; cursor:pointer;
                           background:{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }}; color:#111827;
                           display:flex; align-items:center; gap:8px;"
                    onmouseover="this.style.background='#eff6ff'"
                    onmouseout="this.style.background='{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }}'">
                <span>🌐</span><span>Дистанційно</span>
            </button>
            @php $idx++; @endphp
        </div>

        {{-- Популярні міста (коли немає запиту) --}}
        @if($showPopular && $popular->isNotEmpty())
            <div style="padding:6px 16px 4px; font-size:11px; font-weight:700; color:#6b7280; letter-spacing:.06em; text-transform:uppercase; background:#f9fafb;">
                Популярні міста
            </div>
            @foreach($popular as $city)
                <button wire:click="selectCity('{{ $city->id }}', '{{ addslashes($city->name) }}')" type="button"
                        style="width:100%; text-align:left; padding:9px 16px; font-size:14px; border:none; cursor:pointer;
                               background:{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }};
                               color:{{ $highlighted === $idx ? '#1e40af' : '#111827' }};
                               border-bottom:1px solid #f9fafb;"
                        onmouseover="this.style.background='#eff6ff'; this.style.color='#1e40af';"
                        onmouseout="this.style.background='{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }}'; this.style.color='{{ $highlighted === $idx ? '#1e40af' : '#111827' }}';">
                    {{ $city->name }}
                    @if($city->region)
                        <span style="color:#9ca3af;font-size:13px;"> ({{ $city->region }})</span>
                    @endif
                </button>
                @php $idx++; @endphp
            @endforeach
        @endif

        {{-- Результати пошуку --}}
        @if(!$showPopular)
            @if($results->isNotEmpty())
                <div style="padding:6px 16px 4px; font-size:11px; font-weight:700; color:#6b7280; letter-spacing:.06em; text-transform:uppercase; background:#f9fafb;">
                    Результати пошуку
                </div>
                @foreach($results as $city)
                    <button wire:click="selectCity('{{ $city->id }}', '{{ addslashes($city->name) }}')" type="button"
                            style="width:100%; text-align:left; padding:9px 16px; font-size:14px; border:none; cursor:pointer;
                                   background:{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }};
                                   color:{{ $highlighted === $idx ? '#1e40af' : '#111827' }};
                                   border-bottom:1px solid #f9fafb;"
                            onmouseover="this.style.background='#eff6ff'; this.style.color='#1e40af';"
                            onmouseout="this.style.background='{{ $highlighted === $idx ? '#eff6ff' : 'transparent' }}'; this.style.color='{{ $highlighted === $idx ? '#1e40af' : '#111827' }}';">
                        {{ $city->name }}
                        @if($city->region)
                            <span style="color:#9ca3af;font-size:13px;"> ({{ $city->region }})</span>
                        @endif
                    </button>
                    @php $idx++; @endphp
                @endforeach
            @else
                <div style="padding:20px 16px; text-align:center; font-size:14px; color:#9ca3af;">
                    Жодного міста не знайдено
                </div>
            @endif
        @endif

    </div>
    @endif

</div>
