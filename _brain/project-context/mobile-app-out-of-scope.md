---
date: 2026-05-10
tags: [scope, mobile, project-boundary, do-not-touch]
related: [[../decisions/2026-05-10-mobile-app-out-of-scope]]
---

# Mobile app is OUT OF SCOPE — do not work on it, do not commit it

> **HIGH PRIORITY — read this before touching anything.** The mobile app was personal exploration, not part of the graduation project deliverable. Every future session must treat it as if it does not exist.

## What is out of scope

- The entire `mobile/` directory (React Native / Expo app + all its primitives, screens, theme, package.json, etc.)
- `database/seeders/MobileTestStudentSeeder.php` — existed only to feed the mobile sandbox
- Any API surface (e.g. `routes/api.php`, Sanctum tokens for mobile, `app/Http/Controllers/Api/*`) that was added solely for the mobile app
- The `mobile-test` git branch is a personal sandbox
- Any `_brain/` notes that talk about the mobile app (`2026-05-09-mobile-*`, `2026-05-09-mobile-design-direction-edutrack.md`, `2026-05-09-mobile-raised-center-tab-bar.md`, etc.) are historical only — do not treat their guidance as project requirements

## Rules for every session

1. **Do NOT** analyze, suggest changes to, or improve anything under `mobile/`.
2. **Do NOT** factor the mobile app into project summaries, architecture overviews, feature lists, or design proposals. When asked "what is this project?", describe only the Laravel + Filament admin panel + student portal.
3. **Do NOT** include mobile changes in any commit. If `git status` shows modified `mobile/*` or `MobileTestStudentSeeder.php`, omit them from `git add` calls — even if the user says "commit everything", confirm first before staging mobile files.
4. **Do NOT** run mobile builds, `npm` scripts inside `mobile/`, or Expo commands.
5. **Do NOT** create or update mobile-related `_brain/` notes.
6. **Do NOT** revert the existing mobile commits on `mobile-test` (`92f7c31`, `f91c35b`, `f3c3d22`) — they're harmless history. Just stop adding to them.
7. If the user explicitly and unambiguously asks for mobile work, confirm once ("the mobile app is marked out of scope — do you still want me to work on it?") before touching it.

## What is in scope (for clarity)

- `app/`, `bootstrap/`, `config/`, `database/` (except `MobileTestStudentSeeder.php`), `resources/`, `routes/web.php`, `tests/`, `public/`
- The Laravel 12 + Filament v5 web system: admin panel (`/admin`), student portal (`/portal`), public application flow (`/apply`)
- Everything covered in [[project-overview]], [[architecture]], [[gotchas]], [[design-conventions]]

## Already-committed mobile work

Three existing commits on `mobile-test` contain mobile code. Leave them in place. Do not revert, rebase, or cherry-pick around them. The branch can stay as-is.
