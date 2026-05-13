---
date: 2026-05-09
tags: [daily, mobile, design, ui, edutrack, figma, login, dashboard, account]
related:
  - "[[../decisions/2026-05-09-mobile-design-direction-edutrack.md]]"
  - "[[2026-05-09-font-fix.md]]"
---

# 2026-05-09 — Mobile design refresh: pattern screens (login + dashboard + account)

## Context

User shared a Figma Make project ("EduTrack — Smart School SaaS App", `Cp2Axhf5mJzPeWW2z1GLJw`) and asked to redesign the mobile app to match it. Read 3 representative source files via the Figma MCP — LoginScreen, ParentDashboard, ProfileScreen — plus globals.css and Guidelines.md.

Confirmed three things with the user before touching code (`AskUserQuestion`):
1. **First scope** — all three pattern screens (dashboard + login + account) in one batch.
2. **Fidelity** — adopt fully (switch primary to `#0066FF`, gradient login, colored tiles, hero-header overlap pattern).
3. **DESIGN_SPEC** — replace it with a new spec reflecting the new direction.

Decision logged separately: [[../decisions/2026-05-09-mobile-design-direction-edutrack.md]].

## What was done

### Tokens + config

| File | Change |
|---|---|
| `mobile/src/theme/tokens.ts` | Primary `#2563EB → #0066FF`; added `primaryDark #0052CC`; bg `#F8FAFC → #F5F7FA`; new `tones` map (blue/purple/green/orange/sky/red/amber) with `(bg, fg)` pairs |
| `mobile/tailwind.config.js` | Mirrored color tokens; added `'primary-dark'` and `rounded-3xl: 24px` |
| `mobile/package.json` | Added `expo-linear-gradient ^15.0.8` (via `npx expo install`) |

### Primitives

| File | Change |
|---|---|
| `mobile/src/components/primitives/Card.tsx` | Removed hairline border. New `elevated` prop swaps shadow style: flat (subtle) vs elevated (lifted). |
| `mobile/src/components/primitives/HeroHeader.tsx` | **New** — full-width `bg-primary` band, `pt-5 pb-{6/12/20}` based on `size`, optional back arrow + right slot |
| `mobile/src/components/primitives/IconTile.tsx` | **New** — colored square or circle icon container. Pulls bg from `tones[tone]`; sm/md/lg sizes |
| `mobile/src/components/primitives/QuickActionTile.tsx` | **New** — flat shadowed action card with IconTile + label, replaces inline `QuickAction` from dashboard |
| `mobile/src/components/primitives/index.ts` | Exported the three new primitives |
| `mobile/src/components/Screen.tsx` | Added `headerVariant: 'flat' \| 'hero' \| 'none'`, `heroSize`, `overlap`, `onBack`. Default stays `flat` so unmigrated screens keep `PageHeader`. Negative `marginTop` on the inner ScrollView allows cards to "lift" into the hero band. |

### Screens rewritten

| File | Change |
|---|---|
| `mobile/app/(auth)/login.tsx` | Wrapped in `LinearGradient` (`primary → primaryDark`). White logo tile (rounded-3xl, shadow), 32px white app name, white floating form card (rounded-3xl) with heading + subtitle + the existing inputs/zod schema/error handling/digit normalization preserved. Footer line in white80. |
| `mobile/app/(tabs)/index.tsx` | Replaced flat `PageHeader` with `Screen headerVariant="hero" heroSize="lg" overlap=56`. Stats card now `elevated` with semester name + StatusBadge top, 3-col stats with vertical dividers, optional window pills. Warnings as IconTile cards. Quick actions now `QuickActionTile` with per-feature tones (blue/purple/green/orange). Notifications as flat Card rows with sky/amber circular IconTiles. |
| `mobile/app/(tabs)/account.tsx` | Hero header. Profile card `elevated`, overlapping. Avatar 72 + name + spec + StatusBadge on top. InfoRows with sm tone-colored IconTiles for رقم القيد / البريد / الهاتف. Academic info card with InfoRows (سنة القبول, نوع التسجيل, GPA). Warnings card if count > 0. Settings card with 4 SettingsRows (each with circular IconTile + 2-line text + chevron, separated by `border-b border-border`). Red logout button. App version footer. |

### Spec rewritten

| File | Change |
|---|---|
| `mobile/DESIGN_SPEC.md` | Full rewrite. Documents new colors, tones, typography scale, hero pattern (with ASCII diagram), per-screen layouts for the 3 redesigned screens, list of primitives, anti-patterns, and a migration checklist for the 9 screens still on the old layout. |

## Verified

- `npx tsc --noEmit` — passes, zero errors.
- `npx expo export --platform android` — clean **7.36 MB** Hermes bundle (3458 modules).
- Tab bar already pulls `colors.primary` → automatically uses the new `#0066FF` without code changes.

## Files changed

```
M  mobile/DESIGN_SPEC.md
M  mobile/app/(auth)/login.tsx
M  mobile/app/(tabs)/account.tsx
M  mobile/app/(tabs)/index.tsx
M  mobile/package.json
M  mobile/package-lock.json
M  mobile/src/components/Screen.tsx
M  mobile/src/components/primitives/Card.tsx
M  mobile/src/components/primitives/index.ts
M  mobile/src/theme/tokens.ts
M  mobile/tailwind.config.js
A  mobile/src/components/primitives/HeroHeader.tsx
A  mobile/src/components/primitives/IconTile.tsx
A  mobile/src/components/primitives/QuickActionTile.tsx
```

## Loose ends / next steps

- [ ] User to scan with Expo Go on the morning gate (`npx expo start` from `mobile/`, sign in with `26CS101 / password`) and visually approve the 3 pattern screens before we touch the remaining 9.
- [ ] **Phase B — migrate the remaining 9 screens** to the new design language using the migration checklist at the end of `DESIGN_SPEC.md`. Order suggested: enrollments, grades, course-tree (most-used), then the request screens (survey, add-drop, suspension, exceptional, thesis), then clearance.
- [ ] Update `app/_design-preview.tsx` to add the three new primitives (HeroHeader, IconTile, QuickActionTile) so the design preview stays a true source of truth.
- [ ] Possibly migrate `PageHeader` callers progressively or deprecate `PageHeader` once all screens are on hero — keeping it for now since it's still useful for sub-pages without a hero band.
- [ ] If `noto-kufi-arabic` font weights still feel off after the visual lift (heavier shadows can change perceived weight), revisit the font weight choices.

## Reminder

User mentioned earlier the Noto Kufi Arabic font fix from earlier today ([[2026-05-09-font-fix]]) is still pending a browser hard-refresh test on the Laravel admin/student panels — that's a separate verification, unrelated to the mobile work.

