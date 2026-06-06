---
date: 2026-06-04
tags: [deployment, security, performance, ops, docker, hardening]
related:
  - "[[../project-context/production-hardening-backlog]]"
  - "[[2026-06-04-deploy-readiness-plan]]"
  - "[[2026-06-04-yearweek-postgres-crash]]"
---

# Production-hardening backlog — execution

Executed the full [[../project-context/production-hardening-backlog|production-hardening backlog]]
**except the file-storage decision (C1 + H7)**, which stays deferred until the hosting platform is
chosen. Work left **uncommitted** for review (per [[no-auto-commit]] preference).

## Done (3 batches)

**Batch 1 — docker hardening** (config only, not locally runtime-verifiable):
- H1 OPcache block in `docker/php.ini` (`validate_timestamps=0`, 256M, 20k files).
- H3 gzip + H4 security headers in `docker/nginx.conf` (HSTS left commented until TLS terminates in front).
- M3 new `docker/php-fpm.conf` → `zz-www.conf` pool override (`pm.max_children=20`) — pool dirs live in
  `php-fpm.d`, NOT `php.ini`.
- M4 `HEALTHCHECK` (busybox `wget /up`) + COPY the pool conf in `Dockerfile`.
- M5 graceful shutdown in `docker/supervisord.conf` (`stopsignal=QUIT`, `stopwaitsecs=130 > fastcgi 120`,
  `stopasgroup`).
- M6 entrypoint adds `event:cache` + `filament:optimize` (bundles icons + components).
- M9 replaced fragile `tinker --execute | grep` first-deploy detection with a dedicated
  `app:check-fresh-install` command (exit 0 = fresh).

**Batch 2 — app config/security:**
- C2 `trustProxies(at:'*')` in `bootstrap/app.php` + `SESSION_SECURE_COOKIE` documented in `.env.example`.
- H5 `config/app.php` timezone → `env('APP_TIMEZONE', 'Africa/Tripoli')` + `.env.example`.
- H6 `.env.example` safe defaults: `APP_DEBUG=false`, `LOG_LEVEL=warning`.
- M2 `throttle:6,1` on the `/apply` POST (`registration.store`).

**Batch 3 — code fixes + tests:**
- H2 weekly chart no longer uses MySQL-only `YEARWEEK()` → buckets by `DATE()` in PHP
  (see [[2026-06-04-yearweek-postgres-crash]]).
- M7 Arabic-RTL error pages `errors/{layout,404,403,500}.blade.php` (self-contained, no Vite dependency).
- M1 **already enforced** — every `PrintController` method calls `authorizeAdminOnly()` /
  `authorizeAdminOrCourseFaculty()`; `PrintCourseStudentsController` aborts non-admins. Added a regression
  test rather than new code.
- M8 `ReportsPage` student picker now searchable + `limit(50)` (was `->get()` over all students).

## Verification
- Full suite green: **188 passed / 403 assertions**; pint `{"result":"pass"}`.
- `config:cache` / `route:cache` / `event:cache` all succeed → trustProxies, throttle, tz env all valid.
- New tests: `tests/Feature/ApplicationsOverTimeWidgetTest.php`,
  `tests/Feature/ProductionHardeningTest.php`.

## Loose ends / next steps
- **C1 + H7 storage** still the only open backlog items — depends on platform (persistent volume vs S3).
- Docker/nginx changes need first-deploy confirmation (build, `nginx -t`, OPcache active, HEALTHCHECK,
  TLS → `SESSION_SECURE_COOKIE=true` + HSTS). Checklist in the backlog doc.
- Pre-existing (out of scope, noticed): print routes use bare `auth` middleware which redirects to a
  non-existent `login` route for guests — only reachable by an unauthenticated hit to `/admin/...print`.

## Misc (env gotcha)
- "Unable to connect" to `http://127.0.0.1:8000/apply/track` was just **no dev server running for this
  project** — a stray `php artisan serve` on `:6969` belonged to a different repo (`mobile pos`). Started
  one on `:8000` → 200. See [[2026-06-04-dev-server-wrong-port]].
