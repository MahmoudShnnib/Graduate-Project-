---
date: 2026-06-13
tags: [daily, bell, livewire, notifications, audit-log, ux]
related:
  - "[[2026-06-12-notification-log-viewer-and-activity-bell]]"
  - "[[../decisions/2026-06-13-activity-bell-read-unread-model]]"
---

# 2026-06-13 — Activity Bell: "only new" + server-side seen marker

Branch: `gap-fixes-06-12`. Follows up yesterday's bell work
([[2026-06-12-notification-log-viewer-and-activity-bell]]).

## Goal (from user)

Turn the admin activity bell from a perpetual "last 15" feed into a "what's new since I
last looked" surface. Specifically: (1) show only new/unseen items, (2) clear on view,
(3) originally wanted an explicit Clear button.

## Council consulted

Ran the `llm-council` skill. ChatGPT was unavailable (OpenAI 429/quota every retry).
Gemini's key contributions: strongly recommend a **server-side per-user seen marker** over
localStorage; flagged **timed auto-clear as an audit anti-pattern** (risk of silently
burying unseen approvals). See decision note for the full reasoning + where I diverged.

## Decision

Read/unread "inbox" model, but **clear-on-open** (no timer), and **no Clear button** — the
user correctly pointed out that if opening already clears it, a Clear button is redundant.
Full rationale: [[../decisions/2026-06-13-activity-bell-read-unread-model]].

## What was built

1. **Migration** `2026_06_13_014559_add_last_seen_activity_id_to_users_table` — adds
   `last_seen_activity_id` (unsignedBigInteger, nullable) to `users`. Per-user, survives
   browser clears, syncs across devices.
2. **`User` model** — `last_seen_activity_id` added to `$fillable` + `integer` cast.
3. **`app/Livewire/AdminActivityBell.php`** — rewritten:
   - `refresh()` (called on `mount` + `wire:poll.30s`): builds the unseen list
     (`id > last_seen`, limit 15) as a plain array, and `badgeCount` = count of unseen.
   - `markSeen()` (called when the dropdown opens): advances the user's
     `last_seen_activity_id` to the latest qualifying audit id and zeroes `badgeCount`.
     **Does not touch `$activities`**, so the just-opened items stay visible while the
     dropdown is open; they drop off on the next `refresh()`/poll.
   - `render()` is now pure (returns the view only) — state is managed by the methods, so
     the Livewire-4 "render always runs after an action" trap can't wipe the snapshot.
   - Same admin-decision filter as before (super_admin/manager_assistant, excluding the
     noisy `*_updated` observer actions).
4. **`resources/views/livewire/admin-activity-bell.blade.php`** — rewritten:
   - All `localStorage` logic removed. Badge bound reactively to `$wire.badgeCount`.
   - `x-init="$watch('open', val => { if (val) $wire.markSeen() })"` → mark-seen on open.
   - "تمييز كمقروء" / Clear button **removed**. Empty state + footer "view full audit log"
     link kept.

## Tests

New `tests/Feature/AdminActivityBellTest.php` (6 tests, all pass): counts only new above
marker; ignores `*_updated` observer noise; ignores non-admin authors; markSeen clears
badge + advances marker + keeps items visible; items drop off on next refresh; a brand-new
activity resurfaces after being caught up.

Full suite: **264 passed, 4 failed** — the 4 are the known pre-existing failures
(ExampleTest stale `/admin`, ProductionHardening + StudentPortal 403→302), unrelated.

## Notes / loose ends

- `pint --dirty` also reformatted `app/Policies/NotificationLogPolicy.php` (an already-
  uncommitted file) — import order/whitespace only, harmless.
- Old browser `localStorage['admin_bell_seen']` keys are now dead/unused — harmless leftover.
- Not committed (no-auto-commit). Migration must run on deploy.

---

## Part 2 — Bell became a full "who did what" supervisor feed

After the "only new" work, the user wanted the bell to capture **all** admin activity
(adds / edits / deletes, summarized), visible **only to the super-admin**, showing **only
other people's** actions. Decisions made:
[[../decisions/2026-06-13-activity-bell-full-activity-feed]].

### Why creates/deletes were invisible
`AuditObserver` only had an `updated()` hook on 5 models watching a few fields. Creates and
deletes were never wired — a gap, not a design choice. Also the **bulk** "قبول مبدئي" wrote
no audit log at all (only the single-row action did) — that was the user's original "it
didn't show" bug.

### What changed
1. **Full CRUD audit trail** — `AuditObserver` now logs `created` / `updated` / `deleted`
   (`Model_created` etc.) with a noise blocklist (`updated_at`, `password`, `*_token`,
   `last_seen_activity_id`, …). Registered on **20 domain models** in `AppServiceProvider`
   (AuditLog/NotificationLog/WhatsAppOtp excluded — recursion/transient). This auto-fixes
   the bulk-approval and rejection gaps — no per-action patching needed.
2. **Batch correlation** — migration adds `audit_logs.batch_id` (uuid) + `subject_label`.
   `AuditService::batchId()` memoizes one uuid per request; every row in an operation shares
   it. `subject_label` is captured at log time (so deletes still read with a name).
3. **Display-only collapse** — the bell hides generic `*_(created|updated|deleted)` rows
   whose batch also contains a decision (shows the nice decision line instead) and dedupes a
   record touched twice in one batch. **Storage keeps everything** — collapse is purely the
   bell's view. Verified live: a create+update+decision in one op → 3 audit rows, 1 batch.
4. **Summarized lines** — `AuditService::actionLabel()` now renders unmapped
   `Model_(created|updated|deleted)` as `إضافة/تعديل/حذف {noun}` (noun from
   `AuditLogResource::MODEL_LABELS`); bell appends `: {subject_label}`. Removed the 5 old
   `*_updated` custom labels so phrasing is uniform across all models.
5. **Audience** — bell query: actor role ∈ {super_admin, manager_assistant}, **excluding the
   viewer's own actions** (`user_id != auth id`). Wrapper blade gates the whole bell to
   `role === 'super_admin'` (managers no longer see it).
6. **Row redesign** — leading type-icon (➕ emerald / ✏️ amber / 🗑️ red / ✅ blue) + summarized
   title + "بواسطة {actor}" + relative time. Cap raised to 60 unseen.
7. **Audit-log page** — `actionColor()` now colours created=success / deleted=danger /
   updated=gray.

### Files (Part 2)
`app/Observers/AuditObserver.php` (rewrite), `app/Services/AuditService.php` (batch +
subject + generic labels), `app/Models/AuditLog.php` (+2 fillable), `app/Providers/AppServiceProvider.php`
(observe 20 models), `app/Livewire/AdminActivityBell.php` (rewrite: self-exclude + collapse +
summarize), `resources/views/livewire/admin-activity-bell.blade.php` (rows),
`resources/views/filament/admin-activity-bell.blade.php` (super-admin gate),
`app/Filament/Resources/AuditLogs/AuditLogResource.php` (colors), migration
`2026_06_13_024112_add_batch_and_subject_to_audit_logs_table`.

### Tests
`AdminActivityBellTest` rewritten — 8 pass: self-exclusion, role filter, batch-collapse,
standalone-edit, summarized create label, mark-seen/keep-visible, drop-on-refresh/resurface,
and create/update/delete capture. Full suite: **266 passed, 4 failed** (same pre-existing 4).

### Deploy
Both new migrations must run (`php artisan migrate`).

---

## Part 3 — Audit-log detail page cleanup

User request on `/admin/audit-logs/{id}`:
1. **Removed the عنوان IP field** from the details grid.
2. **Merged the 3 tabs into one page** — the old Alpine tab bar (معلومات / القيم القديمة /
   القيم الجديدة, click-to-view) is gone. Now: a description banner, the تفاصيل السجل grid,
   and a single **التغييرات** table (الحقل / القيمة السابقة / القيمة الجديدة, old struck-through)
   all stacked in one card. Create/delete rows show a friendly "no value changes" note.
3. **Per-action descriptions** — new `AuditService::actionDescription()` returns one short
   Arabic sentence per action (explicit map for ~38 actions + generic
   `إضافة/تعديل/حذف %s` for `Model_(created|updated|deleted)`), shown in the banner so an
   admin instantly understands what happened. Exposed via `ViewAuditLog::getActionDescription()`.
4. Added Arabic `MODEL_LABELS` for the newly-audited models (User→مستخدم, Course→مقرر,
   Specialization→تخصص, TransferCourse, StudentWarning, Supervisor, Setting) so nouns aren't
   raw English in labels/descriptions.

Files: `app/Services/AuditService.php`, `app/Filament/Resources/AuditLogs/Pages/ViewAuditLog.php`,
`app/Filament/Resources/AuditLogs/AuditLogResource.php` (model labels),
`resources/views/filament/resources/audit-logs/pages/view-audit-log.blade.php` (rewrite).
Suite still 266 passed / 4 pre-existing failures.

---

## Part 4 — Collapse one-action-many-logs in the audit list

One business action fans out into many audit rows (worst: **accept student** ≈ 6 rows —
`Application_updated`×2 + `User_created` + `Student_created` + `Student_updated` + the
decision). All share a `batch_id`. User wanted the **list** to show one row per operation.
Council consulted (`llm-council`; ChatGPT down again, Gemini confirmed the representative-row
approach + batch-aware filters). Decision:
[[../decisions/2026-06-13-audit-log-collapse-by-batch]].

### Build
- **Representative-row-per-batch list** (`AuditLogResource::collapsedQuery()`). Storage keeps
  every row — display-only collapse. Representative = the **decision** row (action NOT ending
  `_(created|updated|deleted)`), else lowest id. Rows with `batch_id IS NULL` (legacy/manual)
  stand alone. **Portable SQL** — `NOT EXISTS` + `substr(action,-8) IN (...)`, no `REGEXP`/window
  funcs (the dev/test DB is **SQLite**; prod may be MySQL). A correlated `selectSub` adds
  `batch_size` (note: aliased the sub-table `audit_logs as sub` — sharing the `audit_logs`
  alias self-matched everything and gave a bogus count).
- **"+N إجراء ضمن نفس العملية"** badge on the action column when `batch_size > 1`.
- **Batch-aware action filter** (`AuditLogResource::applyActionFilter()`, extracted so it's
  unit-testable): filtering by a generic action (never a representative) matches operations
  that *contain* it and surfaces their representative.
- **Detail page** gains an **"إجراءات أخرى ضمن نفس العملية"** section listing the batch
  siblings (`ViewAuditLog::getBatchSiblings()`), so the full fan-out is visible in one place.
- **Index** `(batch_id, id)` migration for the correlated lookup.
- Bulk actions collapse to **one** row (user confirmed they want this).

### Tests
New `AuditLogCollapseTest` (7 pass): representative pick + batch_size, generic-only batch
picks earliest, null-batch standalone, system rows hidden, list shows only representative,
batch-aware filter, detail siblings. Filament gotchas hit: page tests need the **Spatie**
super_admin role (via `adminUser()`, not just the `role` column) or the policy 403s; tables
**defer loading** so assert on `getTableRecords()` not rendered HTML; multiple-select filters
**defer** — tested the extracted `applyActionFilter()` directly instead.

Suite: **273 passed, 4 pre-existing failures** (+ the known random-order-flaky
`TransferCourseEditTest`, passes in isolation). Three migrations now pending for deploy.

### Part 4b — the collapse wasn't showing (real bug → [[../bugs-and-fixes/2026-06-13-audit-collapse-wrong-path]])

User reported the list still showed every row. Root cause: **`ListAuditLogs` does not use the
Filament table at all** — it sets `protected string $view` to a custom Blade
(`list-audit-logs.blade.php`) and builds its own query in `getAuditLogs()`. My `table()` /
`collapsedQuery()` wiring was on the *unused* Filament path — and my test gave false
confidence because it asserted `getTableRecords()` (the Filament path), not the real
`getAuditLogs()`. Lesson: **when a Filament page overrides `$view`, the resource `table()` is
dead — test the page's actual data method.**

Fix:
- `ListAuditLogs::getAuditLogs()` now starts from `AuditLogResource::collapsedQuery()` (default)
  or raw `AuditLog::query()` when "show system" is on (uncollapsed escape hatch), and uses
  `applyActionFilter()` for the batch-aware action chips.
- `list-audit-logs.blade.php` action cell now renders a **"+N"** count chip when
  `batch_size > 1`.
- Test rewritten to call the real `getAuditLogs()` (+ a "show system shows raw rows" test).
  8 tests pass; suite **274 passed / 4 pre-existing**.
- Verified on the **live DB** via tinker: list 49→30 rows; the latest acceptance's 7 rows
  collapse to 1 (`exam_passed | +6 more`).
- Note: the Filament `table()` collapse wiring is now dead code (custom view wins) but left in
  place; the canonical path is `getAuditLogs()`. Browser visual check was blocked — Chromium
  won't launch under the agent sandbox (exit 144).

---

## Part 5 — Audit-log hardening (3 tiers, from the critical review)

After a no-cushioning review, agreed to fix 5 of 7 issues; skip #1 (hybrid rewrite, not worth
it) and #7 (tamper-proofing, thesis-only). Decision:
[[../decisions/2026-06-13-audit-log-hardening]].

**Tier 1 — batch-id reset (fixes the Octane/queue landmine).** `AuditService::resetBatch()`
nulls the memoized static. Called at every unit-of-work boundary: `ResetAuditBatch` global
middleware (web), `JobProcessing` listener (queue), `CommandStarting` listener (console). So
separate operations can never share a batch id under a long-lived runtime.

**Tier 2 — honest badge + de-dup the list code (fixes #5, #6; skipped the precompute).**
- `collapsedQuery()` now also selects `batch_decision_count` (rows in the batch that aren't
  generic CRUD). Blade badge: >1 decisions → "N قرار" (bulk), else "+N" (one decision + its
  edits). No more "+59" lie for a 30-student bulk approve.
- **Deliberately did NOT precompute** `is_representative` on the row. It would trade a perf
  worry for a correctness risk: any code path missing the finalize hook would make those logs
  silently vanish from the list — unacceptable for an audit log. The correlated query is plenty
  fast at this scale.
- Added a loud doc-comment on the dead `table()` so nobody edits the wrong path again; removed
  the dead `+N` description column there.

**Tier 3 — semester-based archiving (fixes unbounded growth, #3).**
- Migration: `audit_logs.semester_id` (nullable, indexed) + new `audit_log_archives` cold-store
  table.
- `AuditService::log()` stamps `Semester::activeId()` on every row.
- `AuditService::archiveSemesterLogs($id)` chunk-moves a semester's rows to the archive and
  deletes them from the live table (full trail preserved, live table stays lean).
- Wired into `Semester::archiveCascade()` (inside its existing transaction).

**Skipped, on purpose:** #1 (unify the two logging layers — ~30 call sites for invisible gain;
Tier 2 makes the duplication cheap anyway) and #7 (hash-chain tamper-evidence — compliance
engineering this project doesn't need; rows already read-only in the UI).

### Files (Part 5)
`app/Services/AuditService.php` (resetBatch + archiveSemesterLogs + semester stamp),
`app/Http/Middleware/ResetAuditBatch.php` (new), `bootstrap/app.php` (register middleware),
`app/Providers/AppServiceProvider.php` (queue/console reset listeners),
`app/Models/AuditLog.php` (+semester_id fillable), `app/Models/Semester.php`
(archiveCascade hook), `app/Filament/Resources/AuditLogs/AuditLogResource.php`
(batch_decision_count + dead-code comment), `list-audit-logs.blade.php` (honest badge),
migration `2026_06_13_044939_add_semester_and_archive_to_audit_logs`.

### Tests
`AuditLogLifecycleTest` (4: batch reset, semester stamp, archive move, archiveCascade) +
honest-badge test in `AuditLogCollapseTest`. Suite: **279 passed, 4 pre-existing failures**.
Verified live (rolled back): reset works, semester stamped, archive 3→0 into cold store.

### Deploy
Now **four** pending migrations total (last_seen_activity_id; batch_id+subject_label;
(batch_id,id) index; semester_id+archive table). Run `php artisan migrate`.
