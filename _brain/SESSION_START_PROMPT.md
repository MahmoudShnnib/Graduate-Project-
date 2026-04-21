# Session Start Prompt

> Copy the block below verbatim and paste as the **first message** of a new Claude Code session. It front-loads the vault read, enforces the "match-don't-invent" rule, and makes every error + edit flow back into `_brain/` automatically.

---

## The prompt

```
# Session setup — do this BEFORE any task work

You are picking up a long-running Laravel 12 + Filament v5 graduate-studies
project. The _brain/ vault contains extensive session history that answers
most "how should I do this?" questions. Read it thoroughly before writing
any code.

## Phase 0 — required reads, in this order

1. `CLAUDE.md` at the project root — the 7 Core Working Principles override
   everything else. Principle #7 points at design conventions; follow it.

2. `_brain/project-context/project-overview.md` — what the system does,
   which models exist, what's incomplete.

3. `_brain/project-context/architecture.md` — component map, observer
   layer, request lifecycle diagrams.

4. `_brain/project-context/gotchas.md` — every non-obvious trap this
   codebase has. Skim every heading; if any matches your task domain
   (enrollment, grades, surveys, suspension, Shield, Livewire, Vite,
   Filament tables, observers), READ that entry in full.

5. `_brain/project-context/design-conventions.md` — this is the
   non-negotiable reference for every UI + code pattern. Colors,
   badges, banners, modals, drill-ins, typed exceptions, observer
   cascades, idempotency — all here. **Match, don't invent.**

6. `_brain/research/2026-04-20-agent-mistakes-and-lessons.md` — 10+
   documented mistakes from past sessions with "what happened / why
   wrong / correct default". Don't repeat them.

7. The **3 most recent** files in `_brain/daily-notes/` (sort by
   filename date, newest first). These tell you what the last few
   sessions did and what's in flight.

8. Any file in `_brain/decisions/` and `_brain/bugs-and-fixes/` whose
   title matches your task domain. (Grep the dir — open what's
   relevant.)

## Phase 1 — prove context, then ask

Before touching code, reply with:

- **5-bullet summary**: what we're working on · what's in flight · open
  decisions · known bugs still pending · recommended next step.
- **Clarifying questions** for anything ambiguous in the user's request.
  The project standard is "10 questions > 10 wrong implementations" —
  err on the side of asking.
- **Explicit acknowledgement** of which pinned/deferred tasks exist
  (check the daily notes for "PINNED" markers — currently T1 attachment
  popup is pinned with 3 next-attempt approaches documented).

Do not proceed to implementation until the user confirms your
understanding.

## During implementation — match, don't invent

For every UI element you add (button, banner, modal, badge, toggle):
- Check `design-conventions.md` first. If a pattern exists, use it.
- If a pattern doesn't exist, ask the user which existing page's
  style to mirror — don't guess.

For every domain invariant or state guard:
- Check `design-conventions.md` section on typed exceptions +
  Action-guard-plus-Observer-cascade pattern.
- New exception goes in `app/Exceptions/{Name}Exception.php`.
- All enrollment call sites (4 of them) catch every enrollment-
  domain exception. Don't miss one.

For every observer:
- Register in `AppServiceProvider::boot()`.
- Transition detection uses `wasChanged()` + `getOriginal()` both —
  see the gotchas entry.

For every Filament page:
- Resource-owned CRUD → Resource pages.
- Cross-cutting drill-ins → standalone `Filament\Pages\Page` with
  `getRoutePath()`; `$shouldRegisterNavigation = false`.
- Reusing a Resource's `table($table)` from a standalone Page
  requires `->query(fn () => ...)` — non-optional.

For every Livewire input driving `->disabled()` / `->visible()`:
- `wire:model.live` — NEVER plain `wire:model`.

## Write EVERYTHING back to the vault

This is not optional. After any meaningful action:

**Per task (no matter how small):**
- Append a section to `_brain/daily-notes/YYYY-MM-DD-<topic>.md` (today's
  file — create if it doesn't exist, with YAML frontmatter: date, tags,
  related). Include: what was done, why, files touched (with line
  anchors), tests added, gotchas discovered.

**Per bug fixed:**
- Create `_brain/bugs-and-fixes/YYYY-MM-DD-<slug>.md` with:
  - Symptoms (what the user saw)
  - Root cause (actual explanation, not hand-wave)
  - Fix (code diff summary + file:line anchors)
  - Regression guard (test added or invariant documented)
- Link from today's daily note.

**Per architecture / schema decision:**
- Create `_brain/decisions/YYYY-MM-DD-<title>.md` with:
  - Context (why the decision exists)
  - Decision (what was chosen)
  - Alternatives considered (with why each was rejected)
  - Consequences (positive + negative)
- Link from today's daily note.

**Per new error/trap discovered** (something that would bite a future
session):
- Append to `_brain/research/2026-04-20-agent-mistakes-and-lessons.md`
  as the next numbered Mistake, with: what happened / why wrong / how
  to recognize / correct default. Use the same format as existing entries.
- If it's a project-wide pattern (not agent-specific), add to
  `_brain/project-context/gotchas.md` instead.

**Per new design pattern** (you created a UI shape that will be reused):
- Add a section to `_brain/project-context/design-conventions.md`
  matching the existing structure: file reference, structural pattern,
  when to use.

**Never duplicate** — always link. `[[wikilinks]]` between notes.

## End-of-session protocol

Before closing the session:
- Confirm the daily note is saved with: what was done, tests added,
  bugs fixed (linked), decisions made (linked), loose ends / next
  steps.
- Run `vendor/bin/pint --dirty --format agent` (formatting).
- Run `php artisan test --compact` (regression) — do not leave a failing
  suite.
- Remind the user to commit + push.

## Current state reference (as of 2026-04-21)

- Branch likely `task-session/2026-04-20-multi-fix` or a new branch
  forked from it. Check `git branch --show-current`.
- 78 tests passing.
- T1 (attachment popup on student view) is PINNED with 3 next-attempt
  approaches in the daily note — revisit when ready.
- Domain invariants enforced at Action layer: enrollment cap (4),
  specialization, semester-suspension, closed-offering. Four typed
  exceptions in `app/Exceptions/`. Four observers registered. See
  `architecture.md` observer table.

Now — read the vault, reply with your 5-bullet context summary + any
clarifying questions, and wait for confirmation before writing code.
```

---

## Why this prompt exists

The 2026-04-20 session hit 10 distinct classes of mistakes, most of which were **already documented in the vault but not consulted up-front**. This prompt forces the pre-read so future sessions don't re-walk the same footguns. The vault has:

- Full answers for "what color should this badge be?" / "how do I style a danger banner?" (in `design-conventions.md`)
- Full explanations for "why does `wire:model` not trigger the disabled state to refresh?" (in `gotchas.md` + `agent-mistakes-and-lessons.md`)
- Patterns for "where does this typed exception go?" / "which enrollment call sites need the catch?" (in `design-conventions.md`)

A session that skips this step will re-hit the same traps and waste your time. The prompt + Principle #7 in CLAUDE.md together enforce the read.

## Usage tips

- **Paste the whole prompt** as the first message. Don't condense.
- If the session is for a **specific task**, add a short line after the
  prompt: "Today's task: {one-sentence description}." Claude will ask
  clarifying questions as part of Phase 1.
- **Don't skip Phase 1** — the 5-bullet summary is your audit that
  context actually loaded. If Claude hallucinates anything there, catch
  it before code starts.
- If Claude resists writing back to the vault ("this is a small task"),
  remind it — the vault IS the project memory; skipping the write means
  the next session re-hits the same problem.
