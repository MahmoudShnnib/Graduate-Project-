---
tags: [project-context, deployment, production, checklist, durable]
related:
  - "[[../daily-notes/2026-06-04-deploy-readiness-plan]]"
  - "[[../decisions/2026-06-04-synchronous-notifications-and-timeouts]]"
  - "[[whatsapp-integration-guide]]"
---

# Deployment checklist — demo → real production

Durable go-live checklist. Code/perf hardening (P0–P2) is **done and on `fixes-and-date`**.
What remains is environment, secrets, and the merge — actions that are inherently the operator's.

## ✅ Done in code (on `fixes-and-date`)
- N+1 query elimination across resources, student pages, `Setting` (static memo).
- 14 performance indexes migration (`2026_06_04_011121_add_performance_indexes`) — production is
  **PostgreSQL**, where FK columns have no automatic index.
- Production `php.ini` in the Docker image (`docker/php.ini`): uploads 20M, post 21M, memory 256M,
  `max_execution_time` 120.
- HTTP timeouts: Qonvo `connectTimeout(5)/timeout(10)`; SMTP `MAIL_TIMEOUT` (default 10s).
- Active-semester lookups routed through one memoized resolver (`Semester::active()/activeId()`).
- Opportunistic OTP-row pruning (no scheduler needed).
- Notifications remain **synchronous by design** — see
  [[../decisions/2026-06-04-synchronous-notifications-and-timeouts]].

## 🔓 Operator actions before go-live (NOT code)

### 1. Production `.env`
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set (`php artisan key:generate`).
- [ ] `APP_URL` = the real https domain (wrong value breaks `route()` and PDF asset URLs).
- [ ] `DB_CONNECTION=pgsql`, `DB_HOST/PORT(5432)/DATABASE/USERNAME/PASSWORD`.
- [ ] `MAIL_MAILER` = real transport (smtp/postmark/resend/ses) + credentials + `MAIL_FROM_ADDRESS`.
      (Default is `log` — no mail actually sends until this is set.) `MAIL_TIMEOUT=10` optional.
- [ ] `QONVO_API_TOKEN` + `QONVO_SENDER_PHONE` (the WhatsApp number **authenticated in the Qonvo
      dashboard** — until both are set, OTP + WhatsApp notifications are silently disabled).
- [ ] After editing: `php artisan config:clear` (the Docker entrypoint already runs `config:cache`).

### 2. Rotate the exposed Qonvo token
- [ ] The API token was pasted in plaintext during development — **rotate it** in the Qonvo dashboard
      and use the new value in production `.env`.

### 3. Choose the hosting platform
- [ ] Azure for Students was dropped (2026-06-03). Pick the host. The current setup assumes a
      **container** target (Dockerfile + `docker/` supervisord/nginx). A non-container host changes the
      `php.ini`/supervisord steps.

### 4. Merge
- [ ] Merge the `fixes-and-date` PR into `main` (includes all P0–P2 work + Docker files).

## 🔎 First-deploy verification (smoke test)
- [ ] `phpinfo()` (or `php -i`) shows `upload_max_filesize = 20M`, `memory_limit = 256M`.
      Confirms `docker/php.ini` loaded and PHP-FPM `request_terminate_timeout` isn't capping it.
- [ ] Upload a >2MB bachelor certificate on `/apply` — must succeed (was the silent 2M failure).
- [ ] If Qonvo configured: send a test OTP on `/apply`; a `422` from Qonvo = sender number not
      authenticated (scan QR in dashboard), **not** a bad token.
- [ ] `php artisan whatsapp:test <number>` to live-check all 9 notifications + OTP wording.
- [ ] Approve a test application — confirm the welcome email/WhatsApp fires and the staff action
      returns promptly (timeouts bound it to ~10s worst case).
- [ ] Generate a transcript PDF — confirm it renders within the time budget.

## ⏭️ Deferred (revisit triggers in the decision note)
- Queue worker + scheduler — only if action latency becomes a complaint or batch deadline-reminders
  are added.
- WhatsApp delivery-failure visibility for staff (failed sends currently only `Log::warning`).
</content>
