# 01 — Resume Database Migration & Eloquent Models

## Objetivo
Створити базову архітектуру резюме з гібридним підходом:
- Основна таблиця `resumes` з JSONB полями для метаданих
- Окремі таблиці `experiences` та `skills` для пошуку й матчингу
- Eloquent Models з методами для PATCH операцій

## Database Schema

### 1. Таблиця `resumes`

```php
Schema::create('resumes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    // Метадані
    $table->string('title')->nullable(); // Наприклад: "Senior Laravel Developer"
    $table->enum('status', ['draft', 'published'])->default('draft');
    
    // JSONB для особистої інформації
    $table->jsonb('personal_info')->default(json_encode([
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'email_verified_at' => null,
        'phone' => null,
        'privacy' => false,        // Видимість для роботодавців
        'transparency' => false,   // Компанії бачать переглади
    ]));
    
    // JSONB для локації (простіша структура — без складного гео-пошуку)
    $table->jsonb('location')->default(json_encode([
        'city' => null,
        'city_id' => null,          // ID із Smart City Search
        'street' => null,
        'building' => null,
        'latitude' => null,
        'longitude' => null,
        'no_location_binding' => false, // Опція "Без привязки до міста"
    ]));
    
    // JSONB для сповіщень
    $table->jsonb('notifications')->default(json_encode([
        'site' => true,      // Сповіщення на сайті (за замовчуванням)
        'email' => false,
        'sms' => false,
        'telegram' => false,
        'viber' => false,
        'whatsapp' => false,
    ]));
    
    // JSONB для опціональних полів
    $table->jsonb('additional_info')->default(json_encode([
        'social_links' => [],
        'hobbies' => [],
        'bio' => null,
    ]));
    
    // Для ідентифікації змін (UX: "Збереження...")
    $table->timestamp('last_saved_at')->nullable();
    
    $table->timestamps();
    
    // Індекси для пошуку
    $table->index('user_id');
    $table->index('status');
    $table->index('created_at');
});
```

### 2. Таблиця `experiences`

```php
Schema::create('experiences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
    
    $table->string('position');
    $table->string('company_name');
    $table->string('company_industry')->nullable(); // Для фільтрів
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->boolean('is_current')->default(false);
    
    $table->timestamps();
    
    // Індекси
    $table->index('resume_id');
    $table->index('company_industry');
});
```

### 3. Таблиця `skills`

```php
Schema::create('skills', function (Blueprint $table) {
    $table->id();
    $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
    
    $table->string('skill_name');
    
    $table->timestamps();
    
    // Індекс для пошуку вмінь
    $table->index(['resume_id', 'skill_name']);
});
```

### 4. Таблиця `email_verifications` (для email верифікації)

```php
Schema::create('email_verifications', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->string('code'); // 6-значний код
    $table->timestamp('code_expires_at');
    $table->boolean('is_verified')->default(false);
    $table->timestamp('verified_at')->nullable();
    
    $table->timestamps();
    
    $table->index('email');
    $table->index('code');
});
```

---

## Eloquent Models

### 1. `Resume` Model

```php
// app/Models/Resume.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'personal_info',
        'location',
        'notifications',
        'additional_info',
        'last_saved_at',
    ];

    protected $casts = [
        'personal_info' => 'array',
        'location' => 'array',
        'notifications' => 'array',
        'additional_info' => 'array',
        'last_saved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Методи для PATCH операцій
     */

    /**
     * Оновити лише особисту інформацію (JSONB)
     * Приклад: updatePersonalInfo(['first_name' => 'Іван'])
     */
    public function updatePersonalInfo(array $data): self
    {
        $current = $this->personal_info ?? [];
        $updated = array_merge($current, $data);
        
        $this->update([
            'personal_info' => $updated,
            'last_saved_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Оновити локацію (JSONB)
     */
    public function updateLocation(array $data): self
    {
        $current = $this->location ?? [];
        $updated = array_merge($current, $data);
        
        $this->update([
            'location' => $updated,
            'last_saved_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Оновити сповіщення (JSONB)
     */
    public function updateNotifications(array $data): self
    {
        $current = $this->notifications ?? [];
        $updated = array_merge($current, $data);
        
        $this->update([
            'notifications' => $updated,
            'last_saved_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Оновити додаткову інформацію (JSONB)
     */
    public function updateAdditionalInfo(array $data): self
    {
        $current = $this->additional_info ?? [];
        $updated = array_merge($current, $data);
        
        $this->update([
            'additional_info' => $updated,
            'last_saved_at' => now(),
        ]);

        return $this->fresh();
    }

    /**
     * Перевірити, чи резюме є валідним для публікації
     */
    public function isPublishable(): bool
    {
        $personalInfo = $this->personal_info;
        
        // Критичні поля
        $hasCriticalFields = !empty($personalInfo['first_name']) 
            && !empty($personalInfo['last_name']) 
            && !empty($personalInfo['email']) 
            && !empty($personalInfo['email_verified_at']);
        
        // Мінімум один досвід АБО одна навичка
        $hasContent = $this->experiences()->count() > 0 || $this->skills()->count() > 0;
        
        return $hasCriticalFields && $hasContent;
    }

    /**
     * Отримати статус-індикатори для Stepper-у
     */
    public function getStepperStatus(): array
    {
        return [
            'personal_info' => $this->validatePersonalInfo(),
            'email' => $this->validateEmail(),
            'experience' => $this->validateExperience(),
            'skills' => $this->validateSkills(),
            'location' => $this->validateLocation(),
            'notifications' => true, // Завжди валідно
        ];
    }

    /**
     * Приватні методи валідації
     */
    private function validatePersonalInfo(): bool
    {
        $info = $this->personal_info;
        return !empty($info['first_name']) && !empty($info['last_name']);
    }

    private function validateEmail(): bool
    {
        $info = $this->personal_info;
        return !empty($info['email']) && !empty($info['email_verified_at']);
    }

    private function validateExperience(): bool
    {
        return $this->experiences()->count() > 0;
    }

    private function validateSkills(): bool
    {
        return $this->skills()->count() > 0;
    }

    private function validateLocation(): bool
    {
        $location = $this->location;
        return !empty($location['city']) || $location['no_location_binding'] === true;
    }
}
```

### 2. `Experience` Model

```php
// app/Models/Experience.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $fillable = [
        'resume_id',
        'position',
        'company_name',
        'company_industry',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Валідація: дата закінчення повинна бути після дати початку
     */
    public function isValid(): bool
    {
        if ($this->is_current) {
            return true;
        }

        return $this->end_date && $this->end_date->greaterThan($this->start_date);
    }
}
```

### 3. `Skill` Model

```php
// app/Models/Skill.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'resume_id',
        'skill_name',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
```

### 4. `EmailVerification` Model

```php
// app/Models/EmailVerification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'email',
        'code',
        'code_expires_at',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'code_expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Генерувати 6-значний код
     */
    public static function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Перевірити, чи код не скінчився
     */
    public function isCodeValid(): bool
    {
        return $this->code_expires_at->isFuture();
    }

    /**
     * Перевірити код
     */
    public function verifyCode(string $code): bool
    {
        if ($this->is_verified) {
            return false;
        }

        if (!$this->isCodeValid()) {
            return false;
        }

        if ($this->code !== $code) {
            return false;
        }

        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return true;
    }
}
```

---

## Migration File

```php
// database/migrations/2024_XX_XX_XXXXXX_create_resume_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // resumes таблиця
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->jsonb('personal_info')->default(json_encode([
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'email_verified_at' => null,
                'phone' => null,
                'privacy' => false,
                'transparency' => false,
            ]));
            $table->jsonb('location')->default(json_encode([
                'city' => null,
                'city_id' => null,
                'street' => null,
                'building' => null,
                'latitude' => null,
                'longitude' => null,
                'no_location_binding' => false,
            ]));
            $table->jsonb('notifications')->default(json_encode([
                'site' => true,
                'email' => false,
                'sms' => false,
                'telegram' => false,
                'viber' => false,
                'whatsapp' => false,
            ]));
            $table->jsonb('additional_info')->default(json_encode([
                'social_links' => [],
                'hobbies' => [],
                'bio' => null,
            ]));
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });

        // experiences таблиця
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->string('position');
            $table->string('company_name');
            $table->string('company_industry')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            $table->index('resume_id');
            $table->index('company_industry');
        });

        // skills таблиця
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->string('skill_name');
            $table->timestamps();
            
            $table->index(['resume_id', 'skill_name']);
        });

        // email_verifications таблиця
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('code');
            $table->timestamp('code_expires_at');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('resumes');
    }
};
```

---

## Notes for Claude Code

1. **Créer les 4 modèles Eloquent** dans le dossier `app/Models`:
   - Resume.php
   - Experience.php
   - Skill.php
   - EmailVerification.php

2. **Créer la migration** dans le dossier `database/migrations` avec le nom du format: `YYYY_MM_DD_HHMMSS_create_resume_tables.php`

3. **Indexation**: Tous les indexes sont en place pour les requêtes fréquentes.

4. **JSONB operations**: Pour Postgres, utilisez `jsonb_set()` et `jsonb_merge()` quand vous avez besoin de mettre à jour partiellement.

5. **Type hints**: Tous les modèles ont des type hints pour les relations et les méthodes.

6. **Timestamps**: Utilisez `$table->timestamps()` pour `created_at` et `updated_at`.

---

## Команда для запуску:

```bash
php artisan migrate
```

Файли будуть створені в:
- `app/Models/Resume.php`
- `app/Models/Experience.php`
- `app/Models/Skill.php`
- `app/Models/EmailVerification.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_create_resume_tables.php`
