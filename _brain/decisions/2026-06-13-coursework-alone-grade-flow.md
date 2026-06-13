---
date: 2026-06-13
tags: [decision, grades, notifications, faculty, two-phase-publish]
related:
  - "[[2026-06-13-faculty-only-my-courses]]"
---

# Two-phase grades: coursework-alone entry + notify on coursework publish

## Context
User wanted: faculty able to enter أعمال الفصل (coursework) alone now and the final later; and the
student notified when coursework is published. The premise that "the student is notified the moment
the teacher sets the grade" was **incorrect** — notification only ever fired on publish-finals; the
faculty form just *required both* fields together.

## Decision
The system already had the two-phase machinery (`midterm_published_at` vs `published_at`, admin edit
allowed coursework-only). Closed the faculty-side gaps:
1. Faculty `enter_grade` form: `final_grade` optional (coursework still required). Empty final
   normalised to null; `Grade::booted()` already leaves total/letter/points null.
2. Faculty may **re-open** an approved coursework-only grade to add the final — `enter_grade` is
   visible when the grade is null/Draft, or Approved-with-null-final. Saving resets status to Draft
   so the added final goes back through approve → publish-finals. Hidden while PendingApproval.
3. `publish_midterms` now sends a new `CourseworkGradePublishedNotification` (mail + WhatsApp).
   Finals publish keeps `GradePublishedNotification`.

## Why this is safe
Portal `GradesPage` gates display on the publish **timestamps**, not status — so reverting a grade
to Draft to add a final does NOT hide already-published coursework, and the final stays masked until
`published_at`. Confirmed in `grades.blade.php` (`$isFullyPublished` gate).

## Consequences
- Notification timing: coursework publish AND finals publish notify; entry/approval do not.
- Tests: `GradeCourseworkFlowTest` (coursework-alone save, re-open to add final, hidden while
  pending, notify on coursework publish).
