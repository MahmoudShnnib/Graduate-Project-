---
date: 2026-06-17
tags: [notifications, whatsapp, cleanup]
related: [[2026-06-17-notification-consolidation]] [[2026-06-16-single-final-grade-publish]]
---

# Notification overhaul — 10 → 4 messages

Consolidated the WhatsApp notification set per the user's plan. Full decision + rationale in
[[2026-06-17-notification-consolidation]]. **Nothing committed** (user commits manually).

## What was done
- **Deleted 7 notification classes** + their `NotificationService` methods + every call site
  (Thesis/Suspension/AddDrop resources & view pages, RegistrationController): ApplicationReceived,
  ApplicationExamPassed, GradePublished, ThesisDecision, SuspensionDecision, AddDropDecision,
  ClearanceIssued. Removed now-unused `NotificationService` closure params + imports.
- **Created `GradesPublishedNotification`** — one general per-student message ("تم نشر درجاتك… راجع
  البوابة", `{name}`), sent once per unique student inside the publish loop in `ViewCourseGrades`
  + `ViewSemester` (replaces per-course `GradePublished`).
- **Edited `WelcomeStudentNotification`** — merges old "exam passed" line; **dropped the password
  line + `$plainPassword` param** (also from `notifyWelcomeStudent`).
- **Kept** ApplicationInitiallyApproved (unchanged) + ContactInfoChanged (security alert).
- Trimmed `NotificationLogResource::EVENT_LABELS` to live events (+ `contact_info_changed`);
  rewrote the `whatsapp:test` console command to the 4 survivors.

## Final surviving notifications (4)
1. ApplicationInitiallyApproved — first approval + exam date + tracking link.
2. WelcomeStudent — exam-pass + official acceptance + enrollment-no + email (no password).
3. GradesPublished — general "grades published, check portal".
4. ContactInfoChanged — security alert on phone/email change.

## Tests
- `GradeCourseworkFlowTest` → asserts `GradesPublishedNotification`.
- `NotificationChannelsTest` → added combined-Welcome content assertion (exam-pass + details, no password).
- **Full suite: 389 passed**, pint clean.

## Live delivery check
Sent OTP + all 4 notifications via `php artisan whatsapp:test 0945108260` — all ✔ delivered
through Qonvo (real WhatsApp send, user-requested).

## Next steps / loose ends
- User to commit.
- (Separate, not mine) `GradesPage::excludeSuspendedCurrent` suspension-transcript work is being
  handled in another session.
