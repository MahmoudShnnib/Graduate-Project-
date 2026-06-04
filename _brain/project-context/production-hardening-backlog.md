---
tags: [project-context, deployment, security, performance, ops, backlog, durable]
related:
  - "[[deployment-checklist]]"
  - "[[../daily-notes/2026-06-04-deploy-readiness-plan]]"
  - "[[../decisions/2026-06-04-synchronous-notifications-and-timeouts]]"
---

# Production-hardening backlog (beyond the agreed deploy plan)

Findings from a security + performance + ops audit (2026-06-04), **verified against the real code** —
several agent over-claims were corrected (see "Confirmed safe" below). This is the durable backlog to
work through after the P0–P2 deploy-readiness work (PR #43). **Nothing here is executed yet — planning.**

> Batching suggestion: (a) one "docker hardening" pass → H1, H3, H4, M3–M6; (b) HTTPS/proxy + cookie → C2;
> (c) storage decision → C1/H7 (needs a call: persistent volume vs object storage); (d) YEARWEEK fix → H2.

## ✅ Confirmed safe (audited, NOT issues — don't re-flag)
- **Public file-upload validation is fine** — `bachelor_certificate` / `remedial_statement` validated
  `['nullable','file','mimes:pdf,jpg,jpeg,png','max:5120']` (`RegistrationController` store, ~line 153-154).
- **No blade XSS** — every `{!! !!}` in `resources/views/prints/*` wraps data in `e()` first
  (`e($enrollmentNo)`, `e($semesterName)`, …) before the digit-span `preg_replace`.
- **No sensitive log leaks** — only `WhatsAppOtpService.php:85` logs in that area; it logs the exception,
  never the OTP code / phone / token / password.
- Print routes are `auth`-gated; `/apply/otp/*` + `/apply/track` are throttled; health route `/up` exists
  (`bootstrap/app.php:11`); composer autoloader + Vite build are optimized; static assets cached 1y.

## 🔴 Critical — before go-live
- **C1 — Uploaded files on ephemeral container storage.** `->store('applications','public')` →
  `storage/app/public`; Dockerfile has no `VOLUME`. Applicants' certificates are **lost on every
  redeploy**. Fix: persistent volume mount for `storage/app/public`, OR move to S3-compatible object
  storage (cleaner for containers). [ops] [verified]
- **C2 — HTTPS-behind-proxy broken.** No `TrustProxies` in `bootstrap/app.php` (`->withMiddleware` block is
  empty, line ~13) and `SESSION_SECURE_COOKIE` unset (`config/session.php:172` = `env(...)` → null). Behind
  nginx/LB TLS, secure cookies + `https` URL generation fail. Fix: `$middleware->trustProxies(at: '*')` +
  `SESSION_SECURE_COOKIE=true`. [sec] [verified]

## 🟠 High
- **H1 — OPcache installed but unconfigured.** Dockerfile installs `opcache`, but `docker/php.ini` has no
  opcache block → `validate_timestamps=1` (stats every file every request). Biggest easy perf win. Fix in
  `docker/php.ini`: `opcache.validate_timestamps=0`, `opcache.memory_consumption=256`,
  `opcache.max_accelerated_files=20000`, `opcache.revalidate_freq=0`. [perf] [verified]
- **H2 — `YEARWEEK()` crashes on PostgreSQL.** `app/Filament/Widgets/ApplicationsOverTimeWidget.php:68`
  uses `YEARWEEK(created_at, 1)` (MySQL-only). The weekly chart errors in prod. (Line 43 `DATE(created_at)`
  is fine — Postgres has `date()`.) Fix: `DATE_TRUNC('week', created_at)` or aggregate in PHP. [perf/bug]
  [verified]
- **H3 — No nginx gzip.** Arabic UTF-8 HTML compresses ~3–4×; large TTFB win. Add `gzip on;` + types to
  `docker/nginx.conf`. [perf] [verified]
- **H4 — No nginx security headers.** Add `X-Frame-Options SAMEORIGIN`, `X-Content-Type-Options nosniff`,
  `Referrer-Policy strict-origin-when-cross-origin`, HSTS (once HTTPS) to `docker/nginx.conf`. [sec]
  [verified]
- **H5 — Timezone hardcoded `UTC`.** `config/app.php:68`. Libya is UTC+2 → exam/deadline times display &
  enforce 2h off. Fix: `'timezone' => env('APP_TIMEZONE', 'Africa/Tripoli')` + set in `.env`. [ops/bug]
  [verified]
- **H6 — `.env.example` ships `APP_DEBUG=true`, `LOG_LEVEL=debug`** (lines 4, 21). Leak risk if prod isn't
  overridden. Set safe production defaults + reinforce in the deployment checklist. [sec] [verified]
- **H7 — Uploaded certificates world-readable by URL.** `public` disk + `storage:link` → anyone with the
  (random) filename can fetch applicant PII without auth. Fix: serve via an auth-gated route instead of the
  public symlink (ties into C1's storage decision). [sec/privacy] [verified]

## 🟡 Medium
- **M1 — Print routes `auth` but maybe not *authorized*.** `->middleware(['auth'])` only checks login.
  Confirm `PrintController` enforces a per-record policy/role — otherwise a logged-in student could fetch
  another student's grades/certificate PDF (IDOR). [sec] [CONFIRM in PrintController]
- **M2 — `/apply` POST (submission) unthrottled.** Only OTP/track routes throttle. Add `throttle` to the
  submission route (`routes/web.php:13`). [sec] [verified]
- **M3 — PHP-FPM pool at alpine default** (`pm.max_children=5`). One slow PDF blocks others under load.
  Tune the pool (own pool conf or `docker/php.ini` `[www]`). [perf/ops] [verified]
- **M4 — No Docker `HEALTHCHECK`.** Route `/up` exists; just wire `HEALTHCHECK … CMD … /up` in Dockerfile.
  [ops] [verified]
- **M5 — supervisord lacks graceful shutdown** (`stopsignal=SIGTERM`, `stopwaitsecs`, `stopasgroup`) —
  in-flight requests killed on deploy. `docker/supervisord.conf`. [ops] [verified]
- **M6 — Entrypoint misses `event:cache` / Filament `optimize` / `icons:cache`** (only config/route/view
  cached). `docker/entrypoint.sh`. [perf] [verified]
- **M7 — No custom 404/403/500 pages** (`resources/views/errors/`). Arabic-RTL UX + avoid framework pages.
  [ops/ux] [verified]
- **M8 — `ReportsPage` loads all students into memory** (`->get()` then map, ~line 26). Fine now, scales
  poorly → searchable/lazy select. [perf] [CONFIRM]
- **M9 — Live migrations on deploy** (`migrate --force`, no maintenance window) + fragile
  `tinker --execute` first-deploy detection in `docker/entrypoint.sh:17`. [ops] [verified]

## ⚪ Low / monitor
- DB-driver cache + session — acceptable at single-faculty scale; Redis is the upgrade path if concurrency
  grows. Document, don't change now.
- `SESSION_LIFETIME=120` → consider 60 for an institution.
- OPcache preload/warmup after H1.

## Notes
- Almost everything clusters into: `docker/php.ini`, `docker/nginx.conf`, `docker/supervisord.conf`,
  `docker/entrypoint.sh`, `Dockerfile`, `bootstrap/app.php`, `.env.example` — plus one widget (H2) and the
  storage decision (C1/H7).
- The only true go-live blockers are **C1** (data loss) and **C2** (HTTPS/proxy).
</content>
