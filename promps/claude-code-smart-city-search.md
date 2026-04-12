# Промпт для Claude Code: Розумний пошук міста (Work.ua style)

## 📋 Описання завдання

Реалізувати компонент **«Розумний пошук міста»** для проєкту на Laravel. Логіка має бути максимально наближена до функціоналу Work.ua.

---

## 🛠 Технічний стек (обери один варіант)

- **[✓ РЕКОМЕНДУЄТЬСЯ]** Livewire + Tailwind CSS
- Vue.js 3 + Axios + Tailwind CSS
- Vanilla JS + Axios + Tailwind CSS

> Якщо не зазначено - використовуй **Livewire**

---

## 📦 Backend Requirements (Laravel)

### 1. Database Migration

**Файл:** `database/migrations/XXXX_XX_XX_XXXXXX_create_cities_table.php`

Таблиця має містити:
- `id` (Primary Key, unsignedBigInteger)
- `name_uk` (string) - назва міста українською
- `region` (string, nullable) - область
- `latitude` (decimal: 10,8) - для геолокації
- `longitude` (decimal: 10,8) - для геолокації
- `is_popular` (boolean, default: false) - чи входить до популярних міст
- `slug` (string, unique) - для URL-дружелюбності
- `population` (integer, nullable) - кількість населення (для сортування)
- `timestamps` - created_at, updated_at

**Обмеження:** Індекси на `name_uk`, `is_popular`, `region`

### 2. Model (Eloquent)

**Файл:** `app/Models/City.php`

```php
// Зміст моделі з:
// - Fillable полями
// - Accessor для форматування
// - Scopи для пошуку (searchByName, popularCities)
// - Методом для розрахунку відстані до координат
```

### 3. Controller

**Файл:** `app/Http/Controllers/CityController.php`

**Методи:**
- `index()` - GET `/api/cities` - повертає популярні міста (пагінація 100)
- `search()` - GET `/api/cities/search?q={query}` - пошук міст за запитом
- `nearest()` - POST `/api/cities/nearest` - пошук найближчого міста за координатами

**Критерії:**
- Валідація запиту (мінімум 2 символи для search)
- LIKE %query% пошук (case-insensitive)
- Результати сортуються за: спочатку `is_popular`, потім за схожістю, потім за алфавітом
- Для `nearest()` - SQL запит с Haversine формулою для розрахунку відстані
- Відповідь у JSON форматі з кешуванням (30 хвилин для популярних міст)

### 4. Routes

**Файл:** `routes/api.php`

```php
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/search', [CityController::class, 'search']);
Route::post('/cities/nearest', [CityController::class, 'nearest']);
```

---

## 🎨 Frontend Requirements (UI/UX)

### Layout & Structure

```
┌─────────────────────────────────────┐
│ 📍 Виберіть місто...        [🎯]   │  ← Input Field + Button
├─────────────────────────────────────┤
│ ✓ Вся Україна                       │  ← Спеціальні опції
│ 🌐 Дистанційно                      │
│ ─────────────────────────────────── │  ← Separator
│ ПОПУЛЯРНІ МІСТА                     │  ← Заголовок секції
│ • Київ (м. Київ)                    │
│ • Харків (Харківська обл.)          │
│ • Львів (Львівська обл.)            │
│ ─────────────────────────────────── │  ← Separator (якщо є результати)
│ РЕЗУЛЬТАТИ ПОШУКУ                   │  ← Заголовок секції (якщо є запит)
│ • Миколаїв (Миколаївська обл.)      │
│ • Миргород (Полтавська обл.)        │
└─────────────────────────────────────┘
```

### 1. Input Field Component

**Характеристики:**
- Placeholder: "Виберіть місто або регіон..."
- SVG іконка локації зліва (можна використати Heroicons)
- Поле з минімальною ширина 300px
- Border-radius: md
- На фокусі - dropdown з'являється
- Дебаунс пошуку: 300ms
- Loading state - spinner під час пошуку

### 2. Dropdown Component

**Характеристики:**
- `position: absolute` з правильним z-index
- `top: 100%` (відносно батька)
- Тінь: `shadow-lg`
- Max-height: 400px з overflow-y-auto
- Width: 100% від input
- Border: 1px solid gray-300
- Background: white з opacity
- Закривається при: Esc, click outside, вибір опції
- Максимум 10 опцій на сторінку (для популярних) + 10 для пошуку

### 3. Групування результатів

**Спеціальні опції** (завжди першими):
```
☑ Вся Україна
🌐 Дистанційно
```

**Популярні міста** (якщо запит порожній):
```
[ПОПУЛЯРНІ МІСТА]
• Київ (м. Київ)
• Харків (Харківська обл.)
• Львів (Львівська обл.)
• Одеса (Одеська обл.)
• Запоріжжя (Запоріжька обл.)
```

**Результати пошуку** (якщо є запит):
```
[РЕЗУЛЬТАТИ ПОШУКУ]
• Миколаїв (Миколаївська обл.)
• Миргород (Полтавська обл.)
```

**Empty State:**
```
[Жодного міста не знайдено]
```

### 4. Keyboard Navigation

- **↓ / ↑** - навігація по опціях (highlight)
- **Enter** - вибір помітленої опції
- **Esc** - закриття dropdown
- **Home / End** - перехід на першу/останню опцію
- **Backspace** - очищення поля (на пустому вводі)

### 5. Геолокація («Визначити місто»)

**Кнопка:**
- Розміщення: праворуч від input або поряд
- Іконка: 📍 або 🎯
- На click: запитує дозвіл користувача на геолокацію
- Loading state під час отримання координат
- Ошибка: "Невдалося визначити місце розташування"
- На успіх: автоматичний вибір найближчого міста

**JS Logic:**
```javascript
// navigator.geolocation.getCurrentPosition()
// - Send POST /api/cities/nearest with {latitude, longitude}
// - Server повертає найближче місто в радіусі ~50км
// - Автоматичний вибір або показ варіантів на вибір
```

---

## 🎨 Стилізація (Tailwind CSS)

### Color Scheme (Work.ua style)

```
Primary: blue-600 (#2563eb)
Secondary: gray-500 (#6b7280)
Hover: blue-50 (#eff6ff)
Selected: blue-100 (#dbeafe)
Border: gray-300 (#d1d5db)
Text: gray-900 (#111827)
```

### Component Classes

```
Input Field:
  - px-4 py-2.5 pl-10
  - border border-gray-300 rounded-md
  - focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent
  - text-sm

Dropdown:
  - absolute z-50 mt-1 w-full
  - bg-white rounded-md shadow-lg border border-gray-300
  - divide-y divide-gray-200

Option Item:
  - px-4 py-2.5 cursor-pointer transition
  - hover:bg-blue-50
  - data-selected:bg-blue-100 data-selected:text-blue-900

Section Header:
  - px-4 py-2 text-xs font-semibold
  - text-gray-600 uppercase tracking-wider
  - bg-gray-50
```

---

## 📱 Component Implementation (Livewire)

### Структура файлів:

```
app/
├── Livewire/
│   └── CitySearch.php

resources/
├── views/
│   ├── livewire/
│   │   └── city-search.blade.php
│   └── components/
│       └── svg-location-icon.blade.php
```

### Livewire Component (app/Livewire/CitySearch.php)

**Properties:**
- `$query` (string) - пошуковий запит
- `$results` (array) - результати пошуку
- `$selectedIndex` (int) - індекс помітленої опції
- `$isOpen` (bool) - чи открыт dropdown
- `$selectedCity` (array|null) - вибране місто
- `$isLoadingGeolocation` (bool) - стан геолокації
- `$geolocationError` (string|null) - помилка геолокації

**Методи:**
- `updatedQuery()` - на зміну $query (debounce 300ms)
- `selectCity($cityId)` - вибір міста
- `navigateResults($direction)` - keyboard navigation
- `handleKeyDown($key)` - обробка клавіш
- `requestGeolocation()` - запит на геолокацію
- `nearestCity($lat, $lng)` - AJAX запит на сервер

### Blade Template (resources/views/livewire/city-search.blade.php)

**Структура:**
```html
<div class="relative w-full">
  <!-- Input Field -->
  <div class="relative">
    @svg('location-icon', class="absolute left-3 top-2.5 w-5 h-5 text-gray-400")
    <input
      type="text"
      wire:model="query"
      wire:keydown="handleKeyDown($event.key)"
      @focus="$dispatch('open-dropdown')"
      placeholder="Виберіть місто або регіон..."
      class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600"
    >
    <!-- Geolocation Button -->
    <button
      wire:click="requestGeolocation"
      wire:loading.attr="disabled"
      type="button"
      class="absolute right-3 top-2.5 text-gray-500 hover:text-blue-600 transition"
      title="Визначити місто"
    >
      @if($isLoadingGeolocation)
        <svg class="animate-spin h-5 w-5" ...></svg>
      @else
        📍
      @endif
    </button>
  </div>

  <!-- Dropdown -->
  @if($isOpen)
  <div class="absolute z-50 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-300">
    <!-- Special Options -->
    <div class="divide-y divide-gray-200">
      <button wire:click="selectCity('all')" class="w-full text-left px-4 py-2.5 hover:bg-blue-50">
        ☑ Вся Україна
      </button>
      <button wire:click="selectCity('remote')" class="w-full text-left px-4 py-2.5 hover:bg-blue-50">
        🌐 Дистанційно
      </button>
    </div>

    <!-- Popular Cities Section -->
    @if(!$query && count($results['popular']) > 0)
    <div class="divide-y divide-gray-200">
      <div class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
        Популярні міста
      </div>
      @foreach($results['popular'] as $index => $city)
        <button
          wire:click="selectCity({{ $city['id'] }})"
          @class([
            'w-full text-left px-4 py-2.5 hover:bg-blue-50 transition',
            'bg-blue-100 text-blue-900' => $selectedIndex === ($index + 2)
          ])
        >
          {{ $city['name_uk'] }} @if($city['region'])<span class="text-gray-500">({{ $city['region'] }})</span>@endif
        </button>
      @endforeach
    </div>
    @endif

    <!-- Search Results Section -->
    @if($query && count($results['search']) > 0)
    <div class="divide-y divide-gray-200">
      <div class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase bg-gray-50">
        Результати пошуку
      </div>
      @foreach($results['search'] as $index => $city)
        <button
          wire:click="selectCity({{ $city['id'] }})"
          @class([
            'w-full text-left px-4 py-2.5 hover:bg-blue-50 transition',
            'bg-blue-100 text-blue-900' => $selectedIndex === (count($results['popular']) + $index + 2)
          ])
        >
          {{ $city['name_uk'] }} @if($city['region'])<span class="text-gray-500">({{ $city['region'] }})</span>@endif
        </button>
      @endforeach
    </div>
    @endif

    <!-- Empty State -->
    @if($query && count($results['search']) === 0)
    <div class="px-4 py-3 text-center text-gray-500 text-sm">
      Жодного міста не знайдено
    </div>
    @endif

    <!-- Geolocation Error -->
    @if($geolocationError)
    <div class="px-4 py-2 text-sm text-red-600 bg-red-50">
      ⚠️ {{ $geolocationError }}
    </div>
    @endif
  </div>
  @endif

  <!-- Selected City Display -->
  @if($selectedCity)
  <div class="mt-2 p-3 bg-blue-50 rounded border border-blue-200">
    <p class="text-sm font-medium text-blue-900">
      ✓ Вибрано: <strong>{{ $selectedCity['name_uk'] }}</strong>
      @if($selectedCity['region']) ({{ $selectedCity['region'] }}) @endif
    </p>
  </div>
  @endif

  <!-- Hidden Input (for form submission) -->
  <input type="hidden" name="city_id" value="{{ $selectedCity['id'] ?? '' }}">
</div>
```

---

## 🔧 Backend Code (Controller)

### app/Http/Controllers/CityController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CityController extends Controller
{
    /**
     * Отримати популярні міста
     */
    public function index(): JsonResponse
    {
        $cities = Cache::remember('popular_cities', now()->addMinutes(30), function () {
            return City::where('is_popular', true)
                ->orderBy('population', 'desc')
                ->limit(10)
                ->get(['id', 'name_uk', 'region', 'is_popular'])
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    /**
     * Пошук міст за запитом
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        // Валідація: мінімум 2 символи
        if (strlen(trim($query)) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Запит має містити мінімум 2 символи',
            ], 422);
        }

        $results = City::where('name_uk', 'LIKE', "%{$query}%")
            ->orWhere('region', 'LIKE', "%{$query}%")
            ->orderByRaw('CASE WHEN is_popular = 1 THEN 0 ELSE 1 END')
            ->orderByRaw('MATCH(name_uk) AGAINST(? IN BOOLEAN MODE)', [$query])
            ->orderBy('name_uk')
            ->limit(20)
            ->get(['id', 'name_uk', 'region', 'is_popular'])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Пошук найближчого міста за координатами (Haversine Formula)
     */
    public function nearest(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        // Haversine Formula SQL (距離 в км)
        $city = City::selectRaw(
            '*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance',
            [$latitude, $longitude, $latitude]
        )
            ->orderBy('distance')
            ->first();

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'Міст не знайдено поблизу',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $city->id,
                'name_uk' => $city->name_uk,
                'region' => $city->region,
                'distance' => round($city->distance, 2),
            ],
        ]);
    }
}
```

---

## 🗄️ Database Migration

### database/migrations/XXXX_XX_XX_XXXXXX_create_cities_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name_uk')->unique();
            $table->string('region')->nullable()->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            $table->boolean('is_popular')->default(false)->index();
            $table->string('slug')->unique()->nullable();
            $table->integer('population')->nullable();
            $table->timestamps();

            // Додаткові індекси
            $table->index('name_uk');
            $table->index(['is_popular', 'population']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
```

---

## 📊 Database Seeder

### database/seeders/CitySeeder.php

```php
<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            // Популярні міста
            ['name_uk' => 'Київ', 'region' => 'м. Київ', 'latitude' => 50.4501, 'longitude' => 30.5234, 'is_popular' => true, 'population' => 2884000],
            ['name_uk' => 'Харків', 'region' => 'Харківська обл.', 'latitude' => 50.0039, 'longitude' => 36.2304, 'is_popular' => true, 'population' => 1419000],
            ['name_uk' => 'Львів', 'region' => 'Львівська обл.', 'latitude' => 49.8397, 'longitude' => 24.0297, 'is_popular' => true, 'population' => 757000],
            ['name_uk' => 'Одеса', 'region' => 'Одеська обл.', 'latitude' => 46.4856, 'longitude' => 30.7326, 'is_popular' => true, 'population' => 1009000],
            ['name_uk' => 'Запоріжжя', 'region' => 'Запоріжька обл.', 'latitude' => 47.8388, 'longitude' => 35.1394, 'is_popular' => true, 'population' => 722000],
            
            // Інші міста
            ['name_uk' => 'Миколаїв', 'region' => 'Миколаївська обл.', 'latitude' => 46.9769, 'longitude' => 31.9696, 'is_popular' => false, 'population' => 479000],
            ['name_uk' => 'Миргород', 'region' => 'Полтавська обл.', 'latitude' => 50.1640, 'longitude' => 33.6670, 'is_popular' => false, 'population' => 42000],
            // ... додай більше міст за потребою
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(
                ['name_uk' => $city['name_uk']],
                array_merge($city, ['slug' => str($city['name_uk'])->slug()])
            );
        }
    }
}
```

---

## 🚀 JavaScript для геолокації (без Livewire)

Якщо використовуєш Vanilla JS + Axios:

```javascript
// resources/js/city-search.js

class CitySearch {
    constructor(inputSelector, dropdownSelector) {
        this.input = document.querySelector(inputSelector);
        this.dropdown = document.querySelector(dropdownSelector);
        this.selectedIndex = -1;
        this.debounceTimer = null;
        
        this.init();
    }

    init() {
        this.input.addEventListener('input', (e) => this.onInputChange(e));
        this.input.addEventListener('keydown', (e) => this.onKeyDown(e));
        this.input.addEventListener('focus', () => this.openDropdown());
        document.addEventListener('click', (e) => this.onDocumentClick(e));
    }

    onInputChange(e) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.searchCities(e.target.value);
        }, 300);
    }

    async searchCities(query) {
        if (query.length < 2) {
            this.showPopularCities();
            return;
        }

        try {
            const response = await axios.get('/api/cities/search', { params: { q: query } });
            this.displayResults(response.data.data);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    async requestGeolocation() {
        if (!navigator.geolocation) {
            alert('Геолокація не підтримується вашим браузером');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => this.onGeolocationSuccess(position),
            (error) => this.onGeolocationError(error)
        );
    }

    async onGeolocationSuccess(position) {
        const { latitude, longitude } = position.coords;

        try {
            const response = await axios.post('/api/cities/nearest', { latitude, longitude });
            this.selectCity(response.data.data);
        } catch (error) {
            console.error('Nearest city error:', error);
        }
    }

    onGeolocationError(error) {
        console.error('Geolocation error:', error);
    }

    onKeyDown(e) {
        const items = this.dropdown.querySelectorAll('[data-city-id]');

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.highlightOption();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.highlightOption();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    const cityId = items[this.selectedIndex].dataset.cityId;
                    this.selectCity(cityId);
                }
                break;
            case 'Escape':
                this.closeDropdown();
                break;
        }
    }

    selectCity(city) {
        this.input.value = city.name_uk;
        this.closeDropdown();
        // Dispatch custom event або updateUI
    }

    openDropdown() {
        this.dropdown.classList.remove('hidden');
    }

    closeDropdown() {
        this.dropdown.classList.add('hidden');
        this.selectedIndex = -1;
    }

    onDocumentClick(e) {
        if (!this.input.contains(e.target) && !this.dropdown.contains(e.target)) {
            this.closeDropdown();
        }
    }

    highlightOption() {
        const items = this.dropdown.querySelectorAll('[data-city-id]');
        items.forEach((item, index) => {
            item.classList.toggle('bg-blue-100', index === this.selectedIndex);
        });
    }

    displayResults(cities) {
        // Render results динамічно
    }

    showPopularCities() {
        // Render популярні міста
    }
}

// Ініціалізація
document.addEventListener('DOMContentLoaded', () => {
    new CitySearch('#city-search-input', '#city-search-dropdown');
});
```

---

## ✅ Чек-лист реалізації

- [ ] **Backend:**
  - [ ] Написати міграцію `cities` таблиці
  - [ ] Написати Model `City` з методами пошуку
  - [ ] Написати `CityController` з 3 методами (index, search, nearest)
  - [ ] Зареєструвати routes в `api.php`
  - [ ] Написати `CitySeeder` з даними міст

- [ ] **Frontend (Livewire):**
  - [ ] Створити Livewire компонент `CitySearch`
  - [ ] Написати Blade template з UI
  - [ ] Реалізувати keyboard navigation
  - [ ] Реалізувати debounce пошуку
  - [ ] Реалізувати геолокацію
  - [ ] Додати Tailwind CSS стилізацію

- [ ] **Testing:**
  - [ ] Протестувати пошук міст
  - [ ] Протестувати keyboard navigation
  - [ ] Протестувати геолокацію в різних браузерах
  - [ ] Перевірити accessibility (ARIA labels)
  - [ ] Перевірити mobile responsiveness

- [ ] **Оптимізація:**
  - [ ] Додати кешування популярних міст
  - [ ] Додати rate limiting для API
  - [ ] Оптимізувати SQL запити
  - [ ] Мініфікувати JS/CSS

---

## 📚 Посилання

- **Tailwind CSS:** https://tailwindcss.com/docs
- **Livewire Documentation:** https://livewire.laravel.com
- **Laravel Eloquent ORM:** https://laravel.com/docs/eloquent
- **Haversine Formula:** https://en.wikipedia.org/wiki/Haversine_formula
- **Geolocation API:** https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API
- **Work.ua Inspiration:** https://work.ua/ru/jobs/

---

## 🎯 Додаткові фіч (для майбутнього)

- [ ] Сохранение історії пошуку (localStorage)
- [ ] Улюблені міста (Favorites)
- [ ] Фільтрація за регіонами
- [ ] API для інтеграції з Google Maps
- [ ] Multi-select для синхронізації з job filters
- [ ] PWA support для offline режиму

---

**Версія:** 1.0  
**Останнє оновлення:** Квітень 2026  
**Статус:** ✅ Готово до реалізації
