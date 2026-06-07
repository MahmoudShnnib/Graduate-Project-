---
date: 2026-06-07
tags: [daily, auth, filament, session, docker, nginx, bug]
related:
  - "[[2026-06-07-cet-dokploy-deploy-live]]"
  - "[[../bugs-and-fixes/2026-06-07-login-refresh-loop]]"
---

# 2026-06-07 — Fix Filament login refresh loop

User reported: every login attempt on Filament panels just refreshes the page. Hint mentioned `FilamentUser` / `canAccessPanel` — verified `User` already implements both correctly.

## Investigation

- Reproduced on production `cet.itsyosefali.cloud`: valid admin credentials → redirect to `/admin/login?`, empty form, no error.
- Pattern = session cookie not surviving the post-login redirect (not a 403 / wrong-password validation).

## Changes

- `docker/nginx.conf` — forward `X-Forwarded-*` + `HTTPS` to PHP-FPM behind Traefik/Cloudflare.
- `AppServiceProvider` — force `https` URL scheme when `APP_URL` is HTTPS.
- `app/Filament/Auth/Login.php` + panel providers — full-page post-login redirect; SPA exceptions on login URLs.
- `User::canAccessPanel()` — legacy `secretary` role + `is_active` gate for staff.
- `RolesAndPermissionsSeeder` — repair `secretary` column users to `manager_assistant` Spatie role.
- `tests/Feature/AdminLoginTest.php` — new coverage (local CLI missing `pdo_sqlite` so not executed here).

## Next steps

- **Redeploy** Dokploy app so nginx config ships.
- Smoke-test `/admin/login` and `/portal/login` after deploy.
- Install `php8.4-sqlite3` locally (or run tests in CI) to execute `AdminLoginTest`.
