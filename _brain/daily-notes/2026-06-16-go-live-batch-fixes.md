---
date: 2026-06-16
tags: [go-live, filament, print, validation, survey, notifications, otp]
related: [[2026-06-16-sidebar-badge-refresh]]
---

# Go-live batch fixes (8 of 9 user items)

Worked through a batch of user-reported fixes, one task at a time, each with tests + pint.
Branch: `chore/go-live-high-medium-fixes`. **Nothing committed** — left for user to commit.
Full suite green: **380 passed**.

Task 3 (grades-workflow redesign) was initially deferred, then **completed** the same day once
the user clarified it — see [[2026-06-16-single-final-grade-publish]]. Single final-publish step;
two-phase coursework/midterm publish removed; `midterm_published_at` column dropped. Suite green
at **382 passed** after the change.

## What was done

1. **#8 Apply — English-name field rejects Arabic.** Added a real-time `englishGuard()` JS
   mirroring `arabicGuard()` on `name_en`; server-side regex already blocked Arabic.
   `resources/views/registration/create.blade.php`. Tests in `ApplicationNationalityValidationTest`.

2. **#4 Semester survey filter bug.** `specialization_filter` (`->live()`) shrank the
   `course_ids` CheckboxList options, so Filament re-validated selected courses against the
   smaller list → "المقررات المختار غير صالح". Fixed by unioning selected IDs into the options.
   Extracted `SemesterResource::surveyCourseOptions()` (testable). See [[2026-06-16-semester-survey-filter-validation]].

3. **#5 Apply OTP stays valid after a failed submit.** Backend already kept a 30-min DB grace
   (`WhatsAppOtpService::hasVerified`); only the frontend JS reset `verified=false` on redirect.
   `create()` now passes `$otpVerified` (from `hasVerified(old('phone'))`); the widget hydrates
   from it. `RegistrationController` + `create.blade.php`. Tests in `WhatsAppOtpVerificationTest`.

4. **#2 Users — role required + nationality predefined (project-wide).** `UserForm` role now
   `->required()`. Converted **all four** free-text nationality inputs to a `Select` sourced from
   `config('nationalities.list')` (matches `/apply`): `UserForm`, `StudentResource`,
   `ApplicationResource`, `ListThesisRegistrations` (external supervisor). Aligned the stored value
   set from the old adjective `'ليبية'` to the country noun `'ليبيا'` across factories
   (Application/User/Supervisor) and tests. New `UserFormTest`. See [[2026-06-16-nationality-value-set-alignment]].

5. **#9 Clearance print — one page.** Taller approval rows (9mm→13mm) + removed the 22mm
   `.notes-spacer` before ملاحظة. Verified single-page via mPDF page count (16mm overflowed to 2).
   `resources/views/prints/clearance_form.blade.php`.

6. **#6 Student survey — submit once, lock, warn.** `CourseSurveyPage`: `hasSubmittedSurvey()`
   guard (non-retake rows), once-only `submit()`, locked `toggleCourse()`, `confirmingSubmit`
   modal warning it's final. Blade renders a read-only "submitted" view once finalized.
   New `CourseSurveySubmitOnceTest`.

7. **#1 Notification logs — number column + status audit.** Audit conclusion: `WhatsAppChannel`
   is the only writer and already records `sent` only after a successful send (`failed` in catch) —
   status was already accurate; the bad rows the user saw predated the Jun-12/13 fix.
   Added `phone` column (migration), populated it from the channel, displayed it (`الرقم`) in
   `NotificationLogResource`. New `NotificationLogPhoneTest`. **Side note discovered:** creating a
   student via `studentWithRecord()` emits an incidental notification (LOG_COUNT=2) — worth a look.

8. **#7 Exam-list page — two-option print dropdown, filter-aware.** Replaced the single print
   button with an Alpine dropdown: «قائمة الامتحان» (all initially-approved applicants) and
   «نتائج الامتحان» (processed accepted/rejected with `exam_score` + outcome). Both respect the
   specialization tab (الكل = all specs, adds a التخصص column) and selected semester via query
   params. Generalized `print.exam-list` route (now query-param based) + new `print.exam-results`
   route/method/view (`prints/exam_results.blade.php`). New/rewritten `ExamListPrintTest`;
   updated `ProductionHardeningTest` caller.

## Loose ends / next steps
- **Task 3 (grades workflow)** — needs the user's definition before implementation.
- Investigate the incidental notification fired during `studentWithRecord()` test setup.
- User to review + commit (nothing staged).
