---
date: 2026-05-17
tags: [daily, settings, prints, filament, widgets, dashboard, plan, in-flight]
related:
  - "[[2026-05-17-faculty-supervisor-identity-fields]]"
  - "[[2026-05-17-seeders-and-modifyquery-fix]]"
  - "[[../decisions/2026-05-17-settings-dashboard-widget]]"
  - "[[../project-context/gotchas]]"
---

# 2026-05-17 evening — Print system overhaul (Step 1 of 10)

> **Pick-up note for next session.** Step 1 (settings widget) is done and browser-verified. The remaining 9 steps are the full plan — see "Remaining steps" at the bottom.

## Goal of this feature

User supplied 7 Word docs at `/media/ppsan/d_drive/Downloads/popo/the word docs for the graduation sysrem/` — these are the official Arabic ministry / faculty templates that should drive every printable surface in `/admin`. Today's "print" buttons either do bare `window.print()` (chrome leaks) or render thin DomPDF templates that don't match the ministry look. Goal: replace placeholders, add a multi-print picker where a surface has ≥ 2 printables, and make signature/college names editable from the dashboard.

## Word docs (inventory)

| # | Doc | Surface | Status as of today |
|---|---|---|---|
| 1 | `افادة تخرج عامة.docx` | `/admin/graduates/{id}` → `طباعة الافادة` | Already implemented as JS-window in `view-graduate.blade.php`; **needs diff against new Word doc** in Step 8. |
| 2 | `بيانات رسالة ماجستير2.docx` | `/admin/thesis-registrations/{id}` → `طباعة للوزارة` | Current `pdfs/thesis_form.blade.php` is a 28-line skeleton; **wire new ministry letter** in Step 7. |
| 3 | `تعريف طالب 2.docx` | `/admin/students/{id}` + `/admin/reports-page` | NEW — build in Step 4. |
| 4 | `كشف الحضور والغياب دراسي فصل الخريف 2025 2026.docx` | `/admin/course-offerings/{id}/enroll` + `/admin/my-courses/{id}` | NEW — build in Step 4. |
| 5 | `كشف درجات مؤقت.docx` | `/admin/students/{id}` | NEW — build in Step 4. **`المواد المتبقية` section dropped per user**; print shows only `المواد المنجزة`. |
| 6 | `نتيجة مقرر دراسي2025-2026.docx` | `/admin/course-offerings/{id}/enroll` + `/admin/my-courses/{id}` | NEW — build in Step 4. |
| 7 | `نموذج قائمة مقرر دراسى.doc` | (admin only on enroll page) | **Skipped per user.** Existing `prints/course-students.blade.php` stays untouched. |

## Locked design decisions (don't re-litigate)

| Decision | Value |
|---|---|
| Date format on prints | ISO `2026-05-17` |
| Signature / college defaults | Editable via `Setting` model + dashboard widget (Step 1, done) |
| Graduation certificate | Replace with new Word doc content in Step 8 |
| Faculty surface for course prints | Both `/admin/my-courses/{id}` AND `/admin/course-offerings/{id}/enroll` |
| Hero `طباعة` on `/admin/students/{id}` | Remove (replaced by header-actions picker) |
| Thesis ministry print | First click → stamp Printed + open letter; later clicks → reopen without re-stamping |
| Picker UX | When a surface has ≥ 2 prints, the single `طباعة` button opens a Filament Action modal with a `Radio` choice → submits → redirects to the chosen print's route in a new tab |
| `القسم` derivation on course prints | Lookup at print time from `course_specialization` pivot: all `general` → "عام"; one `specialized` → that spec name; multi-spec specialized → comma-separated; elective-only → "اختياري" |
| `المواد المتبقية` on provisional grades | **Dropped.** Print shows only `المواد المنجزة` (passed, letter ≠ F). |
| Registration semester | Derived from `Application::semester_id` (auto-captured by `ApplicationObserver` at approval); fall back to earliest enrollment's semester for legacy rows |
| Permissions | All prints reachable by super_admin + secretary. Faculty limited to حضور + نتيجة on their own offerings. |

## Step 1 — Settings widget (DONE today)

### Files added/touched

| File | Change |
|---|---|
| `database/migrations/2026_05_17_173828_create_settings_table.php` | NEW — `key` unique + `value` text nullable + timestamps |
| `app/Models/Setting.php` | NEW — `DEFAULTS` const (4 keys), `::get()`, `::set()`, `::allKeyed()` |
| `database/seeders/SettingsSeeder.php` | NEW — `updateOrCreate` over `DEFAULTS` |
| `database/seeders/DatabaseSeeder.php` | Added `SettingsSeeder::class` after `RolesAndPermissionsSeeder::class` |
| `app/Filament/Widgets/SettingsWidget.php` | NEW — top-of-dashboard small gray button, super_admin + secretary only, opens Filament Action modal with form |
| `resources/views/filament/widgets/settings-widget.blade.php` | NEW — explicit `wire:click="mountAction('editSettings')"` + `<x-filament-actions::modals />` slot |

### Decisions captured

- Settings live as a **dashboard widget**, not a separate nav page. User explicitly rejected the standalone-page approach (first attempt). See [[../decisions/2026-05-17-settings-dashboard-widget]].
- Key/value table (extensible) vs single-row table — key/value picked for ease of adding future settings.
- `Setting::get()` always returns something — DB row > explicit default arg > `DEFAULTS` const. Safe to call from print templates without null-guards.
- `phone` / `address` fields removed per user (sections were optional, no print template referenced them).

### Settings keys currently active

```
college_name   → كلية التقنية الإلكترونية – طرابلس
office_name    → مكتب الدراسات العليا
director_name  → د. بشير محمد الجللي
dean_name      → د. هشام الأمين الحجاجي
```

### Gotchas discovered (logged to gotchas.md)

- **Filament widget action modals need explicit `<x-filament-actions::modals />` in the widget Blade.** Without it, clicking the button mounts the action but the modal never appears. The page-level modal slot doesn't see widget actions.
- **`{{ $this->actionName }}` magic render is brittle on widgets.** Use explicit `<x-filament::button wire:click="mountAction('name')">` for reliability.

## Step 2 — `time_slot` on course offerings (TODO)

- Migration: add nullable `string` `time_slot` to `course_offerings`.
- Model `$fillable` updated.
- No UI yet (per user: "make it blank for now, ill later update the abilty to add the tinnming").
- Print templates (Step 4) read `$courseOffering->time_slot ?? ''`.

## Step 3 — Registration semester accessor + display on `/admin/students/{id}` (TODO)

- Add accessor `Student::getRegistrationSemesterAttribute(): ?Semester`:
  1. `$this->user->application?->semester` (preferred — set by `ApplicationObserver` at approval).
  2. Fallback: earliest enrolled semester via `enrollments()->oldest('id')->first()?->courseOffering?->semester`.
  3. Null if neither exists (very old legacy rows).
- Display on `/admin/students/{id}` in the existing "البيانات الشخصية" section as a new `TextEntry::make('registrationSemester.name')->label('الفصل الدراسي للالتحاق')` — placed near `birth_date`.
- Reused by Step 4's `student_letter.blade.php` + `thesis_ministry.blade.php`.

## Step 4 — Five print blade templates (TODO)

Create under `resources/views/prints/`:

| File | Purpose | Source Word doc |
|---|---|---|
| `exam_attendance.blade.php` | كشف حضور الإمتحان النهائي | Doc 4 |
| `course_results.blade.php` | نتيجة مقرر دراسي | Doc 6 |
| `student_provisional_grades.blade.php` | كشف درجات مؤقت — only منجزة section | Doc 5 (cut down) |
| `student_letter.blade.php` | تعريف طالب | Doc 3 |
| `thesis_ministry.blade.php` | بيانات رسالة ماجستير | Doc 2 |

Common shell (copy from existing `prints/course-students.blade.php`):
- Header block reads `Setting::get('college_name')` / `office_name`.
- Footer block reads `Setting::get('director_name')` / `dean_name` for signatures.
- Sticky `<button class="print-btn no-print" onclick="window.print()">طباعة</button>`.
- `@media print { .no-print { display: none } }`.
- `<script>window.addEventListener('load', () => window.print());</script>` for auto-trigger.
- RTL Arabic typography: `Arial`, `Tahoma` fallbacks.
- Date format: ISO `2026-05-17`.

## Step 5 — PrintController + 5 routes (TODO)

New `app/Http/Controllers/PrintController.php` with 5 methods:

```
examAttendance(CourseOffering $offering)        → view('prints.exam_attendance', ...)
courseResults(CourseOffering $offering)         → view('prints.course_results', ...)
provisionalGrades(Student $student)             → view('prints.student_provisional_grades', ...)
studentLetter(Student $student)                 → view('prints.student_letter', ...)
thesisMinistry(ThesisRegistration $thesis)      → view('prints.thesis_ministry', ...)
```

Routes in `routes/web.php`:

```php
Route::middleware('auth')->group(function () {
    Route::get('/admin/course-offerings/{courseOffering}/print-exam-attendance', [PrintController::class, 'examAttendance'])
        ->name('print.exam-attendance');
    Route::get('/admin/course-offerings/{courseOffering}/print-course-results', [PrintController::class, 'courseResults'])
        ->name('print.course-results');
    Route::get('/admin/students/{student}/print-provisional-grades', [PrintController::class, 'provisionalGrades'])
        ->name('print.provisional-grades');
    Route::get('/admin/students/{student}/print-student-letter', [PrintController::class, 'studentLetter'])
        ->name('print.student-letter');
    Route::get('/admin/thesis-registrations/{thesis}/print-ministry', [PrintController::class, 'thesisMinistry'])
        ->name('print.thesis-ministry');
});
```

Per-route policy (inside controller methods):
- Course routes: `super_admin || secretary || $offering->faculty_id === auth()->id()` else 403.
- Student + thesis routes: `super_admin || secretary` else 403.

## Step 6 — Picker actions on 4 surfaces (TODO)

The pattern:

```php
Action::make('print')
    ->label('طباعة')
    ->icon(Heroicon::OutlinedPrinter)
    ->modalHeading('اختر المستند المراد طباعته')
    ->form([
        Radio::make('document')
            ->label('المستند')
            ->options($this->availablePrintsFor(auth()->user()))
            ->required(),
    ])
    ->action(fn (array $data) => redirect()->to($this->printUrlFor($data['document'])))
    ->modalSubmitActionLabel('فتح المستند');
```

Surfaces:

| Surface | Options for super_admin/secretary | Options for faculty |
|---|---|---|
| `EnrollStudents` (replaces `طباعة كشف الطلاب` header action — keep that one too if user wants) | حضور + نتيجة | حضور + نتيجة |
| `ViewMyCourse` | حضور + نتيجة | حضور + نتيجة |
| `ViewStudent` (replaces hero `طباعة`) | كشف درجات مؤقت + تعريف طالب | n/a (faculty can't reach this page) |
| `ReportsPage` | Add a third action `تعريف طالب` next to existing transcript + clearance buttons | n/a |

## Step 7 — Thesis ministry print wire-up (TODO)

Update `printForMinistryAction` on:
- `app/Filament/Resources/ThesisRegistrations/Pages/ViewThesisRegistration.php`
- `app/Filament/Resources/ThesisRegistrations/ThesisRegistrationResource.php` (row action)

```php
->action(function (ThesisRegistration $record) {
    if ($record->status === ThesisStatus::Pending) {
        $record->update(['status' => ThesisStatus::Printed, 'printed_at' => now()]);
        Notification::make()->title('تم تسجيل الطباعة')->success()->send();
    }
    return redirect()->route('print.thesis-ministry', $record);
})
->label(fn (ThesisRegistration $record) => $record->status === ThesisStatus::Printed
    ? 'طباعة للوزارة (تمت في '.$record->printed_at->format('Y-m-d').')'
    : 'طباعة للوزارة')
```

Existing رفض / قبول visibility (`status === Printed`) stays unchanged.

## Step 8 — Update graduation certificate (TODO)

Diff `view-graduate.blade.php`'s JS-built HTML against `افادة تخرج عامة.docx` text content:

```
إفادة تخرج
تفيد كلية التقنية الإلكترونية - طرابلس بأن الطالب ####
رقم قيده: ######
أتم بنجاح جميع المتطلبات الدراسية المقررة لنيل درجة "الماجستير في الهندسة الالكترونية تخصص #####"
وذلك بتاريخ 00/00/2026 م
و بمعدل تراكمي (### من ###)
وقد اعطيت له هذه الافادة بناءً على طلبه لاستعمالها فيما يسمح به القانون
[d. Bashir Mohammed Gelili | Director of Graduated Student]
[d. Hisham Al-hijaji | Dean of College]
+ Official Grading system: A=4 B+=3.5 B=3 C+=2.5 C=2 D+=1.5 D=1 F=0
```

If wording / structure drifted, replace the JS-built HTML to match. Read `Setting::get('director_name')` / `dean_name` for signatures (not hardcoded).

## Step 9 — Remove hero `طباعة` from student view page (TODO)

`resources/views/filament/resources/students/pages/view-student.blade.php`:

Drop the `<button onclick="window.print()">طباعة</button>` block in `.fsv-hero__actions` + its `.fsv-btn--secondary` CSS + `@media print` block. Replaced by Step 6's picker (in header actions).

Keep the Edit button.

## Step 10 — Delete unused thesis-form PDF (TODO)

```
rm resources/views/pdfs/thesis_form.blade.php
```

Also drop `PdfService::generateThesisForm()` if it exists (no callers — the new ministry print replaces it).

## Verification plan (run at end of each step, then full end-to-end at Step 10)

1. `vendor/bin/pint --dirty --format agent` → clean.
2. `php -d memory_limit=1024M artisan test --compact` → 163 still green (no regressions).
3. After Step 6: manual browser walk.
   - Super_admin: `/admin/course-offerings/1/enroll` → `طباعة` → modal with 2 options → each opens correct print.
   - Super_admin: `/admin/students/4` → `طباعة` → modal with 2 options → each opens correct print.
   - Super_admin: `/admin/reports-page` → 3 buttons (transcript / clearance / تعريف طالب).
   - Faculty: `/admin/my-courses/1` → `طباعة` → modal with 2 options.
   - Faculty: hit `/admin/students/4/print-provisional-grades` directly → 403.
4. After Step 7: thesis row in Pending → click `طباعة للوزارة` → letter opens AND status flips. Click again → letter reopens, status unchanged.
5. After Step 8: open `/admin/graduates/1` → click `طباعة الافادة` → bilingual certificate matches Word doc exactly.

## Open questions for next session start

None blocking. All design decisions locked. Pick up at Step 2.

## Test credentials (still seeded)

| Role | Email | Password |
|---|---|---|
| super-admin | `admin@college.edu` | `admin123` |
| secretary | `secretary@college.edu` | `secretary123` |
| faculty | `cs.faculty@example.ly` | `12345678` |
| student | `mohammed.zawai@example.com` | `12345678` |

## Where the plan file lives

The original incremental-plan file the user asked to skip earlier in this session is still on disk at `~/.claude/plans/breezy-beaming-biscuit.md` — it's identical in substance to this daily note. Treat this daily note as the canonical pick-up doc.
