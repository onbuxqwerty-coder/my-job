# Prompt 5: Monetization, Telegram Alerts & Production Readiness

**Context:** The core platform, employer dashboard, and admin panel are ready. Now we need to implement revenue streams and final production optimizations as per `CLAUDE_JOB_BOARD.md`.
**Goal:** Add paid vacancy promotion (Stripe), automated Telegram job alerts, and performance tuning.

---

## 1. Monetization: Featured Vacancies (Stripe/LiqPay)
- **Setup:** Install `laravel/cashier` or implement a custom Service for Stripe/LiqPay.
- **Database Update:** - Add `is_featured` (boolean, default: false) to `vacancies` table.
    - Add `featured_until` (timestamp, nullable) to `vacancies` table.
- **Employer Flow:**
    - Add a "Promote" button to the Employer Dashboard.
    - Implement a Checkout session (Stripe) for a fixed price (e.g., "Premium Listing - 30 days").
    - **Webhook Handler:** Create a secure webhook controller to handle `checkout.session.completed` and update the vacancy status.
- **Frontend:** - Featured vacancies must stay at the top of the search results (`orderByDesc('is_featured')`).
    - Add a visual "Premium" or "🔥" badge to featured vacancy cards.

## 2. Telegram Subscription Alerts (Retention)
- **Telegram Logic:** - Create a command `/alerts` in the Telegram Bot.
    - Allow users to subscribe to categories (e.g., "Notify me about 'IT' and 'Marketing' jobs").
    - Store subscriptions in the `telegram_subscriptions` table.
- **Automated Dispatcher:**
    - Create a Console Command `app:send-vacancy-alerts`.
    - **Logic:** Find all vacancies created in the last 24 hours and match them with active Telegram subscriptions.
    - Send a digest or individual messages to matched users via `TelegramService`.
    - Schedule this command to run hourly in `routes/console.php`.

## 3. Production Optimization & Security
- **Caching:** - Implement caching for the `Category` list and `Sitemap` (TTL: 24h).
    - Use `Cache::remember` in `SeoService`.
- **Rate Limiting:**
    - Apply `throttle` middleware to the Telegram Webhook and the Job Application form.
- **Clean-up:** - Implement a Daily Job to deactivate vacancies where `featured_until` has expired.
- **Performance:** - Run an audit of SQL queries in the `VacancyService` to ensure no N+1 problems (ensure `with(['company', 'category'])` is used everywhere).

## 4. Final System Audit
- Review all generated Services against `CLAUDE_JOB_BOARD.md`.
- Ensure strict typing (`declare(strict_types=1)`) and Constructor Property Promotion are used in all new classes.
- Verify that all sensitive keys are strictly retrieved from `config()` and `.env`.

## 5. Execution Steps
1. Configure Payments (Stripe/LiqPay).
2. Update Vacancy model and UI for "Featured" status.
3. Build the Telegram Alert system and Scheduler.
4. Perform final code cleanup and optimization.