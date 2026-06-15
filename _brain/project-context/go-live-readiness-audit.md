---
date: 2026-06-15
tags: [project-context, production, go-live, security, ops, audit, blocker, durable]
related:
  - "[[deployment-checklist]]"
  - "[[production-hardening-backlog]]"
  - "[[../daily-notes/2026-06-15-go-live-readiness-audit]]"
---

# Go-live readiness audit — "testing data" → real students

> **Date:** 2026-06-15. **System state:** already deployed (Dokploy, Docker, Postgres 16,
> `cet.itsyosefali.cloud`, uploads volume mounted) and in the **final testing phase with dummy
> data**. This audit answers: *what must change/fix before REAL students depend on it.*
>
> Method: fresh 5-dimension read-only audit (security, infra/deploy, data-integrity/Postgres,
> real-user readiness, test health) against the **current** code on branch `docs-dev-test-data` —
> the 2026-06-04 hardening notes were re-verified, not trusted. The top blockers were then
> confirmed by hand (entrypoint + seeder + nginx + mPDF). **No code was changed — planning only.**

## TL;DR verdict — NOT ready to open to real students yet

The 2026-06-04 Docker/security hardening is **genuinely present and correct** (OPcache, gzip,
headers, FPM pool, healthcheck, graceful shutdown, TrustProxies, timezone, entrypoint caching —
all verified in current code). Test suite is **318 passed / 0 failed** (the "known failures" are
already fixed in the uncommitted working tree). The blockers are **data hygiene, PII exposure,
two concrete prod-only code bugs, and operator/ops gaps** — not the framework.

**3 blockers + auto-migrate-without-backup must clear before go-live.**

---

## 🔴 BLOCKERS — do not open to students until these are done

### B1 — Default credentials + FAKE students/faculty get seeded into prod
- **Where:** `docker/entrypoint.sh:74` runs `AdminUserSeeder` on fresh deploy.
  `database/seeders/AdminUserSeeder.php:15-124` creates: `admin@college.edu / admin123`
  (super_admin), `secretary@college.edu / secretary123`, **4 fake faculty** with fabricated
  national IDs/birthdates (all `12345678`), and a **fake student "ppsan"** (`ppsan@pp.com`,
  GPA 3.50). `DatabaseSeeder.php:20` also chains it.
- **Impact:** Known super-admin password on a live system = full compromise. Real students would
  see fake classmates/instructors. This is the single worst go-live item.
- **Fix:** Split the seeder — a real `AdminUserSeeder` that creates **only** super_admin (+ maybe
  secretary) with the password pulled from an env var (`ADMIN_PASSWORD`, fail the seed if unset),
  and move the 4 faculty + "ppsan" student into `DevDataSeeder` (dev-only, already the pattern).
  Remove `AdminUserSeeder` from `DatabaseSeeder` too.
- **Effort:** ~1h. **Also requires checking the LIVE prod DB** — if it was already seeded, these
  rows exist now and must be deleted/rotated (see B3).

### B2 — Existing prod DB already holds dummy data, and there is no "clean launch" switch
- **Why it's stuck:** `app/Console/Commands/CheckFreshInstall.php` gates seeding on "zero users".
  Once any user exists, a redeploy **skips seeding entirely** — so a redeploy will NOT clean the
  test data, and there is no automated clean-prod path.
- **Impact:** Real students could share the system with leftover test students/applications/grades.
- **Fix (operator, manual, one-time):** Back up first, then on the prod DB:
  `php artisan migrate:fresh --force` → run only `RolesAndPermissionsSeeder`, `SettingsSeeder`,
  `SpecializationSeeder`, `SemesterSeeder` (**with corrected real dates — see H3**), `CourseSeeder`,
  and the **sanitized** admin seed from B1. Do **not** run `DevDataSeeder` or the old `AdminUserSeeder`.
  Verify the dummy rows are gone (`gpa.test@dev.ly`, `suspend.test@dev.ly`, `clearance.student@dev.ly`,
  `ppsan@pp.com`, the 4 `*.faculty.ly` / `cs.faculty@example.ly`).

### B3 — Applicant PII (certificates, national-ID scans) is world-readable by URL
- **Where:** uploads use the **`public`** disk (`RegistrationController` store ~lines 217/229
  `->store('applications','public')`; thesis proposals + student degree images likewise).
  `entrypoint.sh:45` `storage:link --force` + `docker/nginx.conf:70-71` (`root /var/www/public;
  try_files $uri`) → `https://<host>/storage/applications/<file>.pdf` is served **statically by
  nginx, before Laravel auth runs**. Filenames are hashed (obscurity), not access-controlled, and
  real URLs are emitted via `asset('storage/...')` in the application infolist.
- **Impact:** Bachelor certificates + remedial statements (bearing national IDs) downloadable by
  anyone with/guessing a URL. This is the deferred "H7" — still open.
- **Fix:** Store on the **private** (`local`) disk; serve via an `auth`-gated route with a policy
  check (admin/secretary or owning applicant) that streams the file; replace `asset('storage/...')`
  with the route. Migrate existing files off the public disk. (S3 + signed URLs is the cleaner
  long-term option and also fixes the redeploy-durability concern.)
- **Effort:** ~half day.

### B4 — `migrate --force` runs on EVERY deploy against live prod with NO backup
- **Where:** `entrypoint.sh:60` always runs `migrate --force` (the fresh-install gate only guards
  seeders, not migrations). No `spatie/laravel-backup`, no `pg_dump` step, no maintenance window.
- **Impact:** A bad/locking migration on a redeploy can corrupt or lock prod with **zero rollback**.
  This project has *already* been bitten by a Postgres-only migration bug (the enum CHECK trap), so
  the risk is concrete, not theoretical.
- **Fix:** Enable **Dokploy's Postgres automated backup** (minimum), and/or add a `pg_dump` to the
  mounted volume before `migrate` in entrypoint. Confirm backups exist **before** real data lands.

---

## 🟠 HIGH — fix before or immediately around launch

- **H1 — Password recovery is 100% WhatsApp/Qonvo-dependent.** Reset + change are OTP-only, no email
  fallback. If Qonvo is unconfigured/down on launch day, locked-out students have **no self-service
  recovery**. *Fix:* treat Qonvo (token + authenticated sender) as a hard launch prerequisite
  (`php artisan app:whatsapp-test`); consider an email-OTP fallback once SMTP is live.
  Files: `PasswordResetController`, `ChangesPasswordWithOtp`, `WhatsAppService::isConfigured()`.

- **H2 — `MAIL_MAILER=log` by default → welcome email (with credentials) goes to a log file, silently.**
  Mail send is try/catch-wrapped so failure is invisible. Students without a WhatsApp-reachable phone
  never get their enrollment number/credentials. *Fix:* configure real SMTP + `MAIL_MAILER=smtp` for
  prod, or drop `'mail'` from `via()` (but that locks in H1). `.env.example:61`, `WelcomeStudentNotification`.

- **H3 — Seeded active semester has leftover Sept 2025 dates; add/drop window is closed in the past.**
  `SemesterSeeder.php:12-21` ("خريف 2025", add_drop 2025-09-01..09-15). The add/drop window is the
  only gate for student add/drop — with a past window, **students cannot add/drop at all**. *Fix:*
  set the real active semester + real start/end + add_drop dates before launch; confirm exactly one
  `is_active=true`. Also confirm the course-survey window.

- **H4 — mPDF temp dir not writable in prod → PDF generation likely 500s.** `PrintController::makeMpdf()`
  (`:309-356`) sets no `tempDir`, so mPDF defaults to `vendor/mpdf/mpdf/tmp` which is **root-owned 755**
  (Dockerfile `COPY . .` as root chowns only `storage` + `bootstrap/cache`; PHP-FPM runs as www-data).
  First transcript/clearance/exam-list/roster PDF a real user requests writes a font-subset temp file →
  EACCES → 500. Local dev never repros. *Fix:* `'tempDir' => storage_path('app/mpdf-tmp')` + `mkdir -p`
  it in entrypoint. **Verify on the live container before trusting** (a redeploy may already be failing).

- **H5 — Logs are ephemeral + no error monitoring.** Only `storage/app/public` is on the mounted
  volume; `storage/logs` is **not** → `laravel.log` vanishes on every redeploy. No Sentry/Flare. *Fix:*
  ensure `LOG_STACK=single,stderr` in prod env (Dokploy retains stderr); ideally add error monitoring.

- **H6 — Enrollment-number generation has a race + a NULL path.** `EnrollmentNumberService::generate`
  does `MAX()+1` with no lock; two concurrent approvals collide → second approval throws a raw
  `QueryException` (the UNIQUE index saves data integrity but the secretary sees an unhandled error,
  approval silently fails). Separately, `ApproveApplication:~81` only generates the number **if a
  semester is active** — otherwise the student is created with `enrollment_no = NULL` and no retry.
  *Fix:* `lockForUpdate`/retry-on-unique-violation, and require an active semester (or surface a clear
  error) on approval.

- **H7 — Staff can change a student's phone/email with no verification; reset is phone-based.**
  `EditStudent.php` / `StudentResource.php` — combined with H1 this is an account-takeover path
  (compromised secretary → change phone → OTP reset). *Fix:* ensure phone/email are in
  `AuditObserver::WATCHED_FIELDS`; notify the old phone/email on change.

---

## 🟡 MEDIUM — schedule shortly after launch

- **M1 — Postgres `LIKE` is case-sensitive (SQLite isn't).** User-facing name searches silently miss
  differently-cased matches: `EnrollStudents.php:334-335`, `ReportsPage.php:50-51`,
  `ListAuditLogs.php:49`. *Fix:* use `ilike` / `LOWER()` on these three. (enrollment_no / tracking_code
  LIKEs are fine — uppercase/numeric prefixes.)
- **M2 — Observer cascade loops aren't transaction-wrapped → partial state on mid-loop failure.**
  `StudentObserver::cascadeWithdraw` / `flushCurrentSemester`, `CourseOfferingObserver::cascadeWithdraw`,
  `SemesterObserver::processNonSubmittingStudents` + `archivePeersOf` loop N drops/updates outside a
  `DB::transaction`. A failure on item 3 leaves 1–2 persisted + GPA half-recomputed. *Fix:* wrap the loops.
- **M3 — Two-active-semester is silently masked.** `Semester::active()` is `where(is_active,true)->first()`
  → picks lowest id if two are active (plausible during term rollover). *Fix:* `orderByDesc('id')` +
  a partial unique index `WHERE is_active` to enforce the invariant at the DB. (Zero-active is well
  null-guarded across pages — no crash.)
- **M4 — `GradeObserver` GPA recalc skips non-Active students** (calls `recalculateStudentGPA` without
  `force`). Approving a grade for an OnHold/Inactive student won't update GPA. **Confirm intended**
  (frozen-GPA-for-suspended is plausibly by design) — product call, not necessarily a bug.
- **M5 — Synchronous notifications block the staff action up to ~10s** per send if Qonvo is slow (no
  queue worker). Won't roll back the business action (sends are post-transaction + try/catch). Acceptable
  at launch volume; `ShouldQueue` + worker is the post-launch upgrade.
- **M6 — `SettingsSeeder` ships placeholder contact info** (`+218 21 000 0000`, `info@cet.edu.ly`) that
  appears on PDFs/forms until an admin edits the settings widget. *Fix:* set real values after the clean seed.
- **M7 — Missing Arabic 419/429/503 error pages** (403/404/500 exist). A student hitting an expired
  session on `/apply` or a rate limit sees the default English Laravel page. *Fix:* add RTL views.
- **M8 — HSTS header is commented out** (`nginx.conf`) — uncomment once TLS is confirmed end-to-end.
- **M9 — Stop using `->enum()` in migrations.** 12 enum-born columns still exist; the
  `2026_06_13_..drop_orphaned_enum_check_constraints` migration covers the *current* schema, but any
  future `->enum()` (and `notification_logs` migration's `down()` which re-adds one) reintroduces the
  Postgres CHECK trap. *Fix:* string + enum cast going forward.

---

## 🔓 OPERATOR — verify on Dokploy (cannot confirm from repo; the live `.env` in the repo is dev sqlite/debug)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `LOG_STACK=single,stderr`
- [ ] `APP_KEY=base64:...` **stable** (never regenerated per deploy — else all sessions/encrypted data break)
- [ ] `APP_URL=https://cet.itsyosefali.cloud` (wrong value breaks `route()` + PDF asset URLs)
- [ ] `DB_CONNECTION=pgsql` + host/db/user/pass
- [ ] `SESSION_SECURE_COOKIE=true` (behind TLS)
- [ ] `QONVO_API_TOKEN` + `QONVO_SENDER_PHONE` (authenticated sender in Qonvo dashboard) — **rotate** the
      token that was pasted in plaintext during dev
- [ ] `MAIL_MAILER` = real transport + `MAIL_FROM_ADDRESS` (see H2)
- [ ] Persistent volume confirmed at `storage/app/public`; Postgres automated backups ON (see B4)
- [ ] Dokploy/Traefik sets HSTS (M8)

---

## ✅ Confirmed GOOD — do NOT redo (verified in current code)

- All 2026-06-04 Docker hardening present & correct: OPcache (`validate_timestamps=0`, 256M, 20k files),
  nginx gzip + security headers, FPM pool (max_children=20), HEALTHCHECK→`/up`, supervisord graceful
  shutdown, entrypoint caches (config/route/view/event + `filament:optimize`), `Africa/Tripoli` timezone,
  TrustProxies `at:'*'`, `composer --no-dev` + `npm run build` (vite manifest in image).
- **Seeders are safe in the deploy path:** entrypoint calls individual seeders explicitly; `DatabaseSeeder`
  is never invoked by deploy and does **not** reference `DevDataSeeder`/`MobileTestStudentSeeder`. The
  fresh-install gate prevents re-seeding over existing data. (The danger is only B1's `AdminUserSeeder`
  contents, which IS in the deploy path.)
- **No queue worker / cron needed** — zero `ShouldQueue`, zero scheduled tasks. Synchronous-by-design holds.
- **No IDOR on print/PDF routes** — `PrintController` enforces per-record authz (admin / owning-student /
  course-faculty) on every method; `PrintCourseStudentsController` admin-gated.
- **No mass-assignment hole** — no `$guarded=[]`; `role`/`is_active`/`status` only set server-side.
- **No secrets committed** — `.env*` gitignored; only `.env.example` tracked (empty `APP_KEY`).
- **Login brute-force covered** — Filament base Login `rateLimit(5)/min`; OTP service hashes codes, caps
  3 sends/15min + 5 verify attempts.
- **Notifications can't corrupt business actions** — sent post-transaction, each try/catch-wrapped;
  WhatsApp sends logged (sent/failed) with an admin UI; reasonable Qonvo timeouts (5s/10s).
- **GPA math is sound** — retake latest-attempt, div-by-zero guard, decimal casts.
- **Unique constraints + FK indexes** all present (enrollment_no, user_id, email, tracking_code, the two
  composite pivots, grades.enrollment_id; perf-index migration covers FKs).
- **403→302 redirect on wrong-panel access is intentional & correct** (logs out + bounces to the right
  login) — not a bug; tests correctly assert it. **Test suite: 318 passed / 0 failed** (60s).

---

## Recommended execution order

1. **B1 + B2** (clean DB + kill default creds) — together; the data-hygiene blockers.
2. **H3** (real semester + add/drop dates) — else enrollment is dead on arrival.
3. **B4 + H5** (backups + log durability) — irreversible-loss risks before real data exists.
4. **B3** (private upload disk + gated download) — PII exposure.
5. **H4** (mPDF tempDir) — verify on live container; core feature else 500s.
6. **H1 + H2** (confirm Qonvo live; decide SMTP/email fallback).
7. **H6, H7, M1–M9** — correctness + UX polish, post-launch acceptable.

## Open product questions for the user
- M4: is "frozen GPA for non-Active students on grade approval" intended, or a bug?
- H2/H1: is email in scope for prod (welcome + recovery fallback), or WhatsApp-only?
- Storage (B3): single-VM persistent volume + gated route, or move to S3/R2 signed URLs?

## Caveat
- The green test suite + several fixes depend on **uncommitted** working-tree changes on
  `docs-dev-test-data`. They must be committed/merged or a clean checkout of `main` regresses.
</content>
</invoke>
