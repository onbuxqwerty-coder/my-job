{{-- Shared filter content (used inside mobile modal) --}}

@if($categoryId || $employmentType || $salaryMin || $salaryMax)
    <div class="filter-section">
        <button wire:click="clearFilters"
                style="font-size: 13px; font-weight: 700; color: var(--color-primary-blue);
                       background: none; border: none; cursor: pointer; padding: 0;">
            ← Скинути всі фільтри
        </button>
    </div>
@endif

{{-- Category --}}
<div class="filter-section">
    <label for="cat-modal" class="filter-label">Категорія</label>
    <select id="cat-modal" wire:model.live="categoryId" wire:loading.attr="disabled" class="filter-select">
        <option value="">Всі категорії</option>
        @foreach($this->categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </select>
</div>

{{-- Employment Type --}}
<div class="filter-section">
    <p class="filter-label">Тип зайнятості</p>
    <div class="radio-group">
        @foreach($this->employmentTypes as $type)
            <label class="radio-item">
                <input type="radio"
                       wire:model.live="employmentType"
                       wire:loading.attr="disabled"
                       value="{{ $type->value }}"/>
                <span>{{ str_replace('-', ' ', $type->value) }}</span>
            </label>
        @endforeach
        @if($employmentType)
            <button wire:click="$set('employmentType', '')"
                    style="font-size: 12px; color: var(--color-primary-blue); background: none; border: none; cursor: pointer; text-align: left; margin-top: 4px;">
                Скинути
            </button>
        @endif
    </div>
</div>

{{-- Salary --}}
<div class="filter-section">
    <p class="filter-label">Зарплата (UAH)</p>
    <div class="salary-row">
        <div>
            <label style="font-size: 11px; color: var(--color-text-gray); display: block; margin-bottom: 4px;">Від</label>
            <input id="salary-min-modal" type="number"
                   wire:model.live.debounce.600ms="salaryMin"
                   wire:loading.attr="disabled"
                   placeholder="0" min="0" class="salary-input"/>
        </div>
        <div>
            <label style="font-size: 11px; color: var(--color-text-gray); display: block; margin-bottom: 4px;">До</label>
            <input id="salary-max-modal" type="number"
                   wire:model.live.debounce.600ms="salaryMax"
                   wire:loading.attr="disabled"
                   placeholder="Будь-яка" min="0" class="salary-input"/>
        </div>
    </div>
</div>
