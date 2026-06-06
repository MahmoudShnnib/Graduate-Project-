---
date: 2026-06-04
tags: [daily, whatsapp, otp, merge, conflicts, tests, deployment]
related:
  - "[[2026-06-03-pre-deploy-cleanup-and-n1-plan]]"
  - "[[../project-context/whatsapp-integration-guide]]"
  - "[[../daily-notes/2026-06-01-whatsapp-otp-apply-verification]]"
---

# 2026-06-04 — WhatsApp OTP merge + branch sync

Short session. Pulled the WhatsApp OTP feature from main into the working branch, resolved merge conflicts, fixed a broken test, and read the integration guide.

---

## What was done

### 1. Merged origin/main → fixes-and-date

Main had 4 new commits ahead of the branch:

| Commit | What |
|---|---|
| `44dfa6d` | feat: WhatsApp OTP phone verification on /apply via Qonvo |
| `0a6420a` | docs: WhatsApp/Qonvo integration guide added to vault |
| `3028dc6` | Merge PR #41 |
| `28b1ef5` | Merge PR #42 |

The WhatsApp OTP feature was developed in parallel with our cleanup (`fixes-and-date`), branched from before the cleanup was merged. This caused conflicts.

### 2. Resolved 2 merge conflicts

**`routes/web.php`**
- Main had: `/apply/sample` route (old test helper) + new OTP routes
- Stash had: everything removed (cleanup)
- Resolution: keep OTP routes, drop `/apply/sample`

**`app/Http/Controllers/RegistrationController.php`**
- Main had: `SampleApplicantData` import + `sample()` method + `WhatsAppOtpService`/`WhatsAppService` imports + `store(Request, WhatsAppService)` signature
- Stash had: all imports/methods removed (cleanup)
- Resolution: keep `WhatsAppOtpService`, `WhatsAppService`, `JsonResponse` imports; drop `SampleApplicantData`; drop `sample()` method; keep `store(Request $request, WhatsAppService $whatsApp)` signature (WhatsApp feature uses `$whatsApp` in store)

### 3. Fixed WhatsAppOtpVerificationTest

`tests/Feature/WhatsAppOtpVerificationTest.php` used `SampleApplicantData::forPublicApplication()` inside the `validApplicationPayload()` helper. Since `SampleApplicantData` was deleted in the cleanup, 3 tests were failing.

Fixed by replacing the call with a hardcoded inline array covering all required validation fields:
- Arabic name (4 words), English name, unique email (`uniqid()`), phone, specialization from factory, mother name, birth date, birth place, gender, marital status, nationality, national ID (12 digits), residence, permanent address, study type, password + confirmation, declaration.

### 4. Read the WhatsApp integration guide

Full guide at `[[../project-context/whatsapp-integration-guide]]`. Key points:

- **Two kinds of messages**: OTP (transactional, via `WhatsAppOtpService`) and 9 notification classes (via `NotificationService` + `WhatsAppChannel`)
- **Notifications are synchronous by design** — they use `Queueable` trait but do NOT implement `ShouldQueue`. This was a deliberate choice. No queue worker needed for notifications.
- **OTP flow on /apply**: gated on `isConfigured()` → if no Qonvo credentials, the OTP widget is hidden and the form works normally (phone field still required but not verified via WhatsApp)
- **`store()` guard**: when Qonvo is configured, `store()` rejects submission unless the phone has a valid verified OTP (`WhatsAppOtpService::hasVerified($phone)` within 30 min)
- **Test pattern**: `Http::fake()` for Qonvo calls; set credentials with `config()->set('services.qonvo.api_token', ...)`
- **Artisan test command**: `php artisan whatsapp:test 0912345678` — sends all 9 notification types to a real number
- **Common gotcha**: sender number must be `authenticated` in Qonvo dashboard (QR scan) before sends work. `422` from API = not authenticated, NOT a bad token.

### 5. Test result

`php artisan test --compact` → **175 passed (378 assertions)** ✅

Up from 165 — the 10 new WhatsApp OTP tests all green.

---

## Current branch state

Branch: `fixes-and-date`
Ahead of `origin/fixes-and-date` by 6 commits (the WhatsApp merge).

**Uncommitted changes still in working tree** (from the N+1 fixes done in another session — stash was popped):
- All the Filament resource `getEloquentQuery()` eager loading fixes
- `Setting.php` cache + `booted()` invalidation
- `AddDropPage.php` + other pages `getActiveSemester()` memoization
- `StudentSurveyView.php` loop pre-loading
- `exam-list.blade.php` dead attributes removed (re-removed after merge reverted them)

> ⚠️ The merge brought back `exam-list.blade.php` with dead attributes (`data-spec`, `data-exam`, `data-print-name`, `print-hidden`). The stash pop re-removed them. Verify the file is clean before committing.

> ⚠️ The merge also brought back the sample data button in `create.blade.php`. The stash pop re-removed it. Verify before committing.

> ⚠️ `CreateStudent.php` — check that `fillSampleData` action and `SampleApplicantData` import are still gone after the stash pop.

---

## What's still NOT done

### From the N+1 plan (`2026-06-03`)
The N+1 fixes are in the working tree (uncommitted). They need to be:
1. Verified with pint + tests (already passing — 175 green)
2. Committed and pushed

### From the production readiness audit
These were identified but never implemented:
- Queue worker in `docker/supervisord.conf` — `php artisan queue:work` not running
- PHP memory limit 256MB in Dockerfile (currently 128MB default)
- Missing database indexes (one migration needed for 17 columns)
- `max_execution_time` mismatch with nginx timeout
- `Semester::where('is_active', true)` firing 26 times — only partially memoized in Pages

---

## How to tell Claude to continue next session

> **"read brain and continue from 2026-06-04 — commit the n+1 fixes then tackle the production issues"**
