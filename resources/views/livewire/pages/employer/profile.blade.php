<?php

declare(strict_types=1);

use App\Models\Company;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $description = '';
    public string $website = '';
    public string $location = '';
    public string $cityId = '';
    public $logo = null;
    public bool $saved = false;

    public function mount(): void
    {
        $company = auth()->user()->company;

        if ($company) {
            $this->name        = $company->name        ?? '';
            $this->description = $company->description ?? '';
            $this->website     = $company->website     ?? '';
            $this->location    = $company->location    ?? '';
            $this->cityId      = (string) ($company->city_id ?? '');
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'website'     => 'nullable|url|max:255',
            'location'    => 'nullable|string|max:255',
            'cityId'      => 'nullable|exists:cities,id',
            'logo'        => 'nullable|image|max:2048',
        ]);

        $data = [
            'name'        => $this->name,
            'slug'        => Str::slug($this->name),
            'description' => $this->description,
            'website'     => $this->website ?: null,
            'location'    => $this->location ?: null,
            'city_id'     => $this->cityId ? (int) $this->cityId : null,
        ];

        if ($this->logo) {
            $data['logo'] = $this->logo->store('logos', 'public');
        }

        Company::updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        $this->saved = true;
        $this->logo  = null;
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="max-w-2xl mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Профіль компанії</h2>
        </div>

        <div class="bg-white rounded-2xl border employer-card-border p-8">
            @if($saved)
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-medium">
                    ✅ Профіль успішно збережено.
                </div>
            @endif

            <form wire:submit="save" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва компанії <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Місто</label>
                    <livewire:city-search wire:model.live="cityId" :key="'company-city'" />
                    @error('cityId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Адреса офісу</label>
                    <input type="text" wire:model="location" placeholder="вул. Хрещатик, 1"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    @error('location') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Вебсайт</label>
                    <input type="text" wire:model="website" placeholder="https://example.com"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    @error('website') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Опис компанії <span class="text-red-500">*</span></label>
                    <textarea wire:model="description" rows="5"
                              class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Логотип</label>
                    @php $currentLogo = auth()->user()->company?->logo_url; @endphp
                    @if($currentLogo)
                        <div class="mb-2 flex items-center gap-3">
                            <img src="{{ $currentLogo }}"
                                 alt="Логотип"
                                 class="w-16 h-16 object-contain rounded-xl border border-gray-200 bg-gray-50 p-1">
                            <span class="text-xs text-gray-400">Поточний логотип</span>
                        </div>
                    @endif
                    <input type="file" wire:model="logo" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700"/>
                    @error('logo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors">
                    <span wire:loading.remove wire:target="save">Зберегти профіль</span>
                    <span wire:loading wire:target="save">Збереження...</span>
                </button>
            </form>
        </div>
    </div>
</div>
