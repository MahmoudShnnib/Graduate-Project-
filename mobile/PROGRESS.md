# PROGRESS.md — بوابة الطالب build log

A continuously-updated log of what was built, what's pending, and decisions you should review.

---

## Phase 0 — Audit ✅ COMPLETE

**Built:**
- Read SYSTEM_SPEC.md, DESIGN_SPEC.md, TEST_CREDENTIALS.md.
- Inspected all 21 Eloquent models, all 13 enums, all 38 migrations, all existing seeders.
- Wrote `mobile/AUDIT.md` — complete inventory + decisions.

**Decisions made (review at AUDIT.md §3, §4, §5):**

1. **Sanctum is NOT installed** — Phase 1 installs it; bearer tokens, no SPA cookies.
2. **`routes/api.php` does NOT exist** — registering it via `bootstrap/app.php`, all routes prefixed `/api/v1`.
3. **In-app notifications:** new `app_notifications` table (NOT reusing `notification_logs` which is for outbound email/WhatsApp). Cleaner separation.
4. **Adding two columns to `exceptional_requests`** — `course_id` (nullable FK) and `opportunity_type` (nullable enum string). Required by DESIGN_SPEC. Both nullable so existing rows + Filament admin keep working.
5. **Test student seeding** — new `MobileTestStudentSeeder` runs after existing seeders. Creates `26CS101` (Active) and `26CS102` (OnHold), updates active semester to "الفصل الدراسي الأول 2026", creates a previous semester, course offerings for both, enrollments, grades (full publish on past, midterm-only on active, one F to seed exceptional flow), 1 active warning, 2 unread notifications.
6. **Specialization rename** — existing `code=2` Arabic name "الحاسب الآلي" updated to "علوم الحاسوب" (idempotent) in MobileTestStudentSeeder so display matches TEST_CREDENTIALS without breaking code-keyed lookups.
7. **No Zustand** — TanStack Query for server state, react-hook-form for forms, expo-secure-store for token, local Context for auth state. Revisit only if needed.
8. **Mobile folder layout** — Expo Router (`app/`) + `src/{api,components,hooks,lib,theme}`, `assets/fonts/`. Hidden design preview at `app/_design-preview.tsx`.

**Spec ambiguities resolved (AUDIT.md §5):** 11 small interpretation decisions documented — tab order, badge counts, exceptional opportunity types, clearance trigger, etc. All chose the simpler interpretation.

**Skipped:** nothing.

**Blockers:** none.

**Next:** Phase 1 — Laravel API foundation.

---

## Phase 1 — Laravel API foundation ✅ COMPLETE

**Built:**

- Installed `laravel/sanctum`; published config + migration; added `HasApiTokens` to `User`.
- Registered `routes/api.php` in `bootstrap/app.php` with `apiPrefix: 'api/v1'`.
- Created 2 migrations:
  - `app_notifications` table (id, student_id, type, title, body, read_at) — for in-app feed.
  - Added `course_id` (FK) and `opportunity_type` (string) to `exceptional_requests`.
- Created `App\Models\AppNotification` with `student()` belongsTo, `unread()` scope.
- Updated `Student` model — added `appNotifications()` HasMany.
- Updated `ExceptionalRequest` model — added `course_id`, `opportunity_type` to fillable; added `course()` BelongsTo.
- Created **15 API resources** under `app/Http/Resources/Api/V1/`:
  StudentResource, SemesterResource, CourseResource, CourseOfferingResource, EnrollmentResource, GradeResource (publishing-aware: hides fields by `published_at`/`midterm_published_at`), SurveyResource, AddDropRequestResource, SuspensionRequestResource, ThesisResource, DiscussionResource, ExceptionalRequestResource, WarningResource, AppNotificationResource, ClearanceResource.
- Created **12 controllers** under `app/Http/Controllers/Api/V1/`:
  AuthController (login/logout/me), SemesterController, EnrollmentController, GradeController, CourseTreeController (groups by general/specialized/elective + per-course status: passed/enrolled/failed/not_taken), SurveyController (transactional bulk replace), AddDropController (with options endpoint exposing window state, usage, addable, droppable), SuspensionController (with three-state options), ThesisController (with eligibility breakdown + phase derivation), ExceptionalRequestController (with failed-courses options), ClearanceController, NotificationController.
- Created `MobileTestStudentSeeder` — produces:
  - Active semester "الفصل الدراسي الأول 2026" + previous "الفصل الدراسي الثاني 2025"
  - Updates spec name `الحاسب الآلي → علوم الحاسوب` (idempotent)
  - Test student `26CS101` (Active, "محمود علي الترهوني", phone 0910000000, password=password)
  - Second student `26CS102` (OnHold) for OnHold banner verification
  - 4 enrollments in previous semester with fully-published grades (A, B+, C+, F)
  - 4 enrollments in active semester (2 midterm-only, 1 unpublished, 1 dropped)
  - 1 active CourseFailure warning (CP611)
  - 2 unread app_notifications
  - GPA recomputed to 2.50

**Verified:**

- `php artisan migrate:fresh --seed --force` runs clean.
- Tinker query confirms: 26CS101 status=active, gpa=2.5, 7 active enrollments, 4 published grades, 2 midterm-only, 1 warning, 2 unread notifications.
- Started `php artisan serve --port=8000`. `curl POST /api/v1/auth/login` with `26CS101 / password` returns a real Sanctum token + full StudentResource.
- Smoke tested 5 endpoints with bearer token: `/me`, `/enrollments`, `/grades`, `/course-tree`, `/notifications` — all return real seeded data.
- Pest test suite: **9/9 API tests passing** (login + auth flows + endpoint resources).
- `vendor/bin/pint --dirty --format agent` ran cleanly.

**Decisions during build (review):**

- `apiPrefix: 'api/v1'` set in `bootstrap/app.php` so routes file holds clean unprefixed paths.
- Login accepts `device_name` (defaults to `"mobile"`) so the user can have multiple device tokens.
- `GradeResource.publish_state` returns `"published" | "midterm_only" | "unpublished"` so client doesn't have to re-derive from timestamps.
- Suspension `options` endpoint returns `{state: 'available'|'pending_block'|'approved_block', semesters: [...]}` — client just picks the right banner from `state`.
- Add/drop `options` endpoint includes `window_open`, `usage` (with `max:3`), `addable`, `droppable` — single roundtrip for the form.
- `course-tree` endpoint pre-groups by type (general/specialized/elective) on the server, so client renders directly.
- Course pivot type mapping uses `enum CourseType` ("general" | "specialized" | "elective"). DESIGN_SPEC says "إجباري / اختياري"; we treat general+specialized as إجباري, elective as اختياري in the mobile UI.
- Discovered + worked around: when the seeder is re-run standalone (without `WithoutModelEvents`), GradeObserver fires and can flip a student to dismissed if it sees a re-published F. Fresh `migrate:fresh --seed` is the correct path; the seeder is idempotent for adds but the F-grade re-publication is event-sensitive — documented for myself.
- Gave the Grade booted hook explicit fallback in the seeder by computing `total/letter/points` ahead of time, so seeded grades are correct even when `WithoutModelEvents` is in effect during `db:seed`.

**Skipped:**

- `clearance/pdf` binary endpoint — listed for Phase 4 stretch.
- Wiring observers to write `app_notifications` rows on real events — Phase 1 only seeds them. Mobile reads whatever's in the table.
- Form Request classes — used inline `$request->validate()` for now (small validation shapes, project doesn't have other API Form Requests yet).

**Blockers:** none.

**Next:** Phase 2 — Expo skeleton + design system.

---

## Phase 2 — Expo skeleton + design system ✅ COMPLETE

**Built:**

- Scaffolded Expo SDK 54 + RN 0.81 + React 19 + TypeScript via `create-expo-app --template blank-typescript`.
- Switched main entry to `expo-router/entry`, deleted `App.tsx`/`index.ts`.
- Installed deps: expo-router, expo-font, expo-secure-store, expo-localization, expo-system-ui, RN safe-area / screens / reanimated / gesture-handler / svg, nativewind 4 + tailwindcss 3.4, @tanstack/react-query, axios, react-hook-form + zod + @hookform/resolvers, lucide-react-native, react-native-worklets, babel-preset-expo.
- Configured **NativeWind v4**: `tailwind.config.js` (theme tokens matching DESIGN_SPEC hex codes), `babel.config.js` (jsxImportSource + nativewind/babel preset), `metro.config.js` (`withNativeWind`), `global.css` (@tailwind directives), `nativewind-env.d.ts`.
- `tsconfig.json` paths alias `@/* -> src/*`.
- `app.json`: bundle ID, scheme, fonts plugin (3 TTF entries), expo-router + expo-secure-store + expo-localization plugins, typedRoutes experiment.
- Downloaded **3 distinct static-weight TTFs** (Regular 400, Medium 500, Bold 700) from fontsource into `assets/fonts/`.
- Folder structure under `src/`: `api/`, `components/{primitives,feature}`, `hooks/`, `lib/`, `theme/`.
- **Design tokens** (`src/theme/tokens.ts`) — exact DESIGN_SPEC hex values exported as TS const.
- **Token storage** (`src/lib/secureStore.ts`) — wraps expo-secure-store for the bearer token.
- **Axios client** (`src/api/client.ts`) — auto-resolves base URL from Expo dev host (LAN IP) or falls back to `10.0.2.2`/`127.0.0.1`. Bearer interceptor + 401 handler.
- **Type definitions** (`src/api/types.ts`) — every API resource shape from Phase 1, hand-typed to match the Laravel resources.
- **Auth context** (`src/hooks/useAuth.tsx`) — `loading | authenticated | unauthenticated`, hydrates from secure-store on boot, calls `/auth/me` to refresh, integrates with the unauthorized handler.
- **10 design primitives** (`src/components/primitives/`): Button (5 variants × 2 sizes × loading/disabled/icon-left), Card, Pill (6 tones, optional dot, sm/md), Banner (5 tones × 7 kinds), Input (label/error/hint, RTL-aligned), Avatar (initials), EmptyState, Skeleton + SkeletonCard, StatusBadge (wraps StatusEnum→Pill), SectionHeader.
- **Composed components**: `Screen` (auto safe-area + header + status banner + scroll), `PageHeader`, `StatusBanner` (auth-aware, shows OnHold/Inactive/Dismissed banner from student status).
- **Routing tree**:
  - `app/_layout.tsx` — RTL force, font load, QueryClient, AuthProvider, AuthGate (redirects unauth → `/login`, auth → `/(tabs)`).
  - `app/(auth)/login.tsx` — RHF + zod, end-to-end working against the Laravel API.
  - `app/(tabs)/_layout.tsx` — 5 bottom tabs with lucide icons + Arabic Kufi labels: الرئيسية / مقرراتي / الطلبات / الدرجات / حسابي.
  - `app/(tabs)/{index,enrollments,grades,account}.tsx` + `requests/{_layout,index}.tsx` — Phase-3 placeholders.
  - **`app/_design-preview.tsx`** — every primitive in every state, scrollable, hidden route.

**Verified:**

- `npx expo export --platform android` builds a clean **7.25 MB bundle** (3457 modules) with all 3 distinct font TTFs included.
- `npx tsc --noEmit` compiles with **no TypeScript errors**.

**Decisions during build (review):**

- **NativeWind v4** (current latest) over v3 — v4 supports Tailwind v3.4 cleanly and ships with the `withNativeWind` Metro helper. The user's spec said "NativeWind (Tailwind v3)" — interpreted as Tailwind v3 (we're on 3.4.19); NativeWind itself is v4.
- **Variable→static font swap** — first attempt used Google Fonts' variable `NotoKufiArabic[wght].ttf` thrice; weights collapsed visually. Switched to fontsource static TTFs (400/500/700) so Bold/Medium actually render distinctly.
- **API base-URL auto-resolves** from `Constants.expoConfig.hostUri` so a phone running Expo Go hits the host laptop's LAN IP automatically. Falls back to `10.0.2.2` (Android emulator) / `127.0.0.1` (iOS sim).
- **Status banner** shown above all screen content (DESIGN_SPEC core rule). Logged in `StatusBanner` and inserted into `Screen` so every screen gets it for free.
- **No emojis anywhere** — entire UI uses lucide icons only.
- **`accent: F97316`** vs **`warning: D97706`** — DESIGN_SPEC distinguishes "Accent orange" (CTAs/chips) from "Warning amber" (status). Pill `tone="warning"` uses amber. `accent` Tailwind class kept available for future non-status uses.
- **No Zustand** — auth state in React Context, server state in TanStack Query.
- **Route segments `(auth)`, `(tabs)`, `requests/`** — Expo Router groups for clean URL paths. Login at `/(auth)/login`, tabs at `/(tabs)/{index,enrollments,...}`, requests subnav supports stacked screens.

**Skipped:**

- Real Expo Go scan/test — that's the morning verification gate.
- Custom splash icon — kept default Expo splash.
- Notification badge wiring on الطلبات tab — Phase 3 wires it.

**Blockers:** none.

**Next:** Phase 3 — implement the 12 screens against the API.

---

## Phase 3 — Screens

(pending)

---

## Phase 4 — Polish

(pending)
