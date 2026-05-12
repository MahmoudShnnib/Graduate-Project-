---
date: 2026-05-11
tags: [decision, design-system, filament, view-pages, infolist, students]
related:
  - "[[../daily-notes/2026-05-11-student-view-figma-redesign]]"
  - "[[../project-context/figma-view-page-design]]"
  - "[[../project-context/design-conventions]]"
---

# Adopt the Figma view-page design language for all admin detail pages

## Context

The user provided a Figma design (`PvkSVB2Wa892WD742MiFyv`, node `1:2`) for `/admin/students/{id}` and explicitly said it would be reused for the other resource view pages. Before this, admin view pages used Filament's default infolist appearance — functional but visually generic, with section headers as plain text and column-based fields stacked vertically.

The new design introduces a richer "identity card + two-column info grid" pattern that's more scannable and looks intentional for an Arabic-first RTL admin panel.

## Decision

The pattern below becomes the convention for **every admin resource view page** (`ViewStudent`, future `ViewApplication`, `ViewCourseOffering`, `ViewSemester`, etc.). Full reusable spec lives in [[../project-context/figma-view-page-design]].

### Page layout

```
┌──────────────────────────────────────────────────────────────────┐
│ HERO CARD                                                        │
│  [Print][Edit]                            [GPA chip][status][NAME]│
│                                                  رقم القيد: NNNNN│
│                                       [icon] meta · [icon] meta  │
└──────────────────────────────────────────────────────────────────┘
┌─────────────────────────────┐ ┌────────────────────────────────────┐
│  RIGHT COLUMN (RTL)         │ │  LEFT COLUMN (RTL)                  │
│  ┌───────────────────────┐  │ │  ┌──────────────────────────────┐  │
│  │ Primary info section  │  │ │  │ Section A                    │  │
│  │ (tall — most fields)  │  │ │  └──────────────────────────────┘  │
│  └───────────────────────┘  │ │  ┌──────────────────────────────┐  │
│  ┌───────────────────────┐  │ │  │ Section B                    │  │
│  │ Document / extra      │  │ │  │  (with optional callout)     │  │
│  │ (bespoke content)     │  │ │  └──────────────────────────────┘  │
│  └───────────────────────┘  │ │                                    │
└─────────────────────────────┘ └────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────┐
│ Full-width sections (related data tables, history, timeline)     │
└──────────────────────────────────────────────────────────────────┘
```

### Core rules

1. **Hide Filament's default page heading.** Override `getHeading()` to return `''` and rely on the hero card for identity. Set `getBreadcrumb()` to the entity's display name so navigation context is preserved.
2. **Empty `getHeaderActions()`.** Action buttons live inside the hero card.
3. **Hero card is custom Blade** at the top of the page Blade view (not part of the infolist). Holds: entity name, status pill, optional stat chips, action buttons, and a meta row with icon-prefixed items.
4. **Infolist uses two stacked column-grids** (`Grid::make(1)->columnSpan(1)` × 2), one per visual column. Each column holds 2+ Sections. This balances row heights and gives uniform `gap-6` spacing.
5. **Filament `Section` is the only card primitive.** Each gets `extraAttributes(['class' => 'fsv-card fsv-card-{slug}'])` so page-scoped CSS can target it without leaking.
6. **TextEntry style: `inlineLabel()` + `icon()` + `placeholder('-')`.** Produces the row-with-icon pattern shown in the Figma.
7. **Bespoke content inside a Section** goes through `\Filament\Schemas\Components\View::make('partial.path')`. The partial accesses `$record` (NOT `$getRecord()` — that doesn't work in Blade).
8. **Page-scoped CSS** lives in the page Blade's `@push('styles')` block, with all selectors prefixed by `.figma-student-view` (or equivalent wrapper class per resource) so the restyle never leaks.
9. **Primary blue is `#1F4E79`** (from `Color::hex('#1F4E79')` in `AdminPanelProvider`). Brand color for the primary `تعديل` button and any prominent numbers.
10. **Print stylesheet** in the same `<style>` block. Hides `.fi-sidebar`, `.fi-topbar`, `.fi-header`, `.fi-breadcrumbs`, hero action buttons, and the Alpine modals.

### Reusable building blocks

| Block | Class prefix | Purpose |
|---|---|---|
| Hero card | `.fsv-hero`, `.fsv-hero__name`, `.fsv-hero__name-row`, `.fsv-hero__chips-row`, `.fsv-hero__enrollment`, `.fsv-hero__meta` | Entity identity + actions + stats |
| Action buttons | `.fsv-btn`, `.fsv-btn--primary`, `.fsv-btn--secondary`, `.fsv-btn--full` | Dark slate primary + outlined secondary, primary uses `#1F4E79` |
| Status pill | `.fsv-status-pill`, `.fsv-status-pill--{success/danger/warning/info/gray}` | Like Filament badge but with tighter padding for inline-with-name use |
| Stat chip | `.fsv-gpa-chip`, `.fsv-gpa-chip--{tier}` | Combo chip — colored tier label + divider + tabular value + muted label |
| Section card | `.fi-section` overrides scoped under `.figma-student-view` | Light header bar + body row separators |
| Transfer callout | `.fsv-transfer-callout` | Red callout block inside a Section (for highlighted notes) |
| Alpine modal | `.fsv-preview-modal*` | Full-screen image preview, ESC + backdrop close |

## Alternatives considered

| Option | Why rejected |
|---|---|
| **Stay with Filament's default infolist look** | The user provided a Figma — explicit preference for the bespoke design. |
| **Build the entire view as custom Blade (no infolist)** | Loses Filament's plumbing (per-entry conditional visibility, badge formatting, `hintAction`, eager loading). Filament infolist + targeted CSS overrides + `View::make` for bespoke blocks is a better cost/value trade. |
| **Page-level CSS in `resources/css/filament/admin/theme.css`** | Would leak the design to every resource. Scoped `@push('styles')` keeps it page-local until we explicitly adopt the pattern on a new page. |
| **Extract hero + chips into reusable Blade components immediately** | Premature — wait until 2+ implementations have stabilized the API. Once we apply this pattern to the second resource, refactor to `<x-admin.entity-hero>` etc. |
| **Use Filament's `getHeader()` instead of inline-Blade hero** | The `getHeader()` slot lives outside the page content area and doesn't play well with full-bleed background cards. The current approach keeps everything in one composable Blade. |

## Consequences

- The pattern is **opt-in per page** — old view pages keep their default look until explicitly migrated.
- Adding a new resource view page that uses this pattern requires: (a) custom Blade page view, (b) wrapping class, (c) `getHeading('')` + `getHeaderActions([])`, (d) restructured infolist. Roughly an hour per page given the spec exists.
- CSS lives in the page Blade, not the theme CSS — keeps things scoped but means **no Tailwind JIT** for these specific styles. Plain CSS in the `<style>` block. That's fine for the small surface area.
- **Future migration path**: when 2+ pages have implemented this, promote the hero, chips, and section CSS to global classes in `resources/css/filament/admin/theme.css` and document the migration in a follow-up decision note.

## Files touched by this decision

See [[../daily-notes/2026-05-11-student-view-figma-redesign]] for the full file list. Reference implementation:
- `app/Filament/Resources/Students/StudentResource.php` (infolist)
- `app/Filament/Resources/Students/Pages/ViewStudent.php` (page chrome)
- `resources/views/filament/resources/students/pages/view-student.blade.php` (hero + scoped CSS)
- `resources/views/filament/resources/students/partials/degree-document.blade.php` (bespoke section body)
