---
date: 2026-04-20
tags: [bug, transfer-courses, filament, edit-page, gpa, regression]
related: [[../daily-notes/2026-04-20-task-session-multi-fix]], [[../project-context/gotchas]]
status: fixed
---

# مواد المعادلة (Transfer courses) not persisted when editing a student

> **Status:** Fixed. T2 of the 2026-04-20 task session.

## Symptoms

User reported: after editing a student and using the "معادلة مواد" action to enter transfer courses with grades, clicking save on the edit form — then visiting the student's portal course tree — the transferred courses don't appear as passed, and the student's GPA doesn't update.

## Root cause

[`app/Filament/Resources/Students/Pages/EditStudent.php`](../../app/Filament/Resources/Students/Pages/EditStudent.php) was a bare `EditRecord` with no lifecycle hooks. The `CreateStudent` page had `handleRecordCreation` that reads the `transfer_courses` hidden form field and creates `TransferCourse` rows ([CreateStudent.php:147-159](../../app/Filament/Resources/Students/Pages/CreateStudent.php)), but this pattern was never mirrored to edit.

Full chain of failure:
1. **Fill:** When an admin opens the edit page, the hidden `transfer_courses` form field starts as `[]` (its default). Existing `TransferCourse` rows for the student were never loaded into the form.
2. **Modal:** Clicking "معادلة مواد" opens the slide-over with an empty repeater (because the modal's `fillForm` pulls from the empty `transfer_courses` form state).
3. **Modal save:** Closing the modal updates the form state only (`$schemaSet('transfer_courses', ...)` at [StudentResource.php:438](../../app/Filament/Resources/Students/StudentResource.php)).
4. **Main save:** Filament's default `handleRecordUpdate` saves fillable Student columns. `transfer_courses` is not a Student column, so it's silently dropped.
5. **Result:** `TransferCourse` table was never touched → course tree + GPA both show zero transfer contribution.

`CourseTreePage::getEnrollmentMap` ([CourseTreePage.php:132-156](../../app/Filament/Student/Pages/CourseTreePage.php)) and `GradeCalculationService` correctly read TransferCourse rows when they exist — they just never existed after editing.

## Fix

Rewrote [`EditStudent.php`](../../app/Filament/Resources/Students/Pages/EditStudent.php) to add two hooks:

### `mutateFormDataBeforeFill`

Populate `transfer_courses` from the student's existing `TransferCourse` relation, mapped to the repeater's field shape (`course_id`, `coursework_grade`, `final_grade`). Now the modal opens with current entries.

### `handleRecordUpdate`

After the standard Student save:
1. Strip `transfer_courses` from `$data` (not a Student column).
2. Call `parent::handleRecordUpdate` to save Student fields.
3. `delete()` all existing TransferCourse rows for this student.
4. If `is_transfer === true`, recreate from form data.
5. Trigger `GradeCalculationService::recalculateStudentGPA` (the service skips suspended students internally, so no special guard needed here).

**`delete + create` vs upsert:** Chose delete-and-recreate because the repeater allows arbitrary add/remove/reorder without tracking primary keys, so there's no clean way to diff old vs new. Matches the create-side pattern and is safe because TransferCourse has no dependents (FKs only go *to* it, nothing references it).

**Toggle-off behavior:** If an admin flips `is_transfer` off, all existing TransferCourse rows are deleted (step 3 runs unconditionally). This keeps the model consistent — a non-transfer student shouldn't have transfer courses.

## Regression test

Created [`tests/Feature/TransferCourseEditTest.php`](../../tests/Feature/TransferCourseEditTest.php) with three specs:
1. **Fill path** — existing TransferCourse rows appear in the form when opening edit.
2. **Save path** — submitting with two new transfer courses creates 2 rows + GPA > 0.
3. **Toggle-off path** — setting `is_transfer = false` deletes existing transfer courses.

All 3 pass. Full suite: 58/58 (was 55, +3 new).

## Related / follow-up

- **Similar latent bug in CreateUser flow?** The [`UserResource`](../../app/Filament/Resources/Users/Pages/CreateUser.php) was audited during T17 and now has `afterCreate`; no transfer-course equivalent on user create, and shouldn't — users aren't the owner of TransferCourse rows.
- **The same pattern may exist elsewhere:** any Filament Resource with a hidden form field that stores related-model data (not a column on the primary model) must have matching `mutateFormDataBeforeFill` + `handleRecordUpdate` hooks. Worth auditing the codebase for hidden fields in Resources with slide-over/modal actions. Adding to loose ends list.
