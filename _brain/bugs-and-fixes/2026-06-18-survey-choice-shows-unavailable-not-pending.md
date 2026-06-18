---
date: 2026-06-18
tags: [bug, fix, course-survey, student-portal, ux]
related: [[../daily-notes/2026-06-17-eight-fix-batch]]
---

# Submitted survey choice showed "المادة غير متاحة هذا الفصل" instead of "قيد المراجعة"

## Symptom
On `/portal/course-survey-page`, right after a student submits their survey, each
chosen course showed **"المادة غير متاحة هذا الفصل"** (course unavailable this semester).
That is misleading: the survey's whole purpose is to pick courses *before* the offerings
are opened, so "no offering yet" is the normal expected state — it should read as
**"قيد المراجعة"** (under review) until the admin enrolls or rejects the choice.

## Root cause
`CourseSurveyPage::getEnrollmentStatuses()` mapped a surveyed course to `'unavailable'`
whenever no `CourseOffering` existed for it in the active semester. During the survey
window (and until the admin opens offerings) that branch fired for every fresh choice.
The blade then rendered the `@else` "المادة غير متاحة هذا الفصل" label.

## Fix
Dropped the `'unavailable'` status entirely. A surveyed choice is now:
- `'rejected'` — `rejected_at` set, or
- `'enrolled'` — an active enrollment exists (via `whereHas('courseOffering', course+semester)`), or
- `'pending'` — everything else, **including no offering yet**.

`course-survey.blade.php` (both render blocks): the status label `@else` now shows
**قيد المراجعة**, and the danger color is gated on `'rejected'` only (pending = warning).

## Regression guard
3 tests in `CourseSurveySubmitOnceTest`:
- submitted choice with **no offering** → `pending` (the bug),
- offering opened + student enrolled → `enrolled`,
- `rejected_at` set → `rejected`.
Full suite 392 green. (Browser screenshot deferred — Chrome was offline; data staged on
`gpa.test@dev.ly` / survey for EE604, no offering.)
