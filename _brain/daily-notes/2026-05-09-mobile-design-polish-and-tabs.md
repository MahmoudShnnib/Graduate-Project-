---
date: 2026-05-09
tags: [daily, mobile, design, ui, navigation, tab-bar, action-hub, polish]
related:
  - "[[../decisions/2026-05-09-mobile-design-direction-edutrack]]"
  - "[[../decisions/2026-05-09-mobile-raised-center-tab-bar]]"
  - "[[2026-05-09-mobile-redesign-pattern-screens]]"
---

# 2026-05-09 — Mobile design polish + raised center tab + action hub

## Context

Continuing from the same-day pattern-screen session ([[2026-05-09-mobile-redesign-pattern-screens]]). User asked to (a) install RN-focused skills via `/find-skills` and (b) "fix and polish the design and make a better nav bar that can navigate to the things the student can do".

Found two solid skills via `npx skills find`:
- **vercel-labs/agent-skills@vercel-react-native-skills** (111.8K installs)
- **callstackincubator/agent-skills@react-native-best-practices** (12.9K installs)

Both installed globally with `npx skills add ... -g -y`. The Vercel skill is now loaded in this session; the Callstack one needs a session reload to surface but is on disk.

User answered the nav-design question with "do what u feel best" — decision logged in [[../decisions/2026-05-09-mobile-raised-center-tab-bar]].

## What was done

### Navigation refactor

| File | Change |
|---|---|
| `mobile/app/(tabs)/_layout.tsx` | Full rewrite. Custom `RaisedTabButton` for the الطلبات tab — a 60×60 primary-blue circle with white 4px ring, primary-blue shadow, lifted by 42% of its size above the tab bar baseline. Carries an amber pending-count badge (top-right) that displays "99+" for counts > 99. Tab bar gets a soft upward shadow (`opacity 0.06, y -2`) for lift. Icon swapped from FileText → LayoutGrid for the hub. |

### Action Hub (الطلبات)

| File | Change |
|---|---|
| `mobile/app/(tabs)/requests/index.tsx` | Full rewrite. Hero header + status summary card (`elevated`) showing active semester name and survey/add-drop window pills. Then a 2-col grid of 7 `ActionCard` tiles, each: large `IconTile` in its own tone, bold title, 2-line body, optional warning Pill for pending count. Final tile (clearance) sits in a row by itself with an empty filler View for visual balance. Recent add/drop history at the bottom. |

### New primitive

| File | Change |
|---|---|
| `mobile/src/components/primitives/SemesterChip.tsx` | **New** — pill-shaped chip with active/inactive shadow variants. Active uses a primary-blue tinted shadow halo for "lifted" emphasis. Extracted from inline duplicates in enrollments + grades. |
| `mobile/src/components/primitives/index.ts` | Exported `SemesterChip` |

### Migrated to new hero pattern

All 9 remaining screens migrated. Pattern applied uniformly:
- `Screen headerVariant="hero"` with `heroSize` + `overlap` per screen
- `onBack={() => router.back()}` on all sub-stack screens
- First card uses `elevated` if it should overlap into the hero
- Inline `bg-{color}-50 / text-{color}-{600}` icon containers replaced with `<IconTile tone="...">`
- Hairline `border border-border` removed from all Cards (the primitive already drops it)
- Selection states on picker rows now lift via colored shadow halos instead of `border-primary`

| File | Notable |
|---|---|
| `mobile/app/(tabs)/enrollments.tsx` | Hero md, overlap 28. Uses extracted `SemesterChip`. Empty state CTA → course tree. |
| `mobile/app/(tabs)/grades.tsx` | Hero lg, overlap 56. Elevated GPA gauge card overlapping. SemesterChip below. Bigger gauge stroke (12px) and 34px GPA value. |
| `mobile/app/(tabs)/requests/course-tree.tsx` | Hero md, onBack. Status icons replaced with tone-colored circular `IconTile` (green/amber/red/sky). |
| `mobile/app/(tabs)/requests/survey.tsx` | Hero md, onBack. Numbered primary-blue badge per selected course. Inline color-50 circles → IconTile (red Trash, blue Plus). |
| `mobile/app/(tabs)/requests/add-drop.tsx` | Hero md, onBack. New `UsageStat` card primitive (inline). Tab segmented control + type segmented control with shadow halos on active. Course picker rows lift on selection. |
| `mobile/app/(tabs)/requests/suspension.tsx` | Hero md, onBack. Disabled semester rows show red Lock IconTile. Picked rows lift with primary shadow. |
| `mobile/app/(tabs)/requests/exceptional.tsx` | Hero md, onBack. Failed-courses picker uses red theme on selection. Opportunity-type segments with primary shadow on active. |
| `mobile/app/(tabs)/requests/thesis.tsx` | Hero lg, onBack. Elevated stepper card. Replaced inline `divide-y divide-border` (NativeWind doesn't support `divide-*`) with explicit `border-b` per row. |
| `mobile/app/(tabs)/requests/clearance.tsx` | Hero lg/md per state, onBack. Eligible state uses elevated form card with green FileCheck2 IconTile. |

### Spec updated

`mobile/DESIGN_SPEC.md` — updated Navigation section with raised center tab pattern, replaced "Screens 3–12" with concrete per-screen sections, added `SemesterChip` to primitives table, replaced "migration checklist" section with "migration status" (all 12 done) + the new patterns codified.

## Verified

- `npx tsc --noEmit` — passes, zero errors.
- `npx expo export --platform android` — clean **7.36 MB** Hermes bundle.
- Tab bar pulls `colors.primary` automatically — `#0066FF` propagates everywhere.

## Files changed (this session, on top of the morning)

```
M  mobile/DESIGN_SPEC.md
M  mobile/app/(tabs)/_layout.tsx
M  mobile/app/(tabs)/enrollments.tsx
M  mobile/app/(tabs)/grades.tsx
M  mobile/app/(tabs)/requests/index.tsx
M  mobile/app/(tabs)/requests/course-tree.tsx
M  mobile/app/(tabs)/requests/survey.tsx
M  mobile/app/(tabs)/requests/add-drop.tsx
M  mobile/app/(tabs)/requests/suspension.tsx
M  mobile/app/(tabs)/requests/exceptional.tsx
M  mobile/app/(tabs)/requests/thesis.tsx
M  mobile/app/(tabs)/requests/clearance.tsx
M  mobile/src/components/primitives/index.ts
A  mobile/src/components/primitives/SemesterChip.tsx
```

## Loose ends / next steps

- [ ] User to scan with Expo Go on the morning gate (`npx expo start` from `mobile/`, sign in with `26CS101 / password`) and visually approve the new tab bar + hub + all 12 screens.
- [ ] Update `app/_design-preview.tsx` to add the four new primitives (HeroHeader, IconTile, QuickActionTile, SemesterChip) so the design preview stays a true source of truth. Also worth showing the raised tab button in isolation for review.
- [ ] If RTL flips the back-arrow direction wrong on some Android devices, swap `ChevronRight` → `ChevronLeft` in `HeroHeader` (icon already mirrored conceptually for RTL = right-pointing means back, but verify in Expo Go).
- [ ] Optional: animation polish — fade/slide on hub action cards (RN Reanimated is already a dep).
- [ ] Possibly deprecate the now-unused `PageHeader` primitive once we're sure nothing imports it.

## Skills installed (persistent for future sessions)

- `vercel-react-native-skills` — RN/Expo best practices (Vercel Labs, 111.8K installs).
- `react-native-best-practices` — performance, FPS, TTI, FlashList (Callstack, 12.9K installs). Will appear in the skill list after a session reload.

---

## Fixes after Expo Go test (later same session)

User scanned with Expo Go and reported (a) hero header clipping the elevated card text on every screen and (b) wanting the GPA gauge + grades tab + مقرراتي tab polished further, plus (c) a richer dummy student to "test everything".

### Bug fix — hero header was clipping elevated cards

Root cause: in `Screen.tsx` the negative `marginTop` was on the ScrollView's `contentContainerStyle`. RN's ScrollView clips content that extends above the contentContainer's frame, so the lifted card got cut off behind the blue band instead of floating above it.

Fix: rewrote `Screen.tsx` to render the `HeroHeader` **inside** the ScrollView (as a regular sibling) and put the negative `marginTop` on a **regular View** inside the ScrollView. The View doesn't clip its negative-margin overlap with its previous sibling. Hero now scrolls with content (which is the correct EduTrack mobile behaviour anyway). Also fixed a pre-existing `paddingTop: overlap ? 16 : 16` typo (always 16) — now `0` when overlapping so the full overlap is honoured.

| File | Change |
|---|---|
| `mobile/src/components/Screen.tsx` | Restructured. Hero/none variants put the header inside the ScrollView; flat variant keeps the legacy sticky-header layout. |

### Polish — Grades tab

Bigger, classified GPA gauge with stat row underneath:

| File | Change |
|---|---|
| `mobile/app/(tabs)/grades.tsx` | New `tierFor(gpa)` returns `(label, tone, pillTone, stroke, trackTint)`. Gauge now 172px / 16px stroke, with a tinted track (matches tier) and SVG `LinearGradient` stroke. Inside the circle: small label, 44px GPA, "من 4.00". Below the gauge: classification Pill + 3-col stat row (Award icon → tier label, BookOpen → passed courses count, Layers → earned credits). Stats sourced from `useEnrollments()` filtered by `published + letter !== F`. Course cards now use a horizontal progress bar (`ScoreRow`) for coursework and final score, plus tinted note rows for midterm-only / unpublished states. |

### Polish — مقرراتي

Hero overlap with summary stats card; richer enrollment cards:

| File | Change |
|---|---|
| `mobile/app/(tabs)/enrollments.tsx` | New summary `Card elevated` overlapping the hero — 3-col stat split (blue BookOpen → enrolled count, green Layers → total credits, red Clock → dropped/withdrawn). Subtitle dynamically shows the selected semester name. Enrollment cards now lead with a tone-colored `IconTile` (sky for elective, blue for إجباري) + course name + lecturer + pill row. Empty state copy expanded with admin-contact hint. |

### Richer dummy student — `26CS103`

Added a third semester ("الفصل الدراسي الأول 2025") and a top-tier showcase student:

| File | Change |
|---|---|
| `database/seeders/MobileTestStudentSeeder.php` | `ensureSemesters()` now returns `oldest` + `previous` + `active`. New `26CS103 / password` ("نور الدين البكوش", `top.student@example.ly`). Full transcript: oldest semester (EE601 A+, EE602 A, EE603 A-, EE604 F), previous (CP611 A, CP612 A-, CP614 B+, CP619 A+), active (CP615 + CP616 midterm-only, CP617 + CM614 unpublished). 1 active CourseFailure warning from EE604. 2 unread notifications. GPA recomputed (≈3.34). |

### Test credentials (after `php artisan migrate:fresh --seed --force`)

- `26CS101 / password` — Active mid-tier student, 1 warning, mixed transcript.
- `26CS102 / password` — OnHold student (verifies the OnHold StatusBanner above every screen).
- `26CS103 / password` — Active showcase student, 3 semesters of grades, 1 historical F → 1 active warning, currently studying. **Use this to test everything end-to-end.**

### Verified

- `npx tsc --noEmit` — passes.
- `npx expo export --platform android` — clean **7.37 MB** Hermes bundle.
- `vendor/bin/pint --dirty --format agent` — pass.

---

## Polish pass via /ui-ux-pro-max (later same session)

User invoked the UI/UX Pro Max skill with "polish the mobile app". Ran the skill's design-system analyzer, then synthesized against the actual gaps in the current build.

### Gaps targeted

1. **Status bar** — was default dark icons; invisible against the blue hero band.
2. **Loading skeleton** — still had hairline border from old design.
3. **Tabular numbers** — GPA / scores wobbled between digits since Noto Kufi Arabic uses proportional figures.
4. **Tactile feedback** — no haptics anywhere; raised tab and form submissions felt flat.
5. **Pull-to-refresh** — no way to refresh data without exiting/re-entering screens.

### Changes

| File | Change |
|---|---|
| `mobile/package.json` | Added `expo-haptics ~15.0.8` (installed via `npm install --legacy-peer-deps` because expo install hit a `react-dom` peer-dep conflict). |
| `mobile/app/_layout.tsx` | Added `<StatusBar style="light" backgroundColor={colors.primary} translucent={false} />` so icons are visible against the blue hero band on every screen. Replaced two hardcoded `#F8FAFC` with `colors.bg`. |
| `mobile/src/components/primitives/Skeleton.tsx` | Rewrote `SkeletonCard` — drops hairline border, uses the same shadow style as the new Card primitive. Added an avatar-circle + 2-line text + content-block skeleton row pattern (closer to actual content). Pulse opacity tightened: 0.5 ↔ 1.0 over 800ms. |
| `mobile/src/lib/haptics.ts` | **New** — centralized wrapper around `expo-haptics`. Five exported helpers: `selection / tap / medium / success / warning / error`. Silently no-ops on unsupported platforms (web). |
| `mobile/src/components/Screen.tsx` | Added optional `onRefresh` + `refreshing` props that get plumbed into a `RefreshControl` on the inner ScrollView. Tint color set to white (visible against the blue hero), Android colors set to primary blue, background white. |
| `mobile/app/(tabs)/_layout.tsx` | `RaisedTabButton` onPress now fires `haptic.tap()` before the navigation handler. Type signature updated to accept the gesture event. |
| `mobile/app/(tabs)/index.tsx` | Plumbed pull-to-refresh (refetches `me`, semester, enrollments, notifications in parallel). Tabular nums on `MiniStat` value. |
| `mobile/app/(tabs)/enrollments.tsx` | Pull-to-refresh (semesters + enrollments). Tabular nums on stat values. |
| `mobile/app/(tabs)/grades.tsx` | Pull-to-refresh (me + semesters + enrollments + grades). Tabular nums on GPA value, "من 4.00", `StatBlock` value, and the `ScoreRow` "X / Y" text. |
| `mobile/app/(tabs)/account.tsx` | Tabular nums on InfoRow values (numeric ones like enrollment_no, GPA). Logout button fires `haptic.warning()` before logging out. |
| `mobile/app/(auth)/login.tsx` | Login fires `haptic.success()` on success and `haptic.error()` on failure. |
| `mobile/app/(tabs)/requests/survey.tsx` | Form submission fires `haptic.success()` / `haptic.error()`. |
| `mobile/app/(tabs)/requests/add-drop.tsx` | Same. |
| `mobile/app/(tabs)/requests/suspension.tsx` | Same. |
| `mobile/app/(tabs)/requests/exceptional.tsx` | Same. |

### Verification

- `npx tsc --noEmit` — passes.
- `cd mobile && npx expo export --platform android` — clean **7.39 MB** Hermes bundle (+20 KB from haptics + status bar wiring).

