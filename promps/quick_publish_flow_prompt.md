# Quick Publish Flow (QPF) — Claude Code Prompt v2.0

## 🎯 ЗАВДАННЯ

Реалізувати модальне вікно швидкого розміщення вакансії для платформи My Job.

**Ціль:** дозволити роботодавцям (авторизованим) розмістити вакансію за 30-40 секунд через modal з 4 полями.

---

## 📋 СПЕЦИФІКАЦІЯ ФОРМИ

### Поля (ОБОВ'ЯЗКОВІ)
1. **Назва посади** (`title`)
   - Type: text input
   - Required: ✅ Так
   - Min length: 5
   - Max length: 255
   - Placeholder: "Наприклад: Senior Developer"

2. **Категорія** (`category_id`)
   - Type: `<select>` (звичайний dropdown)
   - Required: ✅ Так
   - Data: Всі категорії з таблиці `categories`
   - Default: "— Виберіть категорію"
   - Label: "Категорія"

3. **Місто** (`city_id`)
   - Type: Livewire компонент `<livewire:city-search wire:model="city_id" />`
   - Required: ✅ Так
   - NOTE: Використати готовий CitySearch компонент з проекту

4. **Зарплата (від)** (`salary_from`)
   - Type: number input
   - Required: ❌ Ні (опціонально)
   - Min value: 100
   - Max value: 999999
   - Placeholder: "Від (грн)"
   - Helper text: "Опціонально. Вакансії зі зарплатою отримують більше відповідей."

### Кнопка
- Label: "🚀 Розмістити вакансію"
- Type: submit button
- Disabled state: Коли не заповнені 3 обов'язкові поля (title, category_id, city_id)
- Loading state: Показати спінер під час відправки

---

## 🏗️ КОМПОНЕНТ STRUCTURE (Livewire)

### Назва файлу
```
app/Livewire/Employer/QuickPublishForm.php
```

### Namespace
```php
namespace App\Livewire\Employer;
```

### Методи та властивості
```php
class QuickPublishForm extends Component {
    // Public properties
    public bool $show = false;
    public string $title = '';
    public ?int $category_id = null;
    public ?int $city_id = null;
    public ?int $salary_from = null;
    
    // Livewire listeners
    #[On('open-quick-publish')]
    public function open(): void {
        $this->show = true;
    }
    
    // Methods
    public function mount() { }
    public function publish() { }
    public function render() { }
    public function resetForm(): void { }
}
```

### Alpine.js Integration
Modal керується через Alpine.js:
```html
<div x-show="$wire.show" @click.away="$wire.show = false">
    <!-- Modal content -->
</div>
```

---

## 🔐 VALIDATION

```php
// Rules
$rules = [
    'title' => 'required|string|min:5|max:255',
    'category_id' => 'required|exists:categories,id',
    'city_id' => 'required|exists:cities,id',
    'salary_from' => 'nullable|integer|min:100|max:999999',
];

// Messages (українська)
$messages = [
    'title.required' => 'Введіть назву посади',
    'title.min' => 'Назва посади повинна містити мінімум 5 символів',
    'title.max' => 'Назва посади не може перевищувати 255 символів',
    'category_id.required' => 'Виберіть категорію',
    'category_id.exists' => 'Вибрана категорія не існує',
    'city_id.required' => 'Виберіть місто',
    'city_id.exists' => 'Вибране місто не існує',
    'salary_from.integer' => 'Зарплата повинна бути числом',
    'salary_from.min' => 'Зарплата мінімум 100 грн',
    'salary_from.max' => 'Зарплата не може перевищувати 999999 грн',
];
```

---

## 📤 LIVEWIRE METHOD (publish)

```php
public function publish() {
    // Validate
    $validated = $this->validate([
        'title' => 'required|string|min:5|max:255',
        'category_id' => 'required|exists:categories,id',
        'city_id' => 'required|exists:cities,id',
        'salary_from' => 'nullable|integer|min:100|max:999999',
    ]);
    
    // Store in session for post-login creation
    session(['pending_vacancy' => $validated]);
    
    // Redirect to login
    return $this->redirect(route('login'), navigate: true);
}

public function resetForm(): void {
    $this->title = '';
    $this->category_id = null;
    $this->city_id = null;
    $this->salary_from = null;
    $this->show = false;
}
```

---

## 🔗 AUTH FLOW (Post-Login)

### Місце реалізації
File: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

### Logic в authenticated() або store()
```php
protected function authenticated(Request $request, $user) {
    // Check if pending_vacancy exists in session
    if ($request->session()->has('pending_vacancy')) {
        $pendingData = $request->session()->pull('pending_vacancy');
        
        // Create vacancy for authenticated user
        $vacancy = Vacancy::create([
            'title' => $pendingData['title'],
            'category_id' => $pendingData['category_id'],
            'city_id' => $pendingData['city_id'],
            'salary_from' => $pendingData['salary_from'] ?? null,
            'company_id' => $user->company_id, // or auth()->user()->company_id
            
            // Auto-filled defaults
            'salary_to' => null,
            'currency' => 'UAH',
            'description' => null,
            'languages' => [],
            'suitability' => [],
            'employment_type' => json_encode(['full-time']), // або залежно від структури
            'is_active' => true,
            'is_featured' => false,
            'is_hot' => false,
        ]);
        
        // Redirect to edit form
        return redirect()->route('employer.vacancies.edit', $vacancy);
    }
    
    // Default redirect
    return redirect()->intended(route('employer.vacancies.index'));
}
```

---

## 🎨 VIEW (Blade + Livewire + Tailwind CSS)

### File
```
resources/views/livewire/employer/quick-publish-form.blade.php
```

### Structure (Full HTML)
```html
<!-- Modal Overlay -->
<div 
    x-show="$wire.show"
    @click.away="$wire.show = false"
    class="fixed inset-0 bg-black/50 z-40 flex items-center justify-center"
    style="display: none;"
>
    <!-- Modal Container -->
    <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-md mx-4">
        
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">🚀 Швидко розмістити вакансію</h2>
            <p class="text-gray-600 text-sm mt-1">За 30 секунд без складнощів</p>
        </div>
        
        <!-- Form -->
        <form wire:submit="publish" class="space-y-4">
            
            <!-- Title Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Назва посади <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    wire:model="title"
                    placeholder="Наприклад: Senior Developer"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
                @error('title')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            
            <!-- Category Select -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Категорія <span class="text-red-500">*</span>
                </label>
                <select 
                    wire:model="category_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
                    <option value="">— Виберіть категорію</option>
                    @foreach($this->getCategories() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            
            <!-- City Search Component -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Місто <span class="text-red-500">*</span>
                </label>
                <livewire:city-search wire:model="city_id" />
                @error('city_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            
            <!-- Salary Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Зарплата (від)
                </label>
                <input 
                    type="number" 
                    wire:model="salary_from"
                    placeholder="Від (грн)"
                    min="100"
                    max="999999"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
                <p class="text-gray-500 text-xs mt-1">
                    Опціонально. Вакансії зі зарплатою отримують більше відповідей.
                </p>
                @error('salary_from')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-3 mt-6 pt-4 border-t">
                <button 
                    type="button"
                    @click="$wire.show = false"
                    class="flex-1 px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium"
                >
                    Закрити
                </button>
                <button 
                    type="submit"
                    :disabled="!$wire.title || !$wire.category_id || !$wire.city_id || $wire.loading"
                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition font-bold flex items-center justify-center gap-2"
                >
                    @if($this->loading ?? false)
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                        </svg>
                        Обробка...
                    @else
                        🚀 Розмістити
                    @endif
                </button>
            </div>
            
        </form>
        
    </div>
</div>
```

---

## 📊 DATABASE

### Таблиця: `vacancies` (вже існує)

Поля, які використовуються в QPF:
- ✅ `id` (BIGINT)
- ✅ `title` (VARCHAR)
- ✅ `category_id` (BIGINT, FK to categories)
- ✅ `city_id` (BIGINT, FK to cities)
- ✅ `salary_from` (INT, nullable)
- ✅ `company_id` (BIGINT, FK to companies)

### Значення за замовчуванням (при створенні через QPF)
```php
'salary_to' => null,
'currency' => 'UAH',
'description' => null,
'languages' => [],
'suitability' => [],
'employment_type' => json_encode(['full-time']),
'is_active' => true,
'is_featured' => false,
'is_hot' => false,
```

**Примітка:** Нові поля (`status`, `quality_score`, `created_via`) НЕ додаються. QPF використовує існуючу схему `vacancies`.

---

## 🔗 ROUTING & LAYOUT INTEGRATION

### Navbar Button (layouts/app.blade.php)
```html
<!-- В Navbar компоненті -->
<button 
    @click="$dispatch('open-quick-publish')"
    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition"
>
    🚀 Опублікувати
</button>
```

### Global Modal Integration (layouts/app.blade.php)
```html
<!-- Перед </body> -->
<livewire:employer.quick-publish-form />
```

### Alpine.js Data
Modal слухає Livewire еvent через Alpine:
```php
#[On('open-quick-publish')]
public function open(): void {
    $this->show = true;
}
```

---

## 📲 COMPLETE FLOW (From Modal to Vacancy Created)

### Step 1: User у modal
```
User: Заповнює title, category_id, city_id, salary_from
User: Клікає "🚀 Розмістити"
```

### Step 2: Livewire publish() method виконується
```
- Validate дані
- Store в session: session(['pending_vacancy' => $validated])
- Reset form & close modal
- Redirect до /login з navigate: true
```

### Step 3: User на /login сторінці
```
Користувач вводить email/password (або логіниться через auth методи)
```

### Step 4: POST-LOGIN в AuthenticatedSessionController.store()
```
- Check if session('pending_vacancy') exists
- Якщо так: create Vacancy з даними з session
- Pull з session (видалити дані)
- Redirect до /employer/vacancies/{id}/edit
- Якщо ні: Redirect до /employer/vacancies/index
```

### Step 5: Vacancy created, User редиректиться на форму редагування
```
URL: /employer/vacancies/{id}/edit
User видить форму з заповненими 4 полями + можливість додати решту даних
```

---

## 📊 FLOW DIAGRAM

```
┌─────────────────────────────┐
│ Navbar: "🚀 Опублікувати"   │
└──────────────┬──────────────┘
               │ @click="$dispatch('open-quick-publish')"
               ▼
┌─────────────────────────────────┐
│ Modal QuickPublishForm           │
│ - title                         │
│ - category_id                   │
│ - city_id                       │
│ - salary_from                   │
│ [Розмістити] [Закрити]         │
└──────────────┬──────────────────┘
               │ wire:submit="publish"
               ▼
┌─────────────────────────────────┐
│ Livewire: publish()             │
│ - Validate                      │
│ - session(['pending_vacancy'])  │
│ - redirect(route('login'))      │
└──────────────┬──────────────────┘
               │ navigate: true
               ▼
┌─────────────────────────────────┐
│ /login (Auth Page)              │
│ User: email, password           │
│ OR: Telegram | Google | Email   │
└──────────────┬──────────────────┘
               │ Form submit
               ▼
┌─────────────────────────────────┐
│ AuthenticatedSessionController  │
│ - store()                       │
│ - Check session('pending_vacancy')
│ - Create Vacancy in DB          │
│ - Redirect to edit form         │
└──────────────┬──────────────────┘
               │ 
               ▼
┌─────────────────────────────────┐
│ /employer/vacancies/{id}/edit   │
│ Vacancy created & ready to edit │
└─────────────────────────────────┘
```

---

## ✅ CHECKLIST ДЛЯ РЕАЛІЗАЦІЇ

- [ ] Livewire компонент `app/Livewire/Employer/QuickPublishForm.php`
- [ ] Blade view `resources/views/livewire/employer/quick-publish-form.blade.php`
- [ ] Navbar button з `@click="$dispatch('open-quick-publish')"`
- [ ] Global modal integration в `layouts/app.blade.php` (перед `</body>`)
- [ ] Validation rules + error messages (українська)
- [ ] `publish()` метод у компоненті (session + redirect)
- [ ] `resetForm()` метод для очищення
- [ ] `getCategories()` метод у компоненті (для select dropdown)
- [ ] Integration в `AuthenticatedSessionController.store()` (post-login logic)
- [ ] Vacancy creation з defaults при успішному login
- [ ] Test для Livewire компонента (form validation)
- [ ] Test для auth flow (session preservation)
- [ ] UI polish (Tailwind, accessibility, mobile responsive)

---

## 📝 NOTES & ВАЖЛИВІ ДЕТАЛІ

### Backend
- **Auth Controller:** Додати logic в `store()` для перевірки `session('pending_vacancy')`
- **Vacancy Model:** Переконатися, що поле `company_id` обов'язкове
- **Session:** Використовувати `session()->pull()` щоб видалити дані після создания
- **Redirect:** Передавати `Vacancy` object у route для редагування

### Frontend
- **Alpine.js:** Modal контролюється через `x-show="$wire.show"`
- **CitySearch:** Вже готовий компонент, просто вставити `<livewire:city-search wire:model="city_id" />`
- **Disabled Button:** Залежить від `!$wire.title || !$wire.category_id || !$wire.city_id`
- **Navigate:** Використовувати `navigate: true` в redirect для smooth переходу

### UX
- Modal відкривається з Navbar кнопки
- Помилки валідації показуються під кожним полем
- Кнопка "Закрити" закриває modal без submit
- Після redirect на /login — дані зберігаються в session

### No Migration Needed
- ❌ Не додавати нові поля в `vacancies` таблицю
- ✅ Використовувати існуючі поля: `title`, `category_id`, `city_id`, `salary_from`, `company_id`
- ✅ Defaults встановлюються у code, не у database
