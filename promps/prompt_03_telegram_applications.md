# Prompt 3: Job Application System & Telegram Bot Integration

**Context:** The search interface and database are ready. Now we need to enable the "Apply" flow and connect the Telegram bot as defined in `CLAUDE_JOB_BOARD.md`.
**Goal:** Implement job applications logic and set up the Telegram Bot foundation using Nutgram.

---

## 1. Job Application System (The "Apply" Flow)
- **ApplicationService:** Create a service to handle candidate applications.
    - Logic to prevent duplicate applications from the same user to the same vacancy.
    - Logic to handle CV uploads (store files in `storage/app/public/resumes`).
- **UI: Vacancy Detail Page:**
    - Create a single vacancy view route: `/jobs/{slug}`.
    - Implement a Livewire Volt component for the "Apply" form.
    - Fields: `cover_letter` (text), `resume` (file upload: pdf, doc, docx).
    - Success state: show a "Thank you" message after a successful application.

## 2. Telegram Bot Integration (Nutgram)
- **Setup:** - Install `sergiogaspari/nutgram` via composer.
    - Publish the configuration and set the `TELEGRAM_TOKEN` in `.env`.
- **TelegramService:** Create a service to encapsulate bot logic.
- **Command `/start`:**
    - Implement basic "Deep Linking" support. 
    - If a user arrives via `t.me/bot?start=job_{id}`, show summary of that specific job.
    - Provide a button to "View on Website".
- **Account Linking:**
    - Create a simple way to link `telegram_id` to the `User` model (e.g., via a unique token or login).

## 3. Real-time Notifications (Queues)
- **Notification Job:** Create a `SendNewApplicationNotification` job.
- **Logic:** - When an application is created, dispatch this job to notify the **Employer** via Telegram.
    - Message template: "New application for [Job Title]! Candidate: [Name]. View resume: [Link]".
- Ensure the job uses the `TelegramService` and runs asynchronously via the `database` or `redis` queue.

## 4. Execution Steps
1. Create the `ApplicationService`.
2. Generate the single vacancy page and "Apply" Volt component.
3. Install and configure Nutgram.
4. Implement the `/start` command and the notification Job.
5. Update `CLAUDE_JOB_BOARD.md` status if necessary.

*Note: Maintain the strict typing and Service Layer architecture as per the master system prompt.*