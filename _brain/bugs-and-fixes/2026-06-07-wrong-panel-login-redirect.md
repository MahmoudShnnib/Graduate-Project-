---
date: 2026-06-07
tags: [bug, auth, filament, dual-panel, redirect-loop]
related:
  - "[[2026-06-07-login-refresh-loop]]"
  - "[[../project-context/architecture]]"
---

# Wrong-panel login redirect loop (admin vs portal)

## Context

Generic Filament multi-tenant advice: when `canAccessPanel()` / `canAccessTenant()` denies access after auth, users bounce between login and dashboard. This project has **two panels** (admin + student portal), not tenants — same pattern.

## Symptoms

- Student already logged in visits `/admin/login` → endless refresh or 403 bounce.
- Staff enters valid credentials on `/portal/login` → generic "failed" or 403.
- Authenticated staff opens `/portal` → stuck on forbidden instead of admin login.

## Fix

- `User::preferredPanelId()` — returns `admin` or `student` from role + `canAccessPanel`.
- `App\Filament\Auth\Login`:
  - `mount()` — if authenticated but wrong panel, **logout**, invalidate session, redirect to preferred panel login (Arabic notification).
  - `authenticate()` — valid credentials on wrong panel → redirect to preferred login instead of generic failure.
- `bootstrap/app.php` — 403 on `/admin/*` or `/portal/*` for authenticated wrong-panel user → logout + redirect to preferred login.

## Regression guard

`tests/Feature/AdminLoginTest.php` (requires `pdo_sqlite` locally).

## Deploy

Redeploy Docker image on Dokploy after push to `main`.
