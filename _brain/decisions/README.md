---
date: 2026-04-17
tags: [meta, readme, decisions, adr]
related: [[../../CLAUDE]]
---

# Decisions

Architecture and design decisions — the *why* behind non-obvious choices that future readers (and future-you) will otherwise have to reverse-engineer from code. Think lightweight ADRs: context, decision, consequences.

File names use `YYYY-MM-DD-decision-title.md`. Each note should link back to the [[daily-notes]] entry it came from and forward to any follow-up [[bugs-and-fixes]] or [[research]]. Tag with `#decision` plus the domain (e.g. `#enrollment`, `#auth`).

## Index

### 2026-04-20
- [[2026-04-20-survey-rejection-via-timestamp]] — Survey rejection stored as null timestamp, not status field

### 2026-04-21
- [[2026-04-21-closed-offering-enforcement]] — Prevent enrollment in closed course offerings
- [[2026-04-21-student-status-unification]] — Unified student status model

### 2026-04-23
- [[2026-04-23-student-status-restructure]] — Status enum restructure + gate system

### 2026-04-25
- [[2026-04-25-course-retake-system]] — Retake eligibility and grade replacement rules
- [[2026-04-25-thesis-eligibility-rewrite]] — Thesis eligibility gate rewrite

### 2026-04-27
- [[2026-04-27-warning-system-and-dismissal]] — Warning accumulation + auto-dismissal at threshold

### 2026-04-28
- [[2026-04-28-thesis-registration-rework]] — Thesis registration flow rework

### 2026-04-30
- [[2026-04-30-discussion-request-pipeline]] — Discussion request multi-step pipeline
- [[2026-04-30-exceptional-detail-separate-page]] — Exceptional request detail on its own page

### 2026-05-03
- [[2026-05-03-grade-publishing-system]] — Two-timestamp grade publishing (midterm vs final)

### 2026-05-08
- [[2026-05-08-audit-log-custom-list-view]] — Custom Livewire list view for audit logs

### 2026-05-09
- [[2026-05-09-local-font-provider-for-filament]] — Use `LocalFontProvider` to apply Noto Kufi Arabic system-wide in Filament panels
- [[2026-05-08-auto-inactive-survey-close]] — Auto-set Inactive on survey close + semester reset
- [[2026-05-08-enrollment-number-regen-on-spec-change]] — Re-issue enrollment number on specialization change
- [[2026-05-08-course-offering-form-improvements]] — Lock active semester + filter taken courses on create
- [[2026-05-08-transfer-course-deduplication]] — Hide already-selected courses in معادلة repeater
- [[2026-05-08-suspension-request-blocking-rules]] — Approved=permanent block, rejected=semester block
- [[2026-05-08-nav-badge-pending-counts]] — Pending count badges on 7 admin sidebar nav items
- [[2026-05-08-remove-audit-log-restore]] — Removed restore action from audit logs (observer-unsafe)
