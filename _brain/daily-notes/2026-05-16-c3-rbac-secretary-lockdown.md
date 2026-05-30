---
date: 2026-05-16
tags: [daily, security, rbac, filament, policies, shield, ux-audit-fix, c-3]
related:
  - "[[../decisions/2026-05-15-rbac-posture]]"
  - "[[2026-05-15-ux-audit-admin]]"
  - "[[2026-05-16-c1-axe-critical-fixes]]"
  - "[[2026-05-16-h1-h2-light-mode-contrast]]"
  - "[[2026-05-16-m18-faculty-dashboard-widgets]]"
  - "[[../research/audit-2026-05-15/audit|audit report]]"
  - "[[../decisions/2026-05-14-user-resource-scope]]"
---

# 2026-05-16 — C-3 fix: secretary locked out of Users / AuditLogs / Shield Roles

## What was done

Audit finding **C-3** (Critical, security) closed. Secretary can no longer read `/admin/users`, `/admin/audit-logs`, or `/admin/shield/roles`. Super-admin retains full access; faculty and student were already blocked from these surfaces.

Implements the [[../decisions/2026-05-15-rbac-posture]] decision verbatim: navigation visibility AND `viewAny()` policy both gate on the same role check (`role === 'super_admin'`), for defence in depth.

## Files touched (2 production files + 1 test, after the refactor)

The first pass scattered `$user->role === 'super_admin'` checks across both resources and all 3 policies. User pushed back: *"u should have used the filament shield, that's why we added it."* Refactored to use the existing Shield infrastructure as a single source of truth. Memory saved: [[feedback-prefer-shield-over-role-strings]].

| File | Change |
|---|---|
| `database/seeders/RolesAndPermissionsSeeder.php` | Added `$adminOnlyModels = ['Role', 'User', 'AuditLog']` array. Secretary's permission set now `->reject(...)`s every permission whose name ends in `:Role`, `:User`, or `:AuditLog`. Covers all 12 CRUD verbs per model in one place — future Shield regenerations can't silently leak admin-only access. |
| `tests/Feature/RbacSecretaryRestrictionsTest.php` | **new** regression test (4 tests) — secretary gets 403/302 on the 3 URLs; super_admin gets 200 on all 3. |
| (rolled back) `UserResource.php`, `AuditLogResource.php` | The first-pass `shouldRegisterNavigation()` + `canAccess()` overrides were removed. Filament's default chain `shouldRegisterNavigation → canAccess → can('viewAny') → policy` already gates correctly once the underlying Shield permission is revoked. |
| (rolled back) `UserPolicy.php`, `AuditLogPolicy.php`, `RolePolicy.php` | Restored to the Shield-generated form (`return $authUser->can('ViewAny:User')` etc.) — the standard pattern Shield ships. No role-string `if`s. |

## How super_admin still passes

`AppServiceProvider::boot()` registers:

```php
Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);
```

So `->can('viewAny')` short-circuits to `true` for super_admin before the policy method runs. Super_admin retains the full `Permission::all()` grant from the seeder regardless — `Gate::before` is just the fast path.

## Why through Shield, not role strings

The project already invested in Shield (`bezhansalleh/filament-shield`) precisely to be the one place where "who can do what" lives. Scattering `role === 'super_admin'` checks across 5 files (3 policies + 2 resources) duplicates security logic, drifts over time, and bypasses Shield's auditable permission catalog. The proper fix lives in the seeder — one diff, one re-run, every consumer of those permissions (resources, policies, nav, action visibility) reacts automatically.

This direction matches `MyCourseResource`'s existing approach (Shield permission + role narrowing for ownership), supersedes the role-string Alt-A path that the 2026-05-15 RBAC decision tentatively authorized, and is now codified in [[feedback-prefer-shield-over-role-strings]].

## Test results

- New test alone: **4 passed (6 assertions)** in 2.16s.
- Full suite: **162 passed (343 assertions)** — was 158, plus 4 new — no regressions.
- Memory: this run needed `-d memory_limit=6144M` (up from 4GB last session, 512MB three sessions ago). The creep is now worse than the audit fixes — promoting to a real concern; see "Memory creep" below.

## Verification

- `vendor/bin/pint --dirty --format agent` → pass.
- `php artisan optimize:clear` → clean.
- Pest test directly proves the security guarantee (secretary gets 403/302 on all three URLs).
- Browser walk pending:
  1. Log in as `secretary@college.edu` / `secretary123` → visit `/admin`.
  2. Sidebar should no longer show `الأدوار`, `المستخدمون`, or `سجل التدقيق`.
  3. Direct URL typing `/admin/users`, `/admin/audit-logs`, `/admin/shield/roles` → expect 403 (Filament default on `canAccess` failure).
  4. Log in as `admin@college.edu` / `admin123` → all three pages still load with full data.
  5. Log in as `cs.faculty@example.ly` / `password` → confirm faculty sidebar is still the same 9 items (unchanged).

## Audit progress

- ✓ Wave 3 (H-3, H-6) — done.
- ✓ C-1 — done.
- ✓ H-1, H-2 — done.
- ✓ M-18 — done.
- ✓ **C-3 — done this session.**

**All 3 Critical findings now closed.** Remaining work in the fix-plan is High/Medium/Low and Wave 1 (cheap brand fixes).

## Next session anchors — what's left in the fix-plan

In priority order:

- **Wave 1 — brand + cheap a11y** (≤30 min total): `APP_NAME=Laravel`, sidebar topbar `aria-label`s, sidebar group toggle button labels, focus-ring polish, Vite font 404. Quick wins.
- **H-4** Custom Livewire pages with English titles (`ReportsPage`, `CoursesSurveyResultsPage`) — needs `getTitle()` + `$navigationLabel` overrides.
- **H-8** Portal page H1s leak English class names (student dashboard, student courses page, grades page, course survey page) — same pattern, applied to `app/Filament/Student/Pages/*`.
- **H-7** Filament forms fall back to English browser-native validation tooltip — add `novalidate` panel-wide.
- **H-9** Sidebar/Nav doesn't filter by role for secretary — **largely closed by this C-3 fix** (the three nav items disappear). Verify in browser; remaining gaps would be incremental.
- **M-9** Confirmation modals don't name the entity — systemic across many resources.
- **M-1..M-17** long tail.

Plus the **non-audit** maintenance items:
- **Test memory creep** — actively getting worse. Was 512MB → 4GB → 6GB across 5 sessions. Almost certainly an accumulating factory or seed loop in tests. Worth a dedicated debugging session next. Hypothesis to check: `RolesAndPermissionsSeeder` self-heal block (added 2026-05-14) might be calling `shield:generate` per test setUp through `RefreshDatabase` → 192 permissions × N tests = ballooning DB rows + their AR objects in memory.
- **L-3** — Print button on application view page (violates `feedback_no_print_button.md`).
- **Spec update** — promote dual light/dark muted-text values into `figma-view-page-design.md` token table (`#52525b` light / `#71717a` dark for hero meta).
