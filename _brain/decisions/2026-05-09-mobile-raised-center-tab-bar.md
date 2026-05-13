---
date: 2026-05-09
tags: [decision, mobile, navigation, ui, tab-bar, action-hub]
related:
  - "[[2026-05-09-mobile-design-direction-edutrack]]"
  - "[[../daily-notes/2026-05-09-mobile-design-polish-and-tabs]]"
---

# 2026-05-09 — Mobile: raised center tab + dedicated Action Hub

## Context

After the EduTrack visual refresh ([[2026-05-09-mobile-design-direction-edutrack]]) shipped on 3 pattern screens, the user observed that "the 5 tabs are missing — there are some things I can go to directly from them." The 7 student actions (survey, add/drop, suspension, exceptional, thesis, course-tree, clearance) were buried under the الطلبات tab as a vertical list of `NavItem` rows, requiring two layers of navigation to reach the most-used actions and giving no visual signal that الطلبات was where the bulk of the work happens.

Three options were on the table (see chat): keep 5 tabs + add FAB hub, replace الطلبات with a hub screen, drawer + bottom tabs. User deferred the choice to me ("do what u feel best").

## Decision

**Hybrid: keep the 5-tab structure but render the الطلبات tab as a raised circular primary-blue button** (custom `tabBarButton`), and **rebuild the الطلبات screen itself as a 7-action hub** using a 2-col grid of colored tiles.

Rationale:
1. **Discoverability** — the raised center button is impossible to miss. Standard mobile users already recognize the pattern from Twitter/X (compose), Instagram (post), banking apps (transfer). It signals "this is where you act, not just read."
2. **Routing stays** — no new tabs added, no hierarchy changes, all sub-routes (`/requests/survey`, `/requests/add-drop`, etc.) keep working.
3. **Hub > drawer** for our small action set (7 items). A drawer adds a hamburger affordance and a slide-out gesture for a one-screen vertical list — overkill for 7 items that fit in a 2-col grid above the fold.
4. **Hub > replacing tabs with a hub-only stack** — keeping the 5 tabs preserves muscle memory and quick access to home/courses/grades/account.
5. **No FAB-on-top-of-tab-bar** — the raised button **is** a tab, not a separate FAB. Single mental model.

## Implementation

- `app/(tabs)/_layout.tsx` — added `RaisedTabButton` component. It renders the lifted 60×60 primary-blue circle with `LayoutGrid` icon, white 4px ring, primary-blue shadow, amber pending-count badge top-right. Wired via `tabBarButton` prop on the requests `Tabs.Screen` and `tabBarLabel: () => null` to suppress the default label.
- `app/(tabs)/requests/index.tsx` — full rewrite. 2-col grid of 7 `ActionCard` tiles (`IconTile lg` + title + 2-line body + optional warning Pill for pending count). Active-semester status card on top with window pills.
- New primitive: `SemesterChip` extracted from inline duplicates in `enrollments.tsx` and `grades.tsx`. Active chip uses primary-blue shadow halo; inactive flat-shadowed surface.
- All 9 remaining screens migrated to `headerVariant="hero"` per the new design spec, with `onBack={() => router.back()}` on the 7 sub-stack screens.

## Alternatives considered

1. **Drawer with hamburger** — explicitly rejected (see rationale). For 7 items, a hub grid is cheaper and faster to scan than a drawer that requires a gesture/tap to open.
2. **FAB hovering above tab bar** — also rejected. A FAB+tabs creates two CTAs in the same region; users get confused which one to press. Folding the FAB into the center tab is the cleanest version of the "prominent center action" pattern.
3. **Quick actions on dashboard list more screens** — already done (dashboard has 4 colored quick actions). The remaining 3 (suspension, exceptional, course-tree, clearance — actually 4) need to live somewhere; the hub is that somewhere.

## Consequences

- The raised button visually anchors the bottom nav. Tab bar gets a soft top shadow (`opacity 0.06, y -2`) to lift it above the page.
- The center button has a `borderWidth: 4 borderColor: white` collar that "punches through" the tab bar's white background, giving the floating illusion without an `overflow: visible` hack.
- Pending count badge uses amber (warning), matching the dashboard warning palette. If pending count grows past 99, displays "99+".
- The button is `accessibilityRole="button"` with explicit Arabic accessibilityLabel.
- Action hub aggregates pending counts from add/drop, suspension, and exceptional — same source as the tab badge.

## Verification

- `npx tsc --noEmit` — passes with no errors.
- `npx expo export --platform android` — clean 7.36 MB Hermes bundle.
- Manual browser test pending (user runs `npx expo start` + Expo Go scan).
