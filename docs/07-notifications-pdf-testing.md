# Graduate Studies System — Notifications, PDF/Print, and Testing

---

## Notification System

### Architecture

All notifications are dispatched through `NotificationService`. This service is the single chokepoint — no notification is dispatched directly from a Filament page or action without going through this service.

```php
// Usage pattern (all notification calls look like this):
$notificationService->notifyWelcomeStudent($student);
$notificationService->notifySuspensionDecision($request);
$notificationService->notifyThesisDecision($registration);
$notificationService->notifyAddDropDecision($request);
$notificationService->notifyGradePublished($grade);
$notificationService->notifyClearanceIssued($clearance);
$notificationService->notifyApplicationReceived($application);
```

The service:
1. Resolves the notifiable (`Application`, `User`, or `Student.user`)
2. Calls `notifiable->notify(new XxxNotification(...))`
3. Logs the attempt to `notification_logs` table (channel, event, status, sent_at)
4. Catches exceptions and logs failures without aborting the main action

### Notification Channels

The `HasWhatsAppChannel` trait on each notification class decides `via()`:

```php
public function via(mixed $notifiable): array
{
    $channels = ['mail'];
    if ($notifiable->phone && config('services.qonvo.api_token')) {
        $channels[] = WhatsAppChannel::class;
    }
    return $channels;
}
```

Mail is always sent. WhatsApp is only attempted if:
- The notifiable has a `phone` field set
- `QONVO_API_TOKEN` is present in environment config

### WhatsApp Integration (Qonvo)

- Provider: Qonvo API (`qonvo.ly`)
- Libyan phone normalization: `09XXXXXXXX` → `+2189XXXXXXXX`
- `WhatsAppService::sendText(string $phone, string $message)` — HTTP POST to Qonvo
- Base URL is hardcoded in `config/services.php` — no env override for the URL
- To disable WhatsApp in local/test environments: unset `QONVO_API_TOKEN` in `.env`

### Notification Log Status Warning

`NotificationLog.status = 'sent'` means **"we attempted it"**, not "it was delivered":
- For mail: means the mail transport accepted it, not that the recipient opened it
- For WhatsApp: `'sent'` status is set before the Qonvo API call; failures are logged but the log status stays `'sent'`

### All 9 Notification Classes

| Class | Trigger | Notifiable |
|---|---|---|
| `WelcomeStudentNotification` | Application approved | `Application` (email) + `User` (WhatsApp) |
| `ApplicationReceivedNotification` | Public form submitted | `Application` |
| `SuspensionDecisionNotification` | Suspension approved or rejected | `Student.user` |
| `ThesisDecisionNotification` | Thesis approved or rejected | `Student.user` |
| `AddDropDecisionNotification` | Add/drop approved or rejected | `Student.user` |
| `GradePublishedNotification` | `published_at` set on grade | `Student.user` |
| `ClearanceIssuedNotification` | Clearance issued by admin | `Student.user` |
| `DiscussionScheduledNotification` | Discussion date set | `Student.user` |
| `ExceptionalDecisionNotification` | Exceptional request accepted/rejected | `Student.user` |

### Synchronous Execution Warning

No notification implements `ShouldQueue`. All email and WhatsApp calls run synchronously during the HTTP request. If Qonvo is slow, the response is delayed. If upgrading to queued notifications in the future, ensure the queue driver is configured.

---

## PDF & Print System

### Two PDF Stacks

**Stack 1 — DomPDF (`barryvdh/laravel-dompdf`)**

Used for: transcripts, clearance forms, suspension forms, thesis forms (older templates).

`PdfService` methods:
- `generateTranscript(Student $student): string` — returns storage path
- `generateClearanceForm(ClearanceRequest $request): string`
- `generateSuspensionForm(SuspensionRequest $request): string`
- `generateThesisForm(ThesisRegistration $registration): string`

Files saved to `storage/app/public/pdfs/`. Accessible via `asset('storage/'.$path)`.

**Stack 2 — mPDF (ministry-style templates)**

Used for: student letters, provisional grades, exam attendance, course results, graduation certificates.

`PrintController` handles all mPDF routes. Every method follows the same pattern:
```php
public function studentLetter(Student $student): Response
{
    $this->authorizeAdminOnly();
    $student->load(['specialization', 'user', 'registrationSemester']);
    $html = view('prints.student_letter', ['student' => $student, ...])->render();
    $pdf  = $this->makeMpdf();
    $pdf->WriteHTML($html);
    return response($pdf->Output('', 'S'), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="letter-'.$student->id.'.pdf"',
    ]);
}
```

### mPDF Font Stack

| Font | Variants | Purpose |
|---|---|---|
| Noto Naskh Arabic | R + B | All Arabic body text (default) |
| Markazi Text | R only | Numbers — enrollment no., year digits |
| DejaVu Sans | bundled | Auto-substitution for Latin/ASCII glyphs Noto Naskh lacks |
| Amiri | R + B + I + BI | Available as fallback; not used by default |

**Critical mPDF config flags (all must be set):**
```php
'directionality'   => 'rtl',
'autoScriptToLang' => true,
'autoLangToFont'   => false,   // MUST be false — keep our default_font
'useSubstitutions' => true,
'backupSubsFont'   => ['dejavusans'],
'default_font'     => 'noto-naskh',
```

### Print Routes

All under `/admin/...`, protected by `auth` middleware:

| Route name | URL | Document |
|---|---|---|
| `print.student-letter` | `/admin/students/{id}/print-student-letter` | خطاب للطالب (ministry letter) |
| `print.provisional-grades` | `/admin/students/{id}/print-provisional-grades` | درجات مبدئية |
| `course-offering.print-students` | `/admin/course-offerings/{id}/print-students` | قائمة طلاب المقرر |
| `print.exam-attendance` | `/admin/course-offerings/{id}/print-exam-attendance` | كشف الحضور الامتحاني |
| `print.course-results` | `/admin/course-offerings/{id}/print-course-results` | نتائج المقرر |
| `print.graduation-certificate` | `/admin/students/{id}/print-certificate` | شهادة تخرج |

### System Settings Used in Prints

Settings are stored in the `settings` DB table and editable via the admin dashboard SettingsWidget. Templates read them via `Setting::get('key')`:

| Key | Used in | Arabic meaning |
|---|---|---|
| `college_name` | All prints | اسم الكلية |
| `office_name` | Ministry letters | مسمى المكتب |
| `director_name` | Ministry letters | اسم مدير المكتب |
| `dean_name` | Graduation certificate | عميد الكلية |
| `college_name_en` | Graduation certificate (English side) | College name in English |

---

## Testing Overview

### Framework & Setup

- **Pest 4** for all tests (Feature + Unit)
- **RefreshDatabase** applied to every test file
- **SQLite in-memory** database for speed
- Run: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`

### Shared Test Helpers (`tests/Pest.php`)

```php
// Creates User + Student + Spatie role in one call
function studentWithRecord(array $studentAttrs = []): User

// Creates User with super_admin role (both users.role column + Spatie)
function adminUser(): User

// Custom Pest expectation: asserts collection has exactly one item
expect()->extend('toBeOne', fn() => $this->toHaveCount(1));
```

### Test File Structure

```
tests/
├── Feature/
│   ├── EnrollmentConsistencyTest.php    — end-to-end enrollment, drop, observer cascade
│   ├── GradeApprovalTest.php            — grade approval → GPA recalc → notifications
│   ├── StudentPortalTest.php            — survey, add/drop, suspension from student side
│   ├── SuspensionRequestTest.php        — suspension lifecycle
│   ├── ThesisRegistrationTest.php       — thesis eligibility + submission + admin flow
│   └── Example.php                      — Laravel scaffold (removable)
└── Unit/
    ├── GradeCalculationServiceTest.php  — GPA math (needs DB for factory use)
    ├── GradeTest.php                    — letter conversion thresholds
    ├── StudentEligibilityTest.php       — isEligibleForThesis() logic
    └── Example.php                      — Laravel scaffold (removable)
```

### Key Testing Patterns

**Factory states:** `Grade::factory()->passing()` and `Grade::factory()->failing()` auto-set `published_at` for convenience.

**Observer cascade during factory creation:** Creating an enrollment via factory fires observers. If the student is `Inactive`, the enrollment immediately gets withdrawn. Use `StudentStatus::Active` explicitly in test setup.

**Vite manifest requirement:** Tests that hit Filament-rendered pages need `public/build/manifest.json`. If tests fail with Vite manifest errors, run `npm run build`.

**Full domain state required:** When adding an enrollment guard (specialization check, cap check), existing tests may break because factory fixtures don't set up the full domain state (no `course_specialization` pivot, no `specialization_id` on student). Fix the fixtures — don't weaken the guard.

---

## Full System Test Plan (Manual)

The `_brain/project-context/test-plan.md` file contains 425 manual test cases organized into:

| Section | Test IDs | Count |
|---|---|---|
| Setup prerequisites | SETUP-001..010 | 10 |
| Authentication & panel access | AUTH-001..012 | 12 |
| Public application form | APPLY-001..026 | 26 |
| Application tracking | TRACK-001..006 | 6 |
| Admin application review | ADM-APP-001..012 | 12 |
| Enrollment number generation | ENRNO-001..005 | 5 |
| Semester setup | SEM-001..008 | 8 |
| Course offerings | CO-001..012 | 12 |
| Course survey (student) | SUR-S-001..015 | 15 |
| Course survey (admin) | SUR-A-001..012 | 12 |
| Manual enrollment | ENR-001..012 | 12 |
| Add/Drop student | AD-S-001..018 | 18 |
| Add/Drop admin | AD-A-001..008 | 8 |
| Suspension (student) | SUSP-S-001..012 | 12 |
| Suspension (admin) | SUSP-A-001..008 | 8 |
| Grade entry (faculty) | GRADE-F-001..010 | 10 |
| Grade approval & GPA | GRADE-A-001..010 | 10 |
| Transfer courses | TC-001..008 | 8 |
| Warnings — course failure | WARN-CF-001..010 | 10 |
| Warnings — consecutive inactive | WARN-CI-001..008 | 8 |
| Dismissal & exceptional | DISM-001..015 | 15 |
| Thesis eligibility & submission | THESIS-001..018 | 18 |
| Thesis admin decisions | THESIS-A-001..010 | 10 |
| Discussion request | DISC-001..025 | 25 |
| Clearance | CLR-001..010 | 10 |
| Permissions & roles | PERM-001..012 | 12 |
| Status restrictions matrix | MTX-001..030 | 30 |
| Cross-feature interactions | XFEAT-001..015 | 15 |
| RTL/Arabic UI | RTL-001..010 | 10 |
| Livewire reactivity | LIVE-001..008 | 8 |
| Auth/session edges | SESS-001..006 | 6 |
| Notification verification | NOT-001..010 | 10 |
| Data consistency labels | DATA-001..008 | 8 |
| Audit log sanity | AUD-001..005 | 5 |
| Misc edge cases | EDGE-001..010 | 10 |

**Tiers:**
- **Tier 1** (critical path, run 100%) ≈ 152 cases
- **Tier 2** (restrictions, cross-feature, bad inputs, run 100%) ≈ 197 cases
- **Tier 3** (RTL/UX, garbage inputs, spot-check 30–50%) ≈ 76 cases

**Pre-test setup rules:**
- Run `php artisan migrate:fresh --seed`
- Unset `QONVO_API_TOKEN` to prevent real WhatsApp sends
- Run `npm run build` to ensure Vite manifest exists
- Verify Filament Shield permissions: super_admin ≈178 perms, secretary ≈58, faculty ≈8

---

## Known Limitations & Technical Debt

| Issue | Impact | Location |
|---|---|---|
| No queue driver for notifications | Slow HTTP responses if Qonvo is latent | `app/Notifications/*` |
| Enrollment number generation has a race condition | Rare under concurrent approvals | `EnrollmentNumberService` |
| No DB constraint preventing two active semesters | Admin must manually ensure singleton | `semesters` table |
| `BaseAction::can()` is broken scaffolding | Nothing uses it — safe to ignore | `app/Actions/BaseAction.php` |
| `students.*` Livewire pages don't null-guard `auth()->user()->student` | Crash if EnsureStudentRole is misconfigured | `app/Filament/Student/Pages/*` |
| WhatsApp `NotificationLog.status='sent'` is optimistic | Cannot confirm delivery from logs | `NotificationService` |
| Letter grade thresholds not DB-configurable | Changing scale requires code edit | `Grade::convertToLetter()` |
| No Form Request classes for public form | `RegistrationController::store` has 130 lines of inline validation | `RegistrationController` |
| `AuditObserver::WATCHED_FIELDS` is static allow-list | New auditable fields require code edit | `AuditObserver` |
