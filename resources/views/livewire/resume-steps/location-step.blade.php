<div class="px-6 py-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Локація</h2>
        <p class="text-sm text-gray-600 mt-1">Вкажіть ваше місцезнаходження або оберіть роботу без прив'язки</p>
    </div>

    {{-- No location toggle --}}
    <label class="flex items-center gap-3 cursor-pointer p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
        <input
            type="checkbox"
            wire:model="noLocationBinding"
            wire:change="toggleNoLocationBinding"
            class="w-4 h-4 rounded border-gray-300"
        />
        <div>
            <p class="text-sm font-semibold text-gray-900">Без прив'язки до міста</p>
            <p class="text-xs text-gray-500">Віддалена робота / місто не важливе</p>
        </div>
    </label>

    @if (!$noLocationBinding)
        {{-- City search --}}
        <div class="relative">
            <label class="block text-sm font-semibold text-gray-900 mb-2">Місто</label>
            <input
                type="text"
                wire:model.live.debounce.400ms="city"
                wire:blur="closeSuggestions"
                placeholder="Почніть вводити назву міста..."
                class="city-search-input"
                style="width:100%; height:48px; padding:0 16px; font-size:15px;
                       border:1px solid #000; border-radius:var(--radius-lg);
                       color:var(--color-text-dark); background:#fff;
                       outline:none; transition:box-shadow .15s;"
                onfocus="this.style.boxShadow='0 0 0 3px rgba(0,0,0,.1)'"
                onblur="this.style.boxShadow=''"
            />

            @if ($showSuggestions && !empty($citySuggestions))
                <div class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                    @foreach ($citySuggestions as $c)
                        <button
                            wire:mousedown="selectCity({{ $c['id'] }}, '{{ $c['name'] }}', {{ $c['latitude'] ?? 'null' }}, {{ $c['longitude'] ?? 'null' }})"
                            class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-b-0"
                        >
                            <span class="font-medium">{{ $c['name'] }}</span>
                            @if ($c['region'])
                                <span class="text-gray-400 ml-1">· {{ $c['region'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Street --}}
        <div>
            <label class="block text-sm font-semibold text-gray-900 mb-2">Вулиця <span class="text-gray-400">(необов'язково)</span></label>
            <input
                type="text"
                wire:model.lazy="street"
                wire:blur="saveLocation"
                placeholder="Наприклад: вул. Хрещатик"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
        </div>

        {{-- Building --}}
        <div>
            <label class="block text-sm font-semibold text-gray-900 mb-2">Будинок <span class="text-gray-400">(необов'язково)</span></label>
            <input
                type="text"
                wire:model.lazy="building"
                wire:blur="saveLocation"
                placeholder="Наприклад: 10"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
        </div>
    @else
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">Вибрано режим без прив'язки до міста. Вакансії будуть показані з усієї України.</p>
        </div>
    @endif

    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Порада:</strong> Точна локація допомагає знаходити роботодавців поруч з вами.
        </p>
    </div>
</div>
