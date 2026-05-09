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
