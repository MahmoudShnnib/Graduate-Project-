---
date: 2026-04-21
tags: [decision, student-status, domain-invariant, structural, enum]
related: [[../daily-notes/2026-04-21-task-session-p2-multi-fix]], [[2026-04-20-survey-rejection-via-timestamp]], [[2026-04-21-closed-offering-enforcement]], [[../project-context/gotchas]], [[../project-context/architecture]]
---

# Decision: unify the student status system

> Source task: T6 of the 2026-04-21 session.

## Context

Prior to this session, the project had **two independent concepts of "student status"**, neither of which fully gated access to student actions:

1. **Global [`Student.status`](../../app/Models/Student.php)** — enum column on `students.status` with values `active/suspended/withdrawn/graduated`. Set by admin via `/admin/students/{id}/edit`. Used for:
   - Admin display (StudentResource columns/filter/infolist badge).
   - Dashboard status pill.
   - Widget stats counts ([StatsOverviewWidget](../../app/Filament/Widgets/StatsOverviewWidget.php)).
   - Observer cascade: [StudentObserver::updated](../../app/Observers/StudentObserver.php) drops all active enrollments to `Withdrawn` when status leaves `Active`.
   - GPA exclusion: [GradeCalculationService::recalculateStudentGPA](../../app/Services/GradeCalculationService.php) early-returns for Suspended.

2. **Per-semester [`SuspensionRequest`](../../app/Models/SuspensionRequest.php)** — semester-scoped model (introduced in T13 on 2026-04-20). The helper `Student::isSuspendedInSemester(Semester)` returns true only if an **approved** suspension request exists for that term. Used for:
   - Portal action gates: `CourseSurveyPage::submit`, `AddDropPage::submitRequests`, `EnrollStudentInOffering::__invoke` (via `StudentSuspendedInSemesterException`).
   - Dashboard precedence override (the "القيد موقوف مؤقتًا" pill when the active semester is suspended).

The **bug** T6 fixed: admin edits to `Student.status` were purely cosmetic. Setting a student to Suspended/Withdrawn/Graduated did not stop them from submitting surveys, add/drop, thesis, clearance, or getting enrolled — because those paths only consulted `isSuspendedInSemester()`, never `Student.status`.

Additionally, the existing Arabic label "موقوف" was ambiguous — it clashed semantically with the per-semester "موقوف مؤقتًا" banner. Students and admins were confusing the two.

## Decision

### 1. Rename `StudentStatus::Suspended` → `StudentStatus::Inactive`

New enum shape:

| Key | Value | Arabic label |
|---|---|---|
| `Active` | `active` | نشط |
| `Inactive` | `inactive` | القيد موقوف مؤقتًا |
| `Withdrawn` | `withdrawn` | منسحب |
| `Graduated` | `graduated` | متخرج |

The label change puts visual weight on the "temporarily paused" framing and disambiguates from the admin review state. The case/value rename is mechanical but needed for coherent domain semantics going forward.

### 2. One method to answer "can this student act?" — `Student::canPerformActions(?Semester)`

```php
public function canPerformActions(?Semester $semester = null): bool
{
    if ($this->status !== StudentStatus::Active) return false;
    if ($semester && $this->isSuspendedInSemester($semester)) return false;
    return true;
}
```

Every gate across the portal + Action layer uses this one helper. When the user asks "can this student do X?" the answer is a single method call with an optional semester scope.

### 3. Cascade rule — all three non-Active states wipe active enrollments (Option A)

On Stage-4 design: the existing `StudentObserver` already cascades any non-Active status to Withdrawn on all active enrollments. Since the user confirmed "if their status changes to one of these they instantly get removed from any courses they are taking" for Inactive as well, **no observer change is needed** — the rename preserves the current semantics.

Read-path consequence: with cascade, past enrollments lose their "Enrolled" status on a flip. To preserve transcript visibility (the user's "CAN view their data / past grades" requirement), widen the historical-read queries to include withdrawn enrollments with approved grades. See section below.

### 4. Typed exception — `StudentNotActiveException`

Consistent with existing enrollment guards (`EnrollmentCapReached`, `CourseOutsideSpecialization`, `StudentSuspendedInSemester`, `CourseOfferingClosed`). Throws from `EnrollStudentInOffering::__invoke` when `Student.status !== Active`. All 4 enrollment call sites add a catch.

### 5. Portal-page gates

Each of these 5 submit paths gains a `canPerformActions()` pre-guard with a danger-card banner on the UI:

| Page | Method gated |
|---|---|
| [CourseSurveyPage](../../app/Filament/Student/Pages/CourseSurveyPage.php) | `submit()` |
| [AddDropPage](../../app/Filament/Student/Pages/AddDropPage.php) | `submitRequests()` |
| [SuspensionRequestPage](../../app/Filament/Student/Pages/SuspensionRequestPage.php) | `submit()` (on top of T5 approved-semester guard) |
| [ThesisRegistrationPage](../../app/Filament/Student/Pages/ThesisRegistrationPage.php) | `submit()` |
| [ClearancePage](../../app/Filament/Student/Pages/ClearancePage.php) | `requestClearance()` |

These are separate from the existing `isSuspendedInSemester` banners — the two banners can coexist (unlikely in practice, but defensive): semester-scoped + global-status banners are visually distinct (same danger-card shape, different copy).

### 6. Admin picker filters

On [EnrollStudents](../../app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php):
- `enroll_manual` search query adds `->where('students.status', StudentStatus::Active)`.
- `getSurveyStudentOptions()` adds `whereHas('student', fn ($q) => $q->where('status', StudentStatus::Active))`.

Non-Active students are invisible in the "إضافة طالب" search and the "طلبوا المادة" survey picker. Consistent with the enrollment action's guard (defense in depth).

### 7. Dashboard precedence — any non-Active global status wins over the semester badge

Current logic at [dashboard.blade.php:91-126](../../resources/views/filament/student/pages/dashboard.blade.php): semester-scoped suspension overrides the enum-based pill. Updated precedence (highest wins):

1. If `isSuspendedInSemester(current)` → "القيد موقوف مؤقتًا" (danger).
2. Else if `status !== Active` → use `$status->label()` + color map (danger for Inactive/Withdrawn, info for Graduated).
3. Else "نشط" (success).

Same color scheme as before; just ensures the enum label always reflects the truth when semester-scoped override doesn't apply.

### 8. Read-path visibility (Option A consequence)

| Page | Query before | Query after |
|---|---|---|
| [GradesPage::getGrades](../../app/Filament/Student/Pages/GradesPage.php) | `->active()` + `whereHas('grade')` | Drop `->active()`; keep `whereHas('grade')` |
| [GradesPage::getSemesters](../../app/Filament/Student/Pages/GradesPage.php) | `whereHas(..., status=Enrolled)` | Widen via `whereHas('courseOfferings.enrollments', whereHas('grade', Approved))` |
| [CourseTreePage::getEnrollmentMap](../../app/Filament/Student/Pages/CourseTreePage.php) | `$student->activeEnrollments()` | `$student->enrollments()` — but still filters to `isPassed` (Approved+non-F grade) OR `isCurrentlyTaking` (Enrolled+current semester) |

[StudentCoursesPage::getEnrollments](../../app/Filament/Student/Pages/StudentCoursesPage.php) intentionally unchanged — it's the "currently taking" view for the active semester; for a cascaded student, showing no courses IS the correct display (they're not actively enrolled in anything after cascade).

## Alternatives considered

**A. Only cascade Withdrawn + Graduated, leave Inactive enrollments as-is.** Considered during Phase 0 (Q6.2 discussion). Rejected because the user explicitly stated "if their status changes to one of these they instantly get removed from any courses they are taking" — all three trigger cascade. Option A (current) preserves the user's intent.

**B. Keep `Suspended` as the enum case and add a separate `Inactive` field.** Rejected — adds a second per-student boolean on top of the existing per-semester `SuspensionRequest`, further fragmenting "status". The rename consolidates rather than fragments.

**C. Drop global status entirely; rely only on semester-scoped suspensions + withdraw/graduate events.** Rejected — admins need a single-field answer to "is this student a current student?" for display in filters, widgets, and lists. Per-semester checks don't answer that summary question without a walk.

**D. Add the gate on page mount (redirect non-Active students away).** Rejected — students whose file is paused or withdrawn still need read access to their data. Gating on submit-only preserves read access while blocking writes.

## Consequences

### Positive

- Admin edits to `Student.status` now take effect immediately everywhere. Setting a student to Inactive/Withdrawn/Graduated blocks submissions across the portal the next time the page loads.
- Single gate method means future gates are one-line additions (`if (! $student->canPerformActions($semester))`).
- Typed exception matches existing 4-exception pattern (cap/spec/suspension/closed). Consistency reduces cognitive load for future work.
- Observer cascade behavior is unchanged — the rename is mechanical at the observer layer.
- Transcript visibility preserved despite cascade (Option A).

### Negative

- 5 portal pages each need a banner + guard — duplication across Blades. Acceptable cost for clarity; could be extracted to a reusable Blade partial later.
- `wire:model.live` + `canPerformActions()` check fires on every Livewire round-trip. Cheap (one DB query on page load), but worth noting for performance-sensitive sessions with many students.
- Dashboard precedence logic is now more nested (3-level if/elseif/else). Mitigated by extracting the logic to a `@php` block at the top of the Blade.
- `GradesPage::getGrades()` drop of `->active()` means past Dropped enrollments with approved grades would also show — they wouldn't in practice (Dropped enrollments rarely have Approved grades per the existing grade workflow), but worth a test.

## Migration strategy (SQL)

MySQL's `ENUM` type doesn't rename cases cleanly across DB engines. Rather than three-step ALTER/UPDATE/ALTER, the migration:

1. Convert `students.status` from `ENUM(...)` to `VARCHAR(20)` (via `->change()`).
2. `UPDATE students SET status='inactive' WHERE status='suspended'`.
3. Leave as VARCHAR — the Laravel-side `StudentStatus::class` cast enforces valid values.

Down: reverse the UPDATE. Column stays VARCHAR (there's no pre-ENUM state to restore to without losing another round-trip; acceptable tradeoff since the cast enforces domain values anyway).

## Follow-up candidates

- **Audit `Student.status` writes.** The current `StudentObserver::updated` cascade runs on ANY non-Active flip. If a future workflow wants to temporarily pause a student without wiping enrollments (e.g. a financial hold that should be reversible), add a separate state and a separate observer. For now, the three non-Active states all cascade identically.
- **Graduation workflow.** Currently Graduated is a status admin sets manually. A future auto-graduation flow (based on completed credits + clearance) would write this value via an Action, which should fire this same observer cascade to "zero out" the student's active enrollments.
- **Reusable gate banner.** The Blade partial on the 5 gated pages is a candidate for extraction to `resources/views/filament/components/status-banner.blade.php`. Defer until we have the 5th implementation in place and the pattern is stable.
- **Test matrix expansion.** Beyond the core test file, consider a fixture-free parametric test: for each (status × action) pair, assert the expected gate outcome. Use Pest datasets if the existing tests prove too verbose.

## 2026-04-21 addendum — cross-writer override semantics

Two independent writers can put a student in a "cannot perform actions" state:

1. **Admin → `Student.status`** via `/admin/students/{id}/edit` (global, permanent).
2. **Student → `SuspensionRequest`** via the portal; approved by **admin → `SuspensionRequest.status`** in `SuspensionRequestResource` (semester-scoped, temporary).

Between these two writers, the admin's **most recent explicit signal wins**. Concretely:

- An admin save on EditStudent with `status=Active` while an approved semester-scoped suspension exists → `EditStudent::rescindActiveSemesterSuspensionIfOverridden` flips the SuspensionRequest to Rejected. The admin's "this student is Active now" signal overrides the earlier "student is paused this term" signal.
- An admin save with a non-Active status (Inactive / Withdrawn / Graduated) leaves the SuspensionRequest alone — both writers now agree that the student cannot perform actions.
- Display precedence (`Student::getDisplayStatusLabel`) also respects this: Withdrawn and Graduated override semester suspension in the rendered badge because they are terminal states (the student's situation is "more than just paused this term").

The rescind is scoped to the **active** semester only. If a student had approved suspensions for multiple terms (rare; possible via admin approving multiple requests), only the one for `Semester::where('is_active', true)` is rescinded. Past-semester approved suspensions are historical record and should not be rewritten.

No typed exception is thrown from the rescind path — it's a side-effect of admin intent, not a domain violation. Notification informs the admin via a `success` toast.
