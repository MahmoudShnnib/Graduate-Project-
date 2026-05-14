---
date: 2026-05-13
tags: [daily, filament, infolist, redesign, figma, design-system, multi-page]
related:
  - "[[../project-context/figma-view-page-design]]"
  - "[[2026-05-11-student-view-figma-redesign]]"
  - "[[../decisions/2026-05-11-view-page-design-language-from-figma]]"
---

# 2026-05-13 — Figma view-page design rolled out across 7 admin pages + muted pill palette adopted

## What we did

Applied the [[../project-context/figma-view-page-design|Figma view-page design language]] (originally built for `/admin/students/{id}` on 2026-05-11) to **seven additional admin pages** in a single session. Also evolved the design system: the original "bright" pill palette was replaced with a **muted palette** the user explicitly preferred, and that change was backported to student + user views so every page uses one consistent pill family.

### Pages redesigned

| Page | Wrapper class | Hero subject | Below the hero |
|---|---|---|---|
| `/admin/specializations/{id}` | `.figma-specialization-view` | Single-card layout: name + code subtitle + 2 KPIs (مقرر / طالب) merged into one bordered card with hairline divider | Courses relation manager |
| `/admin/users/{id}` | `.figma-user-view` | Name + status pill + role pill (muted) + English-name subtitle + meta (email/phone/specialization) | 2-col infolist: personal data \| contact + extra |
| `/admin/students/{id}` (existing) | `.figma-student-view` | (no structural change, pill palette refreshed to muted) | — |
| `/admin/applications/{id}` | `.figma-application-view` | Name + status pill + study-type pill + transfer pill + tracking-code subtitle + meta (email/phone/spec/date) | 2-col infolist + full-width docs + admin/exam |
| `/admin/semesters/{id}` | `.figma-semester-view` | Name + active/archived pill + add-drop info pill + season/year subtitle + meta (dates/offerings/archive) | 2-col infolist + relation managers (offerings + decided applications) |
| `/admin/course-offerings/{id}/enroll` | `.figma-enroll-view` | Course name + open/closed/archived pill + credits pill + code subtitle + meta (semester/faculty/enrolled count) | Students table |
| `/admin/student-enrollments/{offering}/students` | `.figma-enroll-view` (reused) | Same hero as the enroll page | Read-only students table |
| `/admin/grades/{offering}/grades` | `.figma-grades-view` | Same hero shape + publish-state pill (منشور بالكامل / منشور جزئياً) + enrolled/withGrade/published meta | Grades table with row + bulk actions |

### Design-system evolution: muted pill palette

The original status pill used saturated colors (e.g. `bg #dcfce7 / color #008236`). User reaction in order: "the Role pill are too bright" → "the نشط pill too" → "ok i like these pills better, use them for these too" → "this too ضعيف 0.00 المعدل".

New canonical pill palette (applied to every page via scoped CSS):

| Variant | Light bg / border / text | Dark bg / border / text |
|---|---|---|
| success | `#f0fdf4` / `#dcfce7` / `#166534` | `rgba(34,197,94,.08)` / `rgba(...18)` / `#86efac` |
| danger  | `#fef2f2` / `#fee2e2` / `#991b1b` | `rgba(239,68,68,.08)` / `...18` / `#fca5a5` |
| warning | `#fffbeb` / `#fef3c7` / `#92400e` | `rgba(245,158,11,.08)` / `...18` / `#fcd34d` |
| info    | `#eff6ff` / `#dbeafe` / `#1e40af` | `rgba(59,130,246,.08)` / `...18` / `#93c5fd` |
| gray    | `#fafafa` / `#e4e4e7` / `#52525b` | `rgba(244,244,245,.06)` / `rgb(63 63 70)` / `#d4d4d8` |

Also toned down the **GPA chip tier label**: smaller (`12px / 600`), thin border instead of solid bg, same muted palette. Tier word (ممتاز / جيد / ضعيف) no longer shouts.

A separate `.fsv-role-pill` was introduced for **secondary** identification (role on user, study type on application, etc.) — same palette but slightly softer (defaults to neutral gray, only tinted when a meaningful color is set). The bright status pill in student view originally was much louder; both pills now share the same softer tones.

### Two new rendering recipes — to be documented in the spec

1. **`{{ $this->content }}` for relation-manager pages.** When a view page needs to render its relation managers (semester → offerings + decided applications, specialization → courses), put `{{ $this->content }}` in the custom Blade — that's the Filament `ViewRecord::content()` Schema that wraps infolist + every relation-manager component. Do NOT use just `{{ $this->infolist }}` or the relation managers will silently disappear (this bit us once on the semester page mid-session and was fixed immediately).

2. **Workflow actions stay in `getHeaderActions()`** for pages whose page-header actions trigger complex modals/notifications/audit calls (publish grades, archive semester, toggle offering, enroll survey, retake requests). Don't try to render them inline in the hero card — they need Filament's Action runtime. Only the strip-chrome triple stays: `$view`, `getHeading('')`, `getBreadcrumb($name)`. **Keep** `getHeaderActions()` populated.

   For the simpler resources (Student / User / Application / Specialization) `getHeaderActions()` returns `[]` and Edit lives inside the hero. For workflow-heavy pages (Semester / Grades / EnrollStudents) it stays as it was and the hero is just identity + optional Print.

### Other small touches

- Removed literal emoji glyphs (📘📗📙 in course-type group titles, 📄 in document entries) per user preference. SVG heroicons stay (those aren't "emojis").
- Print button kept on every redesigned view except where the user explicitly removed it (Specializations).
- File-attribute icons revised for accuracy: replaced `Heroicon::HeartFilled` / `IdentificationSolid` (don't exist in v5) with `Heart` / `Identification`.

## Backups

Every page change includes a sibling backup folder with a `cp …` revert script in its README:

```
_backups/specialization-view-2026-05-13/        (round 2 — final accepted single-card design)
_backups/user-view-2026-05-13/
_backups/application-view-2026-05-13/
_backups/semester-view-2026-05-13/
_backups/enroll-students-2026-05-13/
_backups/view-course-enrollments-2026-05-13/
_backups/view-course-grades-2026-05-13/
```

## Verification

- `vendor/bin/pint --dirty --format agent` → pass on every batch.
- All Filament `Heroicon::*` enum names verified against `vendor/filament/support/src/Icons/Heroicon.php`.
- No backend / model / observer / service / migration touched. Strict frontend.

## Open work for the next session

- [ ] Continue rolling the design to the remaining view pages:
  - `/admin/discussion-requests/{id}` (`ViewDiscussionRequest`)
  - `/admin/thesis-registrations/{id}` (`ViewThesisRegistration`)
  - `/admin/graduates/{id}` (`ViewGraduate` — clone of Student)
  - `/admin/audit-logs/{id}` (`ViewAuditLog` — already has a different bespoke layout from earlier work; pair-with the design here is optional)
  - `/admin/my-courses/{id}` (faculty `ViewMyCourse`)
- [ ] Promote the **muted pill palette** + **hero structure** + **Section card overrides** out of per-page `@push('styles')` and into `resources/css/filament/admin/theme.css` once 2–3 more pages confirm the API. We now have 8 implementations — solid sample size.
- [ ] Extract the hero-card markup into a reusable Blade component (`<x-admin.entity-hero>`) so each new page becomes a 10-line Blade. Spec already mentions this under "Components to extract once we have 2+ implementations".
- [ ] Update [[../project-context/figma-view-page-design]] with:
  - The new muted pill palette as the canonical defaults (replace the bright tokens).
  - The `{{ $this->content }}` rendering recipe for relation-manager pages.
  - The "workflow actions stay in page header" rule for action-heavy pages.
- [ ] Consider whether `Specialization` should keep its **single-card** layout (KPIs in a footer band) or be refactored to the standard hero + sections pattern — current single-card was a deliberate response to "isn't much going on, make 2 cards 1" but it diverges from the rest of the family. Both work, but consistency may win.

## Pattern-defining moments from this session

- **"the role pill are too bright"** → the muted palette was born here.
- **"u removed the tables, return them as is"** → the `{{ $this->content }}` rendering recipe was discovered here.
- **"make the top 2 cards one"** → the single-card variant for sparse resources was prototyped on Specializations. Worth documenting as an alternative layout in the spec, alongside the canonical hero+sections.
