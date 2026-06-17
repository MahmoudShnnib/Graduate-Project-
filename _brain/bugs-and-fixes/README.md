---
date: 2026-04-17
tags: [meta, readme, bugs, fixes]
related: [[../../CLAUDE]]
---

# Bugs and Fixes

Record of bugs encountered and how they were resolved — symptoms, root cause, the fix, and anything worth remembering to avoid regressions. Useful when a similar issue reappears months later.

File names use `YYYY-MM-DD-bug-title.md`. Link to the related [[decisions]] or [[daily-notes]] entries, and tag with `#bug`, `#fix`, plus the affected area (e.g. `#enrollment`, `#migration`).

## Index

### 2026-05-09
- [[2026-05-09-noto-kufi-arabic-font-not-applying]] — Filament ignored `--font-sans` Tailwind override; fixed via `LocalFontProvider`

### 2026-06-12
- [[2026-06-12-redundant-whatsapp-notifications-on-approval]] — ExamListPage fired `notifyApplicationExamPassed` + `notifyWelcomeStudent` back-to-back; removed redundant call
- [[2026-06-12-bell-badge-not-clearing]] — Badge didn't clear on close via click-outside; fixed with `$watch('open')` Alpine watcher instead of button click handler

### 2026-06-17
- [[2026-06-17-suspended-student-grade-visibility]] — Suspended (OnHold/Inactive) student's flushed current-term courses still showed grades in كشف الدرجات + المقررات العامة; gated visibility on student status so the visible set matches the GPA-counted set
