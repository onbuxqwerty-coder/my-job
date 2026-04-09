# Prompt 8: Full Visual Overhaul & Professional UX Polish

**Context:** The current UI is a raw HTML skeleton without proper styling. 
**Goal:** Transform the application into a high-end, modern SaaS platform with smooth animations, professional navigation, and a clear visual hierarchy.

---

## 1. Professional Navbar (Blade Component)
- **File:** `resources/views/components/navigation.blade.php`
- **Left Side:** Logo "MyJob" (Text: Indigo-600, font-black, text-2xl) + Search icon.
- **Center:** Links: "Всі вакансії", "Категорії", "Компанії" (Slate-600, hover: Indigo-600).
- **Right Side:** - Guest: "Увійти" (Ghost button) | "Реєстрація" (Solid Indigo button, rounded-xl).
    - Auth: Profile dropdown with Avatar, "Мій профіль", "Вихід".
- **Styling:** `sticky top-0`, `bg-white/80`, `backdrop-blur-md`, `border-b border-slate-200`, `z-50`.

## 2. Professional Footer (Blade Component)
- **File:** `resources/views/components/footer.blade.php`
- **Design:** Dark mode footer (`bg-slate-900`, `text-slate-300`).
- **Content:** - Column 1: MyJob description & Socials.
    - Column 2: "Для кандидатів" (Пошук, Категорії).
    - Column 3: "Для роботодавців" (Подати вакансію, Тарифи).
    - Bottom: Copyright & Privacy policy.

## 3. Main Search Page Overhaul (Volt)
- **File:** `resources/views/livewire/pages/jobs/index.blade.php`
- **Background:** Set global background to `bg-slate-50`.
- **Hero Section:** - H1: "Знайдіть роботу своєї мрії" (Slate-900, font-extrabold, tracking-tight).
    - Subtitle: "50 000+ актуальних вакансій у провідних компаніях України".
- **Sidebar Filters:** - Wrap in a card: `bg-white`, `rounded-2xl`, `p-6`, `shadow-sm`, `border border-slate-200`.
    - Modern inputs: `rounded-xl`, `border-slate-200`, `focus:ring-indigo-500`.
    - **UX:** Add `wire:loading.attr="disabled"` to all inputs to prevent glitchy double-requests.
- **Job Feed:** - **Skeleton Loaders:** When `wire:loading` is active, show 3 pulsing grey cards (`animate-pulse`) instead of the real list.
    - **Job Cards:**
        - Background: `white`, `rounded-2xl`, `p-6`, `border border-slate-200`.
        - Transitions: `hover:shadow-xl`, `hover:border-indigo-400`, `transition-all duration-300`.
        - Layout: Flexbox with Company Logo (14x14, rounded-xl) on the left, Title/Meta in the middle, Salary/Badge on the right.

## 4. Global Layout Integration
- **File:** `resources/views/layouts/app.blade.php`
- Include `<x-navigation />` and `<x-footer />`.
- Wrap `@slot('slot')` in a container with `min-h-screen`.

## 5. Coding Standards (Master Prompt Compliance)
- Use **Tailwind CSS** standard classes only (no inline styles).
- Ensure `declare(strict_types=1);` is present in the Volt component.
- Format all salaries with `number_format($val, 0, '.', ' ')`.