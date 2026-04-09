# Prompt 8.1: TOTAL UI RECONSTRUCTION (SaaS Level)

**Urgent:** The previous output is unacceptable. It looks like raw HTML from 2005. You MUST completely rewrite the view with the following modern UI architecture.

---

## 1. Global Page Wrapper
- Add `bg-slate-50 min-h-screen` to the main div.
- Use a central container: `max-w-7xl mx-auto px-4 py-12`.

## 2. Header & Hero (Centered)
- Title: `text-5xl font-extrabold text-slate-900 mb-4`.
- Subtitle: `text-xl text-slate-600 mb-8`.
- Search Bar: Make it a huge, clean input with a white background, `rounded-2xl`, `shadow-xl`, and a large `Indigo-600` search button inside it. Use `border-none`.

## 3. The Grid (Sidebar + Results)
- Use `grid grid-cols-12 gap-8`.
- **Sidebar (3 cols):** - `bg-white rounded-3xl p-8 border border-slate-200 shadow-sm`.
    - Labels: `text-xs uppercase font-bold tracking-wider text-slate-400 mb-3`.
    - Inputs: `rounded-xl bg-slate-50 border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500`.
- **Results (9 cols):**
    - Space cards with `space-y-6`.

## 4. The Vacancy Card (The "Candy" Part)
Rewrite the vacancy loop to create this specific card:
- Container: `bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-300`.
- **Top Row:** - Left: Company Logo (use `w-16 h-16 rounded-2xl bg-indigo-50` with a bold Initial letter if no logo).
    - Middle: Title (`text-2xl font-bold text-slate-900`) and Company name (`text-indigo-600 font-medium`).
    - Right: Salary (`text-xl font-black text-slate-900 bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl`).
- **Bottom Row:**
    - Badges: Use Indigo-themed badges for category and employment type.
    - Meta: `text-slate-400 text-sm` for location and time ago.

## 5. UX Elements
- **Skeletons:** Create a `wire:loading` block with `bg-slate-200 animate-pulse` rectangles that EXACTLY match the card's shape.
- **Empty State:** If 0 results, show a "No jobs found" message with a large icon.

## 6. Layout Fix
- Wrap everything in `<x-navigation />` and `<x-footer />` components. 
- Ensure Navbar is `fixed` or `sticky` with `backdrop-blur`.

---

**Constraint:** DO NOT use standard browser borders or default radio buttons. Everything must be custom-styled with Tailwind.