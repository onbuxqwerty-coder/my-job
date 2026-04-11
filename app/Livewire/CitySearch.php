<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\City;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Modelable;
use Livewire\Component;

final class CitySearch extends Component
{
    /** Двостороння прив'язка до батьківського cityId */
    #[Modelable]
    public string $value = '';

    public string $cityName     = '';
    public string $query        = '';
    public string $displayName  = '';
    public bool   $isOpen       = false;
    public int    $highlighted  = -1;

    public function mount(): void
    {
        if ($this->cityName) {
            $this->displayName = $this->cityName;
            $this->query       = $this->cityName;
        } elseif ($this->value) {
            $city = City::find((int) $this->value, ['id', 'name']);
            $this->displayName = $city?->name ?? '';
            $this->query       = $this->displayName;
        }
    }

    public function updatedQuery(): void
    {
        $this->highlighted = -1;
        $this->isOpen      = true;
    }

    /** Вибір міста зі списку */
    public function selectCity(string $id, string $name): void
    {
        $this->value       = $id;
        $this->displayName = $name;
        $this->query       = '';
        $this->isOpen      = false;
        $this->highlighted = -1;
    }

    /** Очистити вибір («Вся Україна») */
    public function clearCity(): void
    {
        $this->value       = '';
        $this->displayName = '';
        $this->query       = '';
        $this->isOpen      = false;
        $this->highlighted = -1;
    }

    /** Вибрати "Дистанційно" (remote — окремий стан) */
    public function selectRemote(): void
    {
        $this->value       = 'remote';
        $this->displayName = 'Дистанційно';
        $this->query       = '';
        $this->isOpen      = false;
    }

    public function openDropdown(): void
    {
        $this->isOpen = true;
    }

    public function closeDropdown(): void
    {
        $this->isOpen = false;
    }

    /** Клавіатурна навігація */
    public function navigateDown(int $total): void
    {
        $this->highlighted = min($this->highlighted + 1, $total - 1);
    }

    public function navigateUp(): void
    {
        $this->highlighted = max($this->highlighted - 1, -1);
    }

    /** Популярні міста — кешуємо 30 хв */
    public function getPopularCities(): Collection
    {
        $data = Cache::remember('cities_popular_collection', now()->addMinutes(30), function (): array {
            return City::popular()->limit(10)->get(['id', 'name', 'region', 'is_region_center'])->toArray();
        });

        return collect($data)->map(fn(array $c) => (object) $c);
    }

    /** Результати пошуку (якщо query >= 2 символи) */
    public function getSearchResults(): Collection
    {
        if (mb_strlen(trim($this->query)) < 2) {
            return collect();
        }

        return City::search(trim($this->query))
            ->limit(12)
            ->get(['id', 'name', 'region', 'is_region_center']);
    }

    public function render(): \Illuminate\View\View
    {
        $popular = $this->getPopularCities();
        $results = $this->getSearchResults();
        $showPopular = mb_strlen(trim($this->query)) < 2;

        return view('livewire.city-search', compact('popular', 'results', 'showPopular'));
    }
}
