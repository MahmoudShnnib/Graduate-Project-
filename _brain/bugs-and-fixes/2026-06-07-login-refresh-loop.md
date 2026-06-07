---
date: 2026-06-07
tags: [bug, auth, filament, session, docker, nginx, spa]
related:
  - "[[2026-06-07-cet-dokploy-deploy-live]]"
  - "[[2026-06-06-semester-scoped-applications]]"
  - "[[../project-context/production-hardening-backlog]]"
---

# Login page refresh loop after submit

## Symptoms

- On `/admin/login` (and `/portal/login`), submitting valid credentials clears the form and lands back on `/admin/login?` with no error message — looks like an instant page refresh.
- Reproduced on production `https://cet.itsyosefali.cloud/admin/login` with seeded `admin@college.edu` / `admin123`.

## Root cause

`User` already implemented `FilamentUser` + `canAccessPanel()` — that was **not** the issue.

The real failure mode: **login succeeds and `session()->regenerate()` runs, but the next request arrives without the new session cookie**, so Filament's auth middleware sends the user back to login.

Contributing factors behind TLS-terminating proxies (Traefik / Cloudflare → nginx → PHP-FPM):

1. **`docker/nginx.conf` did not forward `X-Forwarded-Proto` / `HTTPS` to PHP**, so Laravel could not treat requests as secure consistently.
2. **Filament SPA mode** (`->spa(hasPrefetching: true)`, added 2026-06-06) can navigate after login before the regenerated cookie is committed.
3. Legacy `users.role = 'secretary'` (pre-rename migration) was denied by `canAccessPanel()` — secondary issue for older DB rows.

## Fix

- `docker/nginx.conf` — map `X-Forwarded-Proto` → `fastcgi_param HTTPS` + forward proxy headers to PHP-FPM.
- `AppServiceProvider` — `URL::forceScheme('https')` when `APP_URL` is `https://…`.
- `app/Filament/Auth/Login.php` — custom login page forces `redirectIntended(..., navigate: false)` after successful auth.
- Both panel providers — `spaUrlExceptions` for `*/admin/login` and `*/portal/login`; dropped prefetching.
- `User::canAccessPanel()` — accept legacy `secretary` role column; require `is_active` for staff roles.
- `RolesAndPermissionsSeeder::repairUserRoles()` — map `secretary` column → `manager_assistant` Spatie role.
- `tests/Feature/AdminLoginTest.php` — guards `FilamentUser` + login redirect (needs `pdo_sqlite` locally to run).

## Operator follow-up

Redeploy the Docker image on Dokploy so nginx changes take effect. Confirm env:

- `APP_URL=https://cet.itsyosefali.cloud` (exact public URL)
- `SESSION_SECURE_COOKIE=true` (production HTTPS)

## Regression guard

`tests/Feature/AdminLoginTest.php`
