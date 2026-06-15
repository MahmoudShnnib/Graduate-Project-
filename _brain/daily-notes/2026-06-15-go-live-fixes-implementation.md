---
date: 2026-06-15
tags: [daily, go-live, implementation, notifications, observers, data-integrity, security]
related:
  - "[[../project-context/go-live-readiness-audit]]"
  - "[[2026-06-15-go-live-readiness-audit]]"
  - "[[../decisions/2026-06-15-defer-single-active-semester-db-index]]"
  - "[[../bugs-and-fixes/2026-06-15-enrollment-number-race-and-null-path]]"
---

# 2026-06-15 (afternoon) — Go-live fixes implementation

Executed the agreed go-live fix list (H2, H1, H5, H6 + M1, M2, M3, M6, M7, M8, M9, H7) from
[[../project-context/go-live-readiness-audit]]. TDD per fix, `pint` after every PHP edit, full
suite green throughout. Branch: `chore/remove-dompdf-graduate-clearance-print`. **Not committed**
(per no-auto-commit). Baseline 317 → **330 passed / 0 failed** (+13 new tests).

## Done (code)
- **H2 — mail channel removed.** `HasWhatsAppChannel::via()` now returns only WhatsApp (or `[]`),
  never `'mail'` → credentials can't silently land in a log. Also deleted the now-dead `toMail()` +
  `MailMessage` imports from all 10 notification classes (no test referenced them). New
  `NotificationChannelsTest`.
- **M1 — case-insensitive name search.** `LOWER(name) LIKE LOWER(?)` (portable; `ilike` would crash
  SQLite tests) at the 2 surviving sites: `ListAuditLogs:49`, `EnrollStudents:334`. *ReportsPage site
  is gone (deleted in DomPDF removal).* `enrollment_no` left as `like` (digits only). Test added to
  `AuditLogCollapseTest`. NOTE: dev/test is SQLite (LIKE already case-insensitive) so the test proves
  the path, not the Postgres behavior — the `LOWER()` form is what makes it correct on PG.
- **M3 — single-active-semester (partial).** `Semester::active()` now `orderByDesc('id')` →
  deterministic newest-wins instead of silent lowest-id. **Partial unique index DELIBERATELY DEFERRED**
  — see [[../decisions/2026-06-15-defer-single-active-semester-db-index]]. Test added to
  `ActiveSemesterResolverTest`.
- **H6 — enrollment-number race + NULL path.** `EnrollmentNumberService` now retries on
  `UniqueConstraintViolationException` (MAX()+1 isn't atomic; `lockForUpdate` impossible — PG forbids
  `FOR UPDATE` with aggregates), applied to both `generate()` and `regenerateForSpecializationChange()`
  via a shared `assignNextSequential()`. `ApproveApplication` now refuses approval with a clear Arabic
  error when no semester is active (was creating students with `enrollment_no = NULL`). New
  `ApproveApplicationTest` (action had **zero** prior coverage). See bug note.
- **M2 — observer cascades wrapped in `DB::transaction`.** `StudentObserver` (cascadeWithdraw +
  flushCurrentSemester), `CourseOfferingObserver` (cascadeWithdraw), `SemesterObserver` (all 4 loops:
  archivePeersOf, resetInactive, releaseOnHold, processNonSubmitting). Atomic-or-nothing on mid-loop
  failure. Behavior unchanged on happy path (141 cascade tests still green).
- **M7 — error pages.** Added `errors/419,429,503.blade.php` reusing `errors/layout` (Arabic RTL to
  match 403/404/500 — chose consistency over the "English is fine" allowance). `ErrorPagesRenderTest`.
- **M8 — HSTS enabled** in `docker/nginx.conf` (TLS is live end-to-end; internal HTTP healthcheck
  unaffected — curl ignores HSTS). Takes effect on next deploy.
- **M9 — enum trap removed from `down()`.** `fix_notification_logs_table` `down()` now reverts to
  `string(20)` instead of `->enum()` (data-equivalent, no PG CHECK constraint).
- **H7 (optional) — old-contact alert.** New `UserObserver` + `ContactInfoChangedNotification`: when a
  student's phone/email changes (admin edit OR self-service — both go through `User::update`), a
  WhatsApp security alert is sent to the OLD phone (phone change) or current phone (email-only).
  WhatsApp-only, graceful no-op without Qonvo. Scoped to `role==='student'`. `ContactInfoChangedAlertTest`.

## Not code — flagged to operator
- **H1 (by design)** — WhatsApp-only recovery; confirm Qonvo live (`app:whatsapp-test`) as a hard
  launch prerequisite; wrong-phone = staff-reset only. No code.
- **H5** — `.env.example` already ships `LOG_STACK=single,stderr` + `LOG_LEVEL=warning`. Remaining is
  operator verifying the prod `.env` matches. No code.
- **M6** — real faculty contact info (name/phone/email/address) shows on PDFs; placeholders in
  `SettingsSeeder` (`+218 21 000 0000`, `info@cet.edu.ly`). **Needs the user's real values** — set via
  the admin Settings widget after the clean re-seed, or update `SettingsSeeder` defaults. Not invented.

## Verification
- `php artisan test --compact`: 330 passed / 0 failed.
- `pint --dirty`: pass after each edit.
- Browser smoke (Chrome, `127.0.0.1:8000`): admin login OK, audit-log page renders 36 rows, **0 console
  errors/warnings** — confirms app boots cleanly with the new `UserObserver` + provider registration.

## Loose ends / next steps
- Commit when the user gives the go-ahead (still uncommitted).
- The 3 real blockers (B1 seeder split, B2 clean re-seed, B4 backups) are a separate effort — untouched.
- M6 awaits real contact values from the user.
- Reconsider the single-active-semester DB index if the create-then-archive flow is ever reordered.
