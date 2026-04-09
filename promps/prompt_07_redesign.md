# Prompt 7: Radical UI/UX Redesign (Tailwind + Blade)

**Context:** The current UI is a "barebones" skeleton with zero styling (as seen in the provided screenshot). 
**Goal:** Transform the job search landing page into a modern, professional, SaaS-like interface using Tailwind CSS, following the standards in `CLAUDE.md`.

---

## 1. Global Styling & Layout
- Use a **Slate/Indigo** color palette.
- Background: `bg-slate-50`.
- Cards & Containers: `bg-white`, `rounded-2xl`, `shadow-sm`, `border border-slate-200`.
- Typography: Primary font sans-serif, headings in `text-slate-900`, body in `text-slate-600`.

## 2. Component Update: Vacancy Search (Livewire Volt)
Update `resources/views/livewire/pages/jobs/index.blade.php` with the following structure:

### A. Hero Section
- Centered H1: "Знайдіть роботу своєї мрії" (`text-4xl`, `font-extrabold`).
- Subtitle: "Тисячі актуальних вакансій у провідних компаніях України" (`text-slate-500`).

### B. Two-Column Layout (Sidebar + Main)
- **Sidebar (3 cols):** - Wrap filters in a sticky white card.
    - Style inputs (Search, Select, Radio) with `rounded-xl` and `focus:ring-indigo-500`.
    - Use subtle hover states for radio buttons.
- **Main Feed (9 cols):**
    - Display result count clearly.
    - Vacancy Cards:
        - Add `hover:border-indigo-400`, `hover:shadow-md`, and `transition-all`.
        - Company Logo: Create a 14x14 (`w-14 h-14`) square with `rounded-xl`. If no logo, show a stylish initial letter placeholder.
        - Title: `text-xl font-bold group-hover:text-indigo-600`.
        - Salary: Format with `number_format` and use bold slate text.
        - Badges: Employment type should be an Indigo pill (`bg-indigo-50 text-indigo-700`).

## 3. Navigation & Footer
- Create/Update a responsive **Navbar**:
    - Left: "MyJob" Logo (Bold Indigo text).
    - Right: Login/Register buttons or User Dropdown (if auth).
    - Shadowless white background with a thin bottom border.
- Create a simple **Footer** with copyright and basic links.

## 4. Interaction & UX
- Add `wire:loading` states (opacity change or a subtle spinner) for the search results.
- Ensure the "Apply" button or link on the card is prominent but clean.
- Ensure the layout is fully responsive (Stack columns on mobile).

## 5. Execution
1. Refactor `index.blade.php`.
2. Create/Update layout files if necessary.
3. Ensure `declare(strict_types=1);` is preserved in the Volt component.
4. Verify that all Tailwind classes are standard (no custom CSS files needed).