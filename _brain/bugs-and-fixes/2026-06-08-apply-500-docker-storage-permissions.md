---
date: 2026-06-08
tags: [bug, docker, deployment, apply, storage, logging]
related: [[2026-06-06-semester-scoped-applications]]
---

# `/apply` returns 500 in production (Docker)

## Symptoms

Every submission of the public `/apply` form returned **HTTP 500** from nginx in the
production Docker stack. `storage/logs/laravel.log` appeared empty, so the real
exception was invisible.

## Why the log was empty (red herring that wasted time)

`.dockerignore` excludes **`.env`**, so the `.env` (with `LOG_STACK=single,stderr`,
`LOG_LEVEL=warning`) is **never baked into the image**. In the container, config comes
*only* from the `docker-compose.yml` `environment:` block, which passed **no `LOG_*`
vars**. Laravel therefore fell back to `config/logging.php` defaults:
`LOG_CHANNEL=stack` → `LOG_STACK=single` (file only, **no stderr**). So errors went to
`storage/logs/laravel.log` — which is (a) **not** in the persisted volume (only
`storage/app` is), so it's wiped on every rebuild, and `storage/logs/` is also in
`.dockerignore`. Net effect: nothing visible in `docker compose logs`, file ephemeral.

> Broader implication: **all** prod config comes from the compose `environment:` block,
> not `.env`. Anything missing there silently uses framework defaults (this is also why
> `MAIL_*` was on the `log` driver).

## Root cause of the 500

The `app_storage` named volume is mounted over `/var/www/storage/app`. A named volume
that pre-exists (or is seeded as root) keeps its **own ownership**, overriding the
Dockerfile's build-time `chown`. When `www-data` (PHP-FPM) can't write
`storage/app/public`, the certificate upload
(`->store('applications', 'public')` in `RegistrationController::store`) throws
`UnableToWriteFile` — **uncaught** → 500.

Ruled out:
- **Notifications** — `NotificationService::dispatch` wraps `notify()` in try/catch; a
  bad mailer/WhatsApp config cannot 500 the form.
- **No active semester** — `applications.semester_id` is nullable
  (`2026_05_10_221406_add_semester_id_to_applications_table`), so a null insert is fine.

## Fix

1. **`docker/entrypoint.sh`** — `chown -R www-data:www-data storage bootstrap/cache`
   at the top of boot (idempotent), so the volume is always writable. *This is the 500 fix.*
2. **`docker-compose.yml`** — force `LOG_CHANNEL=stderr` + `LOG_LEVEL` so every error
   surfaces in `docker compose logs app` (12-factor, nothing to lose on rebuild).
3. **`docker-compose.yml`** — added `MAIL_*` pass-through env (was silently on `log`).

## Regression guard

Permission fix runs on every container start, so it self-heals. To verify after deploy:

```sh
docker compose exec app sh -c 'su -s /bin/sh www-data -c "touch storage/app/public/_t && echo OK && rm storage/app/public/_t"'
```
