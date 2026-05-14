---
date: 2026-05-11
tags: [context, design-system, filament, view-pages, infolist, reusable]
related:
  - "[[design-conventions]]"
  - "[[../decisions/2026-05-11-view-page-design-language-from-figma]]"
  - "[[../daily-notes/2026-05-11-student-view-figma-redesign]]"
---

# Figma view-page design system

> **The reusable pattern for every admin resource view page** (`ViewStudent`, future `ViewApplication`, `ViewCourseOffering`, `ViewSemester`, etc.). Reference implementation: `/admin/students/{id}`. Source files in §Files. Copy from here when migrating a new resource.

## When to apply

Use this on any admin **view** page (`ViewRecord` subclass) where the entity has:
- A clear identity (name / title / code) that should dominate the top
- 3+ fields' worth of info that group naturally into 2–4 thematic sections
- Often a document/image attached, plus related records / history below

## Layout anatomy

```
┌──────────────────────────────────────────────────────────────────┐
│ HERO CARD                                                        │
│  [Print][Edit]                         [Stat chip][status][NAME] │
│                                              رقم القيد: NNNNN     │
│                                  [icon] meta · [icon] meta       │
└──────────────────────────────────────────────────────────────────┘
┌─────────────────────────────┐ ┌────────────────────────────────────┐
│  RIGHT COLUMN (RTL)         │ │  LEFT COLUMN (RTL)                  │
│  ┌───────────────────────┐  │ │  ┌──────────────────────────────┐  │
│  │ Primary info section  │  │ │  │ Section A                    │  │
│  │ (longest — most rows) │  │ │  └──────────────────────────────┘  │
│  └───────────────────────┘  │ │  ┌──────────────────────────────┐  │
│  ┌───────────────────────┐  │ │  │ Section B                    │  │
│  │ Bespoke section       │  │ │  │  (with optional callout)     │  │
│  │ (View::make body)     │  │ │  └──────────────────────────────┘  │
│  └───────────────────────┘  │ │                                    │
└─────────────────────────────┘ └────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────┐
│ Full-width sections (related-data tables, timeline, history)     │
└──────────────────────────────────────────────────────────────────┘
```

> [!tip] Balance the column heights
> Put roughly equal **row counts** into each column. If one side has more sections, distribute one to the other side so neither column ends with empty grid space. See [[#Gotcha — unbalanced columns create gap artifacts]].

## Design tokens (Figma → CSS)

| Token | Value | Notes |
|---|---|---|
| Card border | `#e4e4e7` | zinc-200 |
| Card body bg | `#ffffff` (light) / `rgb(24 24 27)` (dark) | |
| Section header bg | `rgba(244, 244, 245, 0.5)` (light) / `rgba(63, 63, 70, 0.3)` (dark) | |
| Muted text | `#71717a` | zinc-500 |
| Body text | `#09090b` (light) / `#fafafa` (dark) | |
| Primary action bg | `#1F4E79` → hover `#16395a` | The panel brand color |
| Dark-mode primary | `#2f6aa8` → hover `#1F4E79` | |
| Success pill (muted) | bg `#f0fdf4`, border `#dcfce7`, text `#166534` | Adopted 2026-05-13 — replaces the original `#dcfce7 / #008236` saturated set |
| Danger pill (muted) | bg `#fef2f2`, border `#fee2e2`, text `#991b1b` | Callout block still uses brighter `#fef2f2 / #c10007` for warning emphasis |
| Warning pill (muted) | bg `#fffbeb`, border `#fef3c7`, text `#92400e` | |
| Info pill (muted) | bg `#eff6ff`, border `#dbeafe`, text `#1e40af` | |
| Gray pill | bg `#fafafa`, border `#e4e4e7`, text `#52525b` | |
| Card radius | 12px | rounded-xl |
| Section header padding | `12px 20px 13px` | |
| Section body padding | `20px` | |
| Card-to-card gap | `gap-6` (24px) — from Filament Schema | Don't add extra `space-y-*` between sections; let the schema handle it |
| Name (hero) | 24px / 700 / line-height 32 | h1 |
| رقم القيد value | 22px / 700 / `font-variant-numeric: tabular-nums` | |
| Inline-label value | 14px / 400 (or 600 for emails/phones) | |
| Inline-label label | 14px / 400 / muted | |

## Step-by-step: migrate a view page to this pattern

### 1. Back up first

```bash
mkdir -p _backups/{resource-slug}-view-YYYY-MM-DD
cp app/Filament/Resources/{Plural}/Pages/View{Singular}.php \
   _backups/{resource-slug}-view-YYYY-MM-DD/
cp app/Filament/Resources/{Plural}/{Singular}Resource.php \
   _backups/{resource-slug}-view-YYYY-MM-DD/
cp resources/views/filament/resources/{plural}/pages/view-{singular}.blade.php \
   _backups/{resource-slug}-view-YYYY-MM-DD/ 2>/dev/null || true
```

Write a 3-line `cp` revert script in a `README.md` inside that backup folder.

### 2. Override the page chrome (`View{Singular}.php`)

```php
class ViewSomething extends ViewRecord
{
    protected static string $resource = SomethingResource::class;

    // Point to the custom Blade page view
    protected string $view = 'filament.resources.somethings.pages.view-something';

    // Hide the default page title — the hero card carries the identity
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    // Keep meaningful breadcrumb context
    public function getBreadcrumb(): string
    {
        return $this->record->display_name ?? (string) $this->record->getKey();
    }

    // Action buttons live in the hero card, not the page header
    protected function getHeaderActions(): array
    {
        return [];
    }
}
```

### 3. Restructure the infolist (`{Singular}Resource::infolist()`)

```php
public static function infolist(Schema $schema): Schema
{
    return $schema
        ->columns(2)
        ->components([
            // Right column (RTL): primary info + bespoke section
            Grid::make(1)
                ->columnSpan(1)
                ->components([
                    Section::make('السمة الأساسية')
                        ->extraAttributes(['class' => 'fsv-card fsv-card-primary'])
                        ->components([
                            // 5–10 TextEntries, each:
                            TextEntry::make('field_a')
                                ->label('الحقل')
                                ->icon(Heroicon::User)
                                ->inlineLabel()
                                ->placeholder('-'),
                            // ...
                        ]),

                    // Optional: bespoke section with custom Blade body
                    Section::make('الوثيقة / الصورة / المخصص')
                        ->extraAttributes(['class' => 'fsv-card fsv-card-bespoke'])
                        ->components([
                            \Filament\Schemas\Components\View::make(
                                'filament.resources.somethings.partials.bespoke-block'
                            ),
                        ]),
                ]),

            // Left column (RTL): two stacked sections
            Grid::make(1)
                ->columnSpan(1)
                ->components([
                    Section::make('بيانات التواصل')
                        ->extraAttributes(['class' => 'fsv-card fsv-card-contact'])
                        ->components([ /* 3–5 TextEntries */ ]),

                    Section::make('بيانات إضافية')
                        ->extraAttributes(['class' => 'fsv-card fsv-card-extra'])
                        ->components([
                            /* TextEntries */

                            // Optional inline callout (red, info, warning)
                            TextEntry::make('flag_field')
                                ->hiddenLabel()
                                ->state('ملاحظة: ...')
                                ->icon(Heroicon::InformationCircle)
                                ->color('danger')
                                ->extraAttributes(['class' => 'fsv-transfer-callout'])
                                ->visible(fn ($record): bool => (bool) $record->some_flag),
                        ]),
                ]),

            // Full-width sections below
            Section::make('المواد / السجلات / إلخ')
                ->columnSpanFull()
                ->extraAttributes(['class' => 'fsv-card fsv-card-related'])
                ->hidden(fn ($record): bool => $record->relatedItems()->doesntExist())
                ->components([ /* RepeatableEntry, etc */ ]),
        ]);
}
```

### 4. The page Blade view

Save at `resources/views/filament/resources/{plural}/pages/view-{singular}.blade.php`:

```blade
<x-filament-panels::page>
    @php
        /** @var \App\Models\Something $entity */
        $entity = $this->record;
        $entityName = $entity->display_name ?? '—';
        $statusLabel = $entity->getStatusLabel();   // your method
        $statusColor = $entity->getStatusColor();   // your method
        $editUrl = \App\Filament\Resources\Somethings\SomethingResource::getUrl(
            'edit', ['record' => $entity]
        );
    @endphp

    <div class="figma-view-page space-y-6" dir="rtl">

        {{-- HERO CARD --}}
        <section class="fsv-hero">
            <div class="fsv-hero__actions">
                <a href="{{ $editUrl }}" class="fsv-btn fsv-btn--primary">
                    <span>تعديل</span>
                    <x-heroicon-o-pencil-square class="fsv-btn__icon" />
                </a>
                <button type="button" onclick="window.print(); return false;" class="fsv-btn fsv-btn--secondary">
                    <span>طباعة</span>
                    <x-heroicon-o-printer class="fsv-btn__icon" />
                </button>
            </div>

            <div class="fsv-hero__identity">
                <div class="fsv-hero__name-row">
                    <h1 class="fsv-hero__name">{{ $entityName }}</h1>

                    <span @class([
                        'fsv-status-pill',
                        "fsv-status-pill--{$statusColor}",
                    ])>{{ $statusLabel }}</span>

                    {{-- Optional stat chip (e.g. GPA, count, classification) --}}
                </div>

                @if ($entity->main_identifier)
                    <div class="fsv-hero__enrollment">
                        <span class="fsv-hero__enrollment-label">رقم الـ...:</span>
                        <span class="fsv-hero__enrollment-value">{{ $entity->main_identifier }}</span>
                    </div>
                @endif

                <div class="fsv-hero__meta">
                    {{-- Each meta item: icon FIRST in DOM, text SECOND. RTL puts icon at start (right). --}}
                    @if ($entity->location)
                        <div class="fsv-hero__meta-item">
                            <x-heroicon-o-map-pin class="fsv-hero__meta-icon" />
                            <span>{{ $entity->location }}</span>
                        </div>
                    @endif
                    {{-- repeat for other meta fields --}}
                </div>
            </div>
        </section>

        {{-- INFOLIST: rendered with restyled section cards (see CSS below) --}}
        @if($this->hasInfolist())
            {{ $this->infolist }}
        @endif

        {{-- Optional: full-width related-data tables / activity timeline --}}
    </div>

    @push('styles')
    <style>
        /* Page-scoped — every selector under .figma-view-page */
        /* Copy the CSS block from view-student.blade.php and rename the wrapper class */
    </style>
    @endpush
</x-filament-panels::page>
```

> Use a **unique wrapper class per page** (`figma-student-view`, `figma-application-view`, etc.) so the scoped CSS doesn't collide. Same internal class names (`fsv-*`) are fine since they're nested under the wrapper.

### 5. Bespoke section partial (optional)

If a section needs custom HTML (image preview, embedded form, chart, …), put it in `resources/views/filament/resources/{plural}/partials/{slug}.blade.php`:

```blade
@php
    /** @var \App\Models\Something $record */
    // $record is auto-injected by View::make() — DO NOT use $getRecord() (Blade can't resolve it).
@endphp

<div class="fsv-something-doc" x-data="{ open: false }">
    {{-- Your custom markup here --}}
</div>
```

### 6. Scoped CSS — full block to paste

The complete CSS lives in `view-student.blade.php` under `@push('styles')`. Copy it whole, replace the wrapper class (`figma-student-view` → `figma-{your-page}-view`), and tweak the bespoke partial styles as needed.

Key blocks the CSS provides:
- **Hero card** layout (flex with order swapping for RTL)
- **`fsv-btn`** variants — primary (`#1F4E79`), secondary (outlined), and a `--full` modifier
- **`fsv-hero__name-row`** with right-anchored layout, `align-items: flex-start` so RTL pushes content to the right edge
- **`fsv-status-pill`** with 5 muted color variants (palette refreshed 2026-05-13 — see [[../daily-notes/2026-05-13-figma-view-pages-batch]])
- **`fsv-role-pill`** for secondary tags (role, study-type, etc.) — same palette family
- **`fsv-gpa-chip`** as a reference for compound stat chips (tier + divider + value + label), now with toned-down tier label
- **`fsv-hero__enrollment`** big subtitle pattern
- **`fsv-hero__meta`** icon-prefixed items
- **`.fi-section`** overrides for cards (header bar, body padding, row separators)
- **`.fsv-transfer-callout`** red callout inside a Section
- **Print stylesheet** that hides Filament chrome
- **Alpine modal** (`.fsv-preview-modal*`) for full-screen previews

## Rendering recipes (which slot renders what)

### A. Standard view page — infolist only
Use `{{ $this->infolist }}` in the Blade. Best for resources without relation managers (Student before adding any, User).

### B. Relation-manager page — render BOTH infolist + relation managers
Use `{{ $this->content }}` — Filament's `ViewRecord::content()` Schema wraps infolist + every relation manager. **If you use only `{{ $this->infolist }}` on a resource with relation managers, the tables silently disappear.** Confirmed bite on Semester (offerings + decided applications) and Specialization (courses).

### C. Workflow-action-heavy view page
Pages whose page-header actions trigger complex Filament Action modals (publish grades, archive semester, toggle offering, enroll survey, retake requests) MUST keep `getHeaderActions()` populated. Don't try to render those buttons inline in the hero — they need Filament's Action runtime. The hero in these pages carries only identity (and an optional Print button). Confirmed pages: Semester, Grades, EnrollStudents.

For simpler resources (Student / User / Application / Specialization) `getHeaderActions()` returns `[]` and an Edit button lives inside the hero.

## Filament-specific recipes

### Row with icon next to label (RTL)

```php
TextEntry::make('email')
    ->label('البريد الإلكتروني')
    ->icon(Heroicon::Envelope)
    ->inlineLabel()
    ->weight('semibold')    // optional, for emphasized values like emails/phones/IDs
    ->placeholder('-'),
```

The CSS `.figma-view-page .fi-section-content .fi-in-entry-wrp` adds the row separator and consistent padding.

### Bespoke content inside a Section

```php
Section::make('عنوان')
    ->extraAttributes(['class' => 'fsv-card fsv-card-foo'])
    ->components([
        \Filament\Schemas\Components\View::make('filament.resources.foo.partials.bar'),
    ]),
```

In the partial, use `$record` (the model). Do NOT use `$getRecord()` — that's only resolvable in PHP closures.

### Status pill in the hero

```blade
<span @class([
    'fsv-status-pill',
    'fsv-status-pill--success' => $statusColor === 'success',
    'fsv-status-pill--danger'  => $statusColor === 'danger',
    'fsv-status-pill--warning' => $statusColor === 'warning',
    'fsv-status-pill--info'    => $statusColor === 'info',
    'fsv-status-pill--gray'    => ! in_array($statusColor, ['success', 'danger', 'warning', 'info']),
])>{{ $statusLabel }}</span>
```

### Stat chip (e.g. GPA, count, %)

```blade
@if ($tier)
    <span class="fsv-gpa-chip fsv-gpa-chip--{{ $tier['class'] }}" title="...">
        <span class="fsv-gpa-chip__tier">{{ $tier['label'] }}</span>
        <span class="fsv-gpa-chip__divider"></span>
        <span class="fsv-gpa-chip__value">{{ number_format($value, 2) }}</span>
        <span class="fsv-gpa-chip__label">المعدل</span>
    </span>
@endif
```

Variants: `--success`, `--info`, `--warning`, `--danger`. Pick by computing a tier from the underlying value.

### Inline red callout inside a Section

For "this student is a transfer" / "this application is flagged" etc.:

```php
TextEntry::make('flag_field')
    ->hiddenLabel()
    ->state('ملاحظة: ...')
    ->icon(Heroicon::InformationCircle)
    ->color('danger')
    ->extraAttributes(['class' => 'fsv-transfer-callout'])
    ->visible(fn ($record): bool => (bool) $record->some_flag),
```

The `fsv-transfer-callout` CSS overrides the row to be a standalone callout block instead of a label/value row.

## Gotchas

> [!warning] flex-end in RTL column flips to left
> `align-items: flex-end` on a `flex-direction: column` container in RTL pushes children to the **left** (cross-axis end = left in RTL). Use `align-items: flex-start` + `text-align: right` to anchor content to the right edge in RTL. Same with `justify-content: flex-end` on a row — it justifies to the **left** in RTL.

> [!warning] $getRecord() doesn't work inside View::make() partials
> Use `$record` (auto-injected) in Blade partials. The closure-style `$getRecord()` is for PHP-side closure args only. Silent no-op if misused.

> [!warning] Unbalanced columns create gap artifacts
> If one column's sections end visibly higher than the other column's, you'll see an oversized "gap" between the infolist row and the next sibling on the short side. Fix by either (a) putting roughly equal section counts in each column, or (b) moving a related section INTO the short column so the schema's `gap-6` balances heights.

> [!warning] Filament Schema renders with `gap-6` already
> The `Schema` root has `gap-6` (24px) between its direct children when `hasGap()` is true. Don't stack additional `space-y-*` or `mt-*` on infolist children — you'll double-space them.

> [!warning] Filament default disk is `local` not `public`
> Every `FileUpload` needs explicit `->disk('public')`. Don't rely on env defaults — see [[../bugs-and-fixes/2026-05-10-degree-image-403-private-disk]]. The view-page itself just calls `asset('storage/'.$path)` for files on the public disk.

> [!warning] Filament Section header text is RTL-friendly already
> Don't add extra `text-align: right` to the header. The CSS override only needs to ensure `font-size`, `font-weight`, and `color` match the design tokens.

## Components to extract once we have 2+ implementations

Once the pattern is applied to the second resource view page, refactor these to global Blade components in `resources/views/components/admin/`:

- `<x-admin.hero-card name="..." status-label="..." status-color="..." :meta="$metaArray" :actions="$actions" />`
- `<x-admin.status-pill color="success">{{ $label }}</x-admin.status-pill>`
- `<x-admin.stat-chip tier="success" :value="$gpa" tier-label="ممتاز" value-label="المعدل" />`
- `<x-admin.preview-modal :url="$url" alt="..." />`

Promote the page-scoped CSS to global `resources/css/filament/admin/theme.css` at the same time.

## Implementations (2026-05-14)

Eight pages currently use the pattern — see [[../daily-notes/2026-05-13-figma-view-pages-batch]] for the batch session log + [[../daily-notes/2026-05-14-suspension-request-view-page]] for the 8th.

| Page | Wrapper class | Rendering recipe | Notes |
|---|---|---|---|
| `/admin/students/{id}` | `.figma-student-view` | A (infolist) + manual `$this->table` + activity timeline | Original reference |
| `/admin/users/{id}` | `.figma-user-view` | A (infolist) | Faculty subjects hide `name_en` + show academic-rank section ([[../decisions/2026-05-14-user-resource-scope]]) |
| `/admin/applications/{id}` | `.figma-application-view` | A (infolist) | 2-col + full-width docs + admin/exam |
| `/admin/specializations/{id}` | `.figma-specialization-view` | B (content) | Single-card variant (hero + KPI footer band) — diverges, see note in [[../daily-notes/2026-05-13-figma-view-pages-batch]] |
| `/admin/semesters/{id}` | `.figma-semester-view` | B (content) + C (header actions kept) | Has 2 relation managers |
| `/admin/course-offerings/{id}/enroll` | `.figma-enroll-view` | manual `$this->table` + C | Workflow actions in header |
| `/admin/grades/{offering}/grades` | `.figma-grades-view` | manual `$this->table` + C | Workflow actions in header |
| `/admin/suspension-requests/{id}` | `.figma-suspension-view` | A (infolist) | First implementation without a Print button (per [[feedback]]) — set the new default |

> [!note] Print button is no longer the default
> Earlier 2026-05-13 implementations shipped with a Print button in the hero's `.fsv-hero__actions`. User preference (logged 2026-05-14) is to **omit the Print button by default**. New view pages should skip the `<button onclick="window.print()...">` block and the `@media print` CSS. Add the button only if the user explicitly asks for it on a specific page.

> [!note] `/admin/student-enrollments` was deleted on 2026-05-14
> The earlier 8th implementation was removed (the resource is gone). See [[../daily-notes/2026-05-14-user-resource-overhaul]].

## Files

Reference implementation:

```
app/Filament/Resources/Students/StudentResource.php       — infolist (2 column-grids × 2 sections + extra below)
app/Filament/Resources/Students/Pages/ViewStudent.php     — getHeading('') + getBreadcrumb($name) + getHeaderActions([])
resources/views/filament/resources/students/pages/view-student.blade.php   — hero + render infolist + table + timeline + @push('styles')
resources/views/filament/resources/students/partials/degree-document.blade.php  — bespoke partial via View::make()
_backups/student-view-2026-05-11/                          — revert path
```

## Related notes

- [[design-conventions]] — global Filament conventions (still applies on top of this design system)
- [[../decisions/2026-05-11-view-page-design-language-from-figma]] — the decision rationale
- [[../daily-notes/2026-05-11-student-view-figma-redesign]] — the implementation log
- [[gotchas]] — broader Filament-v5 gotchas (Heroicon enum, hintAction, etc.)
