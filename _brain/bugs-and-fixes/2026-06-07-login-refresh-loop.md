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

`User` already implemented `FilamentUser` + `canAccessPanel()` — that was **not** the primary issue.

Two bugs stacked:

1. **`App\Filament\Auth\Login::authenticate()` returned `null` after success** instead of Filament's `LoginResponse`. Livewire re-rendered the login form — looks like an instant refresh with no error (Filament [#11107](https://github.com/filamentphp/filament/discussions/11107) symptom).
2. **Session cookie not sent on the next request** behind Traefik when `X-Forwarded-Proto` / `HTTPS` were missing in nginx → PHP.

Contributing factors:

- Filament **SPA mode** navigating before the regenerated cookie is committed.
- **nginx gzip** on PHP responses can break Livewire updates in production ([#11107 comment](https://github.com/filamentphp/filament/discussions/11107)).
- Legacy `users.role = 'secretary'` denied by `canAccessPanel()` — secondary.

## Fix

- `app/Filament/Auth/Responses/LoginResponse.php` + bind in `AppServiceProvider` — return proper redirect response after auth.
- `app/Filament/Auth/Login.php` — **return** `parent::authenticate()` on success; wrong-panel handling only in `mount()` / failed validation.
- `docker/nginx.conf` — forward proxy headers; **`gzip off`** in PHP location.
- Both panel providers — **removed `->spa()`** (re-enable later with login URL exceptions if needed).
- `User::canAccessPanel()` — legacy `secretary` + `is_active` gate.
- `tests/Feature/AdminLoginTest.php` — regression guard.

## Operator follow-up

Redeploy the Docker image on Dokploy so nginx changes take effect. Confirm env:

- `APP_URL=https://cet.itsyosefali.cloud` (exact public URL)
- `SESSION_SECURE_COOKIE=true` (production HTTPS)

## Regression guard

`tests/Feature/AdminLoginTest.php`
