# Prompt 4: Employer Dashboard & Admin Panel (Filament)

**Context:** Candidate flow and Telegram integration are finished. Now we need the management side for Employers and Site Admins.
**Goal:** Create a dashboard for employers to manage jobs/applications and a global admin panel using Filament PHP.

---

## 1. Employer Dashboard (Livewire Volt)
- **Route:** `/dashboard/employer` (protected by `auth` and `role:employer` middleware).
- **My Vacancies:** A table showing all jobs posted by the current user's company.
    - Fields: Title, Status (Active/Draft), Applications Count, Created Date.
    - Actions: Edit, Toggle Active/Inactive, Delete (SoftDelete).
- **Application Management:**
    - A view to see all applicants for a specific vacancy.
    - Ability for the Employer to change application status (Screening, Interview, Rejected, Hired).
    - **Trigger:** When status changes, send an automatic Telegram/Email notification to the candidate.

## 2. Global Admin Panel (Filament PHP)
- **Setup:** Install `filament/filament` v3.
- **Resources:** Create Filament resources for:
    - **Users:** Manage roles and telegram links.
    - **Categories:** Add/Edit icons and sorting.
    - **Companies:** Verify companies (add a `is_verified` toggle).
    - **Vacancies:** Global moderation (delete spam or inappropriate jobs).

## 3. SEO & Sitemap
- **SEO Service:** Create a service to generate Meta Tags (Title, Description, OpenGraph) for Vacancy pages.
- **Sitemap:** Generate a dynamic `sitemap.xml` including all active categories and vacancies for Google indexing.

## 4. Polishing UI
- Implement a "Company Profile" edit page for employers.
- Ensure the "Apply" button is hidden or disabled for users with the `employer` role.

## 5. Execution Steps
1. Install and configure Filament PHP.
2. Create Employer Dashboard components (Volt).
3. Implement Status Change logic in `ApplicationService`.
4. Setup SEO metadata logic.
5. Update `CLAUDE_JOB_BOARD.md` to reflect the new management features.

*Note: Follow the strict architecture — logic goes to Services, UI stays in Volt/Filament.*