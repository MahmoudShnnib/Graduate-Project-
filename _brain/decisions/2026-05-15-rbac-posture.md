---
date: 2026-05-15
tags: [decision, security, rbac, filament, shield]
related: [[2026-05-15-admin-ux-audit|audit report]] [[fix-plan]]
---

# Decision — RBAC posture for non-super-admin roles

## Context

The 2026-05-15 UX audit logged in as each role (super-admin, secretary, faculty, student) and compared what each saw. Two findings emerged that together describe the system's RBAC posture today:

**Counter-evidence (faculty does the right thing):**
- Faculty's sidebar nav is correctly filtered to 9 items vs 20 for super-admin: `لوحة التحكم`, `الطلاب`, `قائمة الامتحانات`, `موادي`, `عروض المقررات`, `نتائج استطلاع المقررات`, `الدرجات`, `فرص استثنائية`, `التقارير والنماذج`.
- Faculty does NOT see `التخصصات`, `المستخدمون`, `التسجيل والقبول`, `الأدوار`, `الفصول الدراسية`, `طلبات إيقاف قيد`, `تسجيلات الرسائل`, `طلبات الإضافة/الحذف`, `سجل التدقيق`, `طلبات المناقشة`, `الخريجون`.

This proves the `shouldRegisterNavigation()` pattern works correctly in this codebase.

**The gap (secretary does not):**
- Secretary sees the **same 20 nav items** as super-admin.
- Secretary can **read** the following admin-only resources without a 403:
  - `/admin/shield/roles` — full role + permission catalogue.
  - `/admin/users` — every admin's email and metadata.
  - `/admin/audit-logs` — system-wide activity log, filterable by user/role.
- Header create-actions (`إضافة دور`, `إضافة مستخدم`) appear correctly gated. But the `viewAny()` / `view()` policies aren't blocking list/read.

This is logged as **C-3 (Critical)** in the audit. It's a security finding, not a UX one.

## Decision

Adopt the explicit RBAC rule: **navigation visibility and `viewAny()` policy must both gate on the same role check, for every admin resource that isn't faculty/secretary-business.**

For the three leaking resources (`Shield/Roles`, `Users`, `Audit Logs`):

1. **Hide from nav** for non-super-admin via `shouldRegisterNavigation()` — copy the existing faculty pattern.
2. **Block read** at the policy layer — `viewAny()` and `view()` must return `false` for non-super-admin.

Both fixes are required: hiding from nav doesn't stop URL-typing access; gating only at the policy layer leaves stale nav items that 404 confusingly.

## Alternatives considered

**Alt A — Use Spatie permissions instead of role string checks.** The system already has Shield permissions wired (per `CLAUDE.md`). Switching to `auth()->user()->can('view_role')` would be more granular. ~~**Rejected for now**~~ → **Adopted instead** (2026-05-16). User feedback during the C-3 fix: *"u should have used the filament shield, that's why we added it."* The actual fix is to revoke secretary's `:Role`/`:User`/`:AuditLog` permissions inside `RolesAndPermissionsSeeder::syncRolePermissions()` — a one-place change, no scattered role-string checks. Logged as [[feedback-prefer-shield-over-role-strings]] in memory.

**Alt B — Hide via nav only, leave read access.** Rejected — defence in depth requires both layers. URL-typing is trivial; security must not depend on UI obscurity.

**Alt C — Trust super-admin will never share secretary credentials.** Rejected — Audit Logs visibility is the bigger concern. A secretary should not be able to see *who* fired a sensitive action (e.g. who approved a suspension, who deleted what). The audit log is a security primitive.

## Consequences

- Three resources (Shield/Roles, Users, Audit Logs) need both `shouldRegisterNavigation()` overrides AND policy `viewAny()` / `view()` updates.
- Three policies need to be authored or updated: `RolePolicy`, `UserPolicy`, `AuditLogPolicy`.
- Future admin-only resources must follow the same pattern from creation.
- Add a regression test: a Pest feature test logged in as secretary should `assertForbidden()` (or `assertRedirect()`) on the three URLs.

## How to apply (for the 2026-05-16 fix session)

See [[fix-plan]] Wave 2 (#6) for the file-by-file patch and verification steps.

After fix:
1. Pest test: `tests/Feature/RbacSecretaryRestrictionsTest.php` — log in as secretary, assert 403 on the three URLs.
2. Browser walk: log in as `secretary@college.edu` / `secretary123`, confirm the three nav items vanish AND direct-URL access redirects.
3. Re-audit: re-run round 4's secretary walk, expect three nav items missing + redirect on URL typing.

## Open question for future

Should `Audit Logs` be visible to secretary in a **filtered** form (e.g. only their own activity)? The current design says no — audit logs are a super-admin tool. If product wants secretaries to see "my own audit trail", that's a new feature, not a bug fix.

## Links

- Audit: [[2026-05-15-admin-ux-audit]] (see C-3 and round-5 counter-evidence)
- Fix plan: [[fix-plan]] (Wave 2)
- Related decision (existing): `2026-05-14-user-resource-scope.md` — established that `UserResource` excludes students; that scope decision should be extended here so `UserResource` also excludes secretary.
