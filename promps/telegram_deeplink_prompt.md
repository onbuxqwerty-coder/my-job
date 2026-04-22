# Промпт для Claude Code: Безпарольна авторизація через Telegram Deep Linking

Цей промпт детально описує всю архітектуру та надає готові блоки коду для інтеграції з вашою платформою **My Job** (Laravel 13 + Livewire).

---

## 📋 Задача

Реалізувати безпарольну авторизацію користувачів через Telegram-бота, використовуючи механізм **Deep Linking**. При натисканні кнопки на сайті користувач переходить до бота, підтверджує контакт і миттєво авторизується на сайті без введення паролю.

---

## 🏗️ Архітектура

```
┌─────────────────────┐
│   Frontend (Livewire) │  ◄── Користувач тисне кнопку
└──────────┬──────────┘     "Увійти через Telegram"
           │
           ├─► Генерує session_auth_token
           ├─► Форматує Deep Link: https://t.me/{bot_username}?start={token}
           ├─► Отримує редірект на t.me
           └─► Запускає Polling/WebSocket слухач
               (очікує сигналу від сервера)
                       │
                       ▼
        ┌──────────────────────────┐
        │  Telegram Bot (@тобот)   │
        └────────────┬─────────────┘
                     │
                     ├─► Користувач натискає "Start"
                     ├─► Бот отримує /start {token}
                     ├─► Просить контакт (request_contact=True)
                     └─► Отримує phone_number + telegram_id
                               │
                               ▼
        ┌──────────────────────────────┐
        │   Backend (Laravel Webhook)   │
        └────────────┬─────────────────┘
                     │
                     ├─► Перевіряє токен у Redis
                     ├─► Пов'язує telegram_id + phone з токеном
                     ├─► Створює/оновлює User
                     ├─► Генерує auth_token (JWT або сесія)
                     └─► Відправляє WebSocket сигнал фронту
                               │
                               ▼
        ┌──────────────────────────┐
        │   Frontend (WebSocket)    │
        └────────────┬─────────────┘
                     │
                     ├─► Отримує сигнал "OK"
                     ├─► Зберігає auth_token в localStorage
                     └─► Редіректує в кабінет
```

---

## 📁 Структура файлів

```
my-job/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── TelegramAuthController.php
│   │   └── Middleware/
│   │       └── TelegramWebhookVerifier.php
│   ├── Models/
│   │   ├── User.php (оновити)
│   │   └── TelegramSession.php (новий)
│   ├── Services/
│   │   ├── TelegramAuthService.php (новий)
│   │   └── TelegramBotService.php (новий)
│   └── Jobs/
│       └── ProcessTelegramWebhook.php
├── database/
│   └── migrations/
│       ├── YYYY_MM_DD_create_telegram_sessions_table.php
│       └── YYYY_MM_DD_add_telegram_fields_to_users_table.php
├── routes/
│   ├── web.php (оновити)
│   └── api.php (оновити)
├── resources/
│   └── views/
│       ├── components/
│       │   └── telegram-login-button.blade.php (новий)
│       └── livewire/
│           └── auth/
│               └── telegram-login-modal.blade.php (новий)
├── .env.example (оновити з TELEGRAM_BOT_TOKEN тощо)
└── config/
    └── telegram.php (новий)
```

---

## 🔧 Технічні деталі

### Стек
- **Backend**: Laravel 13, Redis, WebSockets (Laravel WebSockets або soketi)
- **Frontend**: Livewire + Tailwind, Vanilla JS для WebSocket
- **БД**: PostgreSQL (для Users + TelegramSessions)
- **Telegram Bot**: Polling або Webhook (виберіть один)

### Константи
| Параметр | Значення |
|----------|----------|
| **Session TTL** | 5 хвилин (300 сек) |
| **Auth Token TTL** | 30 днів |
| **Webhook Timeout** | 10 секунд |
| **Frontend Polling Interval** | 1 сек (або WebSocket) |

---

## 📝 Детальне завдання для Claude Code

### **Фаза 1: Моделі та Міграції**

#### Файл: `database/migrations/YYYY_MM_DD_add_telegram_fields_to_users_table.php`
```php
// Додати колонки до users таблиці:
// - telegram_id (nullable, unique, bigInteger)
// - phone_number (nullable, string)
// - telegram_verified_at (nullable, timestamp)
```

#### Файл: `database/migrations/YYYY_MM_DD_create_telegram_sessions_table.php`
```php
// Таблиця для зберігання сесій авторизації:
// - id (UUID primary)
// - session_token (unique, string)
// - user_id (nullable, FK to users)
// - telegram_id (nullable, bigInteger)
// - phone_number (nullable, string)
// - status (enum: 'pending', 'authorized', 'expired')
// - expires_at (timestamp)
// - created_at, updated_at
```

#### Файл: `app/Models/User.php`
```php
// Додати методи:
// - findOrCreateByTelegram($telegram_id, $phone)
// - relations: telegramSessions()
// - casts: telegram_verified_at to datetime
```

#### Файл: `app/Models/TelegramSession.php`
```php
// Модель для управління сесіями авторизації
// - belongsTo(User)
// - Методи: isValid(), isExpired(), markAuthorized()
```

---

### **Фаза 2: Backend Services**

#### Файл: `app/Services/TelegramAuthService.php`
```php
// Основна служба авторизації
// Методи:
// 1. generateSessionToken(): string
//    - Генерує унікальний токен (128 символів)
//    - Зберігає його в Redis та БД з TTL 5 хв
//    - Повертає токен

// 2. processWebhookFromBot($telegram_id, $phone_number): array
//    - Отримує дані від Telegram Bot Webhook
//    - Знаходить TelegramSession по session_token (з контексту)
//    - Пов'язує telegram_id + phone з сесією
//    - Створює/оновлює User
//    - Генерує auth_token (JWT або session)
//    - Відправляє WebSocket повідомлення фронту

// 3. verifyTelegramWebhook($data, $telegram_hash): bool
//    - Перевіряє підпис від Telegram (Hash Check)
//    - Формула: sha256(data_check_string) == hash
//    - Очищує від BOT_TOKEN в хеші

// 4. getSessionStatus($session_token): array
//    - Повертає {status, user_id, auth_token}
//    - Для polling-запитів з фронту
```

#### Файл: `app/Services/TelegramBotService.php`
```php
// Служба для управління Telegram Bot
// Методи:
// 1. sendContactRequest($chat_id, $session_token): bool
//    - Надсилає кнопку "Поділитися контактом"
//    - Повідомлення: "Дякуємо! Натисніть кнопку нижче"

// 2. getDeepLink($session_token): string
//    - Формує посилання: https://t.me/{BOT_USERNAME}?start={token}

// 3. handleStartCommand($chat_id, $args): void
//    - Обробляє /start {session_token}
//    - Запускає запит контакту
//    - Встановлює стан в БД

// 4. sendWebhook($url, $payload): bool
//    - Надсилає повідомлення на webhook (опційно)
```

---

### **Фаза 3: Controllers та Routes**

#### Файл: `app/Http/Controllers/TelegramAuthController.php`
```php
// Контролер для управління потоком авторизації
// Методи (Routes):

// 1. POST /api/telegram/init
//    - Перевіряє user_id (auth чи гість)
//    - Викликає TelegramAuthService::generateSessionToken()
//    - Повертає {token, deep_link}
//    - Статус: 200

// 2. GET /api/telegram/status/{session_token}
//    - Вполлінг запит від фронту (перевірка статусу)
//    - Викликає getSessionStatus()
//    - Повертає {status, auth_token} або 404
//    - Polling: таймаут 30 сек

// 3. POST /api/telegram/webhook (Webhook від Bot)
//    - Отримує JSON від Telegram (дані контакту)
//    - Верифікує webhook (Hash Check)
//    - Викликає TelegramAuthService::processWebhookFromBot()
//    - Відправляє WebSocket сигнал
//    - Повертає 200 OK

// 4. POST /api/telegram/webhook/polling (альтернатива)
//    - Якщо використовуєте polling замість WebSocket
//    - Фронт периодично запитує статус
```

#### Файл: `routes/api.php`
```php
// Додати маршрути:
Route::prefix('telegram')->group(function () {
    Route::post('/init', [TelegramAuthController::class, 'init']);
    Route::get('/status/{session_token}', [TelegramAuthController::class, 'status']);
    Route::post('/webhook', [TelegramAuthController::class, 'webhook'])
        ->middleware('telegram.webhook.verify');
});
```

---

### **Фаза 4: Frontend Components**

#### Файл: `resources/views/components/telegram-login-button.blade.php`
```php
{{-- Кнопка "Увійти через Telegram" --}}
<button 
    @click="telegramLogin()" 
    class="btn btn-primary w-full gap-2"
>
    <svg>{{ Telegram Icon }}</svg>
    Увійти через Telegram
</button>

<script>
async function telegramLogin() {
    // 1. POST /api/telegram/init
    // 2. Отримати {token, deep_link}
    // 3. Зберегти token в sessionStorage
    // 4. Запустити WebSocket слухач
    // 5. Редірект на deep_link (window.location.href)
}

function listenTelegramWebSocket() {
    // WebSocket підключення до /ws/telegram/{token}
    // Очікує сигналу {status: 'authorized', auth_token: '...'}
    // При отриманні: редірект на /dashboard
}
</script>
```

#### Файл: `resources/views/livewire/auth/telegram-login-modal.blade.php`
```livewire
{{-- Livewire компонент для модального вікна авторизації --}}
<div class="modal-content">
    <h2>Авторизація через Telegram</h2>
    
    @if($this->status === 'pending')
        <p>Очікування підтвердження...</p>
        <div class="spinner"></div>
    @elseif($this->status === 'authorized')
        <p>✓ Ви авторизовані!</p>
    @else
        <x-telegram-login-button />
    @endif
</div>

@script
<script>
    // Livewire hook: слухати WebSocket
    // Оновлювати $status при отриманні сигналу
</script>
@endscript
```

---

### **Фаза 5: Безпека**

#### Файл: `app/Http/Middleware/TelegramWebhookVerifier.php`
```php
// Middleware для верифікації Webhook від Telegram
// Реалізація:
// 1. Отримати всі дані з запиту (GET + JSON body)
// 2. Витягнути 'hash' параметр
// 3. Сортувати дані по alfabeto
// 4. Формувати data_check_string: "key1=val1\nkey2=val2\n..."
// 5. Обчислити: sha256(data_check_string) == hash?
// 6. Якщо false → abort(403)
```

#### Файл: `.env.example`
```
TELEGRAM_BOT_TOKEN=123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
TELEGRAM_BOT_USERNAME=MyJobBot
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/api/telegram/webhook
TELEGRAM_WEBHOOK_SECRET=your-secret-key-for-validation
```

#### Файл: `config/telegram.php`
```php
// Конфіг для Telegram
return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'session_ttl' => 300, // 5 хвилин
];
```

---

### **Фаза 6: Telegram Bot Script**

#### Файл: `bot/bot.py` (Python + python-telegram-bot)
```python
"""
Telegram Bot для авторизації My Job
"""

from telegram import Update, ReplyKeyboardMarkup, KeyboardButton
from telegram.ext import Application, CommandHandler, MessageHandler, filters, ContextTypes
import requests
import hashlib
import json

BOT_TOKEN = "YOUR_BOT_TOKEN"
WEBHOOK_URL = "https://yourdomain.com/api/telegram/webhook"

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Обробка /start {session_token}"""
    chat_id = update.effective_chat.id
    args = context.args
    
    if not args:
        await update.message.reply_text("Невірна сесія.")
        return
    
    session_token = args[0]
    
    # Зберегти session_token в контексті користувача
    context.user_data['session_token'] = session_token
    
    # Запитати контакт
    keyboard = [[KeyboardButton(text="📱 Поділитися контактом", request_contact=True)]]
    reply_markup = ReplyKeyboardMarkup(keyboard, one_time_keyboard=True, resize_keyboard=True)
    
    await update.message.reply_text(
        "Дякуємо за вхід до My Job! 🎉\n\nНатисніть кнопку нижче, щоб авторизуватися:",
        reply_markup=reply_markup
    )

async def contact_received(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Обробка отриманого контакту"""
    contact = update.message.contact
    telegram_id = contact.user_id
    phone_number = contact.phone_number
    
    session_token = context.user_data.get('session_token')
    
    if not session_token:
        await update.message.reply_text("Помилка сесії.")
        return
    
    # Надіслати Webhook на backend
    payload = {
        'telegram_id': telegram_id,
        'phone_number': phone_number,
        'session_token': session_token,
    }
    
    try:
        response = requests.post(WEBHOOK_URL, json=payload, timeout=10)
        if response.status_code == 200:
            await update.message.reply_text(
                "✓ Авторизація успішна! Повертайтесь на сайт.",
                reply_markup=ReplyKeyboardMarkup([[]], resize_keyboard=True)
            )
        else:
            await update.message.reply_text("Помилка обробки. Спробуйте ще раз.")
    except Exception as e:
        print(f"Webhook error: {e}")
        await update.message.reply_text("Помилка підключення.")

def main():
    app = Application.builder().token(BOT_TOKEN).build()
    
    app.add_handler(CommandHandler("start", start))
    app.add_handler(MessageHandler(filters.CONTACT, contact_received))
    
    # Запуск в режимі Polling
    app.run_polling()

if __name__ == '__main__':
    main()
```

---

### **Фаза 7: WebSocket конфіг**

#### Файл: `config/websockets.php`
```php
// Конфіг для Laravel WebSockets
return [
    'default' => 'websockets',
    'connections' => [
        'websockets' => [
            'driver' => 'websockets',
            'host' => env('WEBSOCKET_HOST', '0.0.0.0'),
            'port' => env('WEBSOCKET_PORT', 6001),
            'path' => '/ws',
            'capacity' => null,
            'ping_interval' => 25,
            'ping_timeout' => 60,
        ],
    ],
];
```

#### Файл: `.env` (додати)
```
BROADCAST_DRIVER=websockets
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=6001
```

---

## 🚀 Послідовність реалізації

1. **Тиждень 1**: Міграції + Моделі + Services
2. **Тиждень 2**: Controllers + Routes + Middleware
3. **Тиждень 3**: Frontend Components + WebSocket
4. **Тиждень 4**: Telegram Bot + Webhook Handling
5. **Тиждень 5**: Тестування + Безпека + Оптимізація

---

## ✅ Чек-лист для контролю якості

- [ ] Користувач генерує токен і перенаправляється на бота
- [ ] Бот отримує `/start {token}` без помилок
- [ ] Запит контакту показується коректно
- [ ] Webhook від бота доходить до сервера
- [ ] Hash Check проходить без помилок
- [ ] User створюється/оновлюється в БД
- [ ] WebSocket відправляє сигнал фронту
- [ ] Фронт отримує сигнал і редіректує
- [ ] Auth Token зберігається в localStorage
- [ ] Користувач авторизується в системі
- [ ] Session TTL = 5 хв (тестування виснення)
- [ ] Повторна спроба з мертвим токеном = помилка

---

## 📚 Додаткові ресурси

- [Telegram Bot API: Contacts](https://core.telegram.org/bots/api#contact)
- [Deep Linking in Telegram](https://core.telegram.org/bots#deep-linking)
- [Laravel WebSockets Docs](https://beyondco.de/docs/laravel-websockets)
- [Hash Check Validation](https://core.telegram.org/widgets/login#checking-authorization)

---

Цей промпт готовий для передачі в **Claude Code**. Достатньо скопіювати, вставити і почати з **Фази 1**! 🚀
