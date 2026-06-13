---
date: 2026-06-12
tags: [notifications, whatsapp, exam-list, approval]
related: [[../daily-notes/2026-06-12-notification-log-viewer-and-activity-bell]]
---

# Bug: 3 Redundant WhatsApp Notifications on Student Acceptance

## Symptoms

Admin approves a student and the student receives 3 separate WhatsApp messages:
1. "تم قبولك مبدئياً" (initial approval — correct, sent earlier)
2. "اجتزت الامتحان وتم قبولك نهائياً" (exam passed)
3. "مرحباً، رقمك الجامعي: X" (welcome with enrollment number)

Messages 2 and 3 fire back-to-back within seconds from the same admin action on the ExamListPage.

## Root Cause

`ExamListPage::markPassed()` called two things in sequence:
1. `$approve->handle($application)` — `ApproveApplication` internally calls `notifyWelcomeStudent` (message 3)
2. `$notifier->notifyApplicationExamPassed($application)` — sends message 2 immediately after

Both announced final acceptance. Message 2 arrived AFTER the more complete message 3 (which had enrollment number + credentials), making it confusing and redundant.

## Fix

Deleted `$notifier->notifyApplicationExamPassed($application)` (line 99) from `ExamListPage::markPassed()`. Also removed the now-unused `NotificationService` import.

**File:** `app/Filament/Pages/ExamListPage.php`

## Verification

Checked `NotificationLog` table after fix — most recent approval (student ID 3, 21:30) produced exactly 1 entry (`welcome_student`). Pre-fix entries (IDs 3+4 at 21:09) showed the old double-fire.

## Other notifications checked

- `directApproveAction` in `ApplicationResource` → only calls `ApproveApplication::handle()` → 1 notification ✓
- `SuspensionRequestResource` + `ViewSuspensionRequest` — both have approve/reject but gated by `status === Pending`. Once decided, status changes and buttons disappear — no double-fire risk.
- `ThesisRegistrationResource` + `ViewThesisRegistration` — same pattern, gated by `status === Printed` ✓
