<?php

declare(strict_types=1);

use App\Enums\EmploymentType;
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
    public string $salaryFrom     = '';
    public string $salaryTo       = '';
    public string $currency       = 'UAH';
    public bool   $isActive       = true;
    public bool   $saved          = false;

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
            $this->salaryFrom     = (string) ($vacancy->salary_from ?? '');
            $this->salaryTo       = (string) ($vacancy->salary_to ?? '');
            $this->currency       = $vacancy->currency;
            $this->isActive       = $vacancy->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string|min:50',
            'employmentType' => 'required|in:' . implode(',', array_column(EmploymentType::cases(), 'value')),
            'categoryId'     => 'required|exists:categories,id',
            'salaryFrom'     => 'nullable|integer|min:0',
            'salaryTo'       => 'nullable|integer|gte:salaryFrom',
            'currency'       => 'required|string|max:10',
        ]);

        $company = auth()->user()->company;

        $data = [
            'company_id'      => $company->id,
            'category_id'     => (int) $this->categoryId,
            'title'           => $this->title,
            'slug'            => Str::slug($this->title) . '-' . ($this->vacancyId ?? uniqid()),
            'description'     => $this->description,
            'employment_type' => $this->employmentType,
            'salary_from'     => $this->salaryFrom ?: null,
            'salary_to'       => $this->salaryTo ?: null,
            'currency'        => $this->currency,
            'is_active'       => $this->isActive,
            'published_at'    => $this->isActive ? now() : null,
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
    public function employmentTypes(): array
    {
        return EmploymentType::cases();
    }
}; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <a href="{{ route('employer.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 mb-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Назад
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $vacancyId ? 'Редагувати вакансію' : 'Нова вакансія' }}</h1>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-8">
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
                                <option value="{{ $type->value }}">{{ ucwords(str_replace('-', ' ', $type->value)) }}</option>
                            @endforeach
                        </select>
                        @error('employmentType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
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

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="isActive" id="isActive" class="w-4 h-4 text-blue-600 rounded"/>
                    <label for="isActive" class="text-sm text-gray-700">Опублікувати одразу (активна)</label>
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors">
                    <span wire:loading.remove wire:target="save">{{ $vacancyId ? 'Оновити вакансію' : 'Опублікувати вакансію' }}</span>
                    <span wire:loading wire:target="save">Збереження...</span>
                </button>

            </form>
        </div>
    </div>
</div>
