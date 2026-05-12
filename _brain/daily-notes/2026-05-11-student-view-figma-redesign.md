---
date: 2026-05-11
tags: [daily, filament, infolist, redesign, figma, students, design-system]
related:
  - "[[../decisions/2026-05-11-view-page-design-language-from-figma]]"
  - "[[../project-context/figma-view-page-design]]"
---

# 2026-05-11 — Student view page redesigned from Figma (reusable pattern)

## What we did

Redesigned `/admin/students/{id}` from a Figma mock-up provided by the user (`PvkSVB2Wa892WD742MiFyv` → node `1:2`). The new layout becomes the **reusable design language** for all admin view pages — see [[../project-context/figma-view-page-design]] for the full spec to copy onto other resources.

### Phase 0 — backup the old page
Before touching anything, copied the three files that compose the current page into `_backups/student-view-2026-05-11/` with a README that has the revert command:
- `app/Filament/Resources/Students/Pages/ViewStudent.php`
- `app/Filament/Resources/Students/StudentResource.php`
- `resources/views/filament/resources/students/pages/view-student.blade.php`

### Phase 1 — initial implementation
- Pulled the Figma design via `get_design_context` (after switching to an upgraded Figma account — the first attempt hit Starter-plan MCP quota).
- Built a custom **hero card** at the top of the page (name, status pill, meta sub-line, Edit/Print buttons).
- Restructured the Filament infolist into 3 Section cards in a 2-col grid: right (RTL) = البيانات الشخصية (full-height), left top = بيانات التواصل والموقع, left bottom = بيانات الدراسة (with red transfer callout).
- Kept الوثيقة العلمية + transfer-courses repeater + enrollment-history table + activity timeline below, restyled to match.
- All TextEntries use `inlineLabel()` + `icon()` to get the row-with-icon pattern.
- Print button → native `window.print()` with `@media print` hiding Filament chrome.
- Removed the default `EditAction` from `getHeaderActions()` since the hero card carries Edit.

### Phase 2 — refinements (over several user feedback rounds)
1. **Removed avatar placeholder** from hero (user request).
2. **Edit button → primary blue** (`#1F4E79`, the panel's brand color) instead of dark slate.
3. **Degree card polish** — extracted into a custom layout with a thumbnail + prominent "عرض بالحجم الكامل" button + Alpine.js full-screen modal; PDF gets a "open PDF" card; empty state with dashed border.
4. **GPA stat block** originally inside the degree card → removed and moved to a chip in the hero name row (tier label + tabular value + "المعدل" label).
5. **Section width** — capped the degree card at 420px, right-anchored. Later moved out of standalone Blade entirely (see Phase 3).
6. **Page heading** — hid Filament's auto-generated "عرض {enrollment_no}" since the hero now serves as the page title (`getHeading()` returns empty string, breadcrumb still uses student name).
7. **RTL bug fix** — the `align-items: flex-end` on the identity column was pushing children to the LEFT in RTL (cross-axis end = left in RTL). Switched to `align-items: flex-start` + `text-align: right` to right-anchor properly. See [[#Gotcha — flex-end in RTL column flips to left]] below.
8. **Hero internals**:
   - Name + chips inline on one row (with `gap: 12px` and extra `margin-inline-end: 12px` on the name for breathing room from the chips cluster).
   - Big prominent `رقم القيد:` subtitle under the name (22px, tabular, neutral text color in both themes).
   - Meta row icons flipped to render on the RIGHT of each item (icon DOM-first, text DOM-second so RTL puts icon at the start).
9. **Section-degree gap fix** — see Phase 3.

### Phase 3 — fix the inconsistent gap before الوثيقة العلمية
The degree section, sitting as a standalone full-width card below the 2-col infolist, had a visually larger gap before it than between the other cards. Root cause: Personal data (right column) ends earlier than Contact+Study (left column), so empty grid space below Personal + the normal 24px gap made the visual distance to the degree card look like ~50px on the right side.

**Fix**: moved the degree section **into the infolist's right column** by wrapping `Section::make('البيانات الشخصية')` + `Section::make('الوثيقة العلمية')` in a `Grid::make(1)->columnSpan(1)`. Both columns now have two stacked sections each → balanced row heights → uniform 24px gap via the schema's built-in `gap-6`.

Since the degree card has a bespoke layout (image + Alpine modal + PDF handling + empty state), extracted its body to a Blade partial at `resources/views/filament/resources/students/partials/degree-document.blade.php` and embedded it via `\Filament\Schemas\Components\View::make('filament.resources.students.partials.degree-document')`.

The View component gets `$record` (the Student) directly per the convention in [[../project-context/design-conventions#Anti-patterns]] (`$getRecord()` is for closures only; views use `$record`).

## Final file set

| File | Role |
|---|---|
| `app/Filament/Resources/Students/StudentResource.php` | Infolist rebuilt — 2 column-grids each holding 2 sections + transfer-courses below |
| `app/Filament/Resources/Students/Pages/ViewStudent.php` | `getHeading()` returns ''; `getBreadcrumb()` uses student name; `getHeaderActions()` returns [] |
| `resources/views/filament/resources/students/pages/view-student.blade.php` | Hero card, infolist render, table, activity timeline, all under `.figma-student-view` scope with `@push('styles')` |
| `resources/views/filament/resources/students/partials/degree-document.blade.php` | New — degree thumbnail + button + Alpine modal + PDF card + empty state |
| `_backups/student-view-2026-05-11/` | Revert path |

## Gotchas discovered

> [!warning] flex-end in RTL column flips to left
> When `flex-direction: column` is used inside an RTL document, `align-items: flex-end` aligns children to the cross-axis END which is the **left** side in RTL — the opposite of what one might intuit. Use `align-items: flex-start` for "anchor to the right edge in RTL," or use logical equivalents (`align-items: start`). Same for `justify-content: flex-end` on a row flex in RTL — it justifies to the **left**.

> [!warning] $getRecord() vs $record inside View::make() partials
> Schema components rendered through `View::make('blade.path')` expose the Eloquent record as `$record` (auto-injected by `Component::getExtraViewData()`). The closure-style `$getRecord()` does NOT work in Blade — it's only for PHP-side `fn ($getRecord) => ...` closures. If you use `$getRecord()` in a Blade partial, you get a silent no-op (the call returns null because the closure binding isn't in scope).

> [!warning] Standalone DOM siblings + Filament Schema gap don't add up evenly
> If you mix a Filament Schema (which has its own `gap-6` internal spacing) with sibling DOM elements outside the schema, the gaps look uneven when the schema's columns have unbalanced heights. The cleanest fix is to put related content INSIDE the schema as a Section so the schema's grid balances row heights for you.

## Decision logged

[[../decisions/2026-05-11-view-page-design-language-from-figma]] — the design pattern itself (hero + 2-stacked-columns + Filament-Section-via-View::make) is now the convention for all admin view pages.

## Reusable pattern logged

[[../project-context/figma-view-page-design]] — full implementation spec with code snippets. Copy-paste-friendly when redesigning the other resource view pages.

## Next steps

- [ ] Apply the same pattern to `/admin/applications/{id}`, `/admin/course-offerings/{id}`, `/admin/semesters/{id}`, and the other resource view pages as the user prefers.
- [ ] Consider promoting the hero card + chips/pills to reusable Blade components (e.g. `<x-admin.entity-hero>`) once we have 2+ implementations.
- [ ] CLAUDE.md may want a short note pointing future sessions at [[../project-context/figma-view-page-design]] for view-page work.
