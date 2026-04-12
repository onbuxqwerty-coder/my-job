# 🧪 Інструкція: Запуск тестів для Back Office роботодавця

## 📋 Структура тестування

```
tests/
├── unit/                    # Юніт-тести (10 тестів)
├── integration/             # Інтеграційні тести (8 тестів)
├── api/                     # API тести (11 тестів)
├── e2e/                     # End-to-End тести (18 тестів)
├── performance/             # Тести продуктивності (5 тестів)
└── accessibility/           # Тести доступності (7 тестів)

РАЗОМ: 59 тестів
```

---

## 🚀 Встановлення залежностей

```bash
# Встановлення основних залежностей
npm install

# Встановлення тестових залежностей
npm install --save-dev \
  vitest \
  @testing-library/react \
  @testing-library/user-event \
  @playwright/test \
  jest-axe \
  msw \
  @vitest/ui \
  @vitest/coverage-v8 \
  axios

# Встановлення Playwright браузерів
npx playwright install
```

---

## 🎯 Запуск тестів

### 1️⃣ Запуск ВСІх тестів одночасно

```bash
npm run test:all
```

**Результат:** Всі 59 тестів виконуються послідовно з детальним звітом

---

### 2️⃣ Запуск за категоріями

#### Юніт-тести (10 тестів)
```bash
npm run test tests/unit
```

Перевіряє:
- ✅ Фільтрування кандидатів
- ✅ Пошук
- ✅ Парсинг шаблонів
- ✅ Роботу з датами

---

#### Інтеграційні тести (8 тестів)
```bash
npm run test tests/integration
```

Перевіряє:
- ✅ Таблиця кандидатів (фільтри, пошук, сортування)
- ✅ Деталі кандидата
- ✅ Нотатки
- ✅ Комунікація та повідомлення

---

#### API тести (11 тестів)
```bash
# Запевніться що сервер запущен: npm run dev (в іншому терміналі)
npm run test:api tests/api
```

Перевіряє:
- ✅ GET /api/candidates
- ✅ POST /api/candidates
- ✅ PATCH /api/candidates/:id/status
- ✅ POST /api/candidates/:id/notes
- ✅ DELETE /api/candidates/:id
- ✅ Помилки 401, 404, 500

---

#### E2E тести (18 тестів)
```bash
# Запевніться що додаток запущен: npm run dev (на localhost:5173)
npm run test:e2e
```

Перевіряє:
- ✅ Повний цикл рекрутингу (від логіну до експорту звіту)
- ✅ Планування собесіди (календар, час, учасники)
- ✅ Пошук кандидатів
- ✅ Додавання нотаток
- ✅ Запрошення на собесіду
- ✅ Переглад дашборду
- ✅ Експорт звітів

---

#### Тести продуктивності (5 тестів)
```bash
npm run test:performance
```

Перевіряє:
- ✅ Завантаження таблиці 1000+ рядків за < 3 сек
- ✅ Пошук за < 500ms
- ✅ Сортування за < 1 сек
- ✅ Пагінація за < 500ms
- ✅ Нема витіків пам'яті

---

#### Тести доступності (7 тестів)
```bash
npm run test:accessibility
```

Перевіряє:
- ✅ ARIA labels на всіх кнопках
- ✅ Таблиця має правильну семантику
- ✅ Нема порушень доступності (axe)
- ✅ Клавіатурна навігація (Tab)
- ✅ Контраст кольорів (WCAG AA)
- ✅ Labels на формах
- ✅ Видимий фокус

---

## 🔍 Детальні команди

### Запуск конкретного тесту

```bash
# Тест фільтрів
npm run test tests/unit/filters.test.js

# Тест таблиці
npm run test tests/integration/candidatesTable.test.js

# Тест комунікації
npm run test tests/integration/communication.test.js

# Тест API кандидатів
npm run test:api tests/api/candidates.api.test.js

# E2E тест повного цикла
npm run test:e2e tests/e2e/workflow.full.recruitment.test.js

# E2E тест планування собесіди
npm run test:e2e tests/e2e/workflow.interview.scheduling.test.js
```

---

### Watch режим (розробка)

```bash
# Автоматичний перезапуск при змінах
npm run test:watch

# Watch конкретного файлу
npm run test:watch tests/unit/filters.test.js

# UI інтерфейс для тестів
npm run test:ui
```

---

### Покриття кодом

```bash
# Генерація звіту про покриття
npm run test:coverage

# Результат буде в: coverage/
# Можна відкрити: coverage/index.html
```

---

## ⚙️ Налаштування перед тестуванням

### 1️⃣ Запустіть сервер (для API тестів)

```bash
# У НОВОМУ терміналі
npm run dev

# Сервер запуститься на http://localhost:3000
# API буде на http://localhost:3000/api
```

### 2️⃣ Запустіть додаток (для E2E тестів)

```bash
# У НОВОМУ терміналі
npm run dev

# Додаток буде на http://localhost:5173
```

### 3️⃣ Запустіть тести (в основному терміналі)

```bash
# Коли все готово, запустіть тести
npm run test:all
```

---

## 📊 Очікувані результати

### ✅ УСПІШНИЙ ЗАПУСК:

```
============================================================
📊 ФІНАЛЬНИЙ ЗВІТ
============================================================
✅ Пройшло: 59
❌ Не пройшло: 0
⏱️  Час виконання: 2m 34s

🎉 ВСІ ТЕСТИ ПРОЙШЛИ УСПІШНО!
============================================================
```

### ❌ ПОМИЛКИ (і як їх виправити):

#### Помилка: "Cannot connect to API"
```
❌ ECONNREFUSED: connect ECONNREFUSED 127.0.0.1:3000
```
**Рішення:** Запустіть сервер в іншому терміналі: `npm run dev`

---

#### Помилка: "Playwright browser not found"
```
❌ Error: Executable doesn't exist at /path/to/chromium
```
**Рішення:** Установіть браузери Playwright:
```bash
npx playwright install
```

---

#### Помилка: "Cannot find module 'vitest'"
```
❌ Error: Cannot find module 'vitest'
```
**Рішення:** Установіть залежності:
```bash
npm install --save-dev vitest @testing-library/react
```

---

## 🎯 Інтерпретація результатів

### Тест пройшов ✅
```
✓ filterCandidates() повинен фільтрувати по статусу (5ms)
```
- Функція працює коректно
- Час виконання нормальний

### Тест не пройшов ❌
```
❌ filterCandidates() повинен фільтрувати по статусу
  Expected: Array length 1
  Received: Array length 0
```
- Функція не працює як очікувалось
- Потрібно виправити код

### Тест skipped ⊘
```
⊘ filterCandidates() [SKIPPED]
```
- Тест пропущен (можливо для доробки)
- Не впливає на успіх інших тестів

---

## 🚨 CI/CD інтеграція

### GitHub Actions (`.github/workflows/test.yml`)

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        run: npm install
      
      - name: Run tests
        run: npm run test:all
      
      - name: Generate coverage
        run: npm run test:coverage
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/coverage-final.json
```

---

## 📈 Аналіз результатів тестів

### Покриття кодом

```bash
npm run test:coverage
```

Приклад звіту:
```
=============================== Coverage summary ===============================
Statements   : 92.3% ( 1230/1332 )
Branches     : 87.5% ( 420/480 )
Functions    : 94.2% ( 156/165 )
Lines        : 91.8% ( 1150/1252 )
===============================================================================
```

**Мета:** > 85% для production

---

### Performance метрики

```bash
npm run test:performance
```

Результати:
```
▶ Table Performance
  ✓ завантажує таблицю 1000 рядків за 2.3s (< 3s) ✅
  ✓ пошук в таблиці за 480ms (< 500ms) ✅
  ✓ сортування за 890ms (< 1s) ✅
```

**Мета:** Всі часи < встановлених меж

---

## 🔐 Перевірка перед commit

Перед тим як зробити `git push`, запустіть:

```bash
# Запускає тести + linting + format
npm run pre-commit

# Або окремо:
npm run test
npm run lint
npm run format
```

---

## 🆘 Дебагування

### Запуск одного тесту з деталями

```bash
npm run test -- --reporter=verbose tests/unit/filters.test.js
```

### Debug режим в VS Code

Додайте в `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "type": "node",
      "request": "launch",
      "name": "Debug Tests",
      "runtimeExecutable": "npm",
      "runtimeArgs": ["run", "test:watch"],
      "console": "integratedTerminal"
    }
  ]
}
```

---

## 📞 Контакти та підтримка

Якщо тести не проходять:

1. **Перевірте환경:**
   ```bash
   npm --version   # v18+
   node --version  # v18+
   ```

2. **Очистіть кеш:**
   ```bash
   npm cache clean --force
   rm -rf node_modules
   npm install
   ```

3. **Перевірте логи:**
   ```bash
   npm run test -- --reporter=verbose
   ```

4. **Створіть issue** з деталями помилки

---

## ✅ Чеклист перед deploy

- [ ] Запустіть `npm run test:all` - всі 59 тестів повинні пройти
- [ ] Покриття кодом > 85%
- [ ] E2E тести проходять на真實даних
- [ ] Нема performance проблем (таблиця 1000+ рядків < 3s)
- [ ] Доступність без помилок (axe zero violations)
- [ ] Нема console.error або warning
- [ ] Можна безпечно deploy в production 🚀

---

## 🎉 Результат

Коли всі тести пройдуть:

```
════════════════════════════════════════════════════════════════
✅ Всі 59 тестів пройшли успішно!
🚀 Ваш Back Office готовий до production!
════════════════════════════════════════════════════════════════
```

Вітаємо! 🎊 Тепер у вас є надійний, протестований Back Office роботодавця!
