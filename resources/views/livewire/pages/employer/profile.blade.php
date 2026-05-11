<?php

declare(strict_types=1);

use App\Enums\BusinessType;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\Vacancy;
use App\Services\ProfileCompletenessService;
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
    public string $businessType = 'legal';
    public string $edrpou = '';
    public string $ipn = '';
    public $logo = null;
    public bool $saved = false;

    public function mount(): void
    {
        $company = auth()->user()->company;

        if ($company) {
            $this->name         = $company->name             ?? '';
            $this->description  = $company->description      ?? '';
            $this->website      = $company->website          ?? '';
            $this->location     = $company->location         ?? '';
            $this->cityId       = (string) ($company->city_id ?? '');
            $this->businessType = $company->business_type?->value ?? 'legal';
            $this->edrpou       = $company->edrpou            ?? '';
            $this->ipn          = $company->ipn               ?? '';
        }
    }

    public function save(): void
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'description'  => 'required|string|max:5000',
            'website'      => 'nullable|url|max:255',
            'location'     => 'nullable|string|max:255',
            'cityId'       => 'nullable|exists:cities,id',
            'logo'         => 'nullable|image|max:2048',
            'businessType' => 'required|in:legal,individual',
            'edrpou'       => $this->businessType === 'legal'       ? 'required|digits:8' : 'nullable',
            'ipn'          => $this->businessType === 'individual'  ? 'required|digits:10' : 'nullable',
        ];

        $this->validate($rules, [
            'edrpou.required' => 'ЄДРПОУ є обов\'язковим для юридичних осіб.',
            'edrpou.digits'   => 'ЄДРПОУ повинен містити рівно 8 цифр.',
            'ipn.required'    => 'ІПН є обов\'язковим для ФОП.',
            'ipn.digits'      => 'ІПН повинен містити рівно 10 цифр.',
        ]);

        $data = [
            'name'          => $this->name,
            'slug'          => Str::slug($this->name),
            'description'   => $this->description,
            'website'       => $this->website   ?: null,
            'location'      => $this->location  ?: null,
            'city_id'       => $this->cityId    ? (int) $this->cityId : null,
            'business_type' => BusinessType::from($this->businessType),
            'edrpou'        => $this->businessType === 'legal'      ? $this->edrpou : null,
            'ipn'           => $this->businessType === 'individual' ? $this->ipn    : null,
        ];

        if ($this->logo) {
            $data['logo'] = $this->logo->store('logos', 'public');
        }

        $company = Company::updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        if ($company->isProfileComplete()) {
            $company->vacancies()
                ->where('status', \App\Enums\VacancyStatus::Active)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now()->addDays(30))
                ->update(['expires_at' => now()->addDays(30)]);
        }

        $score = app(ProfileCompletenessService::class)
            ->employerScore(auth()->user()->fresh())['score'];

        if ($score === 100) {
            $vacancy = Vacancy::where('company_id', $company->id)
                ->whereIn('status', [VacancyStatus::Draft, VacancyStatus::Expired])
                ->latest()
                ->first();

            if ($vacancy) {
                $vacancy->publish();

                session()->flash(
                    'success',
                    'Профіль збережено. Вакансію «' . $vacancy->title . '» активовано на 30 днів!'
                );

                $this->redirect(route('employer.dashboard'), navigate: true);
                return;
            }
        }

        $this->saved = true;
        $this->logo  = null;
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if(session('info'))
            <div class="max-w-2xl mb-4 px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-800 text-sm">
                {{ session('info') }}
            </div>
        @endif

        <div class="max-w-2xl mb-6 flex items-center gap-4 flex-wrap">
            <h2 class="text-lg font-semibold text-gray-900">Профіль компанії</h2>
            @php $verStatus = auth()->user()->company?->verification_status; @endphp
            @if($verStatus === \App\Enums\CompanyVerificationStatus::Verified)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Верифікована компанія
                </span>
            @elseif($verStatus === \App\Enums\CompanyVerificationStatus::Rejected)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    Верифікацію відхилено — зверніться до підтримки
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Очікує верифікації
                </span>
            @endif
        </div>

        <div class="bg-white rounded-2xl border employer-card-border p-8">
            <form wire:submit="save" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва компанії <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Тип підприємства --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип підприємства <span class="text-red-500">*</span></label>
                    <select wire:model.live="businessType"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="legal">Юридична особа (ЄДРПОУ)</option>
                        <option value="individual">ФОП (ІПН)</option>
                    </select>
                    @error('businessType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- ЄДРПОУ --}}
                @if($businessType === 'legal')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ЄДРПОУ <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="edrpou" inputmode="numeric" maxlength="8" placeholder="12345678"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    <p class="mt-1 text-xs text-gray-400">8 цифр без пробілів</p>
                    @error('edrpou') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- ІПН --}}
                @if($businessType === 'individual')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ІПН <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="ipn" inputmode="numeric" maxlength="10" placeholder="1234567890"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    <p class="mt-1 text-xs text-gray-400">10 цифр без пробілів</p>
                    @error('ipn') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

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

                @if($saved)
                    <p class="text-center text-sm text-green-600 font-medium">
                        ✅ Профіль успішно збережено.
                    </p>
                @endif
            </form>
        </div>
    </div>
</div>
