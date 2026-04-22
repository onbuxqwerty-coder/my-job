<div class="px-6 py-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Навички</h2>
        <p class="text-sm text-gray-600 mt-1">Додайте технічні вміння та інструменти, якими ви володієте</p>
    </div>

    {{-- Added skills --}}
    @if (!empty($skills))
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Ваші навички:</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($skills as $skill)
                    <span class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-sm">
                        {{ $skill }}
                        <button wire:click="removeSkill('{{ $skill }}')" class="hover:text-blue-600 text-base leading-none">✕</button>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Search / add --}}
    <div class="relative" x-data="{ open: false }">
        <label class="block text-sm font-semibold text-gray-900 mb-2">Додати навичку</label>
        <div class="flex gap-2">
            <input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                x-on:focus="open = true"
                x-on:blur="setTimeout(() => open = false, 150)"
                placeholder="Напр. Laravel, React, Python..."
                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button wire:click="addSkill()"
                class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition">
                +
            </button>
        </div>

        {{-- Dropdown --}}
        @if (!empty($searchResults))
            <div x-show="open" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                @foreach ($searchResults as $result)
                    <button wire:click="addSkill('{{ $result }}')"
                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-b-0">
                        {{ $result }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Suggestions --}}
    @if (!empty($suggestions))
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Рекомендовані навички:</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $suggestion)
                    <button wire:click="addSkill('{{ $suggestion }}')"
                        class="px-3 py-1.5 border border-gray-300 rounded-full text-sm text-gray-700 hover:bg-blue-50 hover:border-blue-400 transition">
                        + {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Порада:</strong> Навички дійсно впливають на матчинг з роботодавцями. Додайте всі релевантні технології!
        </p>
    </div>
</div>
