# Prompt 6: Production Launch & Monitoring Checklist

**Context:** The application is feature-complete (MVP). Now we need to prepare it for the real server and ensure stability.
**Goal:** Finalize production configs, setup error monitoring, and optimize assets.

---

## 1. Production Config Audit
- **Environment:** Update `.env` for production:
    - `APP_ENV=production`
    - `APP_DEBUG=false`
    - `TELESCOPE_ENABLED=false` (or restricted by email).
- **Optimization:** Run `php artisan optimize` commands (config, routes, views).

## 2. Asset Compilation & File Storage
- **Build:** Compile assets for production using `npm run build`.
- **Storage:** Verify that `php artisan storage:link` is executed on the server so that resumes and logos are accessible.
- **Cleanup:** Setup a scheduler task to clean up old temporary uploads.

## 3. Monitoring & Reliability
- **Error Tracking:** Instructions or logic to integrate **Sentry** or a simple Log-to-Telegram notification for critical `500` errors.
- **Queue Worker:** Setup **Supervisor** configuration to keep `php artisan queue:work` running 24/7.
- **Health Check:** Create a simple `/health` route that returns 200 OK if the DB and Redis are connected.

## 4. Final Bot & Webhook Setup
- **Webhooks:** Ensure `php artisan nutgram:hook:set` points to the production URL.
- **Stripe:** Verify Stripe Webhook secret matches the production dashboard.

## 5. Instructions for the Owner (Me)
- Provide a summary of the daily commands I need to run (or that the Scheduler handles).
- List the `.env` keys that MUST be updated before the first user arrives.