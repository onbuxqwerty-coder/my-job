# Prompt 1: Project Initialization and Database Architecture

**Context:** Developing a general job board platform using Laravel 12.
**Goal:** Initialize the project, set up authentication, and create the core database structure.

---

## 1. Project Setup
- Create a new Laravel 12 project.
- Install **Laravel Breeze** using the **Livewire (Volt Class API)** and **Tailwind CSS** stack.
- Enable strict types by adding `declare(strict_types=1);` to all newly generated PHP files.

## 2. Database Schema & Models
Create migrations, models, and factories for the following entities:

### User (Extended)
- Add `role` field (enum: `candidate`, `employer`, `admin`) with default 'candidate'.

### Category
- Fields: `name` (string), `slug` (unique), `icon` (string, nullable).
- *Examples: IT, Sales, Construction, Healthcare.*

### Company
- Fields:
    - `user_id` (foreignId, constrained, unique - link to employer).
    - `name` (string), `slug` (unique).
    - `logo` (string, nullable), `description` (text).
    - `website` (string, nullable), `location` (string).

### Vacancy
- Fields:
    - `company_id` (foreignId, constrained).
    - `category_id` (foreignId, constrained).
    - `title` (string), `slug` (unique).
    - `description` (longText).
    - `salary_from` (integer, nullable), `salary_to` (integer, nullable).
    - `currency` (string, default: 'UAH').
    - `employment_type` (enum: 'full-time', 'part-time', 'remote', 'hybrid', 'contract').
    - `is_active` (boolean, default: true).
    - `published_at` (timestamp, nullable).

### Application (Job Applications)
- Fields:
    - `vacancy_id` (foreignId, constrained).
    - `user_id` (foreignId, constrained - candidate).
    - `resume_url` (string), `cover_letter` (text, nullable).
    - `status` (enum: 'pending', 'accepted', 'rejected', 'hired' - default: 'pending').

## 3. Relationships
Define the following in the Eloquent models:
- **User**: Has one `Company` (as employer), has many `Applications` (as candidate).
- **Company**: Has many `Vacancies`.
- **Category**: Has many `Vacancies`.
- **Vacancy**: Belongs to `Company`, belongs to `Category`, has many `Applications`.
- **Application**: Belongs to `Vacancy`, belongs to `User`.

## 4. Execution
Please generate the migrations, models with relationships, and corresponding factories. Ensure you use the latest Laravel 12 syntax and features.