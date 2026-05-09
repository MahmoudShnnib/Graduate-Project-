# AUDIT.md — Phase 0

Pre-flight inventory of what exists in the Laravel codebase and what the mobile app + API layer need to add.

---

## 1. Existing Eloquent models — full coverage

Every entity the mobile app needs is already modeled. **No new models will be created.** API resources reuse these.

| Model | Table | Notes |
|---|---|---|
| `User` | `users` | Auth root; has `name`, `name_en`, `email`, `password`, `phone`, `role`, `specialization_id`, `is_active`. Uses Spatie `HasRoles`, Filament `HasPanelShield`. **Does NOT yet use Sanctum's `HasApiTokens` trait** — must be added. |
| `Student` | `students` | Profile + status + GPA. `BelongsTo User` and `Specialization`. Has helpers `canPerformActions()`, `isEligibleForThesis()`, `getThesisEligibilityBreakdown()`, `getCompletedCourseIds()`. |
| `Specialization` | `specializations` | `name`, `code` (integer). Pivot to courses with `type` (general/specialized/elective). |
| `Course` | `courses` | `name`, `code`, `credits`, `is_active`. Belongs to specializations via pivot. |
| `Semester` | `semesters` | `name`, `year`, `season`, `is_active`, `survey_open`, `add_drop_start/end`. Single active at a time. |
| `CourseOffering` | `course_offerings` | course×semester×faculty. `coursework_max`, `final_max`, `grade_distribution`, `paper_sheet_path`. |
| `StudentEnrollment` | `student_enrollments` | Pivot with `status` enum (Enrolled/Dropped/Withdrawn). `hasOne` Grade. |
| `Grade` | `grades` | `coursework_grade`, `final_grade`, auto-derived `total/letter/points` via `booted()`. **Two-stage publish: `midterm_published_at`, `published_at`**. |
| `CourseSurvey` | `course_surveys` | `student × semester × course`, `priority`, `is_retake`, `is_processed`, `rejected_at`. |
| `AddDropRequest` | `add_drop_requests` | `type` enum (Add/Drop), string `status` (no enum cast). |
| `SuspensionRequest` | `suspension_requests` | `status` enum (Pending/Approved/Rejected). Per-semester. |
| `ThesisRegistration` | `thesis_registrations` | Title, supervisor (internal=`faculty_supervisor_id` or external=`supervisor_id`), `is_external_supervisor`, `status`. |
| `DiscussionRequest` | `discussion_requests` | 8-state lifecycle (Pending → Scheduled → Discussed → DiscussionApproved → Completed). |
| `ExceptionalRequest` | `exceptional_requests` | `reason`, `status`. **Missing `course_id` and `opportunity_type` per DESIGN_SPEC.** See §4. |
| `StudentWarning` | `student_warnings` | `type` (CourseFailure/ConsecutiveInactive), `is_active`, optional `course_id` + `semester_id`. |
| `ClearanceRequest` | `clearance_requests` | `status` (pending/completed), `form_path`, `issued_at`. |
| `Supervisor` | `supervisors` | External supervisor metadata. |
| `TransferCourse` | `transfer_courses` | Pre-existing equivalences for transfer students. |
| `NotificationLog` | `notification_logs` | **Outgoing channel log (email/whatsapp), NOT an in-app feed.** No `read_at`. See §4. |

---

## 2. Existing enums

`StudentStatus`, `EnrollmentStatus`, `GradeStatus`, `SuspensionStatus`, `ThesisStatus`, `DiscussionStatus`, `ExceptionalRequestStatus`, `WarningType`, `AddDropType`, `CourseType`, `Season`, `ApplicationStatus`, `NotificationChannel` — all backed string enums with Arabic `label()`. Reuse as-is.

---

## 3. What's missing (must be added in Phase 1)

### 3.1 Sanctum (not installed)

`composer show` confirms no `laravel/sanctum`. Required:

- `composer require laravel/sanctum`
- Publish + run sanctum migrations (creates `personal_access_tokens` table)
- Add `HasApiTokens` to `User`
- Configure stateful domains (we won't use SPA cookies — bearer tokens only)
- Register `auth:sanctum` guard in `config/auth.php`

### 3.2 `routes/api.php` and bootstrap registration

`routes/api.php` does **not** exist. `bootstrap/app.php` only registers `web` + `commands`. Need:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

All API routes will live under `/api/v1/...` and use `auth:sanctum` middleware (except `login`).

### 3.3 In-app notification feed

`NotificationLog` records outbound email/WhatsApp deliveries. It has no read state and exposing it to the mobile app would conflate two concerns. **Decision:** create a new lightweight table `app_notifications`:

```php
id | student_id | type (string) | title (string) | body (text) | read_at (nullable timestamp) | created_at
```

Seeded with 2 unread rows per `TEST_CREDENTIALS.md`. The admin system continues to write `notification_logs` for outbound deliveries unchanged.

### 3.4 Two columns on `exceptional_requests`

DESIGN_SPEC and SYSTEM_SPEC both require:
- a course picker showing failed courses → needs `course_id`
- an opportunity-type segmented control ("فرصة رسوب عادي" / "فرصة حضور") → needs `opportunity_type`

Both nullable to avoid breaking existing rows / Filament admin UI.

### 3.5 API Resource classes

13 to create under `app/Http/Resources/`:
StudentResource, SemesterResource, CourseResource, CourseOfferingResource, EnrollmentResource, GradeResource, SurveyResource, AddDropRequestResource, SuspensionRequestResource, ThesisResource, DiscussionResource, ExceptionalRequestResource, WarningResource, AppNotificationResource, ClearanceRequestResource. (Also Specialization + Supervisor as nested.)

---

## 4. Seeder gaps

`StudentSeeder` produces three students (`251101`, `252101`, `251102`) — the test student `26CS101` is **not** seeded. Active semester is `خريف 2025`, not `الفصل الدراسي الأول 2026`. No course offerings, no enrollments, no grades, no notifications, no warnings, no second OnHold student.

**Decision:** add a new `MobileTestStudentSeeder` that runs *after* the existing seeders and produces *only the additional* data described in `TEST_CREDENTIALS.md`. The active semester is updated by the new seeder (set `is_active=false` on خريف 2025, create a new active semester الفصل الدراسي الأول 2026, plus a previous semester الفصل الدراسي الثاني 2025). Existing students/data stay intact.

This new seeder will produce, for student `26CS101`:

- **User** (Arabic+English name, email, hashed password)
- **Student profile** (CS specialization, Active status, phone, national_id)
- **Active semester** (الفصل الدراسي الأول 2026, `is_active=true`, `survey_open=true`, add/drop window currently open)
- **Previous semester** (الفصل الدراسي الثاني 2025, `is_active=false`, `survey_open=false`)
- **Course offerings** for both semesters (4 courses each)
- **Enrollments**: 4 in active semester (3 Enrolled + 1 Dropped for visual variety), 4 in previous semester (all Enrolled)
- **Grades**: 4 published-fully on previous semester (mix of A/B/C/F — F to seed exceptional request screen), 2 coursework-only published on active semester
- **Course tree**: existing CS courses already cover ≥6 mandatory + 4 elective per CourseSeeder
- **2 unread `app_notifications`**: e.g. "تم نشر درجاتك" and "تم فتح نافذة الاستطلاع"
- **1 active StudentWarning** (CourseFailure, tied to the F-graded course)
- **Second test student `26CS102`** with `OnHold` status

### 4.1 Naming/numbering decisions

- `enrollment_no = '26CS101'` — written verbatim per `TEST_CREDENTIALS.md`. The production `EnrollmentNumberService` would produce a numeric-prefixed code; the field is a plain string so the test value is fine. **Documented mismatch:** does not match the in-system convention but exactly matches the spec.
- Specialization for the test student → existing `Specialization::where('code', 2)` (which has Arabic name `الحاسب الآلي`). `TEST_CREDENTIALS.md` says "علوم الحاسوب (Computer Science)". **Decision:** rename the seeded record's `name` from `الحاسب الآلي` to `علوم الحاسوب` only inside `MobileTestStudentSeeder` (idempotent update) so the displayed name matches the spec without breaking the existing `code = 2` keyed lookups in CourseSeeder, EnrollmentNumberService, etc.

---

## 5. Spec ambiguities — picked simpler interpretation

| # | Ambiguity | Decision |
|---|---|---|
| A1 | DESIGN_SPEC.md tab order is right→left ("Tab 1 (rightmost) = الرئيسية … Tab 5 (leftmost) = حسابي"); RN tabs render in array order. | I render tabs in the array order `[الرئيسية, مقرراتي, الطلبات, الدرجات, حسابي]` and rely on `I18nManager.forceRTL(true)` to flip the visual order so الرئيسية ends up rightmost. |
| A2 | `الطلبات` (Tab 3) badge: "pending count > 0" — which requests count? | Sum of pending counts across AddDrop, Suspension, Thesis, Discussion, Exceptional. Single integer badge. |
| A3 | Grade card "Total chip" letter+numeric on left — e.g. "A 87" or "A+87"? | Letter on top line bold, numeric on second muted line, both inside a colored pill. |
| A4 | Course tree status icons — what status when a student has multiple enrollments in the same course (passed once, failed once)? | Show "passed" (the most favorable terminal outcome). |
| A5 | "Status banner on every screen below header" — does it apply to Login? | No — Login has no header and no authenticated user. Banner shown only on authenticated screens. |
| A6 | Suspension Request "حالة C — Available" — should rejected semesters appear in the picker greyed-out, or be hidden entirely? | Rendered with a lock icon, disabled (matches spec wording "shown with a lock icon and are not selectable"). |
| A7 | Exceptional opportunity types — what enum values? | `regular_failure` ("فرصة رسوب عادي") and `attendance` ("فرصة حضور"). Stored in `opportunity_type` enum-string column. |
| A8 | Thesis screen step 4 ("الموافقة النهائية") — when is it "Completed"? | When `discussion.status ∈ {DiscussionApproved, Completed}`. |
| A9 | Clearance "eligible" — what triggers eligibility? | `discussion.status ∈ {DiscussionApproved, Completed}` AND a `ClearanceRequest` row exists. If no row exists yet but discussion is approved, the API auto-creates a pending one. |
| A10 | Profile screen "تواصل مع الإدارة" link — destination? | Opens a `mailto:` to a hard-coded `admin@example.ly` for now; out of scope for the API. |
| A11 | Notification "circle icon (blue for info, amber for warning)" — type taxonomy? | Two types: `info` (default) and `warning`. Stored in `app_notifications.type`. |

---

## 6. Endpoints needed (Phase 1 list — finalized in Phase 1 commit)

All under `/api/v1`, all `auth:sanctum` except `auth/login`.

```
POST   /auth/login                  → token
POST   /auth/logout                 → 204
GET    /auth/me                     → StudentResource (with user, specialization, GPA, warnings)

GET    /semesters                   → list (active + prior)
GET    /semesters/active            → SemesterResource

GET    /enrollments?semester_id=…   → EnrollmentResource[] (status filter)
GET    /grades?semester_id=…        → GradeResource[] (publishing-aware)

GET    /course-tree                 → grouped by type (general/specialized/elective)

GET    /surveys?semester_id=…       → SurveyResource[]
GET    /surveys/available-courses   → Course[] (filtered by spec + not-yet-passed)
POST   /surveys                     → bulk submit/replace
DELETE /surveys/{id}                → remove one selection

GET    /add-drop-requests           → AddDropRequestResource[] (history)
GET    /add-drop-requests/options   → { addable: CourseOffering[], droppable: Enrollment[], usage: {add: n/3, drop: n/3} }
POST   /add-drop-requests           → create

GET    /suspension-requests         → SuspensionRequestResource[]
GET    /suspension-requests/options → { state: 'available'|'pending_block'|'approved_block', semesters: [{id,name,disabled,reason}] }
POST   /suspension-requests         → create

GET    /thesis                      → { eligibility, registration, discussion, phase }
POST   /thesis/register             → create thesis registration

GET    /exceptional-requests        → ExceptionalRequestResource[]
GET    /exceptional-requests/options → { failed_courses: Course[] }
POST   /exceptional-requests        → create

GET    /clearance                   → { eligible, request? }
GET    /clearance/pdf               → binary stream (Phase 4 stretch)

GET    /notifications               → AppNotificationResource[]
POST   /notifications/{id}/read     → mark read
POST   /notifications/read-all      → bulk mark
```

Resource shapes finalized in code; design intent: every list endpoint returns `data: [...]` (Eloquent default), and "options" endpoints return rich objects with form-driving metadata so the mobile screen never has to duplicate business rules.

---

## 7. Mobile folder structure (planned, finalized in Phase 2)

```
mobile/
├── app/                       (Expo Router file-based routes)
│   ├── _layout.tsx            (RTL force, font load, query client, auth gate)
│   ├── _design-preview.tsx    (hidden — every primitive in every state)
│   ├── (auth)/login.tsx
│   └── (tabs)/
│       ├── _layout.tsx        (5-tab bottom nav, RTL)
│       ├── index.tsx          (الرئيسية / dashboard)
│       ├── enrollments.tsx    (مقرراتي)
│       ├── requests/          (الطلبات group)
│       ├── grades.tsx         (الدرجات)
│       └── account.tsx        (حسابي)
├── src/
│   ├── api/                   (axios client + per-resource fetchers using TanStack Query)
│   ├── components/
│   │   ├── primitives/        (Button, Card, Pill, Banner, Input, Avatar, EmptyState, Skeleton, StatusBadge, SectionHeader)
│   │   └── feature/           (composed pieces — SemesterTabs, GradePill, CoursePicker, etc.)
│   ├── hooks/                 (useAuth, useSession, useStatusGate)
│   ├── lib/                   (token storage, errors, formatters, RTL helpers, date Arabic)
│   └── theme/                 (tailwind preset extensions, Arabic dates, colors token export)
├── assets/fonts/              (NotoKufiArabic-{Regular,Medium,Bold}.ttf)
├── tailwind.config.js
├── babel.config.js
└── app.json                   (Expo config)
```

State strategy:
- **Server state:** TanStack Query (`@tanstack/react-query`).
- **Auth token:** `expo-secure-store` (writes/reads inside the axios interceptor).
- **Form state:** `react-hook-form` + `zod`.
- **Client UI state:** local `useState`/Context only. **Skipping Zustand** — no cross-screen client state demands it; revisit only if a need appears in Phase 3.

---

## 8. Risks and known traps

- **Filament theming + Vite manifest:** building the mobile app does not touch the Laravel frontend bundle, so no Vite-manifest risk. But `php artisan migrate:fresh --seed` during development will wipe Filament admin demo data — acceptable in dev.
- **RTL on Android cold start:** `I18nManager.forceRTL(true)` only takes effect after a JS reload; on Expo Go the very first launch may render LTR for a split second. Acceptable for a graduation-project demo; document in PROGRESS.md.
- **Sanctum token expiry:** default Sanctum tokens never expire. Configure `config/sanctum.php` `expiration => null` (default) or set explicit minutes; for a university app I'll leave them long-lived and rely on logout for invalidation.
- **Auto-Inactive on survey close:** `SemesterObserver` auto-flips Active → Inactive when `survey_open` toggles to `false` for non-submitters. Phase 1 will not toggle survey_open from the API; only admins do. Mobile app remains read-only on this flag.
- **`app_notifications` not yet wired into existing actions:** Phase 1 only seeds notifications. Wiring observers to write to it on grade-publish/request-decision can come later — the mobile feed renders whatever is in the table.
- **Status text in design vs enum:** DESIGN_SPEC pill colors map to enum labels — the API will return both `value` and `label` (and an explicit `color` token from the same enum) so the client renders consistently with admin panel.

---

## 9. Build sequence reminder

Phase 1 → Phase 2 → Phase 3 (12 screens, in order from prompt) → Phase 4. Each phase commits independently. PROGRESS.md updated after each commit.

---

End of audit.
