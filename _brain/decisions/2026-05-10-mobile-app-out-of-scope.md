---
date: 2026-05-10
tags: [decision, scope, mobile]
related: [[../project-context/mobile-app-out-of-scope]]
---

# Mobile app declared out of scope

## Context

During April–May 2026, the `mobile/` directory (React Native / Expo app) was added on the `mobile-test` branch alongside `database/seeders/MobileTestStudentSeeder.php`. Several `_brain/` notes from 2026-05-09 documented mobile design decisions (EduTrack direction, raised center tab bar, pattern screens, polish pass). The graduation project deliverable is the Laravel + Filament web system only — the mobile app was personal experimentation.

## Decision

The mobile app is out of scope for all future work. No analysis, no edits, no commits going forward.

This applies to:
- `mobile/` (entire directory)
- `database/seeders/MobileTestStudentSeeder.php`
- Any API endpoints added exclusively for mobile consumption
- Any future mobile-related ideation

## Alternatives considered

- **Revert all mobile commits and delete the branch.** Rejected — existing commits do no harm; rewriting history is needlessly destructive.
- **Move `mobile/` out of the repo entirely.** Rejected — the branch already exists, simpler to just stop adding to it.
- **Use `git update-index --skip-worktree` on every mobile file.** Not adopted by default. Will revisit only if accidental staging keeps happening.
- **Add `mobile/` to `.gitignore`.** Rejected — `.gitignore` doesn't untrack already-tracked files, and adding it would create misleading repo state. The brain rules + manual care during `git add` are sufficient.

## Consequences

- Project understanding in every future session focuses on Laravel + Filament only.
- Pending uncommitted mobile changes (currently shown in `git status` as `M mobile/...`) will never be staged.
- `MobileTestStudentSeeder` will not be added to `DatabaseSeeder` in any future run.
- Mobile-themed `_brain/` notes from 2026-05-09 are historical reference, not active requirements.
- If the user reverses this decision later, this note should be updated (not deleted) to record the reversal date and reason.

## How to apply

When in doubt during a session: if a file path contains `mobile/` or the word "mobile", stop and re-read this note.
