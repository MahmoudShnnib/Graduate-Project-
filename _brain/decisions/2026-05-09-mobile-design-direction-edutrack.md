---
date: 2026-05-09
tags: [decision, mobile, design, ui, edutrack, figma]
related:
  - "[[../daily-notes/2026-05-09-mobile-redesign-pattern-screens]]"
---

# 2026-05-09 — Mobile redesign: adopt EduTrack visual language

## Context

The mobile companion app (`/mobile`) shipped its Phase 0–4 build under the original `DESIGN_SPEC.md`, which described an "institutional / Arabic government-banking" visual direction — flat surfaces, hairline `1px border-gray-200` cards, uniform primary-blue quick actions, and a plain `PageHeader` strip. The user found a Figma Make project ("EduTrack — Smart School SaaS App", file `Cp2Axhf5mJzPeWW2z1GLJw`) whose visual language they preferred and asked for a full visual refresh.

Source extracted via the official Figma MCP (the `plugin:figma:figma` server, `whoami` confirms account `Ppsan`). Read App.tsx, LoginScreen.tsx, ParentDashboard.tsx, ProfileScreen.tsx, globals.css, and Guidelines.md.

## Decision

Adopt the EduTrack visual language **fully** for the mobile app:

- Switch primary blue from `#2563EB` → `#0066FF`, add gradient pair `#0066FF → #0052CC` for login.
- Switch background from `#F8FAFC` → `#F5F7FA` (negligible perceptual shift, matches Figma).
- Replace flat hairline cards with shadow-only cards (`Card` primitive now has `elevated` prop for hero-overlap variants).
- Adopt the **hero header → overlapping card** signature pattern: every authenticated screen opens with a full-width `bg-primary` band; the first content card pulls up into the band via negative `marginTop`.
- Adopt **per-feature tonal accents** for icon containers — each menu item / setting row / notification gets its own color tile (blue / purple / green / orange / sky / red / amber). Defined as `tones` map in `tokens.ts`.
- Replace login form on grey background with a **white floating card on a blue gradient backdrop**.

Keep:
- 5-tab bottom navigation, sub-stack under الطلبات, all 12 screens, all routing.
- Noto Kufi Arabic, RTL force, Arabic-first copy.
- `StatusBanner` overlay for `OnHold` / `Inactive` / `Dismissed` states.
- API layer, queries, hooks, auth context — untouched.

## Alternatives considered

1. **Cherry-pick patterns** — adopt hero header but keep `#2563EB` and hairline borders. Rejected: half-measures dilute the visual identity the user picked.
2. **Override only the color palette** — quickest path. Rejected: the hero pattern, lifted shadows, and tonal accents are the *signature* of the new direction; tweaking hex values without them would feel inconsistent.
3. **Build a new design preview screen first, then migrate** — proposed but skipped because the user asked for "all of dashboard + login + account in one batch" — which already validates every new pattern.

## Consequences

**Immediate (this commit):**
- 3 screens (login, dashboard, account) ship in the new language as proof of pattern.
- Tokens (`src/theme/tokens.ts`) + Tailwind config (`tailwind.config.js`) updated globally — every other screen's primary blue / bg / accent automatically shifts. Tab bar already pulls from `colors.primary` so it's already on the new blue.
- `Card` primitive updated → every screen using `<Card>` now has the new shadow style and no border. This is intentional: it propagates the new aesthetic without touching every file.
- Three new primitives: `HeroHeader`, `IconTile`, `QuickActionTile`. Re-exported from `primitives/index.ts`.
- `Screen` composed component now supports `headerVariant: 'flat' | 'hero' | 'none'` + `heroSize` + `overlap` + `onBack`. Defaults to `flat` so legacy screens keep their `PageHeader` until migrated.

**Pending (Phase B, separate session):**
- 9 screens still on the old layout (enrollments, grades, course-tree, requests landing, survey, add-drop, suspension, exceptional, thesis, clearance). They render with the new colors and card shadows automatically but still use `PageHeader`. They should be migrated to `headerVariant="hero"` per the migration checklist in the new `DESIGN_SPEC.md`.

**Risks:**
- Card style change is global — any screen relying on the old border for visual separation now relies on shadow alone. On dark/textured Android backgrounds this might lose definition, but our bg is light grey (`#F5F7FA`) so contrast is fine.
- New `tones` palette uses Tailwind's named color values (`#DBEAFE` etc.) baked into `tokens.ts` rather than referencing Tailwind classes — keeps a single source of truth and avoids JIT / NativeWind drift.

## Verification

- `npx tsc --noEmit` — passes with no errors.
- `npx expo export --platform android` — clean 7.36 MB Hermes bundle.
- Manual browser test pending (user runs `npx expo start` + Expo Go scan).
