# 🎯 Промпт: Розробка кабінету Шукача (Job Seeker) - Claude Code

## ✅ Мета проекту
Створити веб-додаток **кабінету Шукача (Job Seeker)** для платформи **My Job**, який буде **дзеркальною копією** бек-офісу роботодавця, але з перспективи пошукача роботи. Система повинна мати **реальну синхронізацію статусів** між Шукачем та Роботодавцем.

---

## 📋 ЕТАП 1: SETUP & АРХІТЕКТУРА

### 1.1 Стек технологій
- **Backend**: Laravel 11 + Livewire 3
- **Frontend**: Blade templates + Alpine.js + Tailwind CSS
- **Database**: PostgreSQL (вже існує)
- **Real-time**: WebSockets (Laravel Echo + Pusher або SupervisorD)
- **API**: RESTful з JSON responses
- **Authentication**: JWT tokens (вже реалізовано)

### 1.2 Основні моделі (вже повинні існувати)
```php
// Моделі, які необхідні:
- User (з role: seeker)
- Vacancy (вакансія від роботодавця)
- Application (заявка Шукача на вакансію)
- Interview (запланована собесіда)
- Notification (сповіщення)
- SeekerProfile (профіль Шукача)
- SeekerResume (резюме Шукача)
- Message (повідомлення між Шукачем і Роботодавцем)
```

### 1.3 Структура проекту в Laravel
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Seeker/
│   │   │   ├── DashboardController.php
│   │   │   ├── ApplicationController.php
│   │   │   ├── InterviewController.php
│   │   │   ├── VacancyController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── ResumeController.php
│   │   │   └── NotificationController.php
│   │   └── Webhook/
│   │       └── SeekerWebhookController.php
│   │
│   └── Livewire/
│       ├── Seeker/
│       │   ├── DashboardComponent.php
│       │   ├── ApplicationsList.php
│       │   ├── ApplicationDetail.php
│       │   ├── InterviewsCalendar.php
│       │   ├── VacanciesSearch.php
│       │   ├── NotificationCenter.php
│       │   └── ProfileForm.php
│
├── Models/
│   ├── Application.php
│   ├── Interview.php
│   ├── SeekerProfile.php
│   ├── SeekerResume.php
│   └── Notification.php
│
├── Services/
│   ├── SeekerService.php
│   ├── ApplicationService.php
│   ├── InterviewService.php
│   └── NotificationService.php
│
├── Events/
│   ├── ApplicationStatusChanged.php
│   ├── InterviewScheduled.php
│   ├── OfferCreated.php
│   └── MessageReceived.php
│
├── Listeners/
│   ├── NotifySeekerOfStatusChange.php
│   ├── NotifySeekerOfInterview.php
│   ├── NotifySeekerOfOffer.php
│   └── NotifySeekerOfMessage.php
│
└── Jobs/
    ├── SendSeekerNotification.php
    ├── UpdateApplicationStatus.php
    └── SyncInterviewData.php

resources/views/
├── seeker/
│   ├── dashboard.blade.php
│   ├── applications/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── interviews/
│   │   ├── index.blade.php
│   │   └── calendar.blade.php
│   ├── vacancies/
│   │   ├── search.blade.php
│   │   └── show.blade.php
│   ├── profile/
│   │   ├── edit.blade.php
│   │   └── resume.blade.php
│   ├── notifications/
│   │   └── index.blade.php
│   └── settings/
│       └── index.blade.php
│
└── livewire/
    ├── seeker/
    │   ├── dashboard-component.blade.php
    │   ├── applications-list.blade.php
    │   ├── application-detail.blade.php
    │   ├── interviews-calendar.blade.php
    │   ├── vacancies-search.blade.php
    │   ├── notification-center.blade.php
    │   └── profile-form.blade.php

routes/
├── seeker.php (новий файл)
└── webhooks.php (оновити)

database/
├── migrations/
│   ├── create_seeker_profiles_table.php
│   ├── create_seeker_resumes_table.php
│   ├── create_notifications_table.php
│   └── ...інші
│
└── factories/
    ├── SeekerProfileFactory.php
    └── SeekerResumeFactory.php
```

---

## 🎨 ЕТАП 2: КОМПОНЕНТИ LIVEWIRE

### 2.1 DashboardComponent (Головна панель)
**Місцезнаходження:** `app/Http/Livewire/Seeker/DashboardComponent.php`

**Функції:**
```php
// Properties
public $statistics = [];
public $recentActivity = [];
public $upcomingInterviews = [];
public $recommendedVacancies = [];
public $userId;

// Methods
public function mount()
{
    // Завантажити статистику
    $this->statistics = [
        'total_applications' => Auth::user()->applications()->count(),
        'active_applications' => Auth::user()->applications()->whereIn('status', ['viewed', 'screening', 'testing'])->count(),
        'upcoming_interviews' => Auth::user()->interviews()->where('date', '>=', now())->count(),
        'received_offers' => Auth::user()->applications()->where('status', 'offer')->count(),
        'rejections' => Auth::user()->applications()->where('status', 'rejected')->count(),
    ];
    
    // Недавня активність (timeline)
    $this->recentActivity = Auth::user()->notifications()->latest()->take(10)->get();
    
    // Майбутні собесіди
    $this->upcomingInterviews = Auth::user()->interviews()
        ->where('date', '>=', now())
        ->where('date', '<=', now()->addDays(7))
        ->orderBy('date')
        ->get();
    
    // Рекомендовані вакансії
    $this->recommendedVacancies = $this->getRecommendedVacancies();
}

public function getRecommendedVacancies()
{
    // Логіка розумних рекомендацій на основі профілю
    return Vacancy::whereNotIn('id', Auth::user()->applications()->pluck('vacancy_id'))
        ->where('status', 'active')
        ->orderByRelevance(Auth::user()->profile)
        ->take(5)
        ->get();
}

public function render()
{
    return view('livewire.seeker.dashboard-component');
}
```

**View файл:** `resources/views/livewire/seeker/dashboard-component.blade.php`
```blade
<div class="space-y-8">
    {{-- Привіт --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Привіт, {{ auth()->user()->name }}! 👋</h1>
    </div>

    {{-- КАРТКИ СТАТИСТИКИ --}}
    <div class="grid grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Всього заявок</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $statistics['total_applications'] }}</p>
                </div>
                <div class="text-4xl text-blue-500">📊</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">На розгляді</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $statistics['active_applications'] }}</p>
                </div>
                <div class="text-4xl text-yellow-500">🤔</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Собесід</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $statistics['upcoming_interviews'] }}</p>
                </div>
                <div class="text-4xl text-purple-500">📞</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Запрошень</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $statistics['received_offers'] }}</p>
                </div>
                <div class="text-4xl text-green-500">💌</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Відмов</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $statistics['rejections'] }}</p>
                </div>
                <div class="text-4xl text-red-500">❌</div>
            </div>
        </div>
    </div>

    {{-- МАЙБУТНІ СОБЕСІДИ --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">📅 Найближчі собесіди</h2>
        @forelse($upcomingInterviews as $interview)
            <div class="border-l-4 border-blue-500 pl-4 py-3 mb-3">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold">{{ $interview->application->vacancy->title }}</p>
                        <p class="text-sm text-gray-600">{{ $interview->application->vacancy->company->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold">{{ $interview->date->format('d.m.Y') }} | {{ $interview->time->format('H:i') }}</p>
                        <p class="text-sm text-gray-600">
                            @if($interview->type === 'online')
                                📹 Google Meet
                            @elseif($interview->type === 'phone')
                                📞 Телефонна
                            @else
                                📍 Офіс
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 mt-2">
                    <button class="text-sm px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Я готовий</button>
                    @if($interview->type === 'online')
                        <a href="{{ $interview->meeting_link }}" target="_blank" class="text-sm px-3 py-1 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Посилання</a>
                    @endif
                    <button class="text-sm px-3 py-1 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Нагадування</button>
                </div>
            </div>
        @empty
            <p class="text-gray-500">Немає запланованих собесід</p>
        @endforelse
    </div>

    {{-- ОСТАННІ ПОВІДОМЛЕННЯ --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">📨 Останні повідомлення</h2>
        @forelse($recentActivity as $notification)
            <div class="py-3 border-b last:border-b-0">
                <p class="text-sm">{{ $notification->message }}</p>
                <p class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-gray-500">Немає повідомлень</p>
        @endforelse
    </div>

    {{-- РЕКОМЕНДОВАНІ ВАКАНСІЇ --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">🎯 Рекомендовані вакансії</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($recommendedVacancies as $vacancy)
                <div class="border rounded-lg p-4 hover:shadow-lg transition">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-semibold">{{ $vacancy->title }}</p>
                            <p class="text-sm text-gray-600">{{ $vacancy->company->name }}</p>
                        </div>
                        <button class="text-2xl hover:scale-125">❤️</button>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">📍 {{ $vacancy->city }} | 💰 {{ $vacancy->salary_from }} - {{ $vacancy->salary_to }} USD</p>
                    <button class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Подати заявку</button>
                </div>
            @empty
                <p class="text-gray-500">Немає рекомендацій</p>
            @endforelse
        </div>
    </div>
</div>
```

### 2.2 ApplicationsList (Список заявок)
**Місцезнаходження:** `app/Http/Livewire/Seeker/ApplicationsList.php`

**Функції:**
```php
namespace App\Http\Livewire\Seeker;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;

class ApplicationsList extends Component
{
    use WithPagination;

    public $filterStatus = 'all';
    public $sortBy = 'date_desc';
    public $searchQuery = '';
    public $perPage = 20;

    protected $queryString = ['filterStatus', 'sortBy', 'searchQuery'];

    public function getApplications()
    {
        $query = Auth::user()->applications()->with(['vacancy', 'vacancy.company']);

        // Фільтр по статусу
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Пошук
        if ($this->searchQuery) {
            $query->whereHas('vacancy', function ($q) {
                $q->where('title', 'like', '%' . $this->searchQuery . '%')
                  ->orWhereHas('company', function ($q2) {
                      $q2->where('name', 'like', '%' . $this->searchQuery . '%');
                  });
            });
        }

        // Сортування
        match ($this->sortBy) {
            'date_desc' => $query->latest(),
            'date_asc' => $query->oldest(),
            'company' => $query->orderByHas('vacancy.company', 'name'),
            default => $query->latest(),
        };

        return $query->paginate($this->perPage);
    }

    public function getStatistics()
    {
        $total = Auth::user()->applications()->count();
        $statuses = [
            'submitted' => Auth::user()->applications()->where('status', 'submitted')->count(),
            'viewed' => Auth::user()->applications()->where('status', 'viewed')->count(),
            'screening' => Auth::user()->applications()->where('status', 'screening')->count(),
            'testing' => Auth::user()->applications()->where('status', 'testing')->count(),
            'interview' => Auth::user()->applications()->where('status', 'interview')->count(),
            'offer' => Auth::user()->applications()->where('status', 'offer')->count(),
            'rejected' => Auth::user()->applications()->where('status', 'rejected')->count(),
        ];

        return compact('total', 'statuses');
    }

    public function resetFilters()
    {
        $this->filterStatus = 'all';
        $this->sortBy = 'date_desc';
        $this->searchQuery = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.seeker.applications-list', [
            'applications' => $this->getApplications(),
            'statistics' => $this->getStatistics(),
        ]);
    }
}
```

**View файл:** `resources/views/livewire/seeker/applications-list.blade.php`
```blade
<div class="space-y-6">
    {{-- Статистика --}}
    <div class="grid grid-cols-7 gap-2 text-center">
        <div class="bg-blue-50 rounded p-3">
            <p class="text-2xl font-bold text-blue-600">{{ $statistics['total'] }}</p>
            <p class="text-xs text-gray-600">Всього</p>
        </div>
        <div class="bg-yellow-50 rounded p-3">
            <p class="text-2xl font-bold text-yellow-600">{{ $statistics['statuses']['submitted'] }}</p>
            <p class="text-xs text-gray-600">📨</p>
        </div>
        <div class="bg-purple-50 rounded p-3">
            <p class="text-2xl font-bold text-purple-600">{{ $statistics['statuses']['viewed'] }}</p>
            <p class="text-xs text-gray-600">👀</p>
        </div>
        <div class="bg-orange-50 rounded p-3">
            <p class="text-2xl font-bold text-orange-600">{{ $statistics['statuses']['screening'] }}</p>
            <p class="text-xs text-gray-600">🤔</p>
        </div>
        <div class="bg-pink-50 rounded p-3">
            <p class="text-2xl font-bold text-pink-600">{{ $statistics['statuses']['testing'] }}</p>
            <p class="text-xs text-gray-600">🧪</p>
        </div>
        <div class="bg-indigo-50 rounded p-3">
            <p class="text-2xl font-bold text-indigo-600">{{ $statistics['statuses']['interview'] }}</p>
            <p class="text-xs text-gray-600">📞</p>
        </div>
        <div class="bg-green-50 rounded p-3">
            <p class="text-2xl font-bold text-green-600">{{ $statistics['statuses']['offer'] }}</p>
            <p class="text-xs text-gray-600">💌</p>
        </div>
    </div>

    {{-- Фільтри --}}
    <div class="bg-white rounded-lg shadow p-4 flex gap-4">
        <input 
            type="text" 
            placeholder="Пошук..." 
            wire:model.live="searchQuery"
            class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        
        <select wire:model.live="filterStatus" class="px-4 py-2 border rounded-lg focus:outline-none">
            <option value="all">Всі статуси</option>
            <option value="submitted">📨 Подана</option>
            <option value="viewed">👀 Переглянута</option>
            <option value="screening">🤔 На розгляді</option>
            <option value="testing">🧪 Тестування</option>
            <option value="interview">📞 Собесіда</option>
            <option value="offer">💌 Пропозиція</option>
            <option value="rejected">❌ Відмова</option>
        </select>

        <select wire:model.live="sortBy" class="px-4 py-2 border rounded-lg focus:outline-none">
            <option value="date_desc">По даті ↓</option>
            <option value="date_asc">По даті ↑</option>
            <option value="company">По компанії</option>
        </select>

        <button wire:click="resetFilters()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Очистити</button>
    </div>

    {{-- Список заявок --}}
    <div class="space-y-3">
        @forelse($applications as $app)
            <a href="{{ route('seeker.applications.show', $app->id) }}" class="block bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="font-bold text-lg">{{ $app->vacancy->company->name }} - {{ $app->vacancy->title }}</p>
                        <p class="text-sm text-gray-600">📧 Подана: {{ $app->created_at->format('d.m.Y') }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                            @if($app->status === 'submitted') bg-blue-100 text-blue-800
                            @elseif($app->status === 'viewed') bg-purple-100 text-purple-800
                            @elseif($app->status === 'screening') bg-yellow-100 text-yellow-800
                            @elseif($app->status === 'testing') bg-pink-100 text-pink-800
                            @elseif($app->status === 'interview') bg-indigo-100 text-indigo-800
                            @elseif($app->status === 'offer') bg-green-100 text-green-800
                            @elseif($app->status === 'rejected') bg-red-100 text-red-800
                            @endif
                        ">
                            @if($app->status === 'submitted') 📨 Подана
                            @elseif($app->status === 'viewed') 👀 Переглянута
                            @elseif($app->status === 'screening') 🤔 На розгляді
                            @elseif($app->status === 'testing') 🧪 Тестування
                            @elseif($app->status === 'interview') 📞 Собесіда
                            @elseif($app->status === 'offer') 💌 Пропозиція
                            @elseif($app->status === 'rejected') ❌ Відмова
                            @endif
                        </span>
                        @if($app->status === 'interview' && $app->interview)
                            <p class="text-sm text-gray-600 mt-2">📅 {{ $app->interview->date->format('d.m.Y H:i') }}</p>
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <button class="text-sm px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Деталі</button>
                    @if($app->status !== 'rejected' && $app->status !== 'offer')
                        <button class="text-sm px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">Відкликати</button>
                    @endif
                </div>
            </a>
        @empty
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <p class="text-gray-600 text-lg">Ви ще не подали жодної заявки</p>
                <a href="{{ route('seeker.vacancies.search') }}" class="text-blue-500 hover:underline mt-2">Пошукати вакансії →</a>
            </div>
        @endforelse
    </div>

    {{-- Пагінація --}}
    <div class="mt-6">
        {{ $applications->links() }}
    </div>
</div>
```

### 2.3 ApplicationDetail (Деталь заявки)
**Місцезнаходження:** `app/Http/Livewire/Seeker/ApplicationDetail.php`

**Функції:**
```php
namespace App\Http\Livewire\Seeker;

use Livewire\Component;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApplicationDetail extends Component
{
    public $application;
    public $statusHistory = [];
    public $testing = null;
    public $interviews = [];
    public $messages = [];

    public function mount($applicationId)
    {
        $this->application = Application::findOrFail($applicationId);
        
        // Перевірити, що це заявка поточного користувача
        if ($this->application->seeker_id !== Auth::id()) {
            abort(403);
        }

        // Завантажити історію статусів
        $this->statusHistory = $this->application->statusHistories()->get();

        // Завантажити тестування (якщо є)
        $this->testing = $this->application->testing;

        // Завантажити собесіди
        $this->interviews = $this->application->interviews()->get();

        // Завантажити повідомлення
        $this->messages = $this->application->messages()->get();
    }

    public function updateStatus($newStatus)
    {
        // Логіка оновлення статусу
        $this->application->update(['status' => $newStatus]);
        
        // Записати історію
        $this->application->statusHistories()->create([
            'old_status' => $this->application->status,
            'new_status' => $newStatus,
            'changed_by' => 'seeker',
            'reason' => 'Користувач оновив статус',
        ]);

        $this->mount($this->application->id);
    }

    public function withdrawApplication()
    {
        if ($this->application->status !== 'rejected' && $this->application->status !== 'offer') {
            $this->application->update(['status' => 'withdrawn']);
            $this->application->statusHistories()->create([
                'old_status' => $this->application->status,
                'new_status' => 'withdrawn',
                'changed_by' => 'seeker',
                'reason' => 'Шукач відкликав заявку',
            ]);

            session()->flash('message', 'Заявка відкликана');
            $this->mount($this->application->id);
        }
    }

    public function sendMessage($message)
    {
        $this->application->messages()->create([
            'sender_id' => Auth::id(),
            'sender_type' => 'seeker',
            'message' => $message,
        ]);

        $this->mount($this->application->id);
    }

    public function confirmInterview($interviewId)
    {
        $interview = $this->application->interviews()->find($interviewId);
        
        if ($interview) {
            $interview->update(['confirmed_by_seeker' => true]);
            session()->flash('message', 'Ви підтвердили участь у собесіді');
            $this->mount($this->application->id);
        }
    }

    public function render()
    {
        return view('livewire.seeker.application-detail', [
            'application' => $this->application,
            'statusHistory' => $this->statusHistory,
            'testing' => $this->testing,
            'interviews' => $this->interviews,
            'messages' => $this->messages,
        ]);
    }
}
```

**View файл:** `resources/views/livewire/seeker/application-detail.blade.php`
```blade
<div class="max-w-4xl space-y-6">
    {{-- ЗАГОЛОВОК --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-gray-600">Заявка на вакансію</p>
                <h1 class="text-3xl font-bold">{{ $application->vacancy->company->name }} - {{ $application->vacancy->title }}</h1>
            </div>
            <div>
                <span class="inline-block px-4 py-2 rounded-full text-lg font-semibold
                    @if($application->status === 'submitted') bg-blue-100 text-blue-800
                    @elseif($application->status === 'viewed') bg-purple-100 text-purple-800
                    @elseif($application->status === 'screening') bg-yellow-100 text-yellow-800
                    @elseif($application->status === 'testing') bg-pink-100 text-pink-800
                    @elseif($application->status === 'interview') bg-indigo-100 text-indigo-800
                    @elseif($application->status === 'offer') bg-green-100 text-green-800
                    @elseif($application->status === 'rejected') bg-red-100 text-red-800
                    @endif
                ">
                    @if($application->status === 'submitted') 📨 Подана
                    @elseif($application->status === 'viewed') 👀 Переглянута
                    @elseif($application->status === 'screening') 🤔 На розгляді
                    @elseif($application->status === 'testing') 🧪 Тестування
                    @elseif($application->status === 'interview') 📞 Собесіда
                    @elseif($application->status === 'offer') 💌 Пропозиція
                    @elseif($application->status === 'rejected') ❌ Відмова
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- ЛІНІЯ ЧАСУ СТАТУСІВ --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Лінія часу</h2>
        <div class="space-y-3">
            @foreach($statusHistory as $history)
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                        @if(!$loop->last)
                            <div class="w-1 h-12 bg-gray-300"></div>
                        @endif
                    </div>
                    <div>
                        <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $history->new_status)) }}</p>
                        <p class="text-sm text-gray-600">{{ $history->created_at->format('d.m.Y H:i') }}</p>
                        <p class="text-sm text-gray-500">{{ $history->reason }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ТЕСТУВАННЯ --}}
    @if($testing)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">🧪 Тестування</h2>
            <div class="space-y-3">
                <p><strong>Назва:</strong> {{ $testing->name }}</p>
                <p><strong>Статус:</strong> 
                    @if($testing->status === 'not_started') ⏳ Не розпочато
                    @elseif($testing->status === 'in_progress') ⏳ В процесі
                    @elseif($testing->status === 'completed') ✅ Завершено
                    @elseif($testing->status === 'failed') ❌ Не пройдено
                    @endif
                </p>
                <p><strong>Закінчення:</strong> {{ $testing->deadline->format('d.m.Y H:i') }}</p>
                <a href="{{ $testing->link }}" target="_blank" class="text-blue-500 hover:underline">Посилання на тест →</a>
            </div>
        </div>
    @endif

    {{-- СОБЕСІДИ --}}
    @if($interviews->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">📞 Собесіди</h2>
            @foreach($interviews as $interview)
                <div class="border rounded-lg p-4 mb-3">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-semibold">{{ $interview->type }} Round</p>
                            <p class="text-sm text-gray-600">{{ $interview->date->format('d.m.Y H:i') }} ({{ $interview->duration }} хв)</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            @if($interview->confirmed_by_seeker) bg-green-100 text-green-800
                            @else bg-yellow-100 text-yellow-800
                            @endif
                        ">
                            @if($interview->confirmed_by_seeker) ✅ Підтверджена
                            @else ⏳ На розгляді
                            @endif
                        </span>
                    </div>
                    
                    @if($interview->type === 'online')
                        <p class="text-sm mb-3">📹 <a href="{{ $interview->meeting_link }}" target="_blank" class="text-blue-500 hover:underline">{{ $interview->meeting_link }}</a></p>
                    @elseif($interview->type === 'offline')
                        <p class="text-sm mb-3">📍 {{ $interview->location }}</p>
                    @endif

                    @if($interview->interviewer_name)
                        <p class="text-sm text-gray-600">Інтерв'юер: {{ $interview->interviewer_name }}</p>
                    @endif

                    @if($interview->notes)
                        <p class="text-sm text-gray-600 mt-2">📝 {{ $interview->notes }}</p>
                    @endif

                    @if(!$interview->confirmed_by_seeker)
                        <div class="flex gap-2 mt-3">
                            <button wire:click="confirmInterview({{ $interview->id }})" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Я готовий</button>
                            <button class="px-4 py-2 bg-red-100 text-red-600 rounded hover:bg-red-200">Я не можу прийти</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- КОМУНІКАЦІЯ --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">💬 Комунікація</h2>
        <div class="bg-gray-50 rounded-lg p-4 h-64 overflow-y-auto mb-4">
            @forelse($messages as $msg)
                <div class="mb-3">
                    <p class="text-sm font-semibold">
                        @if($msg->sender_type === 'seeker') Ви @else {{ $application->vacancy->company->name }} @endif
                    </p>
                    <p class="text-sm bg-white rounded p-2 mt-1">{{ $msg->message }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $msg->created_at->format('d.m.Y H:i') }}</p>
                </div>
            @empty
                <p class="text-gray-500">Немає повідомлень</p>
            @endforelse
        </div>
        
        <form wire:submit="sendMessage" class="flex gap-2">
            <input type="text" placeholder="Ваше повідомлення..." class="flex-1 px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Відправити</button>
        </form>
    </div>

    {{-- ДІЇ --}}
    <div class="flex gap-3">
        @if($application->status !== 'rejected' && $application->status !== 'offer')
            <button wire:click="withdrawApplication()" class="px-6 py-3 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 font-semibold">
                Відкликати заявку
            </button>
        @endif
        <a href="{{ route('seeker.applications.index') }}" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-semibold">
            Назад до заявок
        </a>
    </div>
</div>
```

---

## 🔌 ЕТАП 3: API ENDPOINTS

### 3.1 Маршрути (Routes)
**Файл:** `routes/seeker.php` (новий файл)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Seeker\DashboardController;
use App\Http\Controllers\Seeker\ApplicationController;
use App\Http\Controllers\Seeker\InterviewController;
use App\Http\Controllers\Seeker\VacancyController;
use App\Http\Controllers\Seeker\ProfileController;
use App\Http\Controllers\Seeker\ResumeController;
use App\Http\Controllers\Seeker\NotificationController;

Route::middleware(['auth', 'role:seeker'])->prefix('dashboard/seeker')->group(function () {
    
    // Dashboard
    Route::get('/', DashboardController::class)->name('seeker.dashboard');

    // Applications
    Route::get('/applications', [ApplicationController::class, 'index'])->name('seeker.applications.index');
    Route::post('/applications/{vacancy}/submit', [ApplicationController::class, 'submit'])->name('seeker.applications.submit');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('seeker.applications.show');
    Route::post('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->name('seeker.applications.withdraw');

    // Interviews
    Route::get('/interviews', [InterviewController::class, 'index'])->name('seeker.interviews.index');
    Route::post('/interviews/{interview}/confirm', [InterviewController::class, 'confirm'])->name('seeker.interviews.confirm');
    Route::post('/interviews/{interview}/cancel', [InterviewController::class, 'cancel'])->name('seeker.interviews.cancel');

    // Vacancies
    Route::get('/vacancies', [VacancyController::class, 'search'])->name('seeker.vacancies.search');
    Route::get('/vacancies/{vacancy}', [VacancyController::class, 'show'])->name('seeker.vacancies.show');
    Route::post('/vacancies/{vacancy}/save', [VacancyController::class, 'save'])->name('seeker.vacancies.save');
    Route::post('/vacancies/{vacancy}/unsave', [VacancyController::class, 'unsave'])->name('seeker.vacancies.unsave');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('seeker.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('seeker.profile.update');

    // Resume
    Route::get('/resume', [ResumeController::class, 'index'])->name('seeker.resume.index');
    Route::post('/resume', [ResumeController::class, 'upload'])->name('seeker.resume.upload');
    Route::post('/resume/{resume}/set-default', [ResumeController::class, 'setDefault'])->name('seeker.resume.set-default');
    Route::delete('/resume/{resume}', [ResumeController::class, 'destroy'])->name('seeker.resume.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('seeker.notifications.index');
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead'])->name('seeker.notifications.mark-read');
    Route::post('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('seeker.notifications.settings');

});
```

### 3.2 Webhook для синхронізації
**Файл:** `routes/webhooks.php` (оновити)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\SeekerWebhookController;

Route::post('/webhooks/seeker/application-status-changed', [SeekerWebhookController::class, 'onApplicationStatusChanged']);
Route::post('/webhooks/seeker/interview-scheduled', [SeekerWebhookController::class, 'onInterviewScheduled']);
Route::post('/webhooks/seeker/offer-created', [SeekerWebhookController::class, 'onOfferCreated']);
Route::post('/webhooks/seeker/message-received', [SeekerWebhookController::class, 'onMessageReceived']);
```

### 3.3 Webhook Controller
**Файл:** `app/Http/Controllers/Webhook/SeekerWebhookController.php`

```php
<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Interview;
use App\Events\ApplicationStatusChanged;
use App\Events\InterviewScheduled;

class SeekerWebhookController
{
    // Когда статус заявки изменился в бек-офисе работодателя
    public function onApplicationStatusChanged(Request $request)
    {
        $data = $request->validate([
            'application_id' => 'required|integer',
            'old_status' => 'required|string',
            'new_status' => 'required|string',
            'changed_by' => 'required|string', // 'employer'
        ]);

        $application = Application::find($data['application_id']);
        
        if ($application) {
            // Обновить статус в нашей БД
            $application->update(['status' => $data['new_status']]);

            // Записать историю
            $application->statusHistories()->create([
                'old_status' => $data['old_status'],
                'new_status' => $data['new_status'],
                'changed_by' => $data['changed_by'],
                'reason' => 'Роботодавець оновив статус',
            ]);

            // Отправить событие для Livewire компонентов
            ApplicationStatusChanged::dispatch($application);

            // Отправить сповіщення Шукачу
            $application->seeker->notify(new \App\Notifications\ApplicationStatusUpdated($application));

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Application not found'], 404);
    }

    // Когда интервью запланирован
    public function onInterviewScheduled(Request $request)
    {
        $data = $request->validate([
            'application_id' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'type' => 'required|in:online,offline,phone',
            'meeting_link' => 'nullable|url',
            'location' => 'nullable|string',
            'duration' => 'required|integer',
        ]);

        $application = Application::find($data['application_id']);

        if ($application) {
            // Создать интервью
            $interview = Interview::create([
                'application_id' => $application->id,
                'date' => $data['date'],
                'time' => $data['time'],
                'type' => $data['type'],
                'meeting_link' => $data['meeting_link'] ?? null,
                'location' => $data['location'] ?? null,
                'duration' => $data['duration'],
            ]);

            // Обновить статус заявки
            $application->update(['status' => 'interview']);

            // Отправить событие
            InterviewScheduled::dispatch($interview);

            // Отправить сповіщення
            $application->seeker->notify(new \App\Notifications\InterviewScheduled($interview));

            return response()->json(['success' => true, 'interview_id' => $interview->id]);
        }

        return response()->json(['error' => 'Application not found'], 404);
    }

    // Когда создана пропозиція
    public function onOfferCreated(Request $request)
    {
        $data = $request->validate([
            'application_id' => 'required|integer',
            'salary' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'details' => 'nullable|string',
        ]);

        $application = Application::find($data['application_id']);

        if ($application) {
            // Обновить статус
            $application->update(['status' => 'offer']);

            // Создать запись о пропозиції
            $application->offer()->create($data);

            // Отправить сповіщення
            $application->seeker->notify(new \App\Notifications\OfferReceived($application));

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Application not found'], 404);
    }

    // Когда получено сообщение
    public function onMessageReceived(Request $request)
    {
        $data = $request->validate([
            'application_id' => 'required|integer',
            'message' => 'required|string',
            'sender_type' => 'required|in:employer',
        ]);

        $application = Application::find($data['application_id']);

        if ($application) {
            // Создать сообщение
            $application->messages()->create([
                'sender_id' => $application->vacancy->company->user_id,
                'sender_type' => $data['sender_type'],
                'message' => $data['message'],
            ]);

            // Отправить сповіщення
            $application->seeker->notify(new \App\Notifications\MessageReceived($application));

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Application not found'], 404);
    }
}
```

---

## 📊 ЕТАП 4: МИГРАЦІЇ (Migrations)

### 4.1 Таблиці
```php
// database/migrations/YYYY_MM_DD_create_seeker_profiles_table.php
Schema::create('seeker_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->text('about')->nullable();
    $table->string('current_position')->nullable();
    $table->string('company')->nullable();
    $table->integer('years_experience')->nullable();
    $table->json('specialization')->nullable(); // ['Backend', 'Go', 'Microservices']
    $table->string('linkedin')->nullable();
    $table->string('github')->nullable();
    $table->string('portfolio')->nullable();
    $table->string('twitter')->nullable();
    $table->json('job_preferences')->nullable(); // { position: [], locations: [], salary: {} }
    $table->string('visibility')->default('public'); // public, private, request
    $table->timestamps();
});

// database/migrations/YYYY_MM_DD_create_seeker_resumes_table.php
Schema::create('seeker_resumes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('file_path');
    $table->string('file_name');
    $table->boolean('is_default')->default(false);
    $table->integer('views_count')->default(0);
    $table->timestamps();
});

// database/migrations/YYYY_MM_DD_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('type'); // 'application_status', 'interview_scheduled', 'offer', 'message'
    $table->foreignId('application_id')->nullable()->constrained()->onDelete('cascade');
    $table->text('title');
    $table->text('message');
    $table->boolean('is_read')->default(false);
    $table->timestamps();
});

// database/migrations/YYYY_MM_DD_create_notification_settings_table.php
Schema::create('notification_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('type'); // 'email', 'push', 'in_app'
    $table->string('event'); // 'new_vacancy', 'application_update', 'interview_scheduled'
    $table->string('frequency')->default('immediately'); // immediately, daily, weekly, never
    $table->timestamps();
});
```

---

## ✅ КОНТРОЛЬНИЙ СПИСОК

### Phase 1: Структура та основи (1 тиждень)
- [ ] Міграції для таблиць (Profile, Resume, Notifications)
- [ ] Моделі (SeekerProfile, SeekerResume, Notification)
- [ ] Routes для seeker
- [ ] Middleware для перевірки ролі

### Phase 2: Компоненти Livewire (1-2 тижні)
- [ ] DashboardComponent
- [ ] ApplicationsList
- [ ] ApplicationDetail
- [ ] InterviewsCalendar
- [ ] VacanciesSearch
- [ ] ProfileForm
- [ ] ResumeUpload
- [ ] NotificationCenter

### Phase 3: Controllers та Services (1 тиждень)
- [ ] SeekerService
- [ ] ApplicationController
- [ ] InterviewController
- [ ] VacancyController
- [ ] ProfileController

### Phase 4: Webhooks та синхронізація (1 тиждень)
- [ ] SeekerWebhookController
- [ ] Events & Listeners
- [ ] Queue Jobs
- [ ] Database Triggers

### Phase 5: Тестування та оптимізація (1 тиждень)
- [ ] Unit тести
- [ ] Feature тести
- [ ] E2E тести
- [ ] Performance тестування

---

## 🚀 КОМАНДИ ДЛЯ СТАРТУВАННЯ

```bash
# 1. Створити міграції
php artisan make:migration create_seeker_profiles_table
php artisan make:migration create_seeker_resumes_table
php artisan make:migration create_notifications_table

# 2. Запустити міграції
php artisan migrate

# 3. Створити моделі
php artisan make:model SeekerProfile
php artisan make:model SeekerResume
php artisan make:model Notification

# 4. Створити Livewire компоненти
php artisan livewire:make Seeker/DashboardComponent
php artisan livewire:make Seeker/ApplicationsList
php artisan livewire:make Seeker/ApplicationDetail

# 5. Запустити development сервер
php artisan serve
php artisan queue:listen (в окремому терміналі)
```

---

**Кінець промпта**

### 📝 ЯК КОРИСТУВАТИСЯ ЦИМ ПРОМПТОМ З Claude Code:

1. **Скопіюйте весь текст цього файлу**
2. **Відкрийте Claude Code** в своєму проекті
3. **Вставте промпт в чат Claude Code**
4. **Claude Code буде генерувати файли** (контролери, моделі, views)
5. **Розробляйте поетапно** — не робіть все одночасно!

---

**Рекомендується почати з Phase 1 і Phase 2, а потім переходити до більш складних частин.**
