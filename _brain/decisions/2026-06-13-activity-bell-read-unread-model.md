---
date: 2026-06-13
tags: [decision, bell, ux, livewire, audit-log]
related:
  - "[[../daily-notes/2026-06-13-activity-bell-only-new]]"
  - "[[../daily-notes/2026-06-12-notification-log-viewer-and-activity-bell]]"
---

# Decision — Admin Activity Bell: read/unread model + server-side seen marker

## Context

The bell (added 2026-06-12) showed a fixed "last 15 admin actions" feed with a 24h-count
badge, and tracked "seen" in browser `localStorage`. User wanted it to show **only new**
activity, clear after viewing, and (initially) a Clear button.

## Decision

1. **Inbox ("only new") model, not a persistent feed.** The bell shows only items newer
   than the user's seen marker; empty state when caught up.
2. **Seen state lives server-side, per user** (`users.last_seen_activity_id`), not in
   localStorage.
3. **Clear-on-open, no timer.** Opening the dropdown advances the marker (badge → 0); the
   open items stay visible until the next poll, then drop off.
4. **No Clear button** — redundant once opening clears it.

## Alternatives considered

- **Persistent feed + highlight-new (Gemini's pick).** Rejected: the full audit log
  (`AuditLogResource`) already preserves *all* history one click away, so "items drop off
  the bell" loses nothing — the auditability objection doesn't apply here. User explicitly
  wanted only-new.
- **localStorage marker (status quo).** Rejected: per-browser, not per-user — breaks across
  devices, on cleared browser data, and on shared machines.
- **Timed auto-dismiss ("clear after a few seconds").** Rejected as an anti-pattern: an
  admin who opens the bell then gets distracted could have newly-arriving approvals silently
  marked seen and lost. Clear-on-open gives the same feel without the risk. (Gemini agreed.)
- **Keep the Clear button.** Rejected: user pointed out it does nothing once open already
  clears — redundant control.

## Consequences

- The bell is now a heads-up surface only; history lives solely in the audit log resource.
- One small DB write per open (negligible for ~5 admins).
- `render()` must stay pure (no recompute) so the "stay visible while open" snapshot isn't
  wiped by Livewire 4's post-action re-render. State is driven by `mount`/`refresh`/`markSeen`.

## Council

Consulted via `llm-council`. ChatGPT unavailable (OpenAI 429). Gemini drove points 2 & 3.
