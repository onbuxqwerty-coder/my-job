# 🧪 Тестування Back Office роботодавця

## 📋 Структура тестів

```
tests/
├── unit/                          # Юніт-тести
│   ├── candidates/
│   │   ├── candidateFilter.test.js
│   │   ├── candidateSort.test.js
│   │   ├── candidateSearch.test.js
│   │   └── candidateStatus.test.js
│   ├── communication/
│   │   ├── messageTemplate.test.js
│   │   ├── messageMentions.test.js
│   │   └── messageScheduling.test.js
│   ├── scheduling/
│   │   ├── availableSlots.test.js
│   │   ├── timezoneConversion.test.js
│   │   └── reminderScheduling.test.js
│   └── analytics/
│       ├── conversionFunnel.test.js
│       ├── trendCalculation.test.js
│       └── sourceTracking.test.js
│
├── integration/                   # Інтеграційні тести
│   ├── candidates.integration.test.js
│   ├── communication.integration.test.js
│   ├── scheduling.integration.test.js
│   └── analytics.integration.test.js
│
├── e2e/                          # End-to-end тести (Cypress)
│   ├── candidates.e2e.cy.js
│   ├── communication.e2e.cy.js
│   ├── scheduling.e2e.cy.js
│   └── dashboard.e2e.cy.js
│
└── fixtures/                      # Тестові дані
    ├── candidates.fixture.js
    ├── vacancies.fixture.js
    ├── users.fixture.js
    └── templates.fixture.js
```

---

# 🧪 UNIT ТЕСТИ (Jest)

## 1. Тести Таблиці Кандидатів

```javascript
// tests/unit/candidates/candidateFilter.test.js

import { filterCandidates } from '../../../src/utils/candidateFilters';

describe('Candidate Filters', () => {
  const mockCandidates = [
    {
      id: 1,
      name: 'Іван Петров',
      email: 'ivan@email.com',
      status: 'new',
      vacancyId: 1,
      createdAt: new Date('2026-04-08'),
      rating: 4
    },
    {
      id: 2,
      name: 'Марія Сидор',
      email: 'maria@email.com',
      status: 'reviewing',
      vacancyId: 2,
      createdAt: new Date('2026-04-07'),
      rating: 5
    },
    {
      id: 3,
      name: 'Петро Коваль',
      email: 'petro@email.com',
      status: 'invited',
      vacancyId: 1,
      createdAt: new Date('2026-04-06'),
      rating: 3
    }
  ];

  test('фільтр по статусу: "new"', () => {
    const result = filterCandidates(mockCandidates, { status: 'new' });
    expect(result).toHaveLength(1);
    expect(result[0].name).toBe('Іван Петров');
  });

  test('фільтр по статусу: "reviewing"', () => {
    const result = filterCandidates(mockCandidates, { status: 'reviewing' });
    expect(result).toHaveLength(1);
    expect(result[0].name).toBe('Марія Сидор');
  });

  test('фільтр по вакансії', () => {
    const result = filterCandidates(mockCandidates, { vacancyId: 1 });
    expect(result).toHaveLength(2);
    expect(result.map(c => c.id)).toEqual([1, 3]);
  });

  test('фільтр по датам', () => {
    const result = filterCandidates(mockCandidates, {
      dateFrom: new Date('2026-04-07'),
      dateTo: new Date('2026-04-08')
    });
    expect(result).toHaveLength(2);
  });

  test('фільтр по оцінці', () => {
    const result = filterCandidates(mockCandidates, { rating: 5 });
    expect(result).toHaveLength(1);
    expect(result[0].name).toBe('Марія Сидор');
  });

  test('кілька фільтрів одночасно', () => {
    const result = filterCandidates(mockCandidates, {
      status: 'reviewing',
      vacancyId: 2,
      rating: 5
    });
    expect(result).toHaveLength(1);
    expect(result[0].name).toBe('Марія Сидор');
  });

  test('коли фільтр не збігається, повернути пустий масив', () => {
    const result = filterCandidates(mockCandidates, {
      status: 'hired'
    });
    expect(result).toHaveLength(0);
  });
});
```

```javascript
// tests/unit/candidates/candidateSort.test.js

import { sortCandidates } from '../../../src/utils/candidateSort';

describe('Candidate Sorting', () => {
  const mockCandidates = [
    { id: 1, name: 'Іван', createdAt: new Date('2026-04-08'), rating: 4 },
    { id: 2, name: 'Марія', createdAt: new Date('2026-04-07'), rating: 5 },
    { id: 3, name: 'Петро', createdAt: new Date('2026-04-06'), rating: 3 }
  ];

  test('сортування по імені (A-Z)', () => {
    const result = sortCandidates(mockCandidates, 'name', 'asc');
    expect(result[0].name).toBe('Іван');
    expect(result[1].name).toBe('Марія');
    expect(result[2].name).toBe('Петро');
  });

  test('сортування по імені (Z-A)', () => {
    const result = sortCandidates(mockCandidates, 'name', 'desc');
    expect(result[0].name).toBe('Петро');
    expect(result[1].name).toBe('Марія');
    expect(result[2].name).toBe('Іван');
  });

  test('сортування по датам (новіші першими)', () => {
    const result = sortCandidates(mockCandidates, 'createdAt', 'desc');
    expect(result[0].id).toBe(1);
    expect(result[1].id).toBe(2);
    expect(result[2].id).toBe(3);
  });

  test('сортування по рейтингу (високий до низького)', () => {
    const result = sortCandidates(mockCandidates, 'rating', 'desc');
    expect(result[0].rating).toBe(5);
    expect(result[1].rating).toBe(4);
    expect(result[2].rating).toBe(3);
  });
});
```

```javascript
// tests/unit/candidates/candidateSearch.test.js

import { searchCandidates } from '../../../src/utils/candidateSearch';

describe('Candidate Search', () => {
  const mockCandidates = [
    {
      id: 1,
      name: 'Іван Петров',
      email: 'ivan@email.com',
      phone: '+38 095 123-45-67'
    },
    {
      id: 2,
      name: 'Марія Сидор',
      email: 'maria.sidor@email.com',
      phone: '+38 050 987-65-43'
    },
    {
      id: 3,
      name: 'Петро Коваль',
      email: 'petro.koval@company.ua',
      phone: '+38 099 555-66-77'
    }
  ];

  test('пошук по імені (частичне совпадение)', () => {
    const result = searchCandidates(mockCandidates, 'Іван');
    expect(result).toHaveLength(1);
    expect(result[0].name).toBe('Іван Петров');
  });

  test('пошук по email', () => {
    const result = searchCandidates(mockCandidates, 'maria');
    expect(result).toHaveLength(1);
    expect(result[0].email).toContain('maria');
  });

  test('пошук по телефону', () => {
    const result = searchCandidates(mockCandidates, '099');
    expect(result).toHaveLength(1);
    expect(result[0].phone).toContain('099');
  });

  test('пошук без результатів', () => {
    const result = searchCandidates(mockCandidates, 'xyz');
    expect(result).toHaveLength(0);
  });

  test('пошук не чутливий до регістру', () => {
    const result1 = searchCandidates(mockCandidates, 'МАРІЯ');
    const result2 = searchCandidates(mockCandidates, 'марія');
    expect(result1).toEqual(result2);
  });

  test('пошук по частини прізвища', () => {
    const result = searchCandidates(mockCandidates, 'Петров');
    expect(result).toHaveLength(1);
    expect(result[0].name).toContain('Петров');
  });
});
```

```javascript
// tests/unit/candidates/candidateStatus.test.js

import { canChangeStatus, getStatusTransitions } from '../../../src/utils/statusValidator';

describe('Candidate Status Management', () => {
  test('переход від new до reviewing дозволений', () => {
    const result = canChangeStatus('new', 'reviewing');
    expect(result).toBe(true);
  });

  test('переход від reviewing до invited дозволений', () => {
    const result = canChangeStatus('reviewing', 'invited');
    expect(result).toBe(true);
  });

  test('переход від invited до interview дозволений', () => {
    const result = canChangeStatus('invited', 'interview');
    expect(result).toBe(true);
  });

  test('переход від interview до offer дозволений', () => {
    const result = canChangeStatus('interview', 'offer');
    expect(result).toBe(true);
  });

  test('переход від offer до hired дозволений', () => {
    const result = canChangeStatus('offer', 'hired');
    expect(result).toBe(true);
  });

  test('переход від будь-якого статусу до rejected дозволений', () => {
    expect(canChangeStatus('new', 'rejected')).toBe(true);
    expect(canChangeStatus('reviewing', 'rejected')).toBe(true);
    expect(canChangeStatus('interview', 'rejected')).toBe(true);
  });

  test('недозволений переход - від hired назад до reviewing', () => {
    const result = canChangeStatus('hired', 'reviewing');
    expect(result).toBe(false);
  });

  test('отримати всі можливі переходи від new', () => {
    const transitions = getStatusTransitions('new');
    expect(transitions).toContain('reviewing');
    expect(transitions).toContain('rejected');
  });
});
```

---

## 2. Тести Комунікації

```javascript
// tests/unit/communication/messageTemplate.test.js

import { renderTemplate, validateTemplate } from '../../../src/utils/templateRenderer';

describe('Message Templates', () => {
  const template = `
    Привіт {candidateName},
    
    Ми запрошуємо вас на позицію {vacancyName} у компанії {companyName}.
    
    Дата: {interviewDate}
    Час: {interviewTime}
    Посилання: {meetingLink}
  `;

  const variables = {
    candidateName: 'Іван Петров',
    vacancyName: 'Ship Engineer',
    companyName: 'MyCompany LLC',
    interviewDate: '18.04.2026',
    interviewTime: '11:00',
    meetingLink: 'https://meet.google.com/abc'
  };

  test('рендеринг шаблону з變ables', () => {
    const result = renderTemplate(template, variables);
    expect(result).toContain('Іван Петров');
    expect(result).toContain('Ship Engineer');
    expect(result).toContain('18.04.2026');
    expect(result).not.toContain('{candidateName}');
  });

  test('перевірка що всі変ables присутні', () => {
    const requiredVars = ['candidateName', 'vacancyName', 'companyName'];
    const result = validateTemplate(template, requiredVars);
    expect(result.isValid).toBe(true);
  });

  test('помилка коли не вказані всі Variables', () => {
    const incompleteVars = { candidateName: 'Іван' };
    const result = validateTemplate(template, ['candidateName', 'vacancyName']);
    expect(result.isValid).toBe(false);
    expect(result.missingVars).toContain('vacancyName');
  });

  test('обробка спеціальних символів', () => {
    const specialTemplate = 'Привіт {name}, контракт містить символи: < > &';
    const vars = { name: 'Юзер' };
    const result = renderTemplate(specialTemplate, vars);
    expect(result).toContain('Юзер');
  });
});
```

```javascript
// tests/unit/communication/messageMentions.test.js

import { extractMentions, validateMentions } from '../../../src/utils/mentionParser';

describe('Message Mentions (@mentions)', () => {
  test('видобувати @mentions з тексту', () => {
    const text = '@Марія дивись це @Петро резюме';
    const mentions = extractMentions(text);
    expect(mentions).toEqual(['Марія', 'Петро']);
  });

  test('видобувати @mentions з email', () => {
    const text = 'Привіт @ivan@company.com, як справи?';
    const mentions = extractMentions(text);
    expect(mentions).toContain('ivan@company.com');
  });

  test('не видобувати @ без імені', () => {
    const text = 'Це коштує 100@ або більше';
    const mentions = extractMentions(text);
    expect(mentions).toHaveLength(0);
  });

  test('дублювання @mentions видаляються', () => {
    const text = '@Марія це @Марія потребує @Марія уваги';
    const mentions = extractMentions(text);
    expect(mentions).toEqual(['Марія']);
  });

  test('валідація @mentions на існування користувачів', async () => {
    const mentions = ['Марія', 'НеіснуючийКористувач'];
    const mockUsers = [
      { id: 1, name: 'Марія' },
      { id: 2, name: 'Петро' }
    ];
    
    const result = validateMentions(mentions, mockUsers);
    expect(result.valid).toEqual(['Марія']);
    expect(result.invalid).toEqual(['НеіснуючийКористувач']);
  });
});
```

```javascript
// tests/unit/communication/messageScheduling.test.js

import { scheduleMessage, calculateScheduleTime } from '../../../src/utils/scheduler';

describe('Message Scheduling', () => {
  test('планування повідомлення на конкретний час', () => {
    const now = new Date('2026-04-08T10:00:00');
    const scheduledTime = new Date('2026-04-08T14:00:00');
    
    const result = scheduleMessage({
      message: 'Hello',
      scheduledAt: scheduledTime
    }, now);
    
    expect(result.status).toBe('scheduled');
    expect(result.delayMs).toBe(4 * 60 * 60 * 1000);
  });

  test('не дозволяти планування на часу у минулому', () => {
    const now = new Date('2026-04-08T10:00:00');
    const pastTime = new Date('2026-04-08T09:00:00');
    
    expect(() => {
      scheduleMessage({ message: 'Hello', scheduledAt: pastTime }, now);
    }).toThrow('Cannot schedule in the past');
  });

  test('планування на максимум 30 днів вперед', () => {
    const now = new Date('2026-04-08T10:00:00');
    const futureTime = new Date('2026-05-10T10:00:00'); // 32 дні
    
    expect(() => {
      scheduleMessage({ message: 'Hello', scheduledAt: futureTime }, now);
    }).toThrow('Cannot schedule more than 30 days ahead');
  });

  test('розрахування часу відправлення з урахуванням часової зони', () => {
    const scheduledTime = calculateScheduleTime('2026-04-08T14:00:00', 'Europe/Kyiv');
    expect(scheduledTime).toBeInstanceOf(Date);
  });
});
```

---

## 3. Тести Планування Собесід

```javascript
// tests/unit/scheduling/availableSlots.test.js

import { getAvailableSlots, isSlotAvailable } from '../../../src/utils/slotCalculator';

describe('Interview Scheduling - Available Slots', () => {
  const mockBusyTimes = [
    { start: '10:00', end: '11:00' },
    { start: '14:00', end: '15:30' }
  ];

  test('отримати доступні слоти на день', () => {
    const slots = getAvailableSlots('2026-04-08', 30, mockBusyTimes);
    expect(slots).toContain('09:00');
    expect(slots).toContain('09:30');
    expect(slots).not.toContain('10:00'); // занято
    expect(slots).not.toContain('10:30'); // занято
  });

  test('перевірити чи слот вільний', () => {
    expect(isSlotAvailable('09:00', mockBusyTimes)).toBe(true);
    expect(isSlotAvailable('10:15', mockBusyTimes)).toBe(false);
  });

  test('мінімум 2 дні до собеседи', () => {
    const today = new Date('2026-04-08');
    const slots = getAvailableSlots('2026-04-09', 30, [], today);
    expect(slots).toHaveLength(0); // занадто скоро
  });

  test('максимум 30 днів вперед', () => {
    const today = new Date('2026-04-08');
    const slots = getAvailableSlots('2026-05-10', 30, [], today);
    expect(slots).toHaveLength(0); // занадто далеко
  });

  test('врахування вихідних днів', () => {
    // Субота, 12 квітня 2026
    const slots = getAvailableSlots('2026-04-12', 30, [], new Date('2026-04-08'));
    expect(slots).toHaveLength(0); // вихідний
  });
});
```

```javascript
// tests/unit/scheduling/timezoneConversion.test.js

import { convertTimeToTimezone, getTimezoneOffset } from '../../../src/utils/timezoneHandler';

describe('Timezone Conversion', () => {
  test('конвертація часу з UTC до Київа', () => {
    const utcTime = new Date('2026-04-08T12:00:00Z');
    const kyivTime = convertTimeToTimezone(utcTime, 'Europe/Kyiv');
    expect(kyivTime.getUTCHours()).toBe(12);
  });

  test('отримання offset часової зони', () => {
    const offset = getTimezoneOffset('Europe/Kyiv');
    expect(offset).toBe(3); // UTC+3 (у квітні)
  });

  test('конвертація різних часових зон', () => {
    const time = new Date('2026-04-08T14:00:00Z');
    
    const nyTime = convertTimeToTimezone(time, 'America/New_York');
    const kyivTime = convertTimeToTimezone(time, 'Europe/Kyiv');
    const tokyoTime = convertTimeToTimezone(time, 'Asia/Tokyo');
    
    expect(nyTime.getHours()).not.toBe(kyivTime.getHours());
    expect(tokyoTime.getHours()).not.toBe(nyTime.getHours());
  });
});
```

```javascript
// tests/unit/scheduling/reminderScheduling.test.js

import { scheduleReminders, calculateReminderTimes } from '../../../src/utils/reminderScheduler';

describe('Interview Reminders', () => {
  const interviewTime = new Date('2026-04-08T14:00:00');

  test('планування нагадувань на 24, 1, 15 хвилин', () => {
    const reminders = calculateReminderTimes(interviewTime);
    
    expect(reminders).toHaveLength(3);
    expect(reminders[0].getTime()).toBe(interviewTime.getTime() - 24 * 60 * 60 * 1000);
    expect(reminders[1].getTime()).toBe(interviewTime.getTime() - 1 * 60 * 60 * 1000);
    expect(reminders[2].getTime()).toBe(interviewTime.getTime() - 15 * 60 * 1000);
  });

  test('не дозволяти нагадування у минулому', () => {
    const pastTime = new Date('2026-04-08T10:00:00');
    const now = new Date('2026-04-08T11:00:00');
    
    expect(() => {
      scheduleReminders(pastTime, now);
    }).toThrow('Interview time is in the past');
  });

  test('зберігання нагадувань в БД', async () => {
    const interview = {
      id: 1,
      scheduledAt: interviewTime,
      candidateId: 1
    };
    
    const result = await scheduleReminders(interview);
    expect(result).toHaveLength(3);
    expect(result[0].status).toBe('scheduled');
  });
});
```

---

## 4. Тести Аналітики

```javascript
// tests/unit/analytics/conversionFunnel.test.js

import { calculateFunnel, getConversionRate } from '../../../src/utils/funnelCalculator';

describe('Conversion Funnel Analysis', () => {
  const mockData = {
    new: 100,
    reviewing: 45,
    invited: 28,
    interview: 18,
    offer: 10,
    hired: 6
  };

  test('розрахування воронки', () => {
    const funnel = calculateFunnel(mockData);
    
    expect(funnel.new.count).toBe(100);
    expect(funnel.new.percentage).toBe(100);
    expect(funnel.reviewing.percentage).toBe(45);
    expect(funnel.hired.percentage).toBe(6);
  });

  test('розрахування конвертації між етапами', () => {
    const funnel = calculateFunnel(mockData);
    
    expect(funnel.reviewing.conversionFromPrevious).toBe(45); // 45/100
    expect(funnel.invited.conversionFromPrevious).toBeCloseTo(62.22, 1); // 28/45
    expect(funnel.hired.conversionFromPrevious).toBe(60); // 6/10
  });

  test('загальна конвертація від нових до наймих', () => {
    const rate = getConversionRate(mockData.new, mockData.hired);
    expect(rate).toBe(6); // 6/100 = 6%
  });

  test('обробка порожніх даних', () => {
    const funnel = calculateFunnel({
      new: 0,
      reviewing: 0,
      invited: 0,
      interview: 0,
      offer: 0,
      hired: 0
    });
    
    expect(funnel.new.percentage).toBe(0);
  });
});
```

```javascript
// tests/unit/analytics/trendCalculation.test.js

import { calculateTrend, getGrowthRate } from '../../../src/utils/trendCalculator';

describe('Trend Analysis', () => {
  const mockApplications = [
    { date: '2026-04-01', count: 10 },
    { date: '2026-04-02', count: 15 },
    { date: '2026-04-03', count: 12 },
    { date: '2026-04-04', count: 20 },
    { date: '2026-04-05', count: 18 },
    { date: '2026-04-06', count: 22 },
    { date: '2026-04-07', count: 25 }
  ];

  test('розрахування тренду за 7 днів', () => {
    const trend = calculateTrend(mockApplications, 7);
    expect(trend.average).toBe(17.43); // (10+15+12+20+18+22+25)/7
    expect(trend.total).toBe(122);
    expect(trend.days).toBe(7);
  });

  test('розрахування темпу зростання', () => {
    const rate = getGrowthRate(10, 25); // від 10 до 25
    expect(rate).toBe(150); // 150% зростання
  });

  test('виявлення тренду "вгору"', () => {
    const trend = calculateTrend(mockApplications.slice(4, 7), 3);
    expect(trend.direction).toBe('up');
  });

  test('виявлення тренду "вниз"', () => {
    const downTrend = [
      { date: '2026-04-05', count: 30 },
      { date: '2026-04-06', count: 25 },
      { date: '2026-04-07', count: 20 }
    ];
    const trend = calculateTrend(downTrend, 3);
    expect(trend.direction).toBe('down');
  });
});
```

```javascript
// tests/unit/analytics/sourceTracking.test.js

import { trackSource, getSourceDistribution } from '../../../src/utils/sourceTracker';

describe('Candidate Source Tracking', () => {
  const mockCandidates = [
    { id: 1, source: 'linkedin', createdAt: '2026-04-08' },
    { id: 2, source: 'linkedin', createdAt: '2026-04-08' },
    { id: 3, source: 'website', createdAt: '2026-04-07' },
    { id: 4, source: 'facebook', createdAt: '2026-04-06' },
    { id: 5, source: 'referral', createdAt: '2026-04-05' },
    { id: 6, source: 'linkedin', createdAt: '2026-04-05' }
  ];

  test('отримати розподіл джерел', () => {
    const distribution = getSourceDistribution(mockCandidates);
    
    expect(distribution.linkedin.count).toBe(3);
    expect(distribution.linkedin.percentage).toBeCloseTo(50, 1);
    expect(distribution.website.count).toBe(1);
    expect(distribution.facebook.count).toBe(1);
  });

  test('відстежувати джерело кандидата', () => {
    const source = trackSource('linkedin');
    expect(source.name).toBe('linkedin');
    expect(source.timestamp).toBeInstanceOf(Date);
  });

  test('отримати топ джерела', () => {
    const distribution = getSourceDistribution(mockCandidates);
    const top = Object.entries(distribution)
      .sort((a, b) => b[1].count - a[1].count)[0];
    
    expect(top[0]).toBe('linkedin');
    expect(top[1].count).toBe(3);
  });
});
```

---

# 🧪 ІНТЕГРАЦІЙНІ ТЕСТИ

```javascript
// tests/integration/candidates.integration.test.js

import request from 'supertest';
import app from '../../../src/server';
import { prisma } from '../../../src/db';

describe('Candidates API Integration Tests', () => {
  let authToken;
  let candidateId;
  let vacancyId;

  beforeAll(async () => {
    // Вхід як роботодавець
    const response = await request(app)
      .post('/api/auth/login')
      .send({
        email: 'employer@test.com',
        password: 'password123'
      });
    
    authToken = response.body.token;

    // Створення тестової вакансії
    const vacancyRes = await request(app)
      .post('/api/vacancies')
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        title: 'Test Position',
        description: 'Test description',
        categoryId: 1
      });
    
    vacancyId = vacancyRes.body.id;
  });

  afterAll(async () => {
    await prisma.candidates.deleteMany();
    await prisma.vacancies.deleteMany();
  });

  test('GET /api/candidates - отримати список кандидатів', async () => {
    const response = await request(app)
      .get('/api/candidates')
      .set('Authorization', `Bearer ${authToken}`);

    expect(response.status).toBe(200);
    expect(Array.isArray(response.body.data)).toBe(true);
    expect(response.body).toHaveProperty('total');
    expect(response.body).toHaveProperty('page');
  });

  test('GET /api/candidates - фільтрація по статусу', async () => {
    const response = await request(app)
      .get('/api/candidates?status=new')
      .set('Authorization', `Bearer ${authToken}`);

    expect(response.status).toBe(200);
    response.body.data.forEach(candidate => {
      expect(candidate.status).toBe('new');
    });
  });

  test('GET /api/candidates - фільтрація по вакансії', async () => {
    const response = await request(app)
      .get(`/api/candidates?vacancyId=${vacancyId}`)
      .set('Authorization', `Bearer ${authToken}`);

    expect(response.status).toBe(200);
    response.body.data.forEach(candidate => {
      expect(candidate.vacancyId).toBe(vacancyId);
    });
  });

  test('GET /api/candidates - пошук по імені', async () => {
    const response = await request(app)
      .get('/api/candidates?search=Іван')
      .set('Authorization', `Bearer ${authToken}`);

    expect(response.status).toBe(200);
    response.body.data.forEach(candidate => {
      expect(candidate.name.toLowerCase()).toContain('іван');
    });
  });

  test('GET /api/candidates/:id - отримати деталь кандидата', async () => {
    // Спочатку створити кандидата
    const createRes = await request(app)
      .post('/api/candidates')
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        name: 'Test Candidate',
        email: 'test@email.com',
        vacancyId: vacancyId
      });

    candidateId = createRes.body.id;

    const response = await request(app)
      .get(`/api/candidates/${candidateId}`)
      .set('Authorization', `Bearer ${authToken}`);

    expect(response.status).toBe(200);
    expect(response.body.id).toBe(candidateId);
    expect(response.body.name).toBe('Test Candidate');
  });

  test('PATCH /api/candidates/:id/status - змінити статус', async () => {
    const response = await request(app)
      .patch(`/api/candidates/${candidateId}/status`)
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        status: 'reviewing'
      });

    expect(response.status).toBe(200);
    expect(response.body.status).toBe('reviewing');
  });

  test('POST /api/candidates/:id/notes - додати нотатку', async () => {
    const response = await request(app)
      .post(`/api/candidates/${candidateId}/notes`)
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        text: 'Дуже хороший кандидат'
      });

    expect(response.status).toBe(201);
    expect(response.body).toHaveProperty('id');
    expect(response.body.text).toBe('Дуже хороший кандидат');
  });

  test('PATCH /api/candidates/:id/rating - оцінити кандидата', async () => {
    const response = await request(app)
      .patch(`/api/candidates/${candidateId}/rating`)
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        rating: 5
      });

    expect(response.status).toBe(200);
    expect(response.body.rating).toBe(5);
  });
});
```

```javascript
// tests/integration/scheduling.integration.test.js

import request from 'supertest';
import app from '../../../src/server';

describe('Interview Scheduling Integration Tests', () => {
  let authToken;
  let candidateId;
  let interviewId;

  beforeAll(async () => {
    const response = await request(app)
      .post('/api/auth/login')
      .send({
        email: 'employer@test.com',
        password: 'password123'
      });
    
    authToken = response.body.token;
  });

  test('GET /api/scheduling/available-slots - отримати вільні слоти', async () => {
    const response = await request(app)
      .get('/api/scheduling/available-slots?date=2026-04-18&duration=60')
      .set('Authorization', `Bearer ${authToken}`);

    expect(response.status).toBe(200);
    expect(Array.isArray(response.body.slots)).toBe(true);
    response.body.slots.forEach(slot => {
      expect(slot).toMatch(/^\d{2}:\d{2}$/);
    });
  });

  test('POST /api/interviews - створити запрошення на собесіду', async () => {
    const response = await request(app)
      .post('/api/interviews')
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        candidateId: 1,
        vacancyId: 1,
        date: '2026-04-18',
        time: '11:00',
        duration: 60,
        type: 'video'
      });

    expect(response.status).toBe(201);
    expect(response.body).toHaveProperty('id');
    expect(response.body.status).toBe('scheduled');
    
    interviewId = response.body.id;
  });

  test('POST /api/interviews/:id/confirm - кандидат підтверджує', async () => {
    const response = await request(app)
      .post(`/api/interviews/${interviewId}/confirm`)
      .send({
        token: 'candidate-response-token'
      });

    expect(response.status).toBe(200);
    expect(response.body.status).toBe('confirmed');
  });

  test('POST /api/interviews/:id/reschedule - перенесення собеседи', async () => {
    const response = await request(app)
      .post(`/api/interviews/${interviewId}/reschedule`)
      .set('Authorization', `Bearer ${authToken}`)
      .send({
        date: '2026-04-20',
        time: '14:00'
      });

    expect(response.status).toBe(200);
    expect(response.body.status).toBe('rescheduled');
  });
});
```

---

# 🧪 E2E ТЕСТИ (Cypress)

```javascript
// tests/e2e/candidates.e2e.cy.js

describe('Candidates Management E2E', () => {
  beforeEach(() => {
    cy.login('employer@test.com', 'password123');
    cy.visit('/dashboard/candidates');
  });

  it('повинен відобразити таблицю кандидатів', () => {
    cy.get('table').should('be.visible');
    cy.get('tbody tr').should('have.length.greaterThan', 0);
  });

  it('повинен фільтрувати кандидатів по статусу', () => {
    cy.get('[data-testid="status-filter"]').click();
    cy.get('[data-testid="filter-option-new"]').click();
    
    cy.get('tbody tr').each(($row) => {
      cy.wrap($row).within(() => {
        cy.get('[data-testid="status-badge"]').should('contain', 'Нова');
      });
    });
  });

  it('повинен пошукувати кандидатів', () => {
    cy.get('[data-testid="search-input"]').type('Іван');
    cy.get('tbody tr').first().should('contain', 'Іван');
  });

  it('повинен відкрити деталь кандидата', () => {
    cy.get('tbody tr').first().click();
    cy.url().should('include', '/candidates/');
    cy.get('[data-testid="candidate-detail"]').should('be.visible');
  });

  it('повинен додати нотатку до кандидата', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="add-note-btn"]').click();
    
    cy.get('[data-testid="note-textarea"]').type('Хороший кандидат');
    cy.get('[data-testid="save-note-btn"]').click();
    
    cy.get('[data-testid="note-item"]').should('contain', 'Хороший кандидат');
  });

  it('повинен оцінити кандидата', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="rating-stars"]').eq(4).click(); // 5 зірок
    
    cy.get('[data-testid="rating-display"]').should('contain', '5');
  });

  it('повинен змінити статус кандидата', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="status-dropdown"]').click();
    cy.get('[data-testid="status-option-reviewing"]').click();
    
    cy.get('[data-testid="status-badge"]').should('contain', 'На розгляді');
  });

  it('повинен експортувати кандидатів', () => {
    cy.get('[data-testid="export-btn"]').click();
    cy.get('[data-testid="export-format-csv"]').click();
    
    cy.readFile('cypress/downloads/candidates.csv').should('exist');
  });
});
```

```javascript
// tests/e2e/scheduling.e2e.cy.js

describe('Interview Scheduling E2E', () => {
  beforeEach(() => {
    cy.login('employer@test.com', 'password123');
    cy.visit('/dashboard/candidates');
  });

  it('повинен запросити на собесіду', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="schedule-interview-btn"]').click();
    
    cy.get('[data-testid="interview-modal"]').should('be.visible');
    
    // Вибір дати
    cy.get('[data-testid="calendar"]').should('be.visible');
    cy.get('[data-testid="calendar-day-18"]').click();
    
    // Вибір часу
    cy.get('[data-testid="time-slot-11-00"]').click();
    
    // Вибір типу
    cy.get('[data-testid="interview-type"]').select('video');
    
    // Відправлення
    cy.get('[data-testid="confirm-interview-btn"]').click();
    
    cy.get('[data-testid="success-message"]').should('contain', 'Запрошення відправлено');
  });

  it('повинен показати доступні часові слоти', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="schedule-interview-btn"]').click();
    
    cy.get('[data-testid="calendar-day-18"]').click();
    
    cy.get('[data-testid="time-slot"]').should('have.length.greaterThan', 0);
    cy.get('[data-testid="time-slot"]').first().should('not.have.class', 'disabled');
  });

  it('повинен не дозволяти планування на вихідних', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="schedule-interview-btn"]').click();
    
    // 12 квітня 2026 - субота
    cy.get('[data-testid="calendar-day-12"]').should('have.class', 'disabled');
  });

  it('повинен скасувати запрошення', () => {
    // Припускаємо що запрошення вже існує
    cy.get('[data-testid="interview-item"]').first().within(() => {
      cy.get('[data-testid="cancel-interview-btn"]').click();
    });
    
    cy.get('[data-testid="confirm-cancel"]').click();
    cy.get('[data-testid="success-message"]').should('contain', 'Запрошення скасовано');
  });
});
```

```javascript
// tests/e2e/communication.e2e.cy.js

describe('Communication E2E', () => {
  beforeEach(() => {
    cy.login('employer@test.com', 'password123');
    cy.visit('/dashboard/candidates');
  });

  it('повинен відправити повідомлення', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="send-message-btn"]').click();
    
    cy.get('[data-testid="message-modal"]').should('be.visible');
    cy.get('[data-testid="message-type"]').select('message');
    cy.get('[data-testid="template-select"]').select('standard');
    
    cy.get('[data-testid="message-textarea"]').type('Це тестове повідомлення');
    cy.get('[data-testid="send-btn"]').click();
    
    cy.get('[data-testid="success-message"]').should('contain', 'Повідомлення відправлено');
  });

  it('повинен показувати змінні в шаблоні', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="send-message-btn"]').click();
    
    cy.get('[data-testid="template-select"]').select('interview-invitation');
    
    cy.get('[data-testid="template-preview"]').should('contain', '{candidateName}');
    cy.get('[data-testid="template-preview"]').should('contain', '{interviewDate}');
  });

  it('повинен додавати @mention', () => {
    cy.get('tbody tr').first().click();
    cy.get('[data-testid="send-message-btn"]').click();
    
    cy.get('[data-testid="message-textarea"]').type('@М');
    cy.get('[data-testid="mention-suggestion"]').first().click();
    
    cy.get('[data-testid="message-textarea"]').should('contain', '@Марія');
  });
});
```

```javascript
// tests/e2e/dashboard.e2e.cy.js

describe('Dashboard Analytics E2E', () => {
  beforeEach(() => {
    cy.login('employer@test.com', 'password123');
    cy.visit('/dashboard');
  });

  it('повинен показати KPI метрики', () => {
    cy.get('[data-testid="kpi-active-vacancies"]').should('be.visible');
    cy.get('[data-testid="kpi-total-candidates"]').should('be.visible');
    cy.get('[data-testid="kpi-avg-time-to-fill"]').should('be.visible');
    cy.get('[data-testid="kpi-conversion-rate"]').should('be.visible');
  });

  it('повинен показати воронку конвертації', () => {
    cy.get('[data-testid="conversion-funnel"]').should('be.visible');
    cy.get('[data-testid="funnel-stage"]').should('have.length', 6);
  });

  it('повинен показати тренд графік', () => {
    cy.get('[data-testid="trend-chart"]').should('be.visible');
    cy.get('[data-testid="trend-chart"]').find('svg').should('exist');
  });

  it('повинен фільтрувати по період', () => {
    cy.get('[data-testid="period-filter"]').click();
    cy.get('[data-testid="period-30-days"]').click();
    
    cy.get('[data-testid="kpi-total-candidates"]')
      .invoke('text')
      .then((initialText) => {
        cy.get('[data-testid="period-filter"]').click();
        cy.get('[data-testid="period-7-days"]').click();
        
        cy.get('[data-testid="kpi-total-candidates"]')
          .invoke('text')
          .should('not.equal', initialText);
      });
  });

  it('повинен експортувати звіт', () => {
    cy.get('[data-testid="export-report-btn"]').click();
    cy.get('[data-testid="export-format-pdf"]').click();
    
    cy.readFile('cypress/downloads/report.pdf').should('exist');
  });
});
```

---

# 🧪 FIXTURE ДАНІ

```javascript
// tests/fixtures/candidates.fixture.js

export const mockCandidates = [
  {
    id: 1,
    name: 'Іван Петров',
    email: 'ivan@email.com',
    phone: '+38 095 123-45-67',
    status: 'new',
    vacancyId: 1,
    rating: 4,
    createdAt: new Date('2026-04-08'),
    resume: 'https://example.com/resume1.pdf'
  },
  {
    id: 2,
    name: 'Марія Сидор',
    email: 'maria@email.com',
    phone: '+38 050 987-65-43',
    status: 'reviewing',
    vacancyId: 2,
    rating: 5,
    createdAt: new Date('2026-04-07'),
    resume: 'https://example.com/resume2.pdf'
  }
];

export const mockApplications = [
  {
    id: 1,
    candidateId: 1,
    vacancyId: 1,
    status: 'new',
    createdAt: new Date('2026-04-08')
  }
];
```

```javascript
// tests/fixtures/vacancies.fixture.js

export const mockVacancies = [
  {
    id: 1,
    title: 'Ship Engineer',
    description: 'Senior position',
    categoryId: 1,
    status: 'active',
    createdAt: new Date('2026-04-01')
  },
  {
    id: 2,
    title: 'Vocational Education Teacher',
    description: 'Teaching position',
    categoryId: 2,
    status: 'active',
    createdAt: new Date('2026-04-02')
  }
];
```

---

# 🚀 ЗАПУСК ТЕСТІВ

```bash
# Юніт-тести
npm run test:unit

# Інтеграційні тести
npm run test:integration

# E2E тести
npm run test:e2e

# Всі тести
npm run test

# Тести з coverage
npm run test:coverage

# Watch mode
npm run test:watch
```

---

# 📊 КОНФІГУРАЦІЯ ТЕСТУВАННЯ

```javascript
// jest.config.js

module.exports = {
  testEnvironment: 'node',
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
  collectCoverageFrom: [
    'src/**/*.js',
    '!src/**/*.test.js',
    '!src/index.js'
  ],
  coverageThreshold: {
    global: {
      branches: 70,
      functions: 70,
      lines: 70,
      statements: 70
    }
  },
  testMatch: ['**/__tests__/**/*.js', '**/?(*.)+(spec|test).js']
};
```

```javascript
// cypress.config.js

const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    viewportWidth: 1280,
    viewportHeight: 720,
    setupNodeEvents(on, config) {
      // Налаштування подій
    }
  }
});
```

```javascript
// tests/setup.js

beforeEach(() => {
  jest.clearAllMocks();
});

afterEach(async () => {
  // Очищення БД після кожного тесту
  await prisma.notes.deleteMany();
  await prisma.messages.deleteMany();
  await prisma.interviews.deleteMany();
  await prisma.candidates.deleteMany();
});
```

---

# 📋 ЧЕКЛИСТ ТЕСТУВАННЯ

## Перед запуском тестів переконайтесь що:

- [ ] База даних налаштована (PostgreSQL або MongoDB)
- [ ] Seed дані завантажені
- [ ]環境 змінні встановлені (.env.test)
- [ ] Всі залежності встановлені (npm install)
- [ ] Backend сервер запущений (npm run dev:backend)
- [ ] Frontend запущений (npm run dev:frontend)

## Критерії успіху:

- [ ] 100% unit тестів пройдено ✅
- [ ] 100% integration тестів пройдено ✅
- [ ] 100% E2E тестів пройдено ✅
- [ ] Code coverage > 70% ✅
- [ ] Немає ошибок в консолі ✅
- [ ] Всі API endpoints відповідають ✅
- [ ] UI компоненти відображаються правильно ✅

---

## 🎯 ПРИКЛАД ЗАПУСКУ ТЕСТІВ

```bash
# 1. Встановлення залежностей
npm install

# 2. Налаштування БД
npm run db:setup
npm run db:seed

# 3. Запуск unit тестів
npm run test:unit

# Результат:
# PASS  tests/unit/candidates/candidateFilter.test.js
# ✓ фільтр по статусу: "new" (5ms)
# ✓ фільтр по вакансії (3ms)
# ...
# Test Suites: 4 passed, 4 total
# Tests:       45 passed, 45 total
# Snapshots:   0 total
# Time:        2.543 s

# 4. Запуск інтеграційних тестів
npm run test:integration

# 5. Запуск E2E тестів
npm run test:e2e

# 6. Отримання звіту про покриття
npm run test:coverage
```

---

Готово! Тепер у вас є повний набір тестів для перевірки всіх функцій Back Office! 🎉
