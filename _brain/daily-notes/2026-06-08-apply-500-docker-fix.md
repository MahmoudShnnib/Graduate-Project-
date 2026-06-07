---
date: 2026-06-08
tags: [docker, deployment, bug, apply]
related: [[2026-06-08-apply-500-docker-storage-permissions]]
---

# `/apply` 500 in production Docker — diagnosis & fix

## What was done

Investigated why every `/apply` submission 500'd in the Docker prod stack and why
`laravel.log` was empty. Applied infra fixes (no app-code changes needed):

- `docker/entrypoint.sh` — added `chown -R www-data:www-data storage bootstrap/cache`
  on boot. Root cause: `app_storage` volume not writable by `www-data` → certificate
  upload threw `UnableToWriteFile` → 500.
- `docker-compose.yml` — forced `LOG_CHANNEL=stderr` + `LOG_LEVEL` (errors were invisible
  because `.env` is excluded by `.dockerignore` and no `LOG_*` was passed via compose),
  and added the missing `MAIL_*` pass-through.

Full root-cause write-up: [[2026-06-08-apply-500-docker-storage-permissions]].

## Key discovery

`.env` is in `.dockerignore` → **prod config comes only from the compose
`environment:` block**, not `.env`. Anything not listed there uses framework defaults.

## Next steps

1. Deploy: `docker compose up -d --build`, then submit `/apply` once and confirm success.
2. If it still 500s, read `docker compose logs -f app` (now stderr-enabled) and share the trace.
3. After confirming, set `LOG_LEVEL=warning` in prod env (currently `debug` for diagnosis).
4. Consider setting real `MAIL_*` values so confirmation emails actually send.
