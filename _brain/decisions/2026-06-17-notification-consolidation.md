---
date: 2026-06-17
tags: [decision, notifications, whatsapp]
related: [[2026-06-16-single-final-grade-publish]]
---

# Notification consolidation: 10 → 4 student-facing messages

## Context
The system had 10 notification classes; several were noise (per-course grade spam,
request-decision pings) and two were dead code (`CourseworkGradePublished` already removed
in Task 3; `ClearanceIssued` never dispatched). The user wanted a lean set.

## Decision — final surviving notifications (4)
1. **ApplicationInitiallyApproved** — unchanged (first approval + exam date + tracking link).
2. **WelcomeStudent** — now MERGES the old "exam passed" (#3) message: opens with
   "اجتزت اختبار القبول وتم قبولك رسمياً…" then enrollment-no + email. **No password line**
   (the `$plainPassword` param was dropped from both the notification and the service).
   Fires once on full approval via `ApproveApplication`.
3. **GradesPublished** (new) — ONE general per-student message ("تم نشر درجاتك… راجع البوابة")
   sent when an admin publishes the term's finals. Personalized with `{name}`. Replaces the
   old per-course `GradePublished`. Sent once per unique student inside the publish loop in
   `ViewCourseGrades` + `ViewSemester`.
4. **ContactInfoChanged** — kept (security alert; generic wording, routed to a bare number).

## Removed (7 classes + service methods + call sites)
ApplicationReceived, ApplicationExamPassed (folded into Welcome), GradePublished (replaced),
ThesisDecision, SuspensionDecision, AddDropDecision, ClearanceIssued. All `notify*` methods and
their closure call sites (Thesis/Suspension/AddDrop resources + view pages, RegistrationController)
were removed, along with now-unused `NotificationService` imports/params.

## Other touch-points
- `NotificationLogResource::EVENT_LABELS` trimmed to the live events (+ added `contact_info_changed`).
- `app/Console/Commands/WhatsAppTest.php` preview command updated to the 4 survivors.
- Tests: `GradeCourseworkFlowTest` asserts `GradesPublishedNotification`; `NotificationChannelsTest`
  gained a combined-Welcome content assertion (exam-pass + details, no password).

## Verification
Notification suite + full suite green for this work (385 passed). The single failing test
(`StudentStatusGateTest` "keeps approved grades visible after cascade") is UNRELATED — caused by
an uncommitted same-day `GradesPage::excludeSuspendedCurrent()` change, not this overhaul.
