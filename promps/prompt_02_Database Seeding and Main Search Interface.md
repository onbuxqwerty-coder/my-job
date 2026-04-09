# Prompt 2: Database Seeding and Main Search Interface

**Context:** The foundation (migrations/models) is ready. Now we need data and a UI to display it.
**Goal:** Create a Seeder to populate the DB and build the main landing page with real-time filtering.

---

## 1. Data Seeding
- Create a `DatabaseSeeder` that:
    1. Generates 10 **Categories** (IT, Sales, Marketing, etc.).
    2. Generates 5 **Companies** (each owned by a User with the 'employer' role).
    3. Generates 50 **Vacancies** distributed across categories and companies.
    4. Ensures realistic dates and statuses using the Factories created in Prompt 1.

## 2. Core Search Logic (Service Layer)
- Create a `VacancyService` to handle search logic.
- Implement filtering by:
    - Search query (title/description).
    - Category.
    - Employment Type (using the Enum).
    - Salary range.

## 3. Frontend: Livewire Search Component
- Create a **Livewire Volt** component for the homepage (`resources/views/livewire/pages/jobs/index.blade.php`).
- **Layout:**
    - **Sidebar (Left):** Filters (Checkboxes for Employment Type, Dropdown for Category, Range for Salary).
    - **Main Content (Right):** Search input at the top, followed by a list of Vacancy cards.
- **Card Design:**
    - Company logo (placeholder if null), Title, Company Name, Salary tag, Location, "Posted X days ago".
- **Interactivity:**
    - Use Livewire's `wire:model.live` so the list updates instantly as the user types or changes filters.
    - Add simple pagination (10 per page).

## 4. Execution
1. Update `DatabaseSeeder.php`.
2. Create the `VacancyService`.
3. Build the Livewire component and its Blade view.
4. Define the route `/` to point to this new component.

*Note: Use Tailwind CSS for a professional, clean "LinkedIn-like" look.*