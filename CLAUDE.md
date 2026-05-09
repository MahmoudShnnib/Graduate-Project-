---
date: 2026-04-17
tags: [meta, project-root, claude]
related: [[_brain/project-context/README]]
---

# CLAUDE.md

> This file is the starting point for every session. Read it first, then scan recent [[_brain/daily-notes/README|daily notes]] before touching code.

## Core Working Principles

> **HIGH PRIORITY — applies to every session, every task, without exception.** If a rule below conflicts with anything else in this file, this section wins.

1. **Consistency across the project is mandatory.** When asked to change something in one place, search the entire codebase for related usages and update them everywhere so nothing breaks and nothing becomes inconsistent. Never do a local fix without checking for project-wide impact. Before closing a task, run `Grep` for every symbol, string, label, route name, or class reference touched — and fix all callers.

2. **Document everything in the vault.** For every task, bug, error, or update:
   - Log what was changed, why, and which files were touched in `_brain/daily-notes/` (today's file).
   - If it's a notable fix, also log it in `_brain/bugs-and-fixes/`.
   - If it's a design/architecture decision, log it in `_brain/decisions/`.
   - Use `[[wikilinks]]` to connect related notes so future sessions can learn from past work.

3. **Never hallucinate. If anything is unclear, ASK.** Do not guess file paths, database schemas, function behavior, framework conventions, or user intent. If you are not 100% sure, stop and ask. The user would rather answer 10 questions than fix 10 wrong implementations.

4. **Full file access, but clarify intent.** You have permission to read, edit, and create any file in this project. You do not need to ask permission to touch files — only to clarify intent before touching them.

5. **Work in phases, one task at a time.** Never try to solve multiple tasks simultaneously. Finish one fully, document it, confirm with the user, then move to the next. Do not batch multiple tasks without explicit approval.

6. **Learn from past mistakes.** Before starting Filament / Livewire / testing work, scan [[_brain/research/2026-04-20-agent-mistakes-and-lessons]] for the anti-patterns already encountered in this repo. It catalogues known traps (wrong Filament component namespaces, `wire:model` reactivity gaps, Vite-manifest issues after `optimize:clear`, etc.) and their correct defaults.

7. **Match existing design conventions — don't invent.** [[_brain/project-context/design-conventions]] is the reference for every visual and structural pattern (colors, badges, banners, buttons, drill-ins, grouped lists, exceptions, observers, etc.). When you need to add a button, banner, modal, or anything user-facing, open that file and pick the existing pattern. When in doubt, match — don't invent.

## Project Overview

A **Libyan graduate-studies admissions, enrollment, and academic-lifecycle management system** for a single faculty, built as a graduation project. It is an Arabic-first (RTL), multi-panel Laravel + Filament application covering the full student journey:

1. **Public application** (`/apply`) — applicants submit a multi-step form (Arabic+English names, personal data, specialization, uploaded bachelor certificate, transfer status). They can track status at `/apply/track` by tracking code + national ID.
2. **Admin review** (`/admin`) — secretaries/super admins review applications, set exam results, and on approval the system auto-creates `User` + `Student` rows, generates an **enrollment number** scoped by year+specialization+transfer-type, and sends a welcome notification.
3. **Academic cycle** — per-semester course offerings, a pre-enrollment **course survey** where students rank up to 4 desired courses, manual enrollment by staff, **add/drop** requests (max 3 each, open only during the semester's add-drop window), grade entry by faculty (`/admin` → "موادي") with configurable coursework/final distribution, and super-admin/secretary grade approval.
4. **Student portal** (`/portal`) — RTL portal for students to browse the course tree, view grades, submit surveys/add-drop/suspension/thesis requests, and check clearance.
5. **Support flows** — audit logging on sensitive field changes, dual-channel (email + WhatsApp via Qonvo) notifications, PDF generation (transcripts, suspension/thesis/clearance forms), printable course rosters, and GPA auto-recalculation on grade approval.

For deeper architectural detail see [[_brain/project-context/project-overview]], [[_brain/project-context/architecture]], and [[_brain/project-context/gotchas]].

## Tech Stack

**Runtime & framework**
- PHP 8.5.2 (composer requires `^8.2`)
- Laravel 12 (streamlined structure — no `app/Http/Kernel.php`, middleware in `bootstrap/app.php`)
- Laravel Herd serves the app at `https://graduate-project.test`

**Admin UI & interactivity**
- Filament v5 (two panels: `admin` at `/admin`, `student` at `/portal`)
- `bezhansalleh/filament-shield` — permission generation per Resource
- Livewire 4 (Filament pages + public portal pages)
- Tailwind CSS 4 + Vite 7 (panel themes in `resources/css/filament/{admin,student}/theme.css`)

**Authorization**
- Laravel Gates + Policies (one policy per top-level model)
- `spatie/laravel-permission` for roles/permissions table
- Hybrid role system: `users.role` column (simple string: `super_admin` / `secretary` / `faculty` / `student`) **and** Spatie roles — both are checked, `super_admin` bypasses all gates via `Gate::before`

**Integrations**
- `barryvdh/laravel-dompdf` — transcripts, clearance, suspension, thesis forms
- Custom `WhatsAppChannel` → `WhatsAppService` → **Qonvo API** (`qonvo.ly`) with Libyan phone-format conversion (09XXXXXXXX → +2189XXXXXXXX)
- Laravel mail (driver-agnostic; Postmark/Resend/SES configs exist, no default wired)

**Testing & tooling**
- Pest 4 (`pest-plugin-laravel`), PHPUnit 12, Mockery — `RefreshDatabase` on all Feature + Unit tests
- Laravel Boost 2 (MCP for docs, tinker, DB schema, browser logs)
- Laravel Pint 1, Pail 1, Sail 1
- Shared Pest helpers in `tests/Pest.php`: `studentWithRecord()`, `adminUser()`, custom `toBeOne` matcher

## Folder Structure

```
app/
├── Actions/                 Single-responsibility action classes, often __invoke-able
│   ├── ApproveApplication.php         Multi-step: Application → User → Student → enrollment number → notify
│   ├── CreateApplication.php          Public intake form → Application row
│   ├── BaseAction.php                 Stub — currently unused (see gotchas)
│   └── Enrollment/
│       ├── EnrollStudentInOffering.php   Idempotent upsert + flips matching CourseSurvey.is_processed
│       └── DropStudentFromOffering.php   Sets status to Dropped/Withdrawn on the pivot row
├── Channels/                WhatsAppChannel + WhatsAppMessage DTO
├── Enums/                   Backed enums w/ Arabic `label()` methods (EnrollmentStatus, GradeStatus,
│                            StudentStatus, ApplicationStatus, Season, CourseType, AddDropType,
│                            SuspensionStatus, NotificationChannel)
├── Filament/
│   ├── Pages/               Admin-panel custom pages (ReportsPage, ExamListPage, StudentSurveysList, …)
│   ├── Resources/           14 admin Resources (Students, Applications, CourseOfferings, Grades,
│   │                        Faculty, StudentEnrollments, AddDropRequests, Semesters, Courses,
│   │                        Specializations, SuspensionRequests, ThesisRegistrations, AuditLogs, Users)
│   ├── Student/Pages/       9 student-portal Livewire pages (Dashboard, StudentCourses, CourseTree,
│   │                        CourseSurvey, AddDrop, Grades, Suspension, Thesis, Clearance)
│   └── Widgets/             Admin dashboard widgets (stats, applications over time, per-specialization)
├── Http/
│   ├── Controllers/         Public + portal controllers (Registration, ApplicationTracking,
│   │                        PrintCourseStudents)
│   └── Middleware/          EnsureStudentRole
├── Models/                  17 Eloquent models — see [[_brain/project-context/project-overview|overview]]
├── Notifications/           9 notifications + Concerns/HasWhatsAppChannel trait
├── Observers/               StudentObserver (cascade), GradeObserver (GPA recalc),
│                            AuditObserver (per-model field watchlist → AuditLog)
├── Policies/                14 policies — one per resource
├── Providers/
│   ├── AppServiceProvider.php          Gate::before super_admin bypass + observer registration
│   └── Filament/{Admin,Student}PanelProvider.php
├── Rules/                   ArabicOnly, MinArabicWords (custom validation)
├── Services/                AuditService, EnrollmentNumberService, GradeCalculationService,
│                            NotificationService, PdfService, TrackingCodeService, WhatsAppService
└── View/Components/Form/    Blade form components (Button, Input, Label) used by public registration
bootstrap/
├── app.php                  Routing, middleware, 403→/portal/login exception handler
└── providers.php            AppServiceProvider + both PanelProviders
config/                      Standard Laravel config (services.php has `qonvo` entry)
database/
├── factories/               11 factories (incl. StudentEnrollmentFactory, GradeFactory)
├── migrations/              38 migrations — core schema on 2026-03-09, incremental additions through 2026-04-16
└── seeders/                 RolesAndPermissionsSeeder → Spec/Semester/Course/Admin/Application/Student
resources/
├── css/filament/            Per-panel Filament themes (admin/student)
└── views/
    ├── components/          admin/*, form/*, layout/app (shared Blade components)
    ├── filament/            Filament page + resource Blade overrides (admin + student)
    ├── pdfs/                transcript, clearance_form, suspension_form, thesis_form
    ├── prints/              course-students (printable roster)
    ├── registration/        create.blade.php, track.blade.php (public pages)
    └── welcome.blade.php
routes/
├── web.php                  /, /apply, /apply/track, /admin/course-offerings/{co}/print-students
└── console.php              inspire (default)
tests/
├── Pest.php                 studentWithRecord() + adminUser() + toBeOne helpers; RefreshDatabase on all
├── Feature/                 EnrollmentConsistency, GradeApproval, StudentPortal, SuspensionRequest,
│                            ThesisRegistration, Example
└── Unit/                    GradeCalculationService, Grade (letter conversion), StudentEligibility, Example
_brain/                      Session memory (see [[#Memory System]])
```

## Coding Conventions

**Observed from the code, not just the style guide:**

**PHP / Laravel**
- Every model: `protected $fillable`, `casts()` method (never `$casts` property), explicit relationship return types (`HasMany`, `BelongsTo`, etc.)
- Enums are **backed string enums** in Arabic-labeled `label()` methods — e.g. `EnrollmentStatus::Enrolled->value === 'enrolled'`, `->label() === 'مسجل'`
- Status-filtered relations are named with an `active*` prefix (e.g. `Student::activeEnrollments()`, `CourseOffering::activeEnrollments()`) — presentation code **always** uses these, never unfiltered
- `Model::booted()` is used for auto-derived fields — see `Grade` and `TransferCourse` auto-compute total/letter/points on save
- Constructor property promotion is standard; services and actions inject dependencies via constructor (no static facade calls in business logic)
- `DB::transaction(...)` wraps multi-row writes (see `ApproveApplication`, `EnrollStudentInOffering`)

**Filament patterns**
- One folder per Resource under `app/Filament/Resources/{Resource}/` containing `{Resource}Resource.php` + `Pages/` subfolder; **some** resources have `RelationManagers/` (e.g. Semesters); stub `Schemas/` and `Tables/` folders have been removed
- Arabic labels on every field/column: `->label('الفصل الدراسي')` — never rely on auto-derived labels
- Navigation is organized via `$navigationGroup` (Arabic names: `الإعدادات الأكاديمية`, `إدارة الطلاب`, `هيئة التدريس`)
- Faculty/secretary-scoped Resources override `getEloquentQuery()` and `shouldRegisterNavigation()` based on `auth()->user()->role`
- Student-panel pages extend `Filament\Pages\Page` and define their own Livewire public properties + methods (not Filament form builder)

**Actions & Services (split)**
- **Actions** — orchestrate a single business operation, often invokable (`__invoke`), called from Resources/Pages/Controllers
- **Services** — stateless domain logic reused across actions (GPA calc, enrollment number generation, notifications, audit logging)
- Rule of thumb in this codebase: if it writes to multiple tables or emits side effects, it's an Action. If it's pure computation or an external integration, it's a Service.

**Validation**
- Public forms: inline `$request->validate()` with Arabic error messages (see `RegistrationController`) — **no** Form Request classes currently exist
- Filament forms: component-level `->required()`, `->regex()`, `->rules([new ArabicOnly, ...])`
- Custom rules: `ArabicOnly` (with optional digits), `MinArabicWords`

**Naming**
- Table names: snake_case plural (`student_enrollments`, `course_offerings`)
- Pivot tables: snake_case singular alphabetical (`semester_course`, `course_specialization`)
- Enum keys: `TitleCase` (per guidelines); enum values: `snake_case` strings
- Blade files: kebab-case (`view-student.blade.php`)
- Routes: camelCase names (`registration.create`, `course-offering.print-students`)

**Config & environment**
- `env()` appears only in `config/*.php`; application code uses `config('services.qonvo.api_token')`
- WhatsApp delivery is **opt-in via config** — absence of `QONVO_API_TOKEN` silently disables the channel

**Formatting & testing**
- `vendor/bin/pint --dirty --format agent` after every PHP edit
- Tests use `RefreshDatabase` (see `tests/Pest.php`) and shared helpers `studentWithRecord()` / `adminUser()`
- Feature tests exercise actions end-to-end (see `EnrollmentConsistencyTest` — it drives `EnrollStudentInOffering` + `DropStudentFromOffering` + observer cascade)

## Architectural Patterns

- **Observer-driven cascades.** Business invariants (GPA recalc, enrollment withdrawal, audit trail) are triggered by Eloquent observers registered in `AppServiceProvider::boot()`, not scattered through controllers. See [[_brain/project-context/architecture]].
- **Status-as-pivot.** `StudentEnrollment` is a rich pivot — it carries `status: enrolled/dropped/withdrawn` rather than being deleted. Every query must filter by status.
- **Dual Filament panels sharing one `User`.** `canAccessPanel()` on `User` decides which panel each role can enter. Student role is gated by both `role === 'student'` and `is_active`.
- **Arabic-first RTL.** Every UI string, notification message, validation error, and enum label is authored in Arabic. English is used only for raw identifiers (emails, enrollment numbers, English name field).
- **Single active semester.** `Semester::where('is_active', true)->first()` is the canonical "current term" lookup — called from many pages. The system assumes exactly one active semester at a time.
- **Audit watchlist.** `AuditObserver::WATCHED_FIELDS` is a per-model allow-list of fields whose changes are recorded — silently ignores unlisted fields.

## Memory System

All session notes live in `_brain/`, organized by type: [[_brain/daily-notes/README|daily-notes]], [[_brain/decisions/README|decisions]], [[_brain/bugs-and-fixes/README|bugs-and-fixes]], [[_brain/research/README|research]], and [[_brain/project-context/README|project-context]].

Conventions (Obsidian-flavored markdown):

- **File names:** ISO date prefix — `YYYY-MM-DD-title.md` (e.g. `2026-04-17-enrollment-refactor.md`). `project-context/` notes may skip the date and use a topic-only name.
- **Frontmatter:** every note begins with a YAML block —
  ```yaml
  ---
  date: YYYY-MM-DD
  tags: [relevant, tags]
  related: [[linked-note]]
  ---
  ```
- **Links:** use `[[wikilinks]]` between notes. Prefer linking the concept over pasting the summary.
- **Tags:** use `#tags` inline where they aid discovery (e.g. `#enrollment`, `#filament`, `#bug`).
- **Scope:** capture *why* and *what's non-obvious*. Do not duplicate code, git log, or CLAUDE.md.

## Session Habits

**Start of session:**

1. Read this file (`CLAUDE.md`).
2. Scan the most recent entries in `_brain/daily-notes/` — at minimum today's and yesterday's — to pick up in-flight context.
3. If the user references prior work, check [[_brain/decisions/README|decisions]] and [[_brain/project-context/README|project-context]] for anchors before re-deriving from code.

**End of session:**

1. Create or append to today's file in `_brain/daily-notes/` (`YYYY-MM-DD-<session-topic>.md`) with:
   - what was done (brief),
   - what decisions were made → link to new notes in [[_brain/decisions/README|decisions]],
   - bugs fixed → link to [[_brain/bugs-and-fixes/README|bugs-and-fixes]],
   - loose ends / next steps.
2. Use frontmatter, `[[wikilinks]]`, and `#tags`.
3. Promote anything durable (architectural rules, domain invariants) to [[_brain/project-context/README|project-context]].

## Automatic Session Rules

These rules run on autopilot. Do not wait to be asked.

### 1. Start of every session (before anything else)

- Read this entire `CLAUDE.md` file.
- Read the **3 most recent files** in [[_brain/daily-notes/README|_brain/daily-notes/]] (sort by filename date — newest first).
- Reply with a **5-bullet summary** covering:
  1. What we're currently working on,
  2. Where we left off last session,
  3. Any open decisions or loose ends,
  4. Known bugs still in flight,
  5. Suggested next step.

Do this *before* responding to the user's first task.

### 2. End of every session (before closing)

- Remind the user: *"We should save a session note before closing."*
- Then **create today's daily note automatically** in `_brain/daily-notes/YYYY-MM-DD-<topic>.md` — do not wait for permission. Include frontmatter, what was done, decisions linked, bugs linked, and next steps.

### 3. Whenever a significant decision is made

- **Automatically** create a note in `_brain/decisions/YYYY-MM-DD-<decision-title>.md`. No need to ask.
- Capture: context, decision, alternatives considered, consequences.
- Link it from the day's [[_brain/daily-notes/README|daily note]].

A "significant decision" = anything that shapes future code (architecture, naming conventions, library choices, domain rules, trade-offs between approaches). When unsure, log it.

### 4. Whenever a bug is fixed

- **Automatically** create a note in `_brain/bugs-and-fixes/YYYY-MM-DD-<bug-title>.md`. No need to ask.
- Capture: symptoms, root cause, fix, and any regression guard (tests added, invariants documented).
- Link it from the day's [[_brain/daily-notes/README|daily note]].

### Guiding principle

**When in doubt, over-document rather than under-document. It costs nothing to create a note.**

A forgotten reason is more expensive than a redundant file.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.20
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

</laravel-boost-guidelines>
