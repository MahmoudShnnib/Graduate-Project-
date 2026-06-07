---
date: 2026-06-07
tags: [daily, docker, deployment, compose]
related:
  - "[[../project-context/deployment-checklist]]"
  - "[[2026-06-04-deploy-readiness-plan]]"
  - "[[2026-06-04-production-hardening-execution]]"
---

# 2026-06-07 — Docker Compose for deployment

User requested a `docker-compose.yml` at the project root for production deployment.

## What was added

- **`docker-compose.yml`** — two-service stack:
  - **`app`** — builds from existing `Dockerfile` (nginx + PHP-FPM + supervisord on port 8080). Runs the existing `docker/entrypoint.sh` (migrate, seed, cache, start).
  - **`postgres`** — `postgres:16-alpine` with healthcheck; matches production checklist (PostgreSQL, not sqlite).
- **Volumes:**
  - `postgres_data` — database persistence
  - `app_uploads` → `/var/www/storage/app/public` — bachelor certificates, thesis proposals, generated PDFs (addresses deployment checklist **C1**)
- **Env wiring:** compose overrides `DB_*` to point at the `postgres` service; reads Laravel config from `.env` (optional until first deploy).

## How to deploy

```bash
cp .env.example .env
php artisan key:generate   # or key:generate --show and paste
# Set POSTGRES_PASSWORD, APP_URL, mail, QONVO_* in .env
docker compose up -d --build
```

Smoke: `http://localhost:8080/up`, then `/apply`.

## Next steps (operator)

- Set `SESSION_SECURE_COOKIE=true` when TLS terminates in front of the container.
- Rotate Qonvo token before go-live (see [[../project-context/deployment-checklist]]).
- Point Dokploy / reverse proxy at port 8080.

## Follow-up: Dokploy build failure (same day)

First deploy on Dokploy failed at `composer install` — Dockerfile used PHP 8.3 without `ext-intl` while `composer.lock` requires PHP 8.4+ and Filament needs intl. Fixed in `Dockerfile` (see [[../bugs-and-fixes/2026-06-07-docker-build-php84-intl]]). Push and redeploy.

## Follow-up: host port 8080 already allocated

Dokploy deploy failed binding `0.0.0.0:8080` — another stack on the server already uses it. Removed `ports` from `docker-compose.yml`; app only `expose`s 8080 on the compose network. Dokploy proxy routes by domain → container port 8080. Added `docker-compose.local.yml` for local `8080:8080` bind.

## Follow-up: Dokploy CET org — domain + env configured via MCP

- **Org:** CET (`AFzHcBQ8C5e85KMCTVQXi`)
- **Compose:** `regstration` / `cet-regstration-jrhh7q` (`QQR8-FfXOQhHLJ1rCYcec`)
- **Domain:** `https://cet-regstration-jrhh7q-613782-187-124-221-11.traefik.me` → service `app`, port **8080**, Let's Encrypt
- **Env saved:** production DB (postgres service), `SESSION_SECURE_COOKIE=true`, `APP_KEY` generated, `APP_URL` matches domain
- Removed duplicate traefik.me domain that pointed at port 80
- Redeploy queued
