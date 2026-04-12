# 🚀 Швидкий старт: Запуск тестів (за 1 хвилину)

## ⚡ TL;DR (Найважливіше)

```bash
# 1. Установити залежності
npm install

# 2. Запустити сервер (в новому терміналі)
npm run dev

# 3. Запустити всі тести
npm run test:all

# 4. Результат: всі 59 тестів повинні пройти ✅
```

---

## 📋 Запуск конкретних тестів

```bash
# Юніт-тести (найшвидше - 10 секунд)
npm run test

# Інтеграційні тести
npm run test tests/integration

# API тести (потребує сервер: npm run dev)
npm run test:api

# E2E тести (потребує додаток: npm run dev)
npm run test:e2e

# Performance тести
npm run test:performance

# Доступність (a11y)
npm run test:accessibility

# Watch режим (для розробки)
npm run test:watch

# UI інтерфейс
npm run test:ui

# Покриття кодом
npm run test:coverage
```

---

## 🎯 Що тестується

| Категорія | Кількість | Час | Команда |
|-----------|-----------|-----|---------|
| 🧪 Юніт-тести | 10 | 10s | `npm run test` |
| 🔗 Інтеграційні | 8 | 30s | `npm run test tests/integration` |
| 🌐 API | 11 | 20s | `npm run test:api` |
| 🎬 E2E | 18 | 45s | `npm run test:e2e` |
| ⚡ Performance | 5 | 15s | `npm run test:performance` |
| ♿ Доступність | 7 | 12s | `npm run test:accessibility` |
| **ВСЬОГО** | **59** | **~2.5 хв** | `npm run test:all` |

---

## ✅ Очікуваний результат

```
✅ Пройшло: 59
❌ Не пройшло: 0
⏱️  Час: 2m 34s

🎉 ВСІ ТЕСТИ ПРОЙШЛИ УСПІШНО!
```

---

## 🔧 Встановлення залежностей (перший раз)

```bash
# Основні залежності
npm install

# Тестові залежності
npm install --save-dev vitest @testing-library/react @playwright/test jest-axe

# Браузери для E2E
npx playwright install

# Готово!
```

---

## 🆘 Типові помилки та рішення

### ❌ "Cannot connect to API"
```bash
# Запустіть сервер в НОВОМУ терміналі
npm run dev
```

### ❌ "Browser not found"
```bash
npx playwright install
```

### ❌ "Cannot find module"
```bash
npm install
```

---

## 📊 Файли що вам потрібні

1. **TESTING_SUMMARY.md** ← Починайте звідси!
2. **testing_instructions.md** ← Детальні інструкції
3. **comprehensive_testing_suite.md** ← Повна документація
4. **test.runner.sh** ← Bash скрипт (виконайте: `bash test.runner.sh`)
5. **package.json** ← Скопіюйте залежності

---

## 🎬 Live команди (copy-paste)

### Перший раз (40 секунд)
```bash
npm install && npm install --save-dev vitest @testing-library/react @playwright/test && npx playwright install
```

### Запуск тестів (в основному терміналі)
```bash
npm run test:all
```

### Запуск сервера (в НОВОМУ терміналі)
```bash
npm run dev
```

### Watch режим (для розробки)
```bash
npm run test:watch
```

---

## 📈 Метрики успіху

✅ **59/59 тестів пройшли**
✅ **0 помилок**
✅ **Час < 3 хвилини**
✅ **Покриття кодом > 85%**
✅ **Performance: таблиця 1000+ рядків < 3s**
✅ **Доступність: 0 порушень (axe)**

---

## 🎉 Готово!

Коли всі тести пройдуть - ваш Back Office готовий до production! 🚀

**Довідка:** `npm run test:all 2>&1 | tee test-report.txt`
