---
date: 2026-06-07
tags: [bug, docker, deployment, php, intl]
related:
  - "[[../daily-notes/2026-06-07-docker-compose-deploy]]"
  - "[[../project-context/deployment-checklist]]"
---

# Docker build failed on Dokploy — PHP 8.3 + missing ext-intl

## Symptoms

Dokploy `docker compose up --build` failed at `composer install` in the Dockerfile:

- `filament/support v5.3.2 requires ext-intl * -> it is missing`
- `spatie/laravel-permission 7.2.4 requires php ^8.4` (and Symfony 8 components)
- Base image was `php:8.3-fpm-alpine` while `composer.lock` targets PHP 8.4+

## Root cause

The Dockerfile predated a `composer update` that pulled Symfony 8 / Spatie Permission 7 (PHP 8.4 floor) and Filament's hard `ext-intl` requirement. Local dev runs PHP 8.4+ with intl enabled; the container did not.

## Fix

In `Dockerfile`:

1. Bump base image to `php:8.4-fpm-alpine`.
2. Add `icu-dev` to apk packages.
3. Add `intl` to `docker-php-ext-install`.
4. `mkdir -p storage/framework/{cache/data,sessions,views}` + `storage/logs` + `bootstrap/cache` before `composer dump-autoload` — `.dockerignore` excludes the framework cache dirs so `artisan package:discover` fails with "Please provide a valid cache path" without this.

## Regression guard

Redeploy on Dokploy — build must pass the `composer install` layer. Smoke `/up` after containers start.
