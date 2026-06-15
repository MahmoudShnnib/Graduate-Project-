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

## ⚖️ User decisions (2026-06-15, after review) — READ THIS FIRST
- **Email is OUT of scope — WhatsApp-only.** Fix is to *remove* the `mail` channel from notifications
  (H2), not to configure SMTP. Password recovery is WhatsApp-only **by design** (H1) — Qonvo is a hard
  launch prerequisite; a student with a wrong phone is staff-reset only.
- **B3 (public-disk PII) — ACCEPTED RISK, downgraded from blocker.** Uploads use a 40-char random
  filename; user accepts the obscurity. Residual risk is a *leaked* URL (no auth once known), not
  brute-force. Revisit if scaling.
- **H4 (mPDF tempDir → PDF 500) — RETRACTED.** PDFs work in prod; the agent's permission concern does
  not manifest on the live container.
- **M4 (frozen GPA on non-Active grade approval) — REMOVED.** GPA logic was fixed by the user 2026-06-14;
  off-limits.
- **H7 — audit logging is ALREADY DONE.** `User` is in `AuditObserver`'s model list and `phone`/`email`
  are not in `BLOCKED_FIELDS` (blocked = updated_at, created_at, password, remember_token,
  email_verified_at, last_seen_activity_id), so student phone/email changes ARE audited. Remaining
  (optional): notify the *old* phone/email on change.
- **H3 — testing semester only.** Real semester created via UI at launch; just keep exactly one `is_active`.
  (Caveat: `SemesterSeeder` still has Sept-2025 dates — edit after a clean re-seed.)
- **M7 — English text is fine** for 419/429/503 pages; reuse the existing error layout/system fonts.

## TL;DR verdict — NOT ready to open to real students yet

The 2026-06-04 Docker/security hardening is **genuinely present and correct** (OPcache, gzip,
headers, FPM pool, healthcheck, graceful shutdown, TrustProxies, timezone, entrypoint caching —
all verified in current code). Test suite is **318 passed / 0 failed** (the "known failures" are
already fixed in the uncommitted working tree). After the user decisions above, the remaining work
is **data hygiene + ops gaps + a couple of correctness fixes** — not the framework.

**3 must-do before real students: B1 (default-cred/fake seeder), B2 (clean re-seed), B4 (backups).**
(B3 accepted, H4/M4 dropped — see decisions block.)

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

### B3 — Applicant PII (certificates, national-ID scans) is world-readable by URL — ⚖️ ACCEPTED RISK (downgraded, NOT a blocker)
> User accepts this: uploads use a 40-char random filename; no auth once a URL is known. Residual
> risk is a *leaked* URL, not brute-force. Revisit only if scaling. Details kept below for the record.
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

- **H1 (by design — WhatsApp-only) — Password recovery is 100% Qonvo-dependent.** No email fallback,
  intentionally. *Action (no code):* treat Qonvo (token + authenticated sender) as a **hard launch
  prerequisite** (`php artisan app:whatsapp-test`); document that a student with a wrong/unreachable
  phone is **staff-reset only** (admin edit). Files: `PasswordResetController`, `ChangesPasswordWithOtp`,
  `WhatsAppService::isConfigured()`.

- **H2 (revised — WhatsApp-only) — `mail` is a silent dead channel.** Welcome + other notifications
  still list `mail` in `via()`; with `MAIL_MAILER=log` that writes credentials to a log file and looks
  "sent" (try/catch-wrapped, failure invisible). *Fix:* **remove `'mail'` from `via()`** in the
  notifications (do NOT configure SMTP — email is out of scope). `WelcomeStudentNotification` + the other
  notification classes.

- **H3 (operational — testing semester only) — seeded semester has Sept-2025 dates.**
  `SemesterSeeder.php:12-21` ("خريف 2025", add_drop 2025-09-01..09-15) → add/drop closed. User will
  **create the real semester via the UI at launch**; keep exactly one `is_active=true`. *Caveat:*
  `SemesterSeeder` still ships these dates, so after a clean re-seed (B2) edit/replace the semester.

- **~~H4 — mPDF temp dir~~ — RETRACTED.** PDFs work in prod; the permission concern does not manifest
  on the live container. No action.

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

- **H7 (reduced) — Staff can change a student's phone/email; reset is phone-based.** Audit logging is
  **already done** ✅ — `User` is in `AuditObserver`'s model list and `phone`/`email` are not in
  `BLOCKED_FIELDS`, so these changes ARE logged. *Optional remaining:* notify the **old** phone/email
  on change (alerts a takeover victim). `EditStudent.php` / `StudentResource.php`.

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
- **~~M4 — GPA recalc skips non-Active students~~ — REMOVED.** GPA logic was fixed by the user
  2026-06-14 and is off-limits. No action.
- **M5 — Synchronous notifications block the staff action up to ~10s** per send if Qonvo is slow (no
  queue worker). Won't roll back the business action (sends are post-transaction + try/catch). Acceptable
  at launch volume; `ShouldQueue` + worker is the post-launch upgrade.
- **M6 — `SettingsSeeder` ships placeholder contact info** (`+218 21 000 0000`, `info@cet.edu.ly`) that
  appears on PDFs/forms until an admin edits the settings widget. *Fix:* set real values after the clean seed.
- **M7 — Missing 419/429/503 error pages** (403/404/500 exist). A student hitting an expired session
  on `/apply` or a rate limit sees the default Laravel page. *Fix:* add views reusing the existing error
  layout/system fonts — **English text is fine** (user's call).
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
- [ ] (No mail config needed — email is out of scope; remove the `mail` channel in code instead, see H2)
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

## Recommended execution order (after user decisions)

1. **B1 + B2** (split the seeder + clean re-seed path) — the data-hygiene blockers.
2. **B4** (Dokploy Postgres backups) + **H5** (`LOG_STACK=single,stderr`) — irreversible-loss risks.
3. **H2** (remove the `mail` channel) — quick code cleanup.
4. **H1** (confirm Qonvo live — operational, no code) + **H3** (create real semester at launch via UI).
5. **H6** (enrollment-number race + NULL path).
6. **M1, M2, M3, M6, M7, M8, M9** + **H7** (optional notify-old-contact) — correctness + polish.

*Dropped/accepted, no action: B3 (accepted), H4 (retracted), M4 (GPA, off-limits), M5 (acceptable at launch volume).*

## Resolved product questions
- ~~Email in scope?~~ → **WhatsApp-only.** Remove the `mail` channel (H2).
- ~~Frozen GPA on approval intended?~~ → **N/A, GPA fixed by user.**
- ~~Storage A vs B?~~ → **B3 accepted as-is** (random filename). No storage change.

## Caveat
- The green test suite + several fixes depend on **uncommitted** working-tree changes on
  `docs-dev-test-data`. They must be committed/merged or a clean checkout of `main` regresses.
</content>
</invoke>
