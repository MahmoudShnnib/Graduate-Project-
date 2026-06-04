---
date: 2026-06-04
tags: [daily, deployment, performance, n+1, whatsapp, otp, php-ini, indexes, planning]
related:
  - "[[2026-06-04-whatsapp-merge-and-state]]"
  - "[[2026-06-03-pre-deploy-cleanup-and-n1-plan]]"
  - "[[../decisions/2026-06-04-synchronous-notifications-and-timeouts]]"
  - "[[../project-context/whatsapp-integration-guide]]"
---

# 2026-06-04 — Deploy-readiness plan (re-checked after WhatsApp merge)

**Planning only — nothing executed this session.** Re-audited the pre-deploy plan now that WhatsApp OTP +
notifications are merged into `fixes-and-date`. The WhatsApp work changed the risk picture in three places
(marked 🆕 below). This note is the canonical plan to execute against.

## Verified current state

- **N+1 fixes**: ✅ implemented, **uncommitted** in working tree, 175 tests green. Just need `pint` + commit.
- **Notifications dispatch synchronously in-request** — `NotificationService::dispatch()` → `notify()`,
  `via()` = `['mail', WhatsAppChannel]`. Every approve-application / publish-grade / request-decision =
  1 SMTP send + 1 Qonvo HTTP call **inline**, blocking the staff request.
- **`WhatsAppService::sendText()` sets NO HTTP timeout** → a stalled Qonvo hangs `/apply` (OTP) and every
  admin action.
- **`MAIL_MAILER` = `log`** today (fast, no network). Flipping to real SMTP adds a *second* blocking call
  per notification.
- **Dockerfile ships no `php.ini`** → PHP defaults: `upload_max_filesize=2M`, `post_max_size=8M`,
  `memory_limit=128M`, `max_execution_time=30`.
- **nginx**: `client_max_body_size 20M`, `fastcgi_read_timeout 120`.
- **No DB index migration**, **no queue worker**, **no scheduler** in `docker/supervisord.conf`.
- **OTP endpoints already throttled** (`/apply/otp/send` 6,1; `/apply/otp/verify` 10,1). `whats_app_otps`
  already indexed on `(phone, purpose)` + `expires_at`.

---

## The plan

### P0 — Land done work
1. `vendor/bin/pint --dirty --format agent` → `php artisan test --compact` (175 must stay green) →
   **commit & push the N+1 pass** (15 files + the 3 merge-touched files: `exam-list.blade.php`,
   `create.blade.php`, `CreateStudent.php` — already verified clean in the working tree).

### P1 — Real-use correctness blockers (will actually break in prod)
2. **🆕 PHP `php.ini` in the Docker image.** nginx allows 20M uploads but PHP caps at 2M → the
   bachelor-certificate upload on `/apply` (now the public WhatsApp-OTP entry flow) silently fails. Set:
   - `upload_max_filesize=20M`
   - `post_max_size=21M`
   - `memory_limit=256M` (mPDF transcripts)
   - `max_execution_time=120` (align with nginx `fastcgi_read_timeout` — **no PDF chunking**, this is just
     timer alignment; see "PDF timer" below)
   - ⚠️ verify PHP-FPM pool `request_terminate_timeout` doesn't silently cap `max_execution_time`.
3. **🆕 Timeouts on external sends** (the thing that makes "keep synchronous" safe — see decision note).
   - `WhatsAppService::sendText()` → `->connectTimeout(5)->timeout(10)`.
   - Production mailer → a `timeout`.
   - Bounds worst-case block to ~10s; existing `try/catch` already degrades gracefully.
4. **Re-audit DB indexes** (changed from "apply the old 17"). The N+1 pass changed query shapes, so
   re-measure against the *current* slow queries with `DB::enableQueryLog()` / `EXPLAIN` before writing the
   migration. Real count TBD — do not trust the stale 17.

### P2 — Performance & robustness
5. **active-semester query** — request-scoped memoization is done in the 6 student pages; evaluate one
   shared cached resolver for the remaining ~20 call sites (`Semester::where('is_active', true)->first()`).
6. **🆕 OTP cleanup (infra-free)** — opportunistically delete expired `whats_app_otps` rows for the
   phone/purpose inside `WhatsAppOtpService::request()`. No scheduler needed (consistent with the
   no-worker decision).

### P3 — Deploy hygiene
7. Production `.env` (`APP_DEBUG=false`, `APP_ENV=production`, real `APP_URL`/DB/`MAIL_MAILER`,
   `QONVO_SENDER_PHONE`), **rotate the exposed Qonvo token**, merge Docker files (`Dockerfile`, `docker/`,
   `.dockerignore`) to `main`.

---

## Decisions folded in

- **Notifications stay synchronous; safety = timeouts, no queue worker.** Full rationale +
  revisit-trigger in [[../decisions/2026-06-04-synchronous-notifications-and-timeouts]].
- **PDF timer (`max_execution_time` vs nginx `fastcgi_read_timeout`)**: two independent timers currently
  disagree — nginx waits 120s but PHP kills the script at the 30s default, so the generous nginx timeout is
  dead weight. All PDFs here are single-subject (one student's transcript; one-record clearance/suspension/
  thesis form; one offering's roster), so realistic render is sub-second to a few seconds; the only slowish
  cost is mPDF first-render Arabic font subsetting (Noto Naskh + Markazi + DejaVu), still low single digits.
  **Resolution: set `max_execution_time=120` to align the timers (cheap insurance), NO chunking.** Trigger
  to revisit: an actual "execution time exceeded" on a specific PDF → then profile that document.

## Out of scope (explicitly skipped)
- Queue worker / scheduler.
- Production platform selection (Azure dropped; assume the existing Docker/supervisord target for now).
- WhatsApp delivery-failure visibility for staff (failed sends only `Log::warning` today — noted as a real
  gap if staff come to rely on WhatsApp, but skipped for this pass).

## How to resume / execute
> **"read brain and execute the deploy-readiness plan from 2026-06-04, starting at P0"**

Execute in order P0 → P3. After each change: `vendor/bin/pint --dirty --format agent` +
`php artisan test --compact` (175 green). Re-measure query counts with tinker where relevant.
</content>
