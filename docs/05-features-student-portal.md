# Graduate Studies System — Student Portal Features

The student portal is a separate **Filament v5 panel** at `/portal`. It is accessible only to users with `role = 'student'` AND `is_active = true`. The panel is **RTL Arabic** throughout — brand name `بوابة الطالب`.

All portal pages are hand-rolled Livewire components (not using Filament form builder). Each page is a `Filament\Pages\Page` subclass with public Livewire properties and a Blade view.

---

## Portal Navigation Structure

```
الأكاديمي (Academic)
  ├─ لوحة التحكم (Dashboard)
  ├─ موادي الحالية (My Current Courses)
  ├─ شجرة موادي (Course Tree)
  ├─ درجاتي (My Grades)
  └─ استطلاع المقررات (Course Survey)

الطلبات (Requests)
  ├─ إضافة وحذف (Add/Drop)
  ├─ التوقف الدراسي (Suspension)
  ├─ تسجيل رسالة (Thesis Registration)
  └─ إخلاء طرف (Clearance)
```

---

## Status Gate — `canPerformActions()`

Every **write action** on every portal page is gated by `Student::canPerformActions()`:

```php
public function canPerformActions(): bool
{
    return $this->status === StudentStatus::Active;
}
```

If the student is not Active (OnHold, Inactive, Transferred, Graduated, or Dismissed), the page shows a prominently styled **status banner** explaining why they're blocked, and all submit buttons are disabled. Read-only pages (grades, course tree, current courses) remain accessible for all statuses.

---

## 1. Dashboard (`/portal/student-dashboard`)

The central student overview. Content adapts based on student state.

**Always visible:**
- Welcome card with name + enrollment number
- GPA card
- Current enrollments summary card (list of enrolled courses this semester)

**Conditional cards (shown based on state):**

| Condition | Card shown |
|---|---|
| `warnings_count > 0` | Yellow warning card with count |
| Dismissed | Red dismissal banner with type + link to exceptional request |
| Dismissed + summoned | Danger banner with admin's summon message |
| Thesis approved + no discussion request | Green "request discussion" prompt |
| Discussion status = `scheduled` | Blue card with discussion date |
| Discussion status = `discussed` | Info card "discussion held, awaiting result" |
| Discussion status = `accepted_with_edits` | Warning card "accepted with edits pending" |
| Discussion status = `discussion_approved` | Success card "final approval — proceed to clearance" |
| Discussion status = `completed` | Success card "completed — clearance available" |
| Add/drop window open | Chip/card showing window dates |

---

## 2. My Current Courses (`/portal/student-courses`)

Lists the student's active enrollments for the current semester:
- Columns: course name, course code, credits, faculty name, grade status (if midterm published: shows coursework grade; if published: shows full grade)
- If no active enrollments → empty state message

---

## 3. Course Tree (`/portal/course-tree-page`)

A visual overview of the student's entire academic progress per specialization:

**Structure:**
- General courses section (المقررات العامة)
- Specialized courses section (المقررات التخصصية)
- Elective courses section (المقررات الاختيارية)

**Per course, one of these states:**
- ✓ Passed (green) — has approved grade with letter ≠ F
- ✓ Transfer (info/blue) — passed via معادلة
- ⟳ Enrolled (warning) — currently active enrollment
- ✗ Failed (red) — has approved F grade
- — Not started (gray) — no relevant record

**Summary stats at the top:** completed / enrolled / remaining counts per type + GPA display

---

## 4. My Grades (`/portal/grades-page`)

**Sections:**
1. **Current semester grades** — shows grades for active enrollments; visibility controlled by publish state:
   - Before `midterm_published_at` is set: no grades visible
   - After `midterm_published_at`: coursework grade visible only
   - After `published_at`: full grade (coursework + final + total + letter) visible
2. **Previous semester grades** — all past published grades, grouped by semester
3. **Transfer courses section** — معادلة entries (always visible if any exist)
4. **GPA summary** — current cumulative GPA

---

## 5. Course Survey (`/portal/course-survey-page`)

**Prerequisites:** `survey_open = true` on the active semester AND `canPerformActions() = true`

**Main section — course selection:**
- Displays all courses linked to the student's specialization via `semester_course` pivot for the active semester
- Student toggles desired courses (no upper cap — removed April 2026)
- Courses can be toggled in any order; the page tracks selection priority automatically
- Submit button → creates `CourseSurvey` rows with priority ordering

**Retake section (if applicable):**
- Shows courses the student passed with grade below A (not currently enrolled in)
- Student can select courses to retake
- Submit → creates `CourseSurvey` rows with `is_retake = true`

**Blocked states:**
- `survey_open = false` → survey closed banner; form locked
- Student status ≠ Active → status banner; form locked
- Already submitted this semester → shows submitted courses + ability to view (some implementations allow re-submission)

---

## 6. Add/Drop (`/portal/add-drop-page`)

**Prerequisites:** add/drop window open (`Semester::isAddDropOpen() = true`) AND `canPerformActions() = true`

**Drop section:**
- Lists the student's current active enrollments
- Student selects up to 3 enrollments to drop
- Courses with pending drop requests shown as "pending"

**Add section:**
- Lists available course offerings for the student's specialization (open, not currently enrolled)
- Student selects up to 3 offerings to add

**Rules:**
- Maximum 3 drops per semester, maximum 3 adds per semester
- Cannot drop same enrollment twice (pending request shown)
- Submit → creates `AddDropRequest` rows with `status = pending`

**My requests section:**
- Shows all submitted add/drop requests for the current semester with status badges (قيد المراجعة / تم القبول / تم الرفض)

**Blocked states:**
- Window closed → closed banner
- Approved suspension in active semester → blocked banner
- Status ≠ Active → status banner

---

## 7. Suspension Request (`/portal/suspension-request-page`)

**Prerequisites:** `canPerformActions() = true`

**Submit form:**
- Semester dropdown — shows upcoming semesters
- Reason text area (Arabic, required)
- Suspension form PDF upload (optional, max 10MB)

**Blocking rules enforced by UI + server:**
- Active pending request exists → "يوجد طلب قيد المراجعة" — can't submit new one
- Approved request for the selected semester → semester option disabled with "(تمت الموافقة مسبقاً)" suffix
- Rejected request for a semester → allowed to resubmit for that semester
- Semester dropdown uses `wire:model.live` so the banner and disabled state update immediately on selection change

**My requests section:**
- Shows submitted requests per semester with status badges

---

## 8. Thesis Registration (`/portal/thesis-registration`)

**Prerequisites:** `isEligibleForThesis() = true` AND `canPerformActions() = true`

**Eligibility display (always shown, even when not eligible):**
Four progress cards:
- General courses: X of Y completed
- Specialized courses: X of Y completed
- Elective courses: X of 2 completed
- GPA: current GPA (needs ≥ 3.0)

When eligible: green "مؤهل للتسجيل" banner + form appears
When not eligible: red "غير مؤهل حالياً" banner + detailed breakdown of what's missing

**Submit form (when eligible and no active pending/printed/approved thesis):**
- Arabic title
- English title
- Supervisor type radio: internal faculty (من الكلية) or external (من خارج الكلية)
  - Internal: searchable select from faculty users
  - External: searchable select from `supervisors` table
- Proposal PDF upload
- Derived field: "تاريخ الانتهاء من المقررات" — computed from latest semester's `end_date`

**Previous registrations section:**
- Collapsible list of past thesis submissions with their statuses
- Students see simplified status labels:
  - `pending` or `printed` → "قيد المراجعة"
  - `approved` → "مقبول"
  - `rejected` → "مرفوض" + reason shown

**Blocking rules:**
- Pending or printed registration exists → "طلب قيد المراجعة" — can't submit new
- Approved registration exists → shows approved thesis info + discussion request section
- After rejection → can resubmit

**Discussion request section (when thesis approved):**
- If no discussion request exists → "اطلب مناقشة" button with confirmation modal
- If discussion request exists → shows current phase status in detail

---

## 9. Clearance (`/portal/clearance-page`)

**Prerequisites:** `isDiscussionCompleted() = true` (discussion status = `completed`) AND `canPerformActions() = true`

**Gate display:**
- Discussion not completed → blocked banner with clear message: "لا يمكن تقديم طلب الإخلاء إلا بعد إتمام المناقشة"

**Submit form (when gate passes and no existing request):**
- Clearance form PDF upload (optional)
- Submit → creates `ClearanceRequest` with `status = pending`

**Existing request section:**
- Shows submitted request status
- When admin issues clearance (`status = issued`):
  - Transcript PDF download link appears
  - Clearance certificate PDF download link appears

---

## Student Status Banner (Design Pattern)

When a student's status blocks an action, every portal page shows a styled banner:

```html
<div class="flex items-start gap-4 p-4 rounded-xl border bg-{color}-50 border-{color}-200 dark:bg-{color}-500/10">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-{color}-100">
        <x-heroicon-o-{icon} class="text-{color}-600" style="width:1.25rem;height:1.25rem" />
    </div>
    <div class="flex-1">
        <p class="font-semibold text-{color}-800 text-sm">{title}</p>
        <p class="text-sm text-{color}-700 mt-0.5">{description}</p>
    </div>
</div>
```

| Status | Color | Icon | Title |
|---|---|---|---|
| `OnHold` | `danger` | `pause-circle` | "القيد موقوف مؤقتًا" |
| `Inactive` | `warning` | `clock` | "منقطع" |
| `Transferred` | `info` | `arrow-right-circle` | "منتقل" |
| `Graduated` | `success` | `academic-cap` | "متخرج" |
| `Dismissed` | `danger` | `x-circle` | "مفصول" |
| Survey closed | `warning` | `lock-closed` | "الاستطلاع مغلق" |
| Window closed | `gray` | `inbox` | "نافذة الإضافة والحذف مغلقة" |

---

## Public Application Pages

These are **not** part of the student portal — they run on the public `web` guard.

### `/apply` — Application Form

Multi-step form rendered by `RegistrationController` (Blade, no Filament):
- Personal information: full name (Arabic 4+ words, English), national ID (12 digits), birth date (age 22–60), gender, marital status
- Contact: Libyan phone (09XXXXXXXXX format), email
- Academic: specialization dropdown, study type, delegation fields (conditional on nationality)
- File upload: bachelor certificate (PDF only, max 10MB)
- Declaration checkbox
- Submit → `CreateApplication` action → tracking code displayed

"توليد بيانات تجريبية" button fills all non-file fields with sample Arabic data from `SampleApplicantData`.

### `/apply/track` — Application Tracking

Simple form: tracking code + national ID → shows current application status and submitted data.

---

## Portal Middleware

`EnsureStudentRole` middleware on all `/portal/*` routes:
- Checks `auth()->user()->role === 'student'`
- If not, redirects to `/portal/login`

Unauthenticated 403 on `/portal/*` is caught in `bootstrap/app.php` and redirected to `/portal/login`.
