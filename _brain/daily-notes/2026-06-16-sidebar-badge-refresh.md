---
date: 2026-06-16
tags: [filament, navigation, ux, badges, livewire]
related: [[architecture]], [[gotchas]]
---

# Sidebar navigation badges now auto-refresh after actions

## Problem
Sidebar resource badges (`getNavigationBadge()` counts — pending applications,
add/drop, suspension, thesis, discussion, grades, exceptional, survey) stayed
**stale** after an admin took a decision action. They only updated on full page
load / SPA navigation. The user noticed it on `ExamListPage` accept/reject vs.
the in-page specialization numbers that *do* update live.

## Root cause (Filament framework behavior — not a bug)
The sidebar/topbar is a **persistent shell**, a separate Livewire layer from the
page content. Filament does **not** re-render it on every Livewire action — only
on navigation. In-page numbers (like ExamListPage's) live in the page's own
component, so they recompute on each action. Docs:
> "Once a page in the panel is loaded, the sidebar and topbar are not reloaded
> until you navigate away... dispatch a `refresh-sidebar` or `refresh-topbar`
> browser event."

## Fix
Dispatch `refresh-sidebar` from every admin action that mutates a badge-counted
status. Pattern:
- **Page Livewire components** → `$this->dispatch('refresh-sidebar')`
- **Static Resource table closures** → inject `\Livewire\Component $livewire`,
  call `$livewire->dispatch('refresh-sidebar')`

It's a Filament-native event (built-in listener) — **no `npm run build` needed**.

## Files touched (badge → mutating actions)
- `ExamListPage` — markPassed / markFailed (Applications badge)
- `ApplicationResource` — initialApprove, directApprove, bulkInitialApprove;
  `EditApplication::afterSave()` for the status→Rejected edit path
- `AddDropRequestResource` — approve / reject
- `SuspensionRequestResource` + `ViewSuspensionRequest` — approve / reject
- `ThesisRegistrationResource` + `ViewThesisRegistration` — approve / reject
- `ViewDiscussionRequest` — phase1_accept / phase1_reject
- `ViewCourseGrades` — approve, change_status, edit_grade, approve_all (Grade badge)
- `ViewExceptionalStudent` — summon only (accept/reject already `redirect()`,
  which reloads the sidebar)
- `StudentSurveyView` — enrollSelected, rejectSelected, enrollStudent (covers
  approveRetake), rejectRetake, rejectCourse (Survey `is_processed` badge)
- `EnrollStudents` — survey_students, retake_requests, enroll_manual

## Skipped deliberately
- Exceptional accept/reject — already `$this->redirect(...)` → sidebar reloads.
- `unenrollStudent` / unenroll actions — don't flip `is_processed`, so they don't
  change any sidebar badge.
- `CourseOfferingResource`, `ActionCenterWidget` — only **read** counts, never mutate.
- `CoursesSurveyResults::open_selected` — creates offerings, doesn't process surveys.

## Decision: inline dispatch, not a global JS listener
Considered a global Livewire-commit listener that refreshes the sidebar on any
request — rejected: it would re-run all ~8 badge count queries on every keystroke/
sort, contradicting the [[gotchas|N+1 / query-count discipline]]. Targeted dispatch
in decision actions is surgical and only fires on real mutations.

## Verified
`vendor/bin/pint --dirty` clean. Full suite: **361 passed (894 assertions)**.

## Next steps
- Manual smoke: take an add/drop or exam action, confirm the sidebar number ticks
  without refresh.
- Uncommitted — on branch `chore/go-live-high-medium-fixes`.
