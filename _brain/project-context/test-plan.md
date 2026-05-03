---
date: 2026-05-02
tags: [context, test-plan, qa, full-system, manual-testing]
related: [[project-overview]], [[architecture]], [[gotchas]], [[design-conventions]], [[../decisions/2026-04-23-student-status-restructure]], [[../decisions/2026-04-27-warning-system-and-dismissal]], [[../decisions/2026-04-30-discussion-request-pipeline]], [[../decisions/2026-04-25-thesis-eligibility-rewrite]]
---

# Full System Test Plan — Architect Session (2026-05-02)

> Generated from a full read of `_brain/` + `CLAUDE.md`. **Do not execute** these tests in this session — review first, then run in a follow-up.

## How to use this plan

- **Tiers** — T1 critical path (run 100%), T2 restrictions & cross-feature (run 100%), T3 UI/edge/garbage (spot-check 30–50%).
- **Verification methods** — `Browser` (UI only), `Browser + DB` (open tinker / DB query after action), `Browser + Log` (`tail -f storage/logs/laravel.log` and confirm entry), `Browser + DB + Log` (all three).
- **Status values** — `untested` (default), `passed`, `failed`, `blocked`, `n/a`.
- **Notifications** — never let WhatsApp/Qonvo fire. Verify via `storage/logs/laravel.log` only. If `QONVO_API_TOKEN` is in `.env` locally, unset it before running.
- **Local URL** — `http://127.0.0.1:8000` (per project instructions).
- **Personas** referred to below assume Setup section ran first.

---

## Setup — prerequisites (Tier 1, run before any other tier)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SETUP-001 | 1 | setup | Run `php artisan migrate:fresh --seed`. | Exits 0; tables populated with admin, secretary, ppsan student, 3 named students, 3 dummy students, specializations, semester, courses. | Browser + DB | untested |
| SETUP-002 | 1 | setup | Confirm `QONVO_API_TOKEN` is unset in `.env`; run `php artisan config:clear`. | `config('services.qonvo.api_token')` is null in tinker. WhatsApp channel will be silently skipped. | Browser + DB | untested |
| SETUP-003 | 1 | setup | Login as `admin@college.edu` / `admin123`, create a faculty user via `/admin/users/create` with `role=faculty`, `is_active=true`. Note credentials. | User row created; `role` column matches; Spatie role `faculty` assigned (verify `users.role` and `model_has_roles`). | Browser + DB | untested |
| SETUP-004 | 1 | setup | Tinker: create one student per non-Active status (OnHold, Inactive, Transferred, Graduated, Dismissed). For Dismissed, also set `dismissed_at`, `dismissal_type`, `dismissal_course_id`. | 5 student rows added; `users.role='student'`, `is_active=true`; `Spatie student role` assigned. | Browser + DB | untested |
| SETUP-005 | 1 | setup | Confirm exactly one `Semester` has `is_active=true`. Note its `survey_open` flag and `add_drop_start/end`. | Singleton lookup returns one row. | Browser + DB | untested |
| SETUP-006 | 1 | setup | Prepare a small valid PDF (≤2 MB), a non-PDF file (e.g. `.png`), and an oversized file (>10 MB) for upload tests. | Files exist on disk for later test rows. | Browser | untested |
| SETUP-007 | 1 | setup | Verify `npm run build` ran and `public/build/manifest.json` exists; if missing, run `npm run build`. | Filament admin/student themes load (no Vite manifest error). | Browser | untested |
| SETUP-008 | 1 | setup | Confirm Roles/Permissions seeder ran: `super_admin` ≈178 perms, `secretary` ≈58, `faculty` ≈8, `student`/`panel_user` 0. | Tinker: `Role::with('permissions')->get()->mapWithKeys(...)`. | DB | untested |
| SETUP-009 | 1 | setup | Truncate `storage/logs/laravel.log` (or note current line count). | File empty / baseline known. | Browser | untested |
| SETUP-010 | 1 | setup | Open at least one CourseOffering for the active semester, attach faculty (the user from SETUP-003), `is_open=true`. Confirm course is linked to ≥1 specialization (general/specialized/elective). | CO exists, visible at `/admin/course-offerings`. | Browser + DB | untested |

---

## 1. Authentication & Panel Access

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| AUTH-001 | 1 | happy path | Login as super_admin → land on `/admin`. | Filament admin loaded; all nav groups visible. | Browser | untested |
| AUTH-002 | 1 | happy path | Login as secretary → land on `/admin`. | Admin loads; Users resource hidden or limited per shield perms. | Browser | untested |
| AUTH-003 | 1 | happy path | Login as faculty → land on `/admin`. | Sees only "موادي" (MyCourse) + Grades; no Students/Applications navigation. | Browser | untested |
| AUTH-004 | 1 | happy path | Login as Active student via `/portal/login` → land on `/portal/student-dashboard`. | Dashboard renders in RTL Arabic. | Browser | untested |
| AUTH-005 | 2 | bad judgment | Visit `/admin` while logged in as student. | Redirects to `/portal/login` (or 403 → `/portal/login` per `bootstrap/app.php` handler). | Browser | untested |
| AUTH-006 | 2 | bad judgment | Visit `/portal` while logged in as super_admin. | Blocked by `EnsureStudentRole` middleware → redirect or 403. | Browser | untested |
| AUTH-007 | 2 | status restriction | Set student `is_active=false` → try `/portal/login`. | Login fails or `canAccessPanel()` returns false. | Browser + DB | untested |
| AUTH-008 | 2 | edge | Login form: empty email, empty password. | Arabic validation messages shown. | Browser | untested |
| AUTH-009 | 2 | edge | Login as student whose `users.role='student'` but no Spatie role. | Either logs in (column-only check) or denied (Spatie-only). Document actual behavior — known hybrid hazard from the bug note. | Browser + DB | untested |
| AUTH-010 | 3 | RTL | Confirm login labels, button "تسجيل الدخول", placeholders are Arabic; layout RTL. | Arabic text, no English leak. | Browser | untested |
| AUTH-011 | 2 | session | Log in, leave tab idle past session lifetime, click Save on a form. | Redirected to login; flash message preserved or graceful fallback. | Browser | untested |
| AUTH-012 | 2 | session | Login as student in tab A; admin sets student `is_active=false` in tab B; refresh tab A. | Student kicked out / redirected to login. | Browser + DB | untested |

---

## 2. Public Application Form (`/apply`)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| APPLY-001 | 1 | happy path | Fill all required fields with valid data + valid PDF cert + tick declaration → submit. | Application row created; tracking code shown; redirects to confirmation. | Browser + DB | untested |
| APPLY-002 | 1 | happy path | After APPLY-001, check `applications` table: `status=pending`, `tracking_code` matches `APP-{YEAR}-NNNN` format, `is_transfer=false` (since the radio was removed per T3 2026-04-21). | Row matches expected shape; password is hashed. | DB | untested |
| APPLY-003 | 1 | happy path | After APPLY-001, `notifyApplicationReceived` fires. | `storage/logs/laravel.log` contains a notification log entry; `notification_logs` table has a row. | Log + DB | untested |
| APPLY-004 | 1 | bad judgment | Submit with empty form. | All required fields show Arabic validation errors; nothing persisted. | Browser + DB | untested |
| APPLY-005 | 2 | bad judgment | English text in "الاسم بالعربي". | Custom rule `ArabicOnly` rejects with Arabic message. | Browser | untested |
| APPLY-006 | 2 | bad judgment | Arabic name with only 3 words. | Rejected by `MinArabicWords(4)` rule with Arabic message. | Browser | untested |
| APPLY-007 | 2 | bad judgment | Phone `1234567890` (not 09…). | Rejected — Arabic msg about Libyan format. | Browser | untested |
| APPLY-008 | 2 | bad judgment | Phone `0987654321` (10 digits, wrong prefix). | Rejected. | Browser | untested |
| APPLY-009 | 2 | bad judgment | National ID 11 digits. | Rejected (12-digit rule). | Browser | untested |
| APPLY-010 | 2 | edge | Birthdate giving age = 21. | Rejected (min age 22). | Browser | untested |
| APPLY-011 | 2 | edge | Birthdate giving age = 61. | Rejected (max age 60). | Browser | untested |
| APPLY-012 | 2 | edge | Birthdate giving age exactly 22. | Accepted. | Browser | untested |
| APPLY-013 | 2 | edge | Birthdate giving age exactly 60. | Accepted. | Browser | untested |
| APPLY-014 | 2 | file upload | Upload non-PDF (e.g. `.png`) for bachelor cert. | Rejected with "يجب أن يكون الملف من نوع PDF" (or equivalent Arabic). | Browser | untested |
| APPLY-015 | 2 | file upload | Upload oversized file (>10MB). | Rejected. | Browser | untested |
| APPLY-016 | 2 | file upload | Submit with no file when required. | Rejected. | Browser | untested |
| APPLY-017 | 3 | file upload | Upload PDF with Arabic filename `شهادة.pdf`. | Accepted; stored path readable. | Browser + DB | untested |
| APPLY-018 | 2 | bad judgment | Don't tick declaration checkbox. | Rejected. | Browser | untested |
| APPLY-019 | 2 | bad judgment | Submit a second application while a prior one is still `pending` or `initially_approved` for the same email/national ID. | Blocked — only 1 active application allowed at a time. Resubmit only allowed AFTER admin rejects the prior one. | Browser + DB | untested |
| APPLY-019b | 2 | bad judgment | Submit a second application after a prior one was `accepted` (student exists in system). | Blocked permanently — accepted application = enrolled student; can't reapply. | Browser + DB | untested |
| APPLY-019c | 2 | bad judgment | Submit a second application after the prior one was `rejected`. | Allowed. New application created; prior preserved as historical. | Browser + DB | untested |
| APPLY-020 | 2 | livewire | Spam-click submit button rapidly. | Only one row created. | Browser + DB | untested |
| APPLY-021 | 3 | RTL | Form layout RTL; labels right-aligned; placeholder Arabic; no LTR digits leaking from English. | Visually correct. | Browser | untested |
| APPLY-022 | 3 | UI | Click "توليد بيانات تجريبية" button → form fills with sample data. | All non-file fields populated; checkbox auto-ticked; conditional fields re-evaluate. | Browser | untested |
| APPLY-023 | 3 | UI | After sample-data fill, file inputs remain empty. | File input untouched; helper text reminds user. | Browser | untested |
| APPLY-024 | 3 | rate limit | Hit `/apply/sample` 25 times in <1 minute. | After 20 calls → 429. | Browser | untested |
| APPLY-025 | 2 | edge | Specialization dropdown loads all specializations. | List in Arabic, ≥1 option. | Browser | untested |
| APPLY-026 | 2 | bad judgment | Submit with `delegation_*` fields when not delegated, or omit them when nationality says delegated. | Conditional validation per delegation flag. | Browser | untested |

---

## 3. Application Tracking (`/apply/track`)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| TRACK-001 | 1 | happy path | Use tracking code + national ID from APPLY-001. | Status page shown with current Arabic status. | Browser | untested |
| TRACK-002 | 2 | bad judgment | Wrong national ID. | Friendly Arabic "not found" or generic error; no info leak. | Browser | untested |
| TRACK-003 | 2 | bad judgment | Wrong tracking code. | Same friendly response. | Browser | untested |
| TRACK-004 | 2 | rate limit | Hammer `/apply/track` 6+ times in 1 min from same IP. | Rate limit kicks in (note: P0-3 says no rate limit yet — record actual behavior). | Browser | untested |
| TRACK-005 | 3 | RTL | Layout + status labels Arabic. | Visually correct. | Browser | untested |
| TRACK-006 | 3 | edge | Track an application that has been accepted. | Status shown as "مقبول" or equivalent. | Browser | untested |

---

## 4. Admin Application Review & Approval

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| ADM-APP-001 | 1 | happy path | Open `/admin/applications` list. | 3-tier sort: pending top, initially_approved middle, accepted/rejected bottom; created_at desc within tier. | Browser | untested |
| ADM-APP-002 | 1 | happy path | Open one pending application detail. | Infolist shows all applicant data; Arabic labels everywhere. | Browser | untested |
| ADM-APP-003 | 1 | happy path | Set exam result + approve action. | Status flips to `accepted`; `User` row created; `Student` row created with `enrollment_no` matching `{YY}{specCode}{sessionFlag}{seq}`; welcome notification logged. | Browser + DB + Log | untested |
| ADM-APP-004 | 1 | happy path | Verify `users.role='student'`, Spatie role `student` assigned, `is_active=true`. | Both role mechanisms in sync (per Shield bug fix). | DB | untested |
| ADM-APP-005 | 2 | edge | Approve two applications in same specialization back-to-back. | Enrollment numbers are sequential, no collision. | DB | untested |
| ADM-APP-006 | 2 | bad judgment | Reject an application. | Status `rejected`; notification logged; no Student/User created. | Browser + DB + Log | untested |
| ADM-APP-007 | 2 | edge | Approve an `is_transfer=true` application. | `enrollment_no` uses transfer session flag; student's `is_transfer=true`. | DB | untested |
| ADM-APP-008 | 2 | bad judgment | Click approve twice rapidly. | Only one User+Student created; idempotent. | DB | untested |
| ADM-APP-009 | 2 | data | Welcome notification body contains enrollment_no + login credentials placeholder. | Log entry confirms shape. | Log | untested |
| ADM-APP-010 | 3 | RTL | All Arabic labels rendered; no English leakage. | Visually correct. | Browser | untested |
| ADM-APP-011 | 2 | filter | Filter by status (pending/accepted/rejected) on list. | Filter narrows rows; tier sort still applied within filter. | Browser | untested |
| ADM-APP-012 | 3 | UI | Click sortable column header. | Tier sort survives (per `modifyQueryUsing + reorder` pattern). | Browser | untested |

---

## 5. Enrollment Number Generation

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| ENRNO-001 | 1 | happy path | After approval, `enrollment_no` follows `{YY}{specCode}{sessionFlag}{seq}` format. | Format matches; `seq` increments per prefix. | DB | untested |
| ENRNO-002 | 1 | edge | Approve another applicant in same year/spec/session — sequence increments. | `seq` = max+1. | DB | untested |
| ENRNO-003 | 2 | edge | Soft-delete a student and approve a new applicant in same prefix. | New `seq` skips the soft-deleted number (uses `withTrashed`). | DB | untested |
| ENRNO-004 | 3 | edge | First applicant of a brand-new specialization. | `seq=001`. | DB | untested |
| ENRNO-005 | 2 | edge | Transfer applicant approved → session flag differs from regular. | Prefix differs from regular session. | DB | untested |

---

## 6. Semester Setup & Singletons

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SEM-001 | 1 | happy path | Create a new Semester via `/admin/semesters`. | Row created; not active by default. | Browser + DB | untested |
| SEM-002 | 1 | happy path | Toggle `is_active=true` on the new semester. | Old active semester remains active too — note: no DB constraint preventing two active. Document actual behavior. | Browser + DB | untested |
| SEM-003 | 1 | happy path | Attach courses to semester via the `semester_course` pivot (survey menu). | Courses now appear in student survey. | Browser + DB | untested |
| SEM-004 | 2 | bad judgment | Set `survey_open=false` mid-semester. | Student survey page locks; banner shows. | Browser | untested |
| SEM-005 | 2 | edge | `add_drop_start` in future, `add_drop_end` in past. | Add/drop window is closed (no overlap); student page shows closed banner. | Browser | untested |
| SEM-006 | 2 | edge | Set both `add_drop_start` and `add_drop_end` such that today is between. | Add/drop window open; chip shows on dashboard. | Browser | untested |
| SEM-007 | 1 | observer | Activate an inactive semester while a student has `status=Inactive`. | `SemesterObserver` fires; if student was already Inactive last semester, second consecutive warning is created → may dismiss. | Browser + DB | untested |
| SEM-008 | 3 | RTL | Semester selector Arabic labels (e.g. "خريف 2025"). | No English leakage. | Browser | untested |

---

## 7. Course Offerings

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| CO-001 | 1 | happy path | Create a CourseOffering with course + active semester + faculty + `is_open=true` + 40/60 distribution. | Row created; visible at `/admin/course-offerings`. | Browser + DB | untested |
| CO-002 | 2 | bad judgment | Try to create a duplicate `(course, semester)` offering. | Friendly Arabic error "يوجد مقرر مفتوح بالفعل لهذا الفصل الدراسي" (per Fix 6 in 2026-04-28). | Browser | untested |
| CO-003 | 1 | happy path | Toggle `is_open=false` on offering with active enrollments. | `CourseOfferingObserver` cascades all active enrollments to `Withdrawn`; per-student GPA recalculated. | Browser + DB | untested |
| CO-004 | 1 | observer guard | Try to enroll a student into a closed offering. | `CourseOfferingClosedException` caught; Arabic notification "المقرر مغلق". | Browser | untested |
| CO-005 | 2 | edge | Reopen the offering after CO-003. | Withdrawn students stay withdrawn — admin must re-enroll explicitly. | Browser + DB | untested |
| CO-006 | 2 | observer | Save offering with `is_open=true` again (no-op). | Observer is idempotent — no extra cascades or notifications. | DB | untested |
| CO-007 | 2 | filter | Toggle "open_only" filter off on `/admin/course-offerings`. | Closed offerings appear. | Browser | untested |
| CO-008 | 2 | UI | Close action shows confirmation modal "سيتم إلغاء تسجيل جميع الطلاب…". | Modal renders; confirm proceeds. | Browser | untested |
| CO-009 | 2 | bad judgment | Set `coursework_max+final_max ≠ 100` (e.g. 50/40). | Validation rejects with Arabic error — the sum MUST equal 100. | Browser | untested |
| CO-009b | 2 | edge | Set 30/70, 40/60, 20/80 (each summing to 100). | Each accepted; total grade calc respects the per-offering distribution. | Browser + DB | untested |
| CO-009c | 2 | edge | Set 0/100 or 100/0. | Boundary — accepted (sum=100). | Browser | untested |
| CO-010 | 1 | print | Click "طباعة قائمة الطلاب" on an offering with enrolled students. | Printable RTL roster opens; students sorted by spec → name; columns Arabic. | Browser | untested |
| CO-011 | 3 | RTL | Print preview header has college name + course name + faculty name. | Layout correct. | Browser | untested |
| CO-012 | 2 | edge | Print a roster on an offering with zero enrollments. | Empty state displayed; print still renders. | Browser | untested |

---

## 8. Course Survey — Student Side (`/portal/course-survey-page`)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SUR-S-001 | 1 | happy path | Active student opens survey while `survey_open=true`. | Available courses = student's specialization ∩ semester_course pivot. | Browser + DB | untested |
| SUR-S-002 | 1 | happy path | Select 5 courses (no upper cap; cap was removed 2026-04-20) and submit. | 5 `CourseSurvey` rows created with priorities 1..5. | Browser + DB | untested |
| SUR-S-003 | 1 | happy path | Submit with 0 courses. | Validation rejects — at least 1 required. | Browser | untested |
| SUR-S-004 | 2 | bad judgment | Try to submit while `survey_open=false`. | Form is locked; submit blocked. | Browser | untested |
| SUR-S-005 | 2 | bad judgment | Resubmit (already has surveys this semester). | Behavior per the page logic — typically "already submitted" or replace. Document. | Browser + DB | untested |
| SUR-S-006 | 2 | livewire | Spam toggle a checkbox. | No duplicate rows; UI keeps in sync. | Browser + DB | untested |
| SUR-S-007 | 2 | livewire | Open in two tabs, submit from both. | Only one canonical set persists; second submit is harmless. | Browser + DB | untested |
| SUR-S-008 | 1 | retake | Student passed course X with grade < A; "طلب إعادة مادة" section shows X. | X listed; modal shows course details. | Browser | untested |
| SUR-S-009 | 1 | retake | Submit retake for course X. | `CourseSurvey` row with `is_retake=true` created. | DB | untested |
| SUR-S-010 | 2 | retake | Course passed with A — should NOT appear in retake section. | Filter excludes A grades. | Browser | untested |
| SUR-S-011 | 2 | retake | Currently enrolled in course X — should NOT appear in retake section. | Filter excludes active enrollments. | Browser | untested |
| SUR-S-012 | 1 | status restriction | OnHold student tries to submit survey. | `canPerformActions` blocks; status banner renders; Arabic notification on submit attempt. | Browser | untested |
| SUR-S-013 | 1 | status restriction | Inactive student tries to submit survey. | Blocked by status banner. | Browser | untested |
| SUR-S-014 | 1 | status restriction | Transferred / Graduated / Dismissed students. | All blocked; banner specific to each status. | Browser | untested |
| SUR-S-015 | 3 | RTL | Course tiles RTL; priority numbers shown. | Visually correct. | Browser | untested |

---

## 9. Course Survey — Admin Review (`/admin/courses-survey-results` + `/admin/student-survey/{s}/{sem}`)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SUR-A-001 | 1 | happy path | Open merged page; demand table shows per-course count + per-priority breakdown (1..4 columns). | Counts match `CourseSurvey` rows. | Browser + DB | untested |
| SUR-A-002 | 1 | UI | Toggle "إخفاء الجدول" / "إظهار الجدول" — persists across reload (Session attribute). | Toggle state persists. | Browser | untested |
| SUR-A-003 | 1 | UI | Select courses via row checkboxes; "فتح المقررات المختارة" button enabled (`.live`). | Button enables only when ≥1 checked. | Browser | untested |
| SUR-A-004 | 1 | happy path | Use modal to bulk-create offerings, assign faculty, `is_open=true`. | Multiple `CourseOffering` rows created. | DB | untested |
| SUR-A-005 | 1 | happy path | Click student row → `/admin/student-survey/{s}/{sem}` drill-in. | Page renders; pending courses shown. | Browser | untested |
| SUR-A-006 | 1 | happy path | Click "قبول" on a course row → `EnrollStudentInOffering` runs. | `StudentEnrollment` upserted with `Enrolled`; matching `CourseSurvey.is_processed=true`. | DB | untested |
| SUR-A-007 | 1 | reject | Click "رفض" on a pending row. | `is_processed=true` AND `rejected_at` set; row shows "مرفوض" badge. | Browser + DB | untested |
| SUR-A-008 | 2 | bulk reject | Select 3 rows, click "رفض المحددة"; one row is already enrolled. | 2 rejected, 1 skipped with warning. | Browser + DB | untested |
| SUR-A-009 | 2 | guard | Try to reject a course the student is actively enrolled in. | Blocked — admin must un-enroll first. | Browser | untested |
| SUR-A-010 | 2 | retake | Retake requests appear in separate "طلبات إعادة مواد" section. | Section visible; "إعادة" badges shown. | Browser | untested |
| SUR-A-011 | 2 | retake | Approve a retake → student is re-enrolled; cap rules apply. | New `StudentEnrollment` row; cap counter applies. | DB | untested |
| SUR-A-012 | 3 | data consistency | Pending count badge on courses list ≠ count in modal — should match. | Both use `is_processed=false` filter. | Browser | untested |

---

## 10. Manual Enrollment (`/admin/course-offerings/{id}/enroll`)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| ENR-001 | 1 | happy path | Open page on an open offering. | "إضافة طالب" + "طلبوا المادة" actions visible. | Browser | untested |
| ENR-002 | 1 | happy path | Search Active student → enroll. | Student enrolled; matching survey row processed. | Browser + DB | untested |
| ENR-003 | 2 | guard | Try to enroll a non-Active student via search. | Student doesn't appear (UI filter); even if forced via tinker, action throws `StudentNotActiveException`. | Browser + DB | untested |
| ENR-004 | 1 | guard | Try to enroll into an offering whose course is outside the student's specialization. | `CourseOutsideSpecializationException` → Arabic notification. | Browser | untested |
| ENR-005 | 1 | guard | Already enrolled in 4 active enrollments this semester → try a 5th. | `EnrollmentCapReachedException`. | Browser | untested |
| ENR-006 | 1 | guard | Try to enroll into a closed offering (UI buttons should be hidden too). | Buttons hidden; if forced → `CourseOfferingClosedException`. | Browser | untested |
| ENR-007 | 1 | guard | Approved suspension exists for the active semester (or status=OnHold). | Enrollment refused. | Browser | untested |
| ENR-008 | 1 | bulk | "طلبوا المادة" → select multiple students → enroll. | Mixed results: success/skip/cap-hit per student summarized. | Browser + DB | untested |
| ENR-009 | 2 | UI filter | Already-enrolled students hidden from "إضافة طالب" + "طلبوا المادة" pickers. | Lists exclude active enrollees in this offering. | Browser | untested |
| ENR-010 | 2 | idempotency | Click "enroll student X" twice quickly. | Idempotent — only one active enrollment, no exception thrown. | Browser + DB | untested |
| ENR-011 | 1 | unenroll | Drop a student from the offering. | `StudentEnrollment.status=Dropped`; survey row remains processed. | DB | untested |
| ENR-012 | 2 | guard ordering | Closed + spec mismatch + cap reached simultaneously. | First exception thrown is `CourseOfferingClosedException` (cheapest). | Browser | untested |

---

## 11. Add/Drop — Student Side (`/portal/add-drop-page`)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| AD-S-001 | 1 | happy path | Active student inside add/drop window: select 1 drop + 1 add → submit. | 2 `AddDropRequest` rows created with `status=pending`. | DB | untested |
| AD-S-002 | 1 | happy path | After submit, page shows "طلباتك لهذا الفصل" section listing the requests with badges. | Section visible. | Browser | untested |
| AD-S-003 | 2 | edge | Try to submit 4 drops. | Only 3 allowed; validation blocks. | Browser | untested |
| AD-S-004 | 2 | edge | Try to submit 4 adds. | Only 3 allowed. | Browser | untested |
| AD-S-005 | 2 | edge | Submit exactly 3 drops + 3 adds. | Boundary accepted. | DB | untested |
| AD-S-006 | 2 | bad judgment | Submit nothing. | Validation: at least one. | Browser | untested |
| AD-S-007 | 2 | bad judgment | Submit while window is closed. | Form locked; banner. | Browser | untested |
| AD-S-008 | 2 | livewire | Spam-click submit. | One submission only. | DB | untested |
| AD-S-009 | 2 | livewire | Re-toggle a checkbox repeatedly. | UI stays in sync. | Browser | untested |
| AD-S-010 | 1 | display | After admin approves a drop, that row leaves the active list and "تم القبول" appears in submissions section. | Both UI states correct. | Browser | untested |
| AD-S-011 | 1 | display | After admin rejects an add, "تم الرفض" appears in submissions section. | UI shows red badge. | Browser | untested |
| AD-S-012 | 1 | status restriction | Each non-Active status blocks AddDrop submit (5 datasets). | Status banner replaces form. | Browser | untested |
| AD-S-013 | 2 | guard | Approved suspension for active semester → can't submit. | Blocked with banner. | Browser | untested |
| AD-S-014 | 2 | edge | Pending drop + new request to drop the same enrollment. | UI prevents duplicate; page shows pending state on the row. | Browser | untested |
| AD-S-015 | 3 | RTL | All labels Arabic; drop=red drop badge; add=primary badge. | Visually correct. | Browser | untested |
| AD-S-016 | 2 | bad judgment | Try to drop an enrollment that isn't yours via Livewire payload tampering. | Server-side ownership check blocks. | Browser + Log | untested |
| AD-S-017 | 2 | edge | Submit then refresh during pending response. | No duplicate; one row exists. | Browser + DB | untested |
| AD-S-018 | 2 | UI | Two browser tabs open; submit from tab A, refresh tab B. | Tab B shows pending state for tab A's submissions. | Browser | untested |

---

## 12. Add/Drop — Admin Approval

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| AD-A-001 | 1 | happy path | List page `/admin/add-drop-requests` shows one row per student with pending count. | Grouped index renders. | Browser | untested |
| AD-A-002 | 1 | UI | Status column shows "مكتمل" when pending=0, else "قيد المراجعة". | Computed; no stored flag. | Browser | untested |
| AD-A-003 | 1 | drill-in | Click student row → `/admin/student-add-drop/{s}/{sem}`. | Page renders with progress banner + table. | Browser | untested |
| AD-A-004 | 1 | happy path | Approve a drop request. | `StudentEnrollment.status=Dropped`; `AddDropRequest.status=approved`. | DB | untested |
| AD-A-005 | 1 | happy path | Approve an add request. | `StudentEnrollment` upserted with `Enrolled`. | DB | untested |
| AD-A-006 | 1 | guard | Approve add when student is at cap, suspended, status non-Active, or course closed. | Each rejected with the matching typed exception → Arabic notification. | Browser + Log | untested |
| AD-A-007 | 1 | reject | Reject a request. | `status=rejected`; student notified; log entry. | Browser + Log | untested |
| AD-A-008 | 2 | edge | Approve all 3 drops on an offering reducing student to 1 enrollment. | All cascade properly; GPA recalculated. | DB | untested |

---

## 13. Suspension Request — Student Side

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SUSP-S-001 | 1 | happy path | Active student submits suspension for a future semester with reason text + PDF. | `SuspensionRequest` row created with `status=pending`. | DB | untested |
| SUSP-S-002 | 1 | happy path | Notification logged for admin. | Log entry. | Log | untested |
| SUSP-S-003 | 2 | bad judgment | Submit empty form. | Arabic validation. | Browser | untested |
| SUSP-S-004 | 2 | guard | Pending request exists for any semester → submit blocked. | "يوجد طلب قيد المراجعة". | Browser | untested |
| SUSP-S-005 | 1 | guard | Approved request exists for selected semester → dropdown option disabled with "(تمت الموافقة مسبقاً)" suffix; banner shows. | UI gate active; submit disabled. | Browser | untested |
| SUSP-S-006 | 1 | edge | Approved for semester A → submit for semester B. | Allowed. | DB | untested |
| SUSP-S-007 | 1 | edge | Rejected previously → re-submit allowed for same semester. | Accepted. | DB | untested |
| SUSP-S-008 | 2 | livewire | `wire:model.live` on dropdown; banner updates immediately. | No round-trip lag. | Browser | untested |
| SUSP-S-009 | 1 | status restriction | Each non-Active status blocks suspension submit. | Status banner. | Browser | untested |
| SUSP-S-010 | 2 | file upload | Upload non-PDF as suspension form. | Rejected. | Browser | untested |
| SUSP-S-011 | 2 | file upload | Upload oversized file. | Rejected. | Browser | untested |
| SUSP-S-012 | 3 | RTL | Arabic labels + danger banner styling. | Visually correct. | Browser | untested |

---

## 14. Suspension Request — Admin Decision

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SUSP-A-001 | 1 | happy path | Open `/admin/suspension-requests`, view a pending request. | Detail visible; PDF link. | Browser | untested |
| SUSP-A-002 | 1 | approve | Click approve. | `Student.status` flips to `OnHold` (per 2026-04-23 restructure); `StudentObserver` cascades all active enrollments to `Withdrawn`; suspension row `status=approved`. | Browser + DB | untested |
| SUSP-A-003 | 1 | observer | After approve, GPA frozen — `GradeCalculationService` skips non-Active students. | GPA unchanged from pre-approval. | DB | untested |
| SUSP-A-004 | 1 | defense | `Student::isSuspendedInSemester(activeSemester)` returns true post-approve (defense in depth retained). | Helper returns true. | DB | untested |
| SUSP-A-005 | 1 | reject | Click reject with notes. | `status=rejected`; student notified; no status flip. | Browser + Log | untested |
| SUSP-A-006 | 2 | cross-writer | After approve, admin edits student, sets `status=Active` via change-status header action. | `EditStudent::rescindActiveSemesterSuspensionIfOverridden` flips approved suspension to `Rejected` with admin note; student becomes Active. | Browser + DB | untested |
| SUSP-A-007 | 2 | cross-writer | After approve, admin sets `status=Withdrawn` instead. | Suspension stays Approved (not rescinded). | DB | untested |
| SUSP-A-008 | 2 | notification | Notification body has correct Arabic decision text per `notifySuspensionDecision`. | Log content matches. | Log | untested |

---

## 15. Grade Entry (Faculty)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| GRADE-F-001 | 1 | happy path | Faculty logs in → sees "موادي" with their offerings only. | List scoped to `faculty_id=auth()->id()`. | Browser + DB | untested |
| GRADE-F-002 | 1 | guard | Faculty A visits faculty B's offering URL directly (`/admin/my-courses/{id}` for an offering whose `faculty_id != A->id`). | 403 — a professor must not be able to view/grade a course they don't teach. If currently allowed (P0-2 unfixed), this is a FAILED test, not a deferred one. | Browser | untested |
| GRADE-F-002b | 1 | guard | Faculty A visits Grade entry on faculty B's offering. | 403; cannot enter or modify grades. | Browser | untested |
| GRADE-F-002c | 2 | guard | Faculty A bulk-approves grades on faculty B's offering via tinker / forced action. | Action refused (server-side faculty-ownership check). | Browser + DB | untested |
| GRADE-F-003 | 1 | happy path | Enter coursework + final for a student → save. | `Grade` row created; `total/letter/points` auto-computed; `status=Draft` or `PendingApproval` (per UI flow). | DB | untested |
| GRADE-F-004 | 2 | edge | Coursework > `coursework_max`. | Validation rejects. | Browser | untested |
| GRADE-F-005 | 2 | edge | Final > `final_max`. | Validation rejects. | Browser | untested |
| GRADE-F-006 | 2 | edge | Negative grade. | Rejected. | Browser | untested |
| GRADE-F-007 | 1 | conversion | Total exactly 85 → letter A. | Auto. | DB | untested |
| GRADE-F-008 | 1 | conversion | Total 84 → letter B+ (per 75 threshold). | Auto. | DB | untested |
| GRADE-F-009 | 1 | conversion | Total 49 → letter F. | Auto. | DB | untested |
| GRADE-F-010 | 2 | edge | Save with one component missing. | Total/letter/points NOT auto-computed (only fires when both present). | DB | untested |

---

## 16. Grade Approval & GPA

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| GRADE-A-001 | 1 | happy path | Admin approves a grade. | `status=Approved`; `approved_by`, `approved_at` set; GPA recalculated. | DB + Log | untested |
| GRADE-A-002 | 1 | observer | Only ONE GPA recalc happens per approval (per the bug fix). | Tinker: spy/log shows 1 call. | DB | untested |
| GRADE-A-003 | 1 | audit | Single audit row per approval (not 2 — bug-fix applied). | `audit_logs` count check. | DB | untested |
| GRADE-A-004 | 1 | bulk | Bulk approve N grades. | All approved; per-student GPA recalculated; N audit rows max (not 2N). | DB | untested |
| GRADE-A-005 | 2 | observer | Approve grade for non-Active student. | GPA NOT recalculated (service skips). | DB | untested |
| GRADE-A-006 | 1 | warning trigger | Approve a grade with letter F. | `WarningService::evaluateGradeApproval` creates a `course_failure` warning for that student/course. | DB | untested |
| GRADE-A-007 | 1 | warning resolve | Student passes the same course later → previous failure warning resolved. | Warning row `is_active=false`, `resolved_at` set. | DB | untested |
| GRADE-A-008 | 1 | dismissal | Student fails the same course twice (F → F approval). | Student dismissed; `dismissed_at`, `dismissal_type='course_failure'`, `dismissal_course_id` set. | DB + Log | untested |
| GRADE-A-009 | 2 | retake GPA | Multiple grades for same course (retake). | GPA uses LATEST grade only (per 2026-04-25 retake decision). | DB | untested |
| GRADE-A-010 | 1 | notification | Grade approval triggers `notifyGradePublished`. | Log entry. | Log | untested |

---

## 17. Transfer Courses (مواد المعادلة)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| TC-001 | 1 | happy path | Admin opens `/admin/students/X/edit`; "معادلة مواد" slide-over shows existing transfer courses. | Existing rows pre-filled (per `mutateFormDataBeforeFill`). | Browser | untested |
| TC-002 | 1 | happy path | Add a transfer course with both grades, save the form. | `TransferCourse` row created; auto-computed total/letter/points; GPA recalculated. | DB | untested |
| TC-003 | 1 | edit | Modify an existing transfer course's grade. | Old row deleted, new row created (delete+recreate strategy); GPA updated. | DB | untested |
| TC-004 | 1 | toggle off | Set `is_transfer=false` and save. | All transfer course rows deleted. | DB | untested |
| TC-005 | 2 | filter | Specialization filter inside slide-over scopes course Select. | Course list narrows. | Browser | untested |
| TC-006 | 2 | display | Transfer courses appear in "مواد المعادلة" section on `/portal/grades-page`. | Section renders below main grades. | Browser | untested |
| TC-007 | 2 | display | Transfer courses appear in `/portal/course-tree-page` as passed. | Tree counts include them. | Browser | untested |
| TC-008 | 2 | edge | Course belongs to another specialization — should still allow add (per current behavior; P2-13 in research notes flagged as not enforced). | Document actual behavior. | Browser + DB | untested |

---

## 18. Warnings — Course Failure

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| WARN-CF-001 | 1 | happy path | Student fails course A → admin approves grade. | 1 active `student_warnings` row (type=course_failure, course_id=A); `warnings_count=1`. | DB | untested |
| WARN-CF-002 | 1 | dashboard | Student dashboard shows yellow warning card "لديك تحذير". | UI correct. | Browser | untested |
| WARN-CF-003 | 1 | resolve | Re-enroll in A, pass with C, admin approves. | Failure warning resolved (`is_active=false`); `warnings_count=0`. | DB | untested |
| WARN-CF-004 | 1 | dismissal | Fail A again (after first F) → admin approves. | Student dismissed; `dismissed_at` set; `dismissal_type='course_failure'`; cascade withdraws active enrollments. | DB | untested |
| WARN-CF-005 | 1 | dashboard | Dismissed dashboard shows red banner with dismissal reason + link to exceptional request page. | UI correct. | Browser | untested |
| WARN-CF-006 | 1 | independence | Fail A and B simultaneously. | 2 separate warning rows. Pass A only — A's warning resolved, B's stays active. | DB | untested |
| WARN-CF-007 | 1 | edge | Approve grade A=F (warning created) then un-approve (status flips back to Pending/Draft). | Warning row resolved (`is_active=false`, `resolved_at` set); `warnings_count` decremented; dashboard yellow card disappears. | Browser + DB | untested |
| WARN-CF-007b | 1 | edge | Approve A=F, then change A=F to A=C (re-approve as passing). | Warning resolved on re-approval; GPA recalc uses C; no dismissal. | DB | untested |
| WARN-CF-007c | 1 | edge | Student dismissed for course-failure (2nd F). Admin un-approves the second F. | Dismissal reversed: `dismissed_at`/`dismissal_*` cleared; status returns to Active; warning state recomputed. | Browser + DB | untested |
| WARN-CF-008 | 2 | warning UI | Warning card click shows details: which course failed. | Modal/expansion with course name. | Browser | untested |
| WARN-CF-009 | 2 | edge | Manually set `warnings_count` via admin. | Should be read-only / overwritten by `WarningService::syncWarningsCount`. | Browser + DB | untested |
| WARN-CF-010 | 2 | retake | Student fails A, retakes A, fails again. | Counts as second failure → dismissed. | DB | untested |

---

## 19. Warnings — Consecutive Inactive

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| WARN-CI-001 | 1 | happy path | Admin sets student status=Inactive. | `consecutive_inactive` warning created for current semester; `warnings_count=1`. | DB | untested |
| WARN-CI-002 | 1 | clear | Admin sets back to Active. | Inactive warnings cleared. | DB | untested |
| WARN-CI-003 | 1 | dismissal | Student stays Inactive across two consecutive semester activations. | On 2nd activation, `SemesterObserver` triggers — student dismissed. | DB | untested |
| WARN-CI-004 | 1 | non-consecutive | Inactive S1 → Active S2 → Inactive S3 → Active S4. | Only 1 warning at any given time; no dismissal (consecutive count resets). | DB | untested |
| WARN-CI-005 | 2 | observer | Activate a new semester with an Inactive student already at warning=1. | Second warning fires → dismissed; cascade withdraws (none if no enrollments). | DB | untested |
| WARN-CI-006 | 2 | edge | Set Inactive then immediately back to Active in same session. | Warning fires on flip-to-Inactive, then cleared on flip-back. End state: 0 warnings. | DB | untested |
| WARN-CI-007 | 2 | label | Warning card details show "منقطع في الفصل XX". | UI displays semester name. | Browser | untested |
| WARN-CI-008 | 2 | edge | Inactive student dismissed → admin reactivates via Exceptional → 1 inactive warning still consumes? | Per acceptance logic for inactive: clear all inactive warnings (fresh start, 0 warnings). Verify. | DB | untested |

---

## 20. Dismissal & Exceptional Request

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| DISM-001 | 1 | happy path | Dismissed student logs in → sees dismissal banner + link to exceptional request page. | UI correct; navigation OK. | Browser | untested |
| DISM-002 | 1 | guard | Non-dismissed student visits `/portal/exceptional-request-page`. | 403 via `canAccess()`. | Browser | untested |
| DISM-003 | 1 | submit | Dismissed student submits exceptional request with reason. | `ExceptionalRequest` row created `status=pending`; admin notified. | DB + Log | untested |
| DISM-004 | 2 | duplicate | Submit second request while one is pending. | Blocked. | Browser | untested |
| DISM-005 | 1 | admin list | Admin opens `/admin/exceptional-opportunities-page`. | Two tables: pending requests + dismissed-without-request. Click row → `/admin/exceptional-opportunities-page/{student}` (separate URL per 2026-04-30 decision). | Browser | untested |
| DISM-006 | 1 | accept (course-failure) | Admin clicks قبول on a course-failure dismissal. | Student status flips to Active; new warning created with `has_exceptional_chance=true`; old warnings resolved; cascade updates. | DB + Log | untested |
| DISM-007 | 1 | accept (consecutive) | Admin clicks قبول on a consecutive-inactive dismissal. | Student Active; ALL inactive warnings cleared (fresh start, 0). | DB | untested |
| DISM-008 | 1 | reject | Admin clicks رفض. | Student stays dismissed; can submit again. | DB | untested |
| DISM-009 | 1 | summon | Admin clicks استدعاء + types message. | `summon_message` set; `summon_sent_at`; once-per-request enforcement. | DB | untested |
| DISM-010 | 1 | dashboard | Summoned student sees danger banner with admin's message on dashboard. | UI correct. | Browser | untested |
| DISM-011 | 1 | permanent ban | Course-failure exceptional accepted → student fails same course again → dismissed; `can_reapply=false`. Try to submit new exceptional request. | Form blocked; permanent-ban message shown. | Browser + DB | untested |
| DISM-012 | 2 | edge | Re-summon (admin clicks استدعاء twice for same request). | Second blocked or replaces. Document. | Browser | untested |
| DISM-013 | 2 | navigation | Browser back button works on detail page (separate URL). | Returns to list. | Browser | untested |
| DISM-014 | 3 | RTL | Detail page student info + summon textarea labels Arabic. | Visually correct. | Browser | untested |
| DISM-015 | 2 | UI | Filament action buttons (accept/reject/summon) use `requiresConfirmation()`. | Modals render. | Browser | untested |

---

## 21. Thesis Eligibility & Submission

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| THESIS-001 | 1 | eligibility | Student missing one general course → `isEligibleForThesis()` returns false. | Page shows "غير مؤهل حالياً" banner; per-type breakdown card highlights gap. | Browser + DB | untested |
| THESIS-002 | 1 | eligibility | Student passed all general + all specialized + 2 electives + GPA=3.0. | Eligible; form visible. | Browser + DB | untested |
| THESIS-003 | 1 | eligibility | GPA=2.99 (boundary). | Not eligible. | Browser | untested |
| THESIS-004 | 1 | eligibility | Exactly 2 electives (boundary). | Eligible. | Browser | untested |
| THESIS-005 | 1 | eligibility | 3 electives. | Eligible. | Browser | untested |
| THESIS-006 | 1 | eligibility | 1 elective only. | Not eligible. | Browser | untested |
| THESIS-007 | 1 | submit | Eligible student submits with internal supervisor (faculty user) + Arabic title + English title + PDF. | `ThesisRegistration` row created `status=pending`. | DB | untested |
| THESIS-008 | 1 | submit | Submit with external supervisor (from `supervisors` table). | `supervisor_id` set; `is_external_supervisor=true`. | DB | untested |
| THESIS-009 | 2 | bad judgment | Submit with empty title. | Validation rejects. | Browser | untested |
| THESIS-010 | 2 | file upload | Upload non-PDF as proposal. | Rejected. | Browser | untested |
| THESIS-011 | 2 | duplicate | Submit again while latest is `pending`. | Blocked — `canSubmitNew()` returns false. | Browser | untested |
| THESIS-012 | 1 | resubmit | After admin rejection → submit again. | Allowed; previous registration kept in history. | DB | untested |
| THESIS-013 | 2 | history | Student history section shows previous registrations collapsibly. | UI correct. | Browser | untested |
| THESIS-014 | 1 | status restriction | Each non-Active status blocks submit. | Status banner. | Browser | untested |
| THESIS-015 | 2 | display | Student sees "قيد المراجعة" for both `pending` and `printed` admin statuses. | Abstraction works. | Browser | untested |
| THESIS-016 | 3 | RTL | Form sections + supervisor cards Arabic. | Visually correct. | Browser | untested |
| THESIS-017 | 1 | derived field | "تاريخ الانتهاء من المقررات" is computed from latest semester end_date. | Matches DB calc. | Browser + DB | untested |
| THESIS-018 | 2 | bad judgment | Submit with both internal and external supervisor selected via Livewire tampering. | Server picks one based on radio; other field is null. | DB | untested |

---

## 22. Thesis Admin Decisions (3-step)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| THESIS-A-001 | 1 | print | Admin clicks "طباعة للوزارة" on a pending thesis. | `status=printed`; `printed_at` set. Until printed, accept/reject buttons are NOT visible. | Browser + DB | untested |
| THESIS-A-002 | 1 | accept | After print, click قبول. | `status=approved`; eligibility re-validated; notification logged. | DB + Log | untested |
| THESIS-A-003 | 1 | reject | After print, click رفض with reason. | `status=rejected`, reason stored, student notified. | DB + Log | untested |
| THESIS-A-004 | 1 | print guard | Try to accept without printing first. | Button hidden / action blocked. | Browser | untested |
| THESIS-A-005 | 2 | re-validate | If student became ineligible between submission and approve. | Approve refused with Arabic error. | Browser | untested |
| THESIS-A-006 | 2 | resubmit | Approve → reject → resubmit cycle: each rejection enables a new submission for the student. | History grows; only latest is "live". | DB | untested |
| THESIS-A-007 | 2 | row vs header | Both inline row action and header action available; same effect. | Equivalent behavior. | Browser | untested |
| THESIS-A-008 | 1 | external supervisor | Admin can manage external supervisors via slide-over Repeater. | Add/edit/delete works. | Browser + DB | untested |
| THESIS-A-009 | 2 | UI | Detail page (`ViewThesisRegistration`) shows all 4 sections + proposal download. | Layout correct. | Browser | untested |
| THESIS-A-010 | 3 | clickable rows | List rows clickable → detail page. | Navigation works. | Browser | untested |

---

## 23. Discussion Request (مناقشة) — 4-Phase Pipeline

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| DISC-001 | 1 | gate | Student with thesis NOT yet approved tries to request discussion. | Section not visible / blocked. | Browser | untested |
| DISC-002 | 1 | gate | Thesis approved → green "go request discussion" prompt card on dashboard. | Card visible with link. | Browser | untested |
| DISC-003 | 1 | submit | Submit discussion request from thesis page. | `DiscussionRequest` row `status=pending`; admin notified. | DB + Log | untested |
| DISC-004 | 1 | duplicate | Submit again while one is active. | Blocked. | Browser | untested |
| DISC-005 | 1 | admin list | Admin opens `/admin/discussion-requests`. | Latest request per student (`MAX(id) GROUP BY student_id`). | DB | untested |
| DISC-006 | 1 | phase 1 reject | Admin rejects with reason. | Status `rejected`; reason stored; student notified; student can resubmit. | DB + Log | untested |
| DISC-007 | 1 | phase 1 accept | Admin accepts with date. | Status `scheduled`; `discussion_date` set. | DB | untested |
| DISC-008 | 1 | dashboard | Student sees blue "date set" card with discussion date. | UI correct. | Browser | untested |
| DISC-009 | 1 | phase 2 | Admin clicks "تمت المناقشة" (no date restriction). | Status `discussed`. | DB | untested |
| DISC-010 | 1 | phase 3 reject | Admin rejects post-discussion with reason. | Status `discussion_rejected`; resubmittable. | DB | untested |
| DISC-011 | 1 | phase 3 accept | Admin clicks قبول. | Status `discussion_approved`. | DB | untested |
| DISC-012 | 1 | phase 3 with edits | Admin clicks "قبول مع تعديلات". | Status `accepted_with_edits`. | DB | untested |
| DISC-013 | 1 | phase 3→4 | After accepted_with_edits, admin clicks "تم التعديلات". | Status `discussion_approved`. | DB | untested |
| DISC-014 | 1 | phase 4 | Admin clicks "تم التجليد والتسليم". | Status `completed`; `completed_at` set. | DB | untested |
| DISC-015 | 1 | clearance gate | Before completed, `/portal/clearance-page` shows blocked banner. | UI shows prerequisite banner referencing discussion. | Browser | untested |
| DISC-016 | 1 | clearance gate | After completed, clearance unlocks. | Form visible. | Browser | untested |
| DISC-017 | 1 | dual labels | Admin sees 8 detailed phase labels; student sees simplified labels. | Per-side text correct (admin: "تمت المناقشة"/etc; student: "قيد المراجعة"/"مقبول مع التاريخ"/"مرفوض"/"تمت الموافقة النهائية"). | Browser | untested |
| DISC-018 | 1 | dashboard | Each active phase has its own card on dashboard (6 variants: Pending, Scheduled, Discussed, AcceptedWithEdits, DiscussionApproved, Completed). | All render with correct icons/colors. | Browser | untested |
| DISC-019 | 2 | resubmit | Student resubmits after rejection at Phase 1 or Phase 3. | New row created; admin sees latest. | DB | untested |
| DISC-020 | 2 | confirmation modal | Student submit/resubmit uses Filament `x-filament::modal`, not native browser confirm. | Modal looks Filament-styled. | Browser | untested |
| DISC-021 | 2 | status restriction | Each non-Active status blocks discussion submit. | Status banner. | Browser | untested |
| DISC-022 | 2 | edge | Admin rejects discussion at phase 3 — student tries to access clearance. | Clearance still blocked. | Browser | untested |
| DISC-023 | 2 | progress bar | Detail page shows phase progress bar (4 steps). | Step indicators correct per current status. | Browser | untested |
| DISC-024 | 2 | livewire | Date picker `wire:model.live` on the accept-action modal. | Date updates trigger consequence text properly. | Browser | untested |
| DISC-025 | 3 | RTL | Phase progress + cards layout RTL. | Visually correct. | Browser | untested |

---

## 24. Clearance Request

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| CLR-001 | 1 | gate | Discussion not Completed → page blocked. | Blocked banner. | Browser | untested |
| CLR-002 | 1 | gate | Discussion Completed → form visible. | Form appears. | Browser | untested |
| CLR-003 | 1 | submit | Submit clearance with form PDF. | `ClearanceRequest` row created; admin notified. | DB + Log | untested |
| CLR-004 | 1 | issue | Admin issues clearance. | Status updated; transcript PDF generated; clearance PDF generated; `notifyClearanceIssued` fires. | DB + Log | untested |
| CLR-005 | 2 | duplicate | Submit clearance again after one is pending. | Blocked. | Browser | untested |
| CLR-006 | 2 | bad judgment | Submit before thesis approved. | Blocked even before checking discussion (early-out). | Browser | untested |
| CLR-007 | 2 | status restriction | Each non-Active status blocks clearance submit. | Status banner. | Browser | untested |
| CLR-008 | 2 | file upload | Upload non-PDF or oversized. | Rejected. | Browser | untested |
| CLR-009 | 1 | PDF | Generated transcript includes transfer courses + GPA + Arabic labels. | Output correct (open + visually verify). | Browser | untested |
| CLR-010 | 3 | RTL | Forms + PDF layout RTL. | Visually correct. | Browser | untested |

---

## 25. Permissions / Roles / Filament Shield

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| PERM-001 | 2 | super_admin | Super admin can access every Resource (Gate::before bypass). | All visible. | Browser | untested |
| PERM-002 | 2 | secretary | Secretary can CRUD Students/Applications/CourseOfferings/Grades, view-only Faculty/Users. | Per role permissions. | Browser | untested |
| PERM-003 | 2 | faculty | Faculty sees only "موادي" + Grades (own courses). | Restricted. | Browser | untested |
| PERM-004 | 2 | faculty | Faculty cannot delete a Student. | Action absent / 403. | Browser | untested |
| PERM-005 | 2 | role drift | Edit user, change `role` column → save. | `afterSave` hook resyncs Spatie role. | DB | untested |
| PERM-006 | 2 | role drift | Create user via `/admin/users/create`. | `afterCreate` hook syncs Spatie. | DB | untested |
| PERM-007 | 2 | hybrid hazard | User with `role=secretary` but no Spatie role. | Document actual: column-only checks pass, Spatie-gated UI fails. | Browser + DB | untested |
| PERM-008 | 2 | edge | Run `php artisan db:seed --class=RolesAndPermissionsSeeder` again. | Idempotent; counts unchanged. | DB | untested |
| PERM-009 | 2 | route guard | Faculty visits `/admin/students/1/edit` directly. | 403 (or hidden). | Browser | untested |
| PERM-010 | 2 | route guard | Student visits `/admin`. | Redirect / 403. | Browser | untested |
| PERM-011 | 2 | inactive | Inactive faculty (`is_active=false`) tries to login. | Blocked. | Browser | untested |
| PERM-012 | 3 | nav | Per role, top-level nav groups appear in correct Arabic order. | Visually correct. | Browser | untested |

---

## 26. Status Restrictions Matrix (every status × every action)

> Run all rows. Datasets: OnHold, Inactive, Transferred, Graduated, Dismissed.

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| MTX-OH-001 | 2 | status restriction | OnHold student: open Course Survey page. | Status banner; submit blocked. | Browser | untested |
| MTX-OH-002 | 2 | status restriction | OnHold: Add/Drop submit. | Blocked. | Browser | untested |
| MTX-OH-003 | 2 | status restriction | OnHold: Suspension submit. | Blocked. | Browser | untested |
| MTX-OH-004 | 2 | status restriction | OnHold: Thesis submit. | Blocked. | Browser | untested |
| MTX-OH-005 | 2 | status restriction | OnHold: Discussion submit. | Blocked. | Browser | untested |
| MTX-OH-006 | 2 | status restriction | OnHold: Clearance submit. | Blocked. | Browser | untested |
| MTX-OH-007 | 2 | status restriction | OnHold: read pages (Grades, CourseTree, StudentCourses). | Read access preserved. | Browser | untested |
| MTX-IN-001 | 2 | status restriction | Inactive: Course Survey submit. | Blocked. | Browser | untested |
| MTX-IN-002 | 2 | status restriction | Inactive: Add/Drop submit. | Blocked. | Browser | untested |
| MTX-IN-003 | 2 | status restriction | Inactive: Suspension submit. | Blocked. | Browser | untested |
| MTX-IN-004 | 2 | status restriction | Inactive: Thesis submit. | Blocked. | Browser | untested |
| MTX-IN-005 | 2 | status restriction | Inactive: Discussion submit. | Blocked. | Browser | untested |
| MTX-IN-006 | 2 | status restriction | Inactive: Clearance submit. | Blocked. | Browser | untested |
| MTX-IN-007 | 2 | status restriction | Inactive: read access works. | Visible. | Browser | untested |
| MTX-TR-001 | 2 | status restriction | Transferred: every write submit. | All blocked. | Browser | untested |
| MTX-TR-002 | 2 | status restriction | Transferred: read access works. | Visible. | Browser | untested |
| MTX-GR-001 | 2 | status restriction | Graduated: every write submit. | Blocked. | Browser | untested |
| MTX-GR-002 | 2 | status restriction | Graduated: read transcripts. | Visible — past data still accessible. | Browser | untested |
| MTX-DM-001 | 2 | status restriction | Dismissed: write submits. | Blocked. | Browser | untested |
| MTX-DM-002 | 2 | status restriction | Dismissed: ExceptionalRequest page accessible. | Yes (only-for-dismissed). | Browser | untested |
| MTX-DM-003 | 2 | status restriction | Dismissed: dashboard banner visible. | Yes. | Browser | untested |
| MTX-AA-001 | 2 | admin filter | EnrollStudents picker excludes non-Active students. | UI filter active. | Browser | untested |
| MTX-AA-002 | 2 | admin filter | Survey picker (`survey_students`) excludes non-Active. | Same. | Browser | untested |
| MTX-AA-003 | 2 | action guard | Forced enrollment of non-Active via tinker call. | `StudentNotActiveException`. | Log | untested |
| MTX-DISP-001 | 2 | display precedence | Active student with approved suspension in active semester → display "القيد موقوف مؤقتًا" (terminal-state-aware). | Per Student::getDisplayStatusLabel rule: terminal Withdrawn/Graduated wins; Inactive shows same label as semester suspension. | Browser | untested |
| MTX-DISP-002 | 2 | display precedence | Withdrawn/Transferred status → admin display shows "منتقل" not semester suspension. | Per precedence rules. | Browser | untested |
| MTX-DISP-003 | 2 | display precedence | Graduated → admin display "متخرج". | Same. | Browser | untested |
| MTX-CASC-001 | 1 | observer | Flip Active → OnHold. | All active enrollments cascade to Withdrawn; GPA frozen at last value. | DB | untested |
| MTX-CASC-002 | 1 | observer | Flip Active → Transferred. | Same cascade. | DB | untested |
| MTX-CASC-003 | 1 | observer | Flip Active → Graduated. | Same cascade. | DB | untested |

---

## 27. Cross-Feature Interactions

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| XFEAT-001 | 2 | mid-semester dismissal | Active student with 4 enrollments fails 2nd same-course → admin approves grade → dismissed mid-semester. | All 4 active enrollments cascade to Withdrawn; warnings/dismissal fields set; GPA frozen. | DB | untested |
| XFEAT-002 | 2 | spec change | Admin changes student's `specialization_id`. | Existing enrollments stay (no cascade); future course tree filters use new spec; thesis eligibility re-evaluates against new spec. | DB | untested |
| XFEAT-003 | 2 | course closed mid-enrollment | Mid-semester admin closes an offering. | All enrolled students cascade to Withdrawn; GPA recalculated; student dashboards lose the course. | DB | untested |
| XFEAT-004 | 2 | overlapping requests | Student has pending add/drop AND pending suspension. | Suspension approval cascades withdraw; outstanding add-drop becomes moot. Verify nothing crashes. | DB | untested |
| XFEAT-005 | 2 | thesis approved + status flip | Thesis approved → admin flips status to Inactive. | Thesis row preserved; student blocked from discussion submit. Re-activation restores access. | DB | untested |
| XFEAT-006 | 2 | discussion completed + status flip | Discussion `completed` → admin flips status to Inactive. | Clearance access blocked despite discussion completion (status gate). | Browser | untested |
| XFEAT-007 | 2 | retake + cap | Student at 3 enrollments + retake makes 4 → submit 5th. | Cap blocks; consistent error. | DB | untested |
| XFEAT-008 | 2 | survey + closed offering | Course in survey but no offering / offering closed. | Survey row `is_processed=false` remains; admin sees demand. | DB | untested |
| XFEAT-009 | 2 | suspension + grade approval | Suspended student has a Pending grade — admin approves. | Grade approves but GPA not recalculated (skip non-Active). | DB | untested |
| XFEAT-010 | 2 | exceptional accept + new failure | Course-failure dismissal → exceptional accept → student fails same course AGAIN. | Dismissed permanently; `can_reapply=false`. | DB | untested |
| XFEAT-011 | 2 | warnings concurrent types | Student fails A (warning 1) AND becomes Inactive (warning 2 type CI). | 2 warnings of different types coexist; `warnings_count=2`. | DB | untested |
| XFEAT-012 | 2 | dropping all enrollments | Student drops all 4 enrollments via add/drop. | Status remains Active (no auto-Inactive — that's deferred per decision). | DB | untested |
| XFEAT-013 | 2 | semester rollover | Activate next semester. | `SemesterObserver` processes Inactive students; new survey opens; old enrollments are historical. | DB | untested |
| XFEAT-014 | 2 | concurrent approvals | Two admins approve different applications simultaneously (same prefix). | One succeeds, other may fail with race (per gotcha) — verify graceful fail. | Log | untested |
| XFEAT-015 | 2 | enrollment restoration | Reopen a closed offering after cascade. | Withdrawn students stay withdrawn; admin must re-enroll explicitly. | DB | untested |

---

## 28. RTL / Arabic-Specific UI

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| RTL-001 | 3 | RTL | Every visited page has `dir="rtl"` and right-aligned text. | All pages. | Browser | untested |
| RTL-002 | 3 | RTL | Every status badge label is Arabic. | No English leak. | Browser | untested |
| RTL-003 | 3 | RTL | Validation errors all Arabic. | Confirm on a few public + admin forms. | Browser | untested |
| RTL-004 | 3 | RTL | Tables: action buttons don't overlap on narrow screens. | Visual sanity check. | Browser | untested |
| RTL-005 | 3 | RTL | Modals open with text right-aligned, close button on the left. | Visually correct. | Browser | untested |
| RTL-006 | 3 | RTL | Dropdowns open within viewport (don't go off-screen). | Visually correct. | Browser | untested |
| RTL-007 | 3 | RTL | Mixed Arabic + numbers (enrollment_no like "251101") render correctly inline. | No mirror-flip of digits. | Browser | untested |
| RTL-008 | 3 | RTL | "العودة لـ..." back-link uses arrow-right icon (visually points back). | Per design conventions §13. | Browser | untested |
| RTL-009 | 3 | RTL | Filament select `searchable+preload` shows themed dropdown (not native). | Per gotcha — invisible-text bug avoided. | Browser | untested |
| RTL-010 | 3 | RTL | Print roster + transcript PDF render RTL. | Layout correct. | Browser | untested |

---

## 29. Livewire Reactivity

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| LIVE-001 | 2 | reactivity | Course-survey course-tile click immediately reflects (toggles selection). | No round-trip lag. | Browser | untested |
| LIVE-002 | 2 | reactivity | Admin survey-results checkbox uses `wire:model.live` → "فتح المقررات المختارة" enabled state updates instantly. | Per gotcha. | Browser | untested |
| LIVE-003 | 2 | reactivity | Suspension semester dropdown `wire:model.live` → banner + disabled state update. | Per bug fix. | Browser | untested |
| LIVE-004 | 2 | spam | Double-click submit on every form. | One submission only. | Browser + DB | untested |
| LIVE-005 | 2 | edge | Open same form in two tabs, submit from both. | Server idempotent; consistent state. | Browser + DB | untested |
| LIVE-006 | 2 | edge | Refresh page mid-pending-Livewire-action. | Either completes or is rolled back; no half-state. | DB | untested |
| LIVE-007 | 2 | edge | Browser back during pending request. | Page recovers. | Browser | untested |
| LIVE-008 | 2 | reactivity | Admin status change modal: Select `live()` → consequence Placeholder updates per choice. | Real-time text. | Browser | untested |

---

## 30. Auth/Session Edge Cases

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| SESS-001 | 2 | session | Student logged in. Admin sets `is_active=false`. Student refreshes. | Logged out / 403. | Browser + DB | untested |
| SESS-002 | 2 | session | Student logged in. Admin changes student's status to Dismissed. Student already on a write page; clicks submit. | Status banner now blocks; clear Arabic message. | Browser | untested |
| SESS-003 | 2 | session | Admin demoted to faculty mid-session. Tries to use admin-only action. | 403 / hidden. | Browser | untested |
| SESS-004 | 2 | session | Tab logged-out (cookie cleared) clicks submit on cached page. | 401/redirect to login. | Browser | untested |
| SESS-005 | 2 | session | CSRF token expired (419 Page Expired). | Friendly Arabic error page or message — not Laravel's stock English 419 page. Verify Arabic copy on both `/apply` and `/admin` flows. | Browser | untested |
| SESS-005b | 2 | session | After CSRF expiry, refresh page and resubmit. | New token issued; submit succeeds. | Browser + DB | untested |
| SESS-006 | 2 | session | Two concurrent sessions for same user (different browsers). | Both work; last write wins on conflicts. | Browser + DB | untested |

---

## 31. Notification Verification

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| NOT-001 | 1 | notification | After application approval, log entry: `WelcomeStudentNotification` Mail-channel sent. | `storage/logs/laravel.log` line. | Log + DB | untested |
| NOT-002 | 1 | notification | Suspension decision (approve/reject) → `notifySuspensionDecision` fires; log + `notification_logs` row. | Log + DB. | Log + DB | untested |
| NOT-003 | 1 | notification | Thesis decision → `notifyThesisDecision` fires. | Log entry. | Log + DB | untested |
| NOT-004 | 1 | notification | Add/Drop decision → `notifyAddDropDecision` fires. | Log entry. | Log + DB | untested |
| NOT-005 | 1 | notification | Grade approval → `notifyGradePublished`. | Log entry. | Log + DB | untested |
| NOT-006 | 1 | notification | Clearance issued → `notifyClearanceIssued`. | Log entry. | Log + DB | untested |
| NOT-007 | 1 | guard | `QONVO_API_TOKEN` unset. Submit application. | Only mail channel; no Qonvo HTTP call; log shows email-only path. | Log | untested |
| NOT-008 | 2 | guard | If Qonvo would have been called, no actual HTTP request was made. | Use `Http::fake()` or check no outbound call in log. | Log | untested |
| NOT-009 | 2 | edge | Notification log status — verify "sent" reflects "attempted" not delivered (per gotcha). | Document. | Log + DB | untested |
| NOT-010 | 2 | exception | Force a notification-throw via tinker (e.g. mock failure). | Service catches; logs error; main action still completes. | Log | untested |

---

## 32. Data Consistency (Tier 3 spot checks)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| DATA-001 | 3 | labels | Every page uses "وحدات" not "ساعات" for credits. | Per 2026-04-23 rename. | Browser | untested |
| DATA-002 | 3 | labels | Every page uses "شجرة موادي" not "الخطة الدراسية". | Per 2026-04-20 rename. | Browser | untested |
| DATA-003 | 3 | labels | "مسجّلون" not "مسجّلون فعلاً" on offering count column. | Per 2026-04-21 rename. | Browser | untested |
| DATA-004 | 3 | counts | Course tree stats scope to specialization (passed/current). | Per 2026-04-25 fix. | Browser + DB | untested |
| DATA-005 | 3 | counts | Survey badge counts match between list view and modal (uses `is_processed=false`). | Per T8 fix. | Browser + DB | untested |
| DATA-006 | 3 | filter | Admin students list "منتقل من كلية أخرى" filter (TernaryFilter on `is_transfer`). | Filters correctly. | Browser | untested |
| DATA-007 | 3 | filter | Status filter on students list works for all 6 statuses. | Each option narrows correctly. | Browser | untested |
| DATA-008 | 3 | sort | Tables sort correctly on column click; tier sorts (applications) survive. | Per design conventions. | Browser | untested |

---

## 33. Audit Log Sanity

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| AUD-001 | 2 | audit | Application status change writes a row. | `audit_logs` row exists. | DB | untested |
| AUD-002 | 2 | audit | Student status change writes a row with old/new. | `audit_logs` row. | DB | untested |
| AUD-003 | 2 | audit | Grade approval writes exactly 1 row (post-bug-fix). | Count = 1, not 2. | DB | untested |
| AUD-004 | 2 | audit | Activity timeline on `/admin/students/{id}` aggregates 6 sources (CourseSurvey, AddDrop, Suspension, Thesis, Clearance, AuditLog). | Renders correctly; 20 most recent + "عرض الكل" toggle. | Browser | untested |
| AUD-005 | 2 | audit | Audit log shows IP + user_id + Arabic status labels in old/new. | Per `tryFrom()->label()` rendering. | Browser | untested |

---

## 34. Misc Edge Cases & Garbage Inputs (Tier 3)

| Test ID | Tier | Category | Description | Expected Result | Verification Method | Status |
|---|---|---|---|---|---|---|
| EDGE-001 | 3 | bad judgment | Search forms with SQL-like input (`' OR 1=1 --`). | Eloquent escapes; no SQL injection. | DB | untested |
| EDGE-002 | 3 | bad judgment | Paste large blobs of Arabic text into reason fields. | Stored or rejected per max length. | DB | untested |
| EDGE-003 | 3 | bad judgment | Submit XSS-flavored input in name fields (`<script>`). | Rendered escaped, not executed. | Browser | untested |
| EDGE-004 | 3 | empty | View `/admin/applications` when DB is empty. | Empty state renders. | Browser | untested |
| EDGE-005 | 3 | empty | View `/portal/grades-page` for new student with no grades. | Empty state ("لا توجد درجات بعد"). | Browser | untested |
| EDGE-006 | 3 | empty | View course tree for student with no enrollments. | Tree renders all-blank/clock states. | Browser | untested |
| EDGE-007 | 3 | empty | View add/drop with no offerings & no enrollments. | Friendly empty state. | Browser | untested |
| EDGE-008 | 3 | bad judgment | Direct URL hack: `/admin/student-add-drop/9999/9999`. | 404 or graceful error. | Browser | untested |
| EDGE-009 | 3 | bad judgment | Direct URL hack: `/admin/exceptional-opportunities-page/9999`. | 404 / graceful. | Browser | untested |
| EDGE-010 | 3 | bad judgment | Multiple browsers logged in as same student; submit conflicting actions. | Last write wins; nothing crashes. | DB | untested |

---

## Summary

- **Total test cases generated:** 425 (after 2026-05-02 user clarifications added 11 sub-rows for boundary cases).
- **Tier breakdown:** Tier 1 ≈ 152 · Tier 2 ≈ 197 · Tier 3 ≈ 76. (Approximate — many cases include cross-tier datasets.)
- **Feature breakdown:**
  - Setup: 10 (T1)
  - Authentication & Panel Access: 12 (8 T2, 1 T3, 3 mixed)
  - Public Application: 26
  - Application Tracking: 6
  - Admin Application Review: 12
  - Enrollment Number: 5
  - Semester Setup: 8
  - Course Offerings: 12
  - Course Survey (student): 15
  - Course Survey (admin): 12
  - Manual Enrollment: 12
  - Add/Drop (student): 18
  - Add/Drop (admin): 8
  - Suspension Request (student): 12
  - Suspension (admin): 8
  - Grade Entry (faculty): 10
  - Grade Approval & GPA: 10
  - Transfer Courses: 8
  - Warnings — course failure: 10
  - Warnings — consecutive inactive: 8
  - Dismissal & Exceptional: 15
  - Thesis Eligibility & Submission: 18
  - Thesis Admin Decisions: 10
  - Discussion Request: 25
  - Clearance: 10
  - Permissions/Roles: 12
  - Status Restrictions Matrix: 30
  - Cross-Feature: 15
  - RTL/Arabic: 10
  - Livewire Reactivity: 8
  - Auth/Session Edges: 6
  - Notification Verification: 10
  - Data Consistency: 8
  - Audit Log Sanity: 5
  - Misc Edge Cases: 10

## Open questions — answered 2026-05-02

User answers captured; affected rows updated above.

| # | Topic | Answer | Rows affected |
|---|---|---|---|
| 1 | Un-approving any grade — broad behavior | TBD; document during run | GRADE-A-006/007/010 (still untested; no expected change to plan) |
| 2 | `coursework_max + final_max` must equal 100 | YES | CO-009 strict; CO-009b/c added for boundary distributions |
| 3 | Duplicate applications | Only ONE active at a time. Blocked while `pending` or `initially_approved`; permanently blocked after `accepted`; allowed after `rejected`. | APPLY-019, APPLY-019b, APPLY-019c |
| 4 | `/apply/track` rate limit | TBD; document during run | TRACK-004 stays as-is |
| 5 | Un-approving an F grade | Warning auto-resolves; `warnings_count` decrements; dismissal reverses if it was the trigger | WARN-CF-007, WARN-CF-007b, WARN-CF-007c |
| 6 | Concurrent enrollment-number approval race | TBD; document during run | XFEAT-014 stays as-is |
| 7 | CSRF expiry | Friendly Arabic message expected, not Laravel default | SESS-005, SESS-005b |
| 8 | Faculty cross-course access | A professor must NOT have access to a course they don't teach. P0-2 unfixed = FAILED test, not deferred. | GRADE-F-002, GRADE-F-002b/c (promoted to Tier 1) |

Items #1, #4, #6 marked TBD — verify behavior at run time and decide pass/fail then.
