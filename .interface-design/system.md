# Admin Panel Design System
> Graduate Student Management System — Filament v5, Laravel 12, RTL/Arabic

---

## 1. Color System

The panel uses **Filament semantic color tokens exclusively**. Never hardcode raw Tailwind colors (e.g. `green-500`) — use the named tokens below. This ensures dark mode compatibility and visual consistency.

| Token | Meaning | Use when |
|---|---|---|
| `success` | Positive, active, approved | Active students, approved requests, passing grades, open semester |
| `warning` | Pending, needs attention | Pending applications, pending grade approval, awaiting action |
| `danger` | Rejected, failed, inactive | Rejected applications, failed grades (F), withdrawn/suspended |
| `info` | Informational, in-progress | Initially approved, specialization badges, chart metrics |
| `gray` | Neutral, secondary | Study type, draft state, toggleable secondary data |

**Primary brand color:** `#1F4E79` (deep institutional blue) — set in `AdminPanelProvider`, shared by both admin and student panels.

---

## 2. Typography & Layout

- **Font:** `Instrument Sans` (via `app.css` `@theme`)
- **Direction:** RTL (Arabic), locale `ar`
- **Language:** All labels, headings, confirmations, and notifications in Arabic

---

## 3. Navigation

### Groups (Admin Panel)
- `الإعدادات الأكاديمية` — Courses, Semesters, Specializations, CourseOfferings
- `إدارة الطلاب` — Students, Grades, Enrollments, Requests

### Icon Convention
- Navigation icons: **Outlined Heroicons** (`Heroicon::OutlinedCalendar`, `Heroicon::OutlinedBookOpen`)
- Action icons: **Solid/default Heroicons** (`Heroicon::Check`, `Heroicon::XMark`, `Heroicon::Pencil`)
- Stat widget icons: `heroicon-o-*` string format

### Sorting
Use `protected static ?int $navigationSort` to control ordering within groups.

### Conditional Navigation
Use `shouldRegisterNavigation(): bool` for context-sensitive pages (e.g. AddDrop only shows when the period is open).

---

## 4. Table Patterns

### Columns
```php
// Primary identifier — always searchable
TextColumn::make('name')->label('الاسم')->searchable()->sortable()

// Status — always badge + semantic color
TextColumn::make('status')->label('الحالة')
    ->badge()
    ->color(fn (StatusEnum $state): string => match ($state) {
        Status::Pending  => 'warning',
        Status::Approved => 'success',
        Status::Rejected => 'danger',
    })
    ->formatStateUsing(fn (StatusEnum $state): string => $state->label())

// Boolean column
IconColumn::make('is_active')->label('نشط')->boolean()

// Secondary/optional columns
TextColumn::make('phone')->label('النقال')->toggleable()->placeholder('-')

// Dates
TextColumn::make('created_at')->label('التاريخ')->date()->sortable()

// Numeric with badge
TextColumn::make('gpa')->badge()
    ->color(fn (string $state): string => (float) $state >= 3.0 ? 'success' : 'danger')
```

### Defaults
- `->defaultSort('created_at', 'desc')` on listing tables
- `->placeholder('-')` for nullable columns
- `->toggleable()` on secondary/less-important columns

### Filters
- Use `SelectFilter` for status and categorical fields
- Use `->relationship()` for relational filters

---

## 5. Record Actions Pattern

Actions always follow this order: **workflow actions first → CRUD last**.

```php
->recordActions([
    // 1. Workflow / approval actions (conditional visibility)
    Action::make('approve')
        ->label('قبول')->icon(Heroicon::Check)->color('success')
        ->requiresConfirmation()
        ->modalHeading('...')->modalDescription('...')
        ->action(fn (...) => ...)
        ->visible(fn (Model $record) => $record->status === Status::Pending),

    Action::make('reject')
        ->label('رفض')->icon(Heroicon::XMark)->color('danger')
        ->requiresConfirmation()
        ->visible(fn (Model $record) => $record->status === Status::Pending),

    // 2. Standard CRUD
    ViewAction::make(),
    EditAction::make(),
    DeleteAction::make(),
])
->toolbarActions([
    // Bulk actions
    DeleteBulkAction::make(),
])
```

**Action color rules:**
- Approve/positive: `success`
- Reject/negative: `danger`
- Edit/neutral change: `info`
- Close/lock: `warning`
- Open/unlock: `success`

---

## 6. Form (Schema) Patterns

### Section Structure
```php
Schema::make()->components([
    // Header section — compact, full-width summary
    Section::make()->columnSpanFull()->compact()->components([
        Grid::make(['default' => 2, 'sm' => 4])->components([...])
    ]),

    // Content sections — numbered in Arabic convention
    Section::make('أولاً: البيانات الشخصية')->columnSpanFull()->columns(3)->components([...]),
    Section::make('ثانياً: بيانات التواصل')->columnSpan(1)->columns(1)->components([...]),
    Section::make('ثالثاً: نوع الدراسة')->columnSpan(1)->columns(1)->components([...]),
    Section::make('رابعاً: المستندات')->columnSpanFull()->columns(2)->components([...]),
])
```

### Arabic Section Numbering Convention
Use ordinal prefixes for multi-section forms:
`أولاً:` / `ثانياً:` / `ثالثاً:` / `رابعاً:` / `خامساً:` ...

### Column Span Rules
- Default section: `->columns(2)`
- Wide fields (textarea, address, full-row): `->columnSpan(2)` or `->columnSpanFull()`
- Side-by-side sections: left `->columnSpan(1)`, right `->columnSpan(1)` within a 2-col schema
- Full-width sections: `->columnSpanFull()`

### Conditional Visibility
```php
->visible(fn ($get): bool => $get('study_type') === 'internally_delegated')
->visibleOn('create')  // show only on create page
```

---

## 7. Infolist (View Page) Patterns

```php
// Bold primary identifiers
TextEntry::make('enrollment_no')->label('رقم القيد')
    ->weight(FontWeight::Bold)->copyable()

// Status badge
TextEntry::make('status')->label('الحالة')
    ->badge()->color(fn (StatusEnum $state) => ...)->formatStateUsing(...)

// Boolean
IconEntry::make('is_transfer')->label('منتقل من كلية أخرى')->boolean()

// File links
TextEntry::make('degree_image')
    ->formatStateUsing(fn (?string $state) => $state ? '📄 '.basename($state) : '—')
    ->url(fn (?string $state) => $state ? asset('storage/'.$state) : null)
    ->openUrlInNewTab()

// Empty state: em dash (—) NOT hyphen (-)
->placeholder('—')
```

---

## 8. Status/Badge Color Map (Cross-Resource Standard)

This color mapping is used consistently across all resources. **Never deviate** without a strong reason.

```php
// Application status
Pending          => 'warning'
InitiallyApproved => 'info'
Accepted         => 'success'
Rejected         => 'danger'

// Student status
Active           => 'success'  (implied)
Suspended        => 'warning'  (implied)
Withdrawn        => 'danger'   (implied)

// Grade status
Draft            => 'gray'
PendingApproval  => 'warning'
Approved         => 'success'

// Grade letter
A                => 'success'
B+, B            => 'info'
C+, C            => 'warning'
D+, D            => 'gray'
F                => 'danger'

// AddDrop type
Add              => 'success'
Drop             => 'danger'

// Generic pending/approved/rejected
pending          => 'warning'
approved         => 'success'
rejected         => 'danger'
```

---

## 9. Dashboard Widgets

### StatsOverviewWidget Pattern
```php
Stat::make('Label in Arabic', $count)
    ->description('Secondary info string')
    ->color('success|warning|danger|info')
    ->icon('heroicon-o-icon-name')
```

- 4 stats on the main dashboard: active students, pending applications, pending grades, current semester
- Color reflects urgency: active=success, pending=warning, missing=danger

---

## 10. Architecture Conventions

### File Organization
```
app/Filament/Resources/ModelName/
├── ModelNameResource.php        # Main resource (navigation, pages list)
├── Pages/
│   ├── ListModelNames.php
│   ├── CreateModelName.php
│   ├── EditModelName.php
│   └── ViewModelName.php        # Only if view page needed
├── Schemas/                     # Extract when form/infolist is complex
│   ├── ModelNameForm.php        # static configure(Schema): Schema
│   └── ModelNameInfolist.php
└── Tables/                      # Extract when table is complex
    └── ModelNamesTable.php      # static configure(Table): Table
```

### When to Extract vs Inline
- **Inline** form/table when the resource is simple (Semester, AddDrop)
- **Extract** to `/Schemas/` and `/Tables/` when complex (User, Application)

### Navigation Group Declaration
```php
protected static string|\UnitEnum|null $navigationGroup = 'اسم المجموعة';
```

### Enum Labels
All enums should implement a `label(): string` method returning Arabic text. Enums are used directly in `->options(EnumClass::class)`.

---

## 11. Notification Pattern

Always use `Filament\Notifications\Notification` for user feedback:
```php
Notification::make()->title('رسالة النجاح')->success()->send();
Notification::make()->title('خطأ')->body($e->getMessage())->danger()->send();
```

---

## 12. What NOT to Do

- ❌ Don't use raw Tailwind color classes (`text-green-500`, `bg-blue-700`) in Filament components
- ❌ Don't hardcode Arabic status strings in tables — use `formatStateUsing()` + enum `label()`
- ❌ Don't skip `requiresConfirmation()` on destructive or irreversible actions
- ❌ Don't add navigation icons that are filled/solid for nav items — use Outlined variants
- ❌ Don't use English placeholders — all UI text must be Arabic
- ❌ Don't use hyphen `-` as placeholder in infolists — use em dash `—`
