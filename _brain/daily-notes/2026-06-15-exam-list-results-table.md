---
date: 2026-06-15
tags: [exam-list, filament, ux, grades]
related: [[2026-06-15-shield-admin-gating]]
---

# Exam list — required grade + collapsible results table

## What was done

On `/admin/exam-list-page` (`ExamListPage` + `exam-list.blade.php`):

1. **Grade now required to accept/reject.** `markPassed()` / `markFailed()` call a new
   `applicationsMissingScore()` helper and **abort the whole batch** (warning notification
   listing the offending names) if *any* selected applicant has an empty score box. Previously
   the score was optional and silently saved as `null`. Decision confirmed with user: block-all,
   not partial-process.

2. **New "نتائج الامتحان التفاضلي" results table** below the pending list. Driven by
   `getProcessedApplications()` — all applications with `exam_result_date` set (i.e. already
   passed/failed), scoped to the same semester + specialization-tab filters as the pending list.
   Columns: #, name, specialization (when no spec tab), national id, grade, result badge
   (مقبول=success / مرفوض=danger), result date. Rendered **outside** the pending `@if/@else`
   so it still shows after everyone has been processed.

3. **Styled like `/admin/courses-survey-results`** — same `fi-ta` card + always-visible header
   + round chevron toggle.

4. **Smooth open/close** — the survey page uses a Livewire `@if` that snaps instantly. Here the
   toggle is **Alpine** (`x-data="{ open:false }"` + `x-show` + `x-collapse` + `x-cloak`) so the
   height animates. Starts **collapsed** (user choice).

## Tests

- `ExamListConfirmationTest`: existing accept/reject happy-paths updated to `set('scores', ...)`;
  added 2 guard tests (refuse accept/reject with no grade → status stays `InitiallyApproved`) and
  1 test for `getProcessedApplications()` scoping.
- `AuditBatchPerSubjectTest`: both exam tests updated to set scores (guard would otherwise abort).
- All green (12 passed). Pint clean.

## Archived-semester read-only gate (follow-up)

User wanted the نتائج table to be "added to archiving". Clarified that exam results are
already preserved per-semester (fields on `applications`, stamped with `semester_id`) and the
page already clears for the new active semester (combo `selectedSemester` defaults to active in
`mount()`; `getSemesters()` lists archived semesters too). The genuine gap was that an **archived**
semester selected in the combo still allowed editing/accepting — which would create a student in a
closed term.

Added (decisions: *history-only* view + *hard guard*):

- `ExamListPage::isSelectedSemesterArchived()` — true when combo points at a semester with
  `archived_at` set.
- Hard guard at the top of `markPassed()` / `markFailed()` — abort with a warning if archived
  (defense-in-depth; matches the archived = read-only rule in `archiveCascade()` / EnrollStudents /
  EditSemester).
- View: when archived, hides the pending list + score inputs + accept/reject + print button, shows
  an "هذا الفصل مؤرشف — للعرض فقط" notice, and the نتائج results table is the only content.
- Results table auto-**expands** when archived (`x-data="{ open: @js($isArchived) }"`), and a
  `wire:key="processed-results-{semesterId}"` forces Alpine to re-init on each combo switch so the
  default-open state applies when switching to an archived term.
- `SemesterFactory::archived()` state added.
- Tests: refuse-accept-when-archived + `isSelectedSemesterArchived()` gate. All green.

**No change to `archiveCascade()` was needed** — exam results need no copy/snapshot; they live on
the semester-scoped applications.

## Next steps / loose ends

- Older rows processed before this change may have `exam_score = null` → shown as `—` in the grade
  column (intentional, no backfill).
- Uncommitted, per no-auto-commit rule.
