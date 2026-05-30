---
date: 2026-05-15
tags: [decision, i18n, arabic, design-language]
related: [[2026-05-15-admin-ux-audit|audit report]] [[fix-plan]] [[design-conventions]]
---

# Decision — Arabic-first promise is leaking in 6+ places

## Context

The project is Arabic-first RTL. `CLAUDE.md` enshrines this:

> "Every UI string, notification message, validation error, and enum label is authored in Arabic. English is used only for raw identifiers (emails, enrollment numbers, English name field)."

The 2026-05-15 UX audit surveyed all 20 admin routes + 7 portal pages across 4 roles. Six distinct **categories of leak** were found where English bleeds into the Arabic-first UI. Some leaks are config-level (5-minute fixes), some are systemic (require a translation key audit), and one is a schema decision.

## Decision

Adopt **"if a user can see it, it must be Arabic"** as a hard project rule. Treat any English string visible to end users (admin, secretary, faculty, student) as a P1 bug.

The leak categories, ordered by ease of fix:

1. **`APP_NAME=Laravel`** — leaks to `<title>`, sidebar brand, every PDF header.
   - Fix: one-line `.env` change.
   - **L-1 / L-2** in audit.

2. **Custom Livewire pages without explicit `getTitle()` or `$navigationLabel`** — Filament falls back to kebab-cased class names: `Reports Page`, `Student Dashboard`, `Grades Page`, etc.
   - Affected (audit-confirmed): `ReportsPage`, `CoursesSurveyResultsPage` on admin; `StudentDashboard`, `StudentCoursesPage`, `GradesPage`, `CourseSurveyPage` on portal.
   - Fix: each affected class needs `getTitle()` + `$navigationLabel`.
   - **H-4 / H-8** in audit.

3. **Missing translation keys in `emptyState*()` partials** — `لا توجد students`, `لا توجد student enrollments`.
   - 3+ instances confirmed; pattern is "Arabic preposition + raw English noun".
   - Fix: grep all `emptyState*` calls + set Arabic strings inline.
   - **M-13** in audit (promoted to "systemic" after round 5).

4. **Filepond + 3rd-party widget locale strings** — `تصفح Files`, `(Hard Copy)`.
   - Fix: publish the relevant Filepond/Filament locale via `vendor:publish` and activate Arabic.
   - **M-16** in audit.

5. **HTML5 native validation messages** — browser-native English on Arabic-first forms when a required field is empty.
   - Fix: add `novalidate` to Filament forms so server-side Arabic validation renders.
   - **H-7** in audit.

6. **Single `name` field on models with user-visible names** — `Course.name` is English-only because the migration didn't add `name_ar`. `Semester.name` accepts whatever was typed (audit saw `summer 2026` mixed with `ربيع 2026`).
   - Fix: schema decision. Recommended: add `name_ar` for `courses`. For `semesters`, enforce Arabic via factory + validation.
   - **M-10 / M-14** in audit.

## Alternatives considered

**Alt A — Live with English in technical contexts.** Acceptable for emails / tracking codes / enrollment numbers (already excluded). Not acceptable for course names, page titles, empty states — those are user-content surfaces.

**Alt B — Auto-translate at render time.** Would need a translation service + caching. Higher complexity, lower trust. Rejected for now; revisit only if the project ever needs full English locale support.

**Alt C — Status quo (do nothing).** Rejected — the audit shows the gap is *visible to native Arabic speakers immediately*, and the leakage scales as the codebase grows.

## Consequences

- Every new Filament Page must include `getTitle()` + `$navigationLabel` from creation.
- Every new model with a user-visible name should plan for `name_ar` from the migration.
- Every new `emptyState*()` call must use an Arabic literal or `__()` key.
- Forms should default to `novalidate` panel-wide so server-side Arabic validation always wins.
- Filepond / any future 3rd-party widget needs its locale activated when installed.

## How to apply (for future code review)

Before approving any PR that touches user-facing UI:

1. Grep the diff for English strings: `git diff main | grep -E '^\+.*[A-Za-z]{3,}' | grep -vE 'href|class|wire:|aria-|emoji|//|/\*'` — anything left should be inside `<code>`, an attribute, an identifier, or already in a translation key.
2. If the file is a Filament Page class, confirm `getTitle()` is set.
3. If the diff adds an empty-state, confirm the literal is Arabic.
4. If a new model has a name-like field, confirm the migration has both columns or the column is bilingual.

## Links

- Audit: [[2026-05-15-admin-ux-audit]]
- Fix plan: [[fix-plan]] (see Wave 4)
- Memory: `feedback_arabic_first_required.md`
