---
date: 2026-04-30
tags: [decision, thesis, discussion, clearance, pipeline]
related: [[../decisions/2026-04-28-thesis-registration-rework]], [[../daily-notes/2026-04-30-session3-discussion-requests]]
---

# Decision: Thesis → مناقشة → Clearance Pipeline

## Context

Previously the flow was: thesis approved → clearance available. This was too simple — the real-world process has a multi-phase discussion (مناقشة) step between thesis approval and clearance eligibility.

## Decision

Insert a 4-phase **Discussion Request** system between thesis approval and clearance:

### Phase Flow

```
Thesis Approved → Student requests مناقشة
  → Phase 1: Admin reviews → Reject (with reason) OR Accept (set date)
  → Phase 2: Discussion happens → Admin marks "تمت المناقشة"
  → Phase 3: Admin decides result → Reject / Accept / Accept with Edits
    - If Accept with Edits → "تم التعديلات" button → proceeds as Accept
  → Phase 4: Admin marks "تم التجليد والتسليم"
  → ONLY NOW → Clearance page unlocks
```

### Status Enum (`DiscussionStatus`)

| Case | Value | Phase |
|---|---|---|
| Pending | pending | 1 |
| Rejected | rejected | 1 |
| Scheduled | scheduled | 2 |
| Discussed | discussed | 3 entry |
| DiscussionRejected | discussion_rejected | 3 |
| AcceptedWithEdits | accepted_with_edits | 3→4 |
| DiscussionApproved | discussion_approved | 4 entry |
| Completed | completed | 4 done |

### Dual Label System

- Admin sees detailed phase labels (8 distinct statuses)
- Student sees simplified labels: قيد المراجعة, مقبول (with date), مرفوض (with reason), تمت الموافقة النهائية

### Clearance Gate Change

- **Before:** `isThesisApproved()` — checks `ThesisStatus::Approved`
- **After:** `isDiscussionCompleted()` — checks `DiscussionStatus::Completed`

### Rejection & Resubmission

- Both Phase 1 and Phase 3 rejections include a reason (textarea)
- Student can resubmit after any rejection (Rejected or DiscussionRejected)

## Alternatives Considered

- Single "approved/rejected" status — too simple for the real workflow
- Separate pages per phase — unnecessary complexity, single detail page with phase-aware actions is cleaner

## Implementation Details

- Admin table shows only latest request per student (`MAX(id) GROUP BY student_id`)
- "تمت المناقشة" button has no date restriction — discussion date is advisory, not a gate
- Student dashboard shows a distinct card for every active phase (6 variants: Pending, Scheduled, Discussed, AcceptedWithEdits, DiscussionApproved, Completed)
- Thesis-approved students without a discussion request see a green prompt card on dashboard
- Completed card links directly to clearance page

## Consequences

- Clearance is no longer available immediately after thesis approval
- Existing students with approved theses but no discussion request will see the clearance page blocked until they go through the discussion flow
- Future: WhatsApp notification when discussion date is set (logged for future implementation)
