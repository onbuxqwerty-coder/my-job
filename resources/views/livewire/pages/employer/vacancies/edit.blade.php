<?php

declare(strict_types=1);

use App\Enums\EmploymentType;
use App\Enums\Language;
use App\Enums\Suitability;
use App\Models\Category;
use App\Models\Vacancy;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $vacancyId = null;

    public string $title          = '';
    public string $description    = '';
    public string $employmentType = '';
    public string $categoryId     = '';
    public string $cityId         = '';
    public string $salaryFrom     = '';
    public string $salaryTo       = '';
    public string $currency       = 'UAH';
    public bool   $isActive       = true;
    public bool   $saved          = false;

    /** @var array<string> */
    public array $languages   = [];

    /** @var array<string> */
    public array $suitability = [];

    public function mount(int $vacancyId = null): void
    {
        $this->vacancyId = $vacancyId;

        if ($vacancyId) {
            $vacancy = Vacancy::where('company_id', auth()->user()->company->id)
                ->findOrFail($vacancyId);

            $this->title          = $vacancy->title;
            $this->description    = $vacancy->description;
            $this->employmentType = $vacancy->employment_type->value;
            $this->categoryId     = (string) $vacancy->category_id;
            $this->cityId         = (string) ($vacancy->city_id ?? '');
            $this->salaryFrom     = (string) ($vacancy->salary_from ?? '');
            $this->salaryTo       = (string) ($vacancy->salary_to ?? '');
            $this->currency       = $vacancy->currency;
            $this->isActive       = $vacancy->is_active;
            $this->languages      = $vacancy->languages ?? [];
            $this->suitability    = $vacancy->suitability ?? [];
        }
    }

    public function save(): void
    {
        $this->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string|min:50',
            'employmentType' => 'required|in:' . implode(',', array_column(EmploymentType::cases(), 'value')),
            'categoryId'     => 'required|exists:categories,id',
            'cityId'         => 'nullable|exists:cities,id',
            'salaryFrom'     => 'nullable|integer|min:0',
            'salaryTo'       => 'nullable|integer|gte:salaryFrom',
            'currency'       => 'required|string|max:10',
            'languages'      => 'array',
            'languages.*'    => 'in:' . implode(',', array_column(Language::cases(), 'value')),
            'suitability'    => 'array',
            'suitability.*'  => 'in:' . implode(',', array_column(Suitability::cases(), 'value')),
        ]);

        $company = auth()->user()->company;

        $data = [
            'company_id'      => $company->id,
            'category_id'     => (int) $this->categoryId,
            'city_id'         => $this->cityId ? (int) $this->cityId : null,
            'title'           => $this->title,
            'slug'            => Str::slug($this->title) . '-' . ($this->vacancyId ?? uniqid()),
            'description'     => $this->description,
            'employment_type' => $this->employmentType,
            'salary_from'     => $this->salaryFrom ?: null,
            'salary_to'       => $this->salaryTo ?: null,
            'currency'        => $this->currency,
            'is_active'       => $this->isActive,
            'published_at'    => $this->isActive ? now() : null,
            'languages'       => $this->languages ?: null,
            'suitability'     => $this->suitability ?: null,
        ];

        if ($this->vacancyId) {
            Vacancy::where('company_id', $company->id)->findOrFail($this->vacancyId)->update($data);
        } else {
            Vacancy::create($data);
        }

        $this->saved = true;
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::orderBy('position')->orderBy('name')->get();
    }

    #[Computed]
    public function employmentTypes(): array { return EmploymentType::cases(); }

    #[Computed]
    public function languageOptions(): array { return Language::cases(); }

    #[Computed]
    public function suitabilityOptions(): array { return Suitability::cases(); }
}; ?>

<div class="min-h-screen bg-gray-50">
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="max-w-2xl mb-6">
            <a href="{{ route('employer.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Вакансії
            </a>
            <h2 class="text-lg font-semibold text-gray-900">{{ $vacancyId ? 'Редагувати вакансію' : 'Нова вакансія' }}</h2>
        </div>

        <div class="max-w-2xl bg-white rounded-2xl border border-gray-200 p-8">
            @if($saved)
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-medium">
                    Вакансію {{ $vacancyId ? 'оновлено' : 'опубліковано' }}.
                    <a href="{{ route('employer.dashboard') }}" class="underline ml-2">До кабінету →</a>
                </div>
            @endif

            <form wire:submit="save" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва посади <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="title"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Категорія <span class="text-red-500">*</span></label>
                        <select wire:model="categoryId" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Оберіть...</option>
                            @foreach($this->categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип зайнятості <span class="text-red-500">*</span></label>
                        <select wire:model="employmentType" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Оберіть...</option>
                            @foreach($this->employmentTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('employmentType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Місто</label>
                    <livewire:city-search wire:model.live="cityId" :key="'vacancy-city-' . ($vacancyId ?? 'new')" />
                    @error('cityId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Зарплата від</label>
                        <input type="number" wire:model="salaryFrom" min="0"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Зарплата до</label>
                        <input type="number" wire:model="salaryTo" min="0"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Валюта</label>
                        <select wire:model="currency" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="UAH">UAH</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Опис вакансії <span class="text-red-500">*</span></label>
                    <textarea wire:model="description" rows="8"
                              placeholder="Опишіть обов'язки, вимоги та умови роботи..."
                              class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <p class="block text-sm font-medium text-gray-700 mb-2">Знання мов</p>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            @foreach($this->languageOptions as $lang)
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; color:#374151;">
                                    <input type="checkbox" wire:model="languages" value="{{ $lang->value }}"
                                           style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;"/>
                                    {{ $lang->label() }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="block text-sm font-medium text-gray-700 mb-2">Підходить</p>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            @foreach($this->suitabilityOptions as $item)
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; color:#374151;">
                                    <input type="checkbox" wire:model="suitability" value="{{ $item->value }}"
                                           style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;"/>
                                    {{ $item->label() }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="isActive" id="isActive" class="w-4 h-4 text-blue-600 rounded"/>
                    <label for="isActive" class="text-sm text-gray-700">Опублікувати одразу (активна)</label>
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        style="width:100%; background:#2563eb; color:#fff; font-weight:700; font-size:0.9rem; padding:10px 16px; border:none; border-radius:12px; cursor:pointer; margin-top:8px; display:block;">
                    <span wire:loading.remove wire:target="save">Зберегти вакансію</span>
                    <span wire:loading wire:target="save">Збереження...</span>
                </button>

            </form>
        </div>
    </div>
</div>
