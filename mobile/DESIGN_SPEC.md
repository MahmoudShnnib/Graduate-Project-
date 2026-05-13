# DESIGN_SPEC.md — بوابة الطالب (Student Portal)

A mobile app for graduate students at a Libyan university faculty. Companion to the existing Filament admin/student web system.

This spec was rewritten on 2026-05-09 to adopt the **EduTrack** visual language (Figma Make: `Cp2Axhf5mJzPeWW2z1GLJw`) — lifted shadow cards, blue hero headers, per-feature accent tiles. Replaces the previous "institutional/banking" direction.

---

## CORE DESIGN RULES (apply to every screen)

**Layout direction:** RTL throughout. All content flows right-to-left, all text right-aligned. Navigation icons mirrored where it carries semantic meaning.

**Style:** Friendly-professional, lifted, soft. Cards float over the page background with subtle shadows — no hairline borders. Each feature carries its own accent tone (blue / purple / green / orange / sky / red / amber). Every authenticated screen opens with a full-width blue hero band and the first content card overlaps into it.

**Platform:** iOS and Android (use native patterns where appropriate — bottom tab bar, labeled icons).

**Language:** All UI text, labels, headings, buttons, and placeholders are in Arabic. English appears only in the enrollment number field (e.g. "26CS101").

**Font:** Noto Kufi Arabic — used everywhere. Weights: 700 for headings, 500 for labels and buttons, 400 for body.

### Colors (`src/theme/tokens.ts`)

| Token            | Hex       | Usage                                                  |
|------------------|-----------|--------------------------------------------------------|
| Primary          | `#0066FF` | Hero header, CTA buttons, active tab, links, big stat  |
| Primary dark     | `#0052CC` | Gradient pair on login                                 |
| Accent           | `#F97316` | Reserved for future highlights                         |
| Background       | `#F5F7FA` | All screen backgrounds                                 |
| Surface          | `#FFFFFF` | Cards, inputs                                          |
| Ink              | `#1E293B` | Headings, primary text                                 |
| Muted            | `#64748B` | Secondary text, timestamps                             |
| Border           | `#E2E8F0` | Dividers inside cards (no card outlines)               |
| Success          | `#16A34A` | Approved, enrolled, completed                          |
| Danger           | `#DC2626` | Rejected, blocked, dismissed, logout                   |
| Warning          | `#D97706` | Pending, inactive, on-hold                             |
| Info             | `#0EA5E9` | Informational states                                   |

### Per-feature tones (`tones` in `tokens.ts`)

Each tone is a `(bg, fg)` pair used by `IconTile` and `QuickActionTile`. The `bg` goes on the icon container, the `fg` on the lucide icon's `color` prop.

| Tone     | bg        | fg        | Used for                           |
|----------|-----------|-----------|------------------------------------|
| `blue`   | `#DBEAFE` | `#0066FF` | Surveys, primary info, enrollment  |
| `purple` | `#F3E8FF` | `#9333EA` | Add/drop, password, secondary      |
| `green`  | `#DCFCE7` | `#16A34A` | Grades, GPA, approvals             |
| `orange` | `#FFEDD5` | `#EA580C` | Thesis, contact, support           |
| `sky`    | `#E0F2FE` | `#0284C7` | Notifications, info, privacy       |
| `red`    | `#FEE2E2` | `#DC2626` | Destructive, safety, blocked       |
| `amber`  | `#FEF3C7` | `#D97706` | Warnings, on-hold                  |

### Typography scale

| Role                    | Size | Weight | Class                |
|-------------------------|------|--------|----------------------|
| Hero title (white)      | 22   | 700    | `font-kufi-bold`     |
| Hero subtitle (white80) | 13   | 400    | `font-kufi`          |
| Section header          | 14   | 500    | `font-kufi-medium`   |
| Card title              | 16   | 700    | `font-kufi-bold`     |
| Card body               | 13   | 400    | `font-kufi`          |
| Stat number (primary)   | 24   | 700    | `font-kufi-bold`     |
| Stat label (muted)      | 11   | 400    | `font-kufi`          |
| Button label            | 14   | 500    | `font-kufi-medium`   |

### Touch targets

All interactive elements min 48×48pt. Card rows min 56pt tall. `Pressable` uses `active:opacity-80` or `active:opacity-70` instead of background flip so the colored tile doesn't lose its tone on press.

### Cards

Two variants — both via `Card` primitive:

- **Flat** — default, `shadow-sm` style (`opacity 0.06`, `radius 8`, `y-offset 2`). Used for list rows, regular content cards.
- **Elevated** (`elevated` prop) — `shadow-lg` style (`opacity 0.12`, `radius 18`, `y-offset 8`). Used for hero-overlap cards (dashboard stats, profile card).

Both: `bg-surface`, `rounded-2xl` (16px), `px-4 py-4`. **No border outline** — separation comes from shadow.

### Hero header pattern

Every authenticated screen uses `Screen` with `headerVariant="hero"`. The header renders a flat blue band (`bg-primary`) with title + optional subtitle + optional back button, padded `pt-5 pb-12` (or `pb-20` for `heroSize="lg"`). The page content scrolls under it with negative `marginTop` set by `overlap` (typical: `56`). The first child Card uses `elevated` so it visually lifts into the band.

```
┌─ blue band ────────────────────────┐
│ مرحباً، محمود علي                  │
│ رقم القيد: 26CS101                 │
│  ┌─────────────────────────────┐  │
│  │  Elevated Card overlap      │  │ ← overlaps via -56px
└──┤                             ├──┘
   │  Stats / content            │
   └─────────────────────────────┘
```

### Status banners

When the student's status is `OnHold` / `Inactive` / `Dismissed`, `StatusBanner` renders above all content (still automatic via `Screen`). Shape unchanged from the prior spec. Banners stay flat-tinted (`bg-amber-50` / `bg-red-50` / etc.) — no card shadow.

---

## NAVIGATION (Bottom Tab Bar)

5 tabs, labels below icons, active tab in `colors.primary` (`#0066FF`). The center tab (الطلبات) is rendered as a **raised circular primary-blue button** that floats above the tab bar baseline (custom `tabBarButton`), drawing attention to the action hub.

- **Tab 1 (rightmost under RTL):** الرئيسية — Home icon
- **Tab 2:** مقرراتي — BookOpen icon
- **Tab 3 (visually centered, raised):** الطلبات — LayoutGrid icon, white on `bg-primary` 60×60 circle with 4px white border + blue shadow. Amber pending-count badge pinned to the top-right.
- **Tab 4:** الدرجات — GraduationCap icon
- **Tab 5 (leftmost under RTL):** حسابي — UserCircle icon

The tab bar itself has `shadowColor #000`, `opacity 0.06`, `radius 10`, `y-offset -2` to lift it above the surface. Background white, top border `border` token. Layout in `app/(tabs)/_layout.tsx`; the raised button is the local `RaisedTabButton` component in the same file.

### Why a center action button

The 7 student actions (survey, add/drop, suspension, exceptional, thesis, course-tree, clearance) all live inside the الطلبات stack. The raised center button signals visually that this tab is **where students do their work** — the dashboard and grades tabs are read-only views, account is settings, courses is a list. Making the action hub prominent reduces the cognitive cost of "which tab does X live under?".

---

## SCREEN 1 — الرئيسية (Dashboard)

**Hero header** (`heroSize="lg"`, `overlap=56`): "مرحباً، {first two words of name}" + "رقم القيد: 26CS101" subtitle.

**Stats card** (`Card elevated`, overlapping the hero):
- Top row: active semester name + StatusBadge with dot
- Divider, then 3-col stat split with vertical `w-px bg-border` dividers:
  - مقررات مسجلة (count)
  - إشعارات جديدة (unread count)
  - المعدل التراكمي (GPA, 2 decimals)
- Bottom (conditional): Pills for "نافذة الاستطلاع مفتوحة" / "نافذة الإضافة/الحذف مفتوحة"

**Warnings** (max 2): Each is a flat Card with an `amber` IconTile (circle), warning type label and course on right. Replaces the previous amber-left-border banner.

**Quick actions**: 2×2 grid of `QuickActionTile` — each is a flat shadowed card with a colored `lg` IconTile and label below. Tones:
- استطلاع المقررات → `blue`
- طلب إضافة/حذف → `purple`
- درجاتي → `green`
- تسجيل الرسالة → `orange`

**Notifications**: Up to 3 items. Each item is a flat Card row with circular IconTile (`sky` for info, `amber` for warning), title + relative-Arabic time, and a left ChevronLeft.

---

## SCREEN 2 — حسابي (Account / Profile)

**Hero header** (`heroSize="lg"`, `overlap=56`): "الملف الشخصي".

**Profile card** (`Card elevated`, overlapping):
- Avatar (72px, primary fill, white initials) on right, name + specialization + StatusBadge on left
- Border-top divider, then 3 InfoRows with small (`sm`) IconTiles:
  - رقم القيد (`blue`)
  - البريد الإلكتروني (`green`)
  - رقم الهاتف (`purple`)

**Academic info** Card (flat) with 3 InfoRows:
- سنة القبول (`sky`)
- نوع التسجيل (`orange`)
- المعدل التراكمي (`green`)

**Warnings** (conditional, count > 0): Single flat Card row with amber circular IconTile + "لديك N تحذير نشط" + warning Pill.

**Settings** Card (flat) with 4 SettingsRows separated by `border-b border-border`. Each row has a circular medium IconTile + label + hint + ChevronLeft:
- الإشعارات (`blue`)
- تغيير كلمة المرور (`purple`)
- تواصل مع الإدارة (`orange`) — opens `mailto:`
- سياسة الخصوصية (`sky`)

**Logout button**: full-width danger Button with LogOut icon.

**Footer**: app version line in muted text.

---

## SCREEN — Login

Not part of the authenticated flow — uses its own `LinearGradient` wrapper instead of `Screen`.

- Full-screen vertical `LinearGradient` from `colors.primary` (`#0066FF`) to `colors.primaryDark` (`#0052CC`)
- **Logo block** (centered): white `rounded-3xl` 80×80 tile with shadow + `GraduationCap` icon in primary blue, app title "بوابة الطالب" in 32px white, subtitle "نظام إدارة الدراسات العليا" in 14px white80
- **Form card**: white `rounded-3xl` p-6 with heavy shadow. Heading "تسجيل الدخول" + subtitle. Then `Input` for رقم القيد and password, error text, and full-width `Button` size lg
- Footer line: "للمساعدة تواصل مع شؤون الطلاب" in white80, then DEV API URL in dev mode

Arabic-Indic and Persian digit normalization on enrollment_no input is preserved.

---

## SCREEN — مقرراتي (Enrollments)

Hero header (`heroSize="md"`, `overlap=28`): "مقرراتي" + "المقررات المسجلة لهذا الفصل".

**Semester chip row** (`SemesterChip` primitive) — horizontal scroll of pills. Active chip uses primary blue + heavy primary-tinted shadow; inactive chips are flat surfaces with subtle shadow. Chips overlap the hero band slightly.

**Enrollment cards** — flat shadowed cards. Each: course name + code + faculty (right), إجباري/اختياري + credit pills, StatusBadge on left.

**Empty state** — BookOpen icon + heading + CTA to course tree.

---

## SCREEN — الدرجات (Grades)

Hero header (`heroSize="lg"`, `overlap=56`): "الدرجات" + "معدلك ودرجاتك التفصيلية".

**GPA gauge card** (`elevated`) overlapping the hero — circular SVG progress (12px stroke, color-coded green/amber/red), GPA value 34px bold, "من 4.00" muted.

Below: `SemesterChip` row, then per-course cards with coursework/final score rows shown only when published, midterm-only / unpublished notes, color-coded `GradePill` (letter + numeric).

---

## SCREEN — الطلبات (Action Hub)

The الطلبات tab routes here. Hero header (`heroSize="lg"`, `overlap=56`): "الطلبات والإجراءات" + "كل ما يخص مسارك الأكاديمي".

**Status summary card** (`elevated`): active semester name + pills for "الاستطلاع مفتوح/مغلق" and "الإضافة/الحذف مفتوح/مغلق" (with dot, tone success when open).

**Action grid** — 2-column grid of 7 colored action cards (3 rows of 2 + a final solo card with empty filler for visual balance). Each card has a large IconTile in its own tone, title bold, body in 11px muted, optional warning Pill (e.g. "2 قيد المراجعة") pinned top-left:

| # | Action            | Tone   | Icon          | Route                    |
|---|-------------------|--------|---------------|--------------------------|
| 1 | استطلاع المقررات  | blue   | ClipboardList | `/requests/survey`       |
| 2 | الإضافة والحذف    | purple | FileEdit      | `/requests/add-drop`     |
| 3 | إيقاف القيد       | orange | CalendarX     | `/requests/suspension`   |
| 4 | الفرص الاستثنائية | red    | RotateCw      | `/requests/exceptional`  |
| 5 | تسجيل الرسالة     | green  | ScrollText    | `/requests/thesis`       |
| 6 | شجرة المقررات     | sky    | TreePine      | `/requests/course-tree`  |
| 7 | إخلاء الطرف       | amber  | Award         | `/requests/clearance`    |

**Recent add/drop history** — up to 3 of the most recent items as flat cards.

---

## SCREENS — Sub-stack screens

All sub-screens under `(tabs)/requests/*` use:

- `headerVariant="hero"`, `heroSize="md"` typically (or `lg` if the first card is a big elevated one — thesis stepper, clearance form)
- `onBack={() => router.back()}` so the hero shows the ChevronRight back arrow
- `overlap=28` for `md` headers, `overlap=56` for `lg`

### استطلاع المقررات (Survey)
State-aware Banner (success/info/warning), then "Selected" list (numbered primary-blue circles + course rows + red Trash IconTile), "Available" list (course rows + blue Plus IconTile), full-width submit Button.

### الإضافة والحذف (Add/Drop)
Window-status Banner, two `UsageStat` cards side by side (number colored by usage tone, status Pill), segmented "طلب جديد / السجل" tabs, segmented "إضافة / حذف" type toggle (active toggle gets colored shadow halo). Course picker rows lift when selected (blue or red bg + elevated shadow).

### طلب إيقاف قيد (Suspension)
Three states (approved-block / pending-block / available) via `Banner`. Available state shows a card list of semesters — disabled rows show a red lock IconTile, picked row gets blue bg + elevated shadow.

### الفرص الاستثنائية (Exceptional)
Info Banner explaining the feature, failed-courses picker (red-themed selection), 2-button segmented control for opportunity type (active = primary blue + shadow halo), reason textarea, submit, history.

### تسجيل الرسالة (Thesis)
Hero header. Elevated stepper card (4 vertical steps with completed/active/future state, primary-blue connecting lines for completed). Either eligibility breakdown card (with success/danger banner) OR registration + discussion cards.

### إخلاء الطرف (Clearance)
Two states: empty state with diploma icon + "غير متاح بعد" copy, OR elevated form card with green FileCheck2 IconTile + clearance details + 2 action buttons (طباعة / مشاركة).

### شجرة المقررات (Course Tree)
Hero header. Three collapsible Card sections (إجبارية عامة / إجبارية تخصصية / اختيارية), each row: tone-colored circular IconTile (green/amber/red/sky for passed/enrolled/failed/not_taken) + course name + code + credits + status label.

---

## Component primitives (`src/components/primitives/`)

| Primitive          | Purpose                                                                 |
|--------------------|-------------------------------------------------------------------------|
| `Avatar`           | Initials over primary fill                                              |
| `Banner`           | Tinted full-width banner (kind=success/warning/danger/etc.)             |
| `Button`           | 5 variants × 2 sizes, loading/disabled, optional icon                   |
| `Card`             | Shadow card, `elevated` prop for hero-overlap variant                   |
| `EmptyState`       | Centered icon + heading + subtext + optional CTA                        |
| `ErrorState`       | CloudOff icon + retry button                                            |
| `HeroHeader`       | Blue band with title/subtitle + optional back/right; `sm/md/lg` sizes   |
| `IconTile`         | Square or circle colored icon background; tone + size                   |
| `Input`            | Labeled input with error text, RTL aligned                              |
| `Pill`             | Status pill, 6 tones, optional dot                                      |
| `QuickActionTile`  | Flat shadowed action card with IconTile + label                         |
| `SectionHeader`    | Section title above card groups                                         |
| `SemesterChip`     | Pill-style chip for semester selectors; active uses primary-blue shadow |
| `Skeleton`/Card    | Pulse loading placeholder                                               |
| `StatusBadge`      | Wraps StatusEnum → Pill with localized label and tone                   |

`Screen` (composed) — hero/flat/none header variants, automatic status banner, scroll, overlap support.

---

## Anti-patterns (do not do these)

- **Hairline 1px card borders** — the new design relies on shadow + bg contrast. `border-border` only inside cards as dividers.
- **Single-color quick actions** — every action card carries its tone. Don't fall back to all-blue.
- **Gradient cards (other than login)** — gradients are reserved for the login backdrop. Cards are flat surfaces.
- **Floating toasts / snackbars** — keep using inline `Banner kind="danger"` for mutation errors.
- **Emojis in UI** — lucide icons only.

---

## Migration status

All 12 screens have been migrated to the new design language as of 2026-05-09. Patterns established for future screens:

- Use `Screen headerVariant="hero"`. Default to `heroSize="md"` + `overlap=28`. Use `lg` + `56` only when the first child is a "showpiece" elevated card (GPA gauge, profile card, stats card, thesis stepper, clearance form).
- Sub-stack screens **must** pass `onBack={() => router.back()}` so the hero shows the back arrow.
- Add `elevated` to the first card if it should overlap the hero.
- Use `<IconTile tone="...">` for every colored icon container — never inline `bg-{color}-50` / `text-{color}-600`.
- Use `<QuickActionTile tone="...">` for 2-col action grids (dashboard).
- Drop hairline `border border-border` from all Cards (the primitive no longer adds them).
- Selection states on picker rows: lift via colored shadow halo (primary blue or red, etc.) + tinted bg, instead of `border-primary`.

---

End of spec.
