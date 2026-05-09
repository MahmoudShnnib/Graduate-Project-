# PROGRESS.md — بوابة الطالب build log

A continuously-updated log of what was built, what's pending, and decisions you should review.

---

## Phase 0 — Audit ✅ COMPLETE

**Built:**
- Read SYSTEM_SPEC.md, DESIGN_SPEC.md, TEST_CREDENTIALS.md.
- Inspected all 21 Eloquent models, all 13 enums, all 38 migrations, all existing seeders.
- Wrote `mobile/AUDIT.md` — complete inventory + decisions.

**Decisions made (review at AUDIT.md §3, §4, §5):**

1. **Sanctum is NOT installed** — Phase 1 installs it; bearer tokens, no SPA cookies.
2. **`routes/api.php` does NOT exist** — registering it via `bootstrap/app.php`, all routes prefixed `/api/v1`.
3. **In-app notifications:** new `app_notifications` table (NOT reusing `notification_logs` which is for outbound email/WhatsApp). Cleaner separation.
4. **Adding two columns to `exceptional_requests`** — `course_id` (nullable FK) and `opportunity_type` (nullable enum string). Required by DESIGN_SPEC. Both nullable so existing rows + Filament admin keep working.
5. **Test student seeding** — new `MobileTestStudentSeeder` runs after existing seeders. Creates `26CS101` (Active) and `26CS102` (OnHold), updates active semester to "الفصل الدراسي الأول 2026", creates a previous semester, course offerings for both, enrollments, grades (full publish on past, midterm-only on active, one F to seed exceptional flow), 1 active warning, 2 unread notifications.
6. **Specialization rename** — existing `code=2` Arabic name "الحاسب الآلي" updated to "علوم الحاسوب" (idempotent) in MobileTestStudentSeeder so display matches TEST_CREDENTIALS without breaking code-keyed lookups.
7. **No Zustand** — TanStack Query for server state, react-hook-form for forms, expo-secure-store for token, local Context for auth state. Revisit only if needed.
8. **Mobile folder layout** — Expo Router (`app/`) + `src/{api,components,hooks,lib,theme}`, `assets/fonts/`. Hidden design preview at `app/_design-preview.tsx`.

**Spec ambiguities resolved (AUDIT.md §5):** 11 small interpretation decisions documented — tab order, badge counts, exceptional opportunity types, clearance trigger, etc. All chose the simpler interpretation.

**Skipped:** nothing.

**Blockers:** none.

**Next:** Phase 1 — Laravel API foundation.

---

## Phase 1 — Laravel API foundation ⏳ NEXT

(updated after Phase 1 commit)

---

## Phase 2 — Expo skeleton + design system

(pending)

---

## Phase 3 — Screens

(pending)

---

## Phase 4 — Polish

(pending)
