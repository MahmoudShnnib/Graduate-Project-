---
date: 2026-06-21
tags: [filament, students, applications, validation, documents, nationality, daily]
related:
  - "[[../bugs-and-fixes/2026-06-21-admission-documents-lost-on-approval]]"
  - "[[../bugs-and-fixes/2026-06-21-application-edit-study-type-gap]]"
---

# Student-create validation overhaul + admission-document persistence + study-type grid gap

Branch `feature/notification-logs-batching`. **Uncommitted.** Full suite **458 green** (+5).
Three tasks reported by the user against the live dev app (`127.0.0.1:8000`).

## Task 1 — empty gap under نوع الدراسة on application edit
**Real cause (from the user's screenshot):** the resource FORM rendered its section cards in a
**2-column grid** (no `->columns()` on the schema). `البيانات الشخصية` (tall) sat beside
`نوع الدراسة` (short) → the whole left column below the short card was empty = the gap. NOT the
delegation fields (my first guess was wrong; `self_funded` #24 has no field-level gap).

Fix:
- `ApplicationResource::form` → `$schema->columns(1)` so sections stack full-width (matches
  `StudentResource::form`). Eliminates the empty second column. Verified via rendered HTML
  (`--cols-lg` 2→1).
- Kept the separately-valid within-section fix: `study_type` `->live()` +
  `delegation_number`/`delegation_entity` `->columnSpan(2)` (both resources) so a lone visible
  delegation field fills its row. See [[../bugs-and-fixes/2026-06-21-application-edit-study-type-gap]].

## Task 2 — admission documents vanished after approval
Root cause: `applications` has `bachelor_certificate` + `remedial_statement`, but `students`
had **no such columns**, and `ApproveApplication` never copied them. The student view only
showed `degree_image` (post-grad). So on approval the files were orphaned on the application.
See [[../bugs-and-fixes/2026-06-21-admission-documents-lost-on-approval]].

Fix:
- Migration `add_admission_documents_to_students_table` — adds the two nullable string columns
  **and backfills** from each student's originating application (matched on `user_id`), so
  already-approved students (e.g. #16) recover their files. Same path string → shared file,
  no copy needed.
- `Student::$fillable` += both columns.
- `ApproveApplication` copies both app→student.
- `CreateStudent::handleRecordCreation` passes both from the form.
- `StudentResource::form` new **مستندات القبول** section (FileUpload ×2; bachelor required on
  create only, remedial optional; pdf/jpg/png ≤5 MB → `students/documents`).
- `StudentResource::infolist` new **مستندات القبول** section (TextEntry ×2 → `asset('storage/…')`,
  mirrors `ApplicationInfolist`). Renders on the view page via `{{ $this->infolist }}`.

## Task 3 — apply-grade validation on `/admin/students/create`
The admin form had almost no validation vs the public `/apply` form (the "good" reference,
`RegistrationController` + `config/nationalities.php`). Brought to parity:
- **Conditional national-ID** (mirrors apply): nationality `->live()`; `national_id` label,
  helperText, maxLength all switch on `nationality === config('nationalities.default')` (=`ليبيا`).
  Libyan → `الرقم الوطني`, exactly 12 digits (`^\d{12}$`). Non-Libyan → `رقم الهوية / جواز السفر`,
  `^[A-Za-z0-9]{4,20}$`. Closure rule + the existing `unique:students` kept.
- `user_phone` → Libyan format closure (digits, starts `09`, length 10) when filled.
- `mother_name` → ArabicOnly + MinArabicWords(2). `birth_place`/`residence`/`permanent_address`
  → ArabicOnly(allowNumbers). `profession`/`workplace` → nullable ArabicOnly. `birth_date` →
  min/max age 22–60. All with Arabic messages.

### Filament gotcha (important)
This project's Filament evaluates **array-element closures in `->rules([...])`** and uses the
RETURN value as the rule (see the existing email `unique` closure). So a Laravel inline rule
must be wrapped: `->rules([ fn (Get $get): \Closure => function ($attr,$val,$fail) use ($get) {…} ])`.
A bare `function($attr,$val,$fail){}` in the array would be mis-evaluated. Confirmed by passing tests.

## Tests
- `ApproveApplicationTest` +1 (docs copied app→student).
- `StudentCreateNationalIdTest` (new, 4): Libyan 12-digit accept/reject, non-Libyan passport
  accept/reject. Uses `assertHas(No)FormErrors(['national_id'])` to isolate the rule without
  needing to satisfy the file upload.

## Loose ends / open question
- **Task 1**: the user cited app **#24**, which is `self_funded` → structurally **no** gap there.
  My fix removes the gap on **delegated** applications (the real cause). If they still see a gap
  on a self_funded app, need a screenshot — couldn't reproduce structurally.
- Migration was run on the dev DB (backfill confirmed: student #16 cert restored). Uncommitted.
- Mobile out of scope — untouched.
