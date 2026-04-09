# Prompt 8: Professional Navigation, Footer & UX Polish

**Context:** The project needs a professional frame (Navbar/Footer) and better feedback during data loading (Skeletons/Loading states) as shown in the current UI.
**Goal:** Create a polished, production-ready interface with smooth transitions.

---

## 1. Professional Navbar (Blade Component)
- **Create** `resources/views/components/navigation.blade.php`:
    - **Logo:** "MyJob" on the left (Indigo-600, font-black, text-2xl).
    - **Links (Center):** "Всі вакансії", "Категорії", "Компанії".
    - **Auth (Right):** - If Guest: "Увійти" (ghost button) and "Реєстрація" (solid indigo button).
        - If Auth: User avatar/initials dropdown with "Мій профіль", "Налаштування" and "Вихід".
    - **Style:** Sticky top, white background, `backdrop-blur` effect, thin `border-b border-slate-200`.

## 2. Footer (Blade Component)
- **Create** `resources/views/components/footer.blade.php`:
    - 3-column layout: 
        1. Brand info & Social icons.
        2. Quick links (About, Terms, Privacy).
        3. Newsletter mockup or Contact info.
    - Style: `bg-slate-900`, `text-slate-400`, padding top/bottom.

## 3. UX Polish: Loading States & Skeletons
- **Update** `resources/views/livewire/pages/jobs/index.blade.php`:
    - **Disable Inputs:** Add `wire:loading.attr="disabled"` to all filter inputs (search, category select, radios) to prevent rapid-fire requests.
    - **Skeleton Loaders:** - Wrap the vacancy list in a div with `wire:loading.remove`.
        - Create a `wire:loading` block that shows 3 "skeleton" cards (grey pulsing rectangles matching the layout of actual job cards).
    - **Smoothness:** Add `wire:target` to specific filters so the loader only triggers when search parameters change.

## 4. Layout Integration
- **Update** `resources/views/layouts/app.blade.php`:
    - Inject the `<x-navigation />` component at the top.
    - Inject the `<x-footer />` component at the bottom.
    - Ensure the main content area has `min-h-[calc(100vh-200px)]` to keep the footer at the bottom on empty pages.

## 5. Execution Rules
- Follow **Tailwind CSS** best practices.
- Ensure all PHP logic uses `declare(strict_types=1);`.
- Maintain the Indigo/Slate color scheme defined in previous redesign steps.