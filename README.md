# نظام إدارة الدراسات العليا — Graduate Studies Management System

> An Arabic-first (RTL) web platform that automates the full academic lifecycle of a Libyan university's graduate-studies faculty — from public application intake all the way to graduation clearance.

<p align="left">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Filament-v5-FDAE4B" alt="Filament v5">
  <img src="https://img.shields.io/badge/Livewire-4-4E56A6" alt="Livewire 4">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-38BDF8?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Tested_with-Pest_4-5A67D8" alt="Pest 4">
</p>

---

## 📖 About

This is a **graduation project**: a single-faculty graduate-studies admissions, enrollment, and academic-lifecycle management system. Its goal is to replace the manual, paper-driven work that admin secretaries do by hand — application intake, admissions tracking, enrollment, add/drop, grade entry and approval, GPA calculation, thesis supervision, suspension requests, and end-of-studies clearance.

The entire interface is authored in **Arabic and runs right-to-left**. English appears only in raw identifiers (emails, enrollment numbers, and the applicant's English name required for the bachelor certificate).

The application is built around **three actors**, each with their own experience:

| Actor | Entry point | What they can do |
|---|---|---|
| **Applicants** | `/apply` (public) | Submit a multi-step application, then track its status at `/apply/track` using a tracking code + national ID |
| **Staff** — super-admin · secretary · faculty | `/admin` (Filament) | Review applications, manage students & courses, enter and approve grades, run reports, and generate official PDFs |
| **Students** | `/portal` (Filament, RTL) | Browse the course tree, submit course surveys, view grades, and file add/drop · suspension · thesis · clearance requests |

---

## ✨ Features

### Public application (`/apply`)
- Multi-step Arabic intake form (personal data, specialization, transfer status, bachelor-certificate upload) with custom Arabic validation rules (`ArabicOnly`, `MinArabicWords`, Libyan phone format, 12-digit national ID).
- Self-service status tracking by tracking code + national ID.

### Admin review (`/admin`)
- Secretaries/super-admins review applications and set exam results.
- On approval, the system **auto-provisions the student**: creates the `User` + `Student` rows, generates a year/specialization/transfer-scoped **enrollment number**, and sends a welcome notification — all in a single DB transaction.

### Academic cycle
- Per-semester course offerings with a configurable coursework/final grade split (default 40/60).
- A pre-enrollment **course survey** where students rank desired courses; staff then roster students manually.
- **Add/drop** requests (max 3 each), open only during the semester's add-drop window.
- Faculty-scoped grade entry ("موادي") with auto-computed letter grade and grade points, followed by super-admin/secretary **grade approval** that triggers GPA recalculation.

### Student portal (`/portal`)
- Course tree, transcript/grades, course surveys, and add/drop · suspension · thesis · clearance requests.
- All reads go through status-filtered relations and only show **approved** grades.

### Support flows
- **Thesis registration** + a 4-phase discussion pipeline (review → scheduled → discussed → result).
- **Audit logging** on a per-model allow-list of sensitive fields.
- **Dual-channel notifications** — email + optional WhatsApp via the Qonvo gateway (auto-falls back to email-only when no token is configured).
- **PDF generation** via mPDF — clearance forms, graduation certificates, ministry/exam/grade forms, and printable course rosters.

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Runtime** | PHP 8.2+ |
| **Framework** | Laravel 12 (streamlined structure — middleware in `bootstrap/app.php`) |
| **Admin & portal UI** | Filament v5 — two panels: `admin` (`/admin`) and `student` (`/portal`) |
| **Interactivity** | Livewire 4 |
| **Styling** | Tailwind CSS 4 + Vite 7 (per-panel themes) |
| **Authorization** | Laravel Gates/Policies + `spatie/laravel-permission` + `bezhansalleh/filament-shield` |
| **PDF** | `mpdf/mpdf` |
| **WhatsApp** | Custom channel → Qonvo API (`qonvo.ly`) with Libyan phone normalization |
| **Testing** | Pest 4 / PHPUnit 12 / Mockery (`RefreshDatabase` everywhere) |

---

## 🏗 Architecture

The system follows a consistent, observer-driven flow that keeps domain invariants self-healing:

```
public form ─► Action ─► Model ─► (Observer) ─► Service ─► Notification
                                       │
                                       └─► AuditLog
```

- **Thin controllers/pages → Actions → Model writes → Observer side-effects → Services for reusable logic.**
- **Actions** orchestrate a single business operation (e.g. `ApproveApplication`, `EnrollStudentInOffering`); **Services** hold stateless domain logic (GPA calc, enrollment-number generation, notifications, audit).
- **Observers** are the glue: dropping a student cascades their enrollments to `Withdrawn`; approving a grade recalculates GPA — the caller never has to remember to do it.
- **Status-as-pivot:** `StudentEnrollment` is a rich pivot carrying `enrolled / dropped / withdrawn` rather than being deleted, so every query filters by status (`activeEnrollments`, …).
- **Single active semester:** exactly one `Semester` is active at a time and acts as the canonical "current term".
- **Hybrid roles:** a simple `users.role` column **and** Spatie roles are both checked; `super_admin` bypasses all gates via `Gate::before`.

---

## 🚀 Getting Started

### Requirements
- PHP **8.2+**
- Composer
- Node.js + npm
- A database (SQLite works out of the box for local dev)

### Installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — migrate and seed the skeleton (roles, specializations,
#    semesters, courses, an admin account)
php artisan migrate --seed

# 4. Build front-end assets
npm run build
```

### Running locally

```bash
# Single command — serves the app, queue listener, and Vite together
composer run dev
```

Then open the app at the configured URL (Laravel Herd serves it at `https://graduate-project.test`).

> **Want sample data?** The default seeders only build the skeleton. Run the dedicated `DevDataSeeder` to populate dummy students, applications, offerings, and grades for local testing.

### WhatsApp (optional)
WhatsApp delivery is opt-in. Set `QONVO_API_TOKEN` in `.env` to enable it; without it the system silently uses email only.

---

## 🧪 Testing

The project uses **Pest 4** with `RefreshDatabase` on every test.

```bash
# Run the whole suite (compact output)
php artisan test --compact

# Filter to one file or test
php artisan test --compact --filter=GradeApproval
```

Feature tests exercise actions end-to-end (enrollment cascade, grade approval + GPA recalc, suspension flush, course-survey rules), and unit tests cover the grading service and eligibility logic.

---

## 📂 Project Structure

```
app/
├── Actions/        Single-responsibility business operations (ApproveApplication, Enrollment/*)
├── Enums/          Backed string enums with Arabic label() methods
├── Filament/       Admin Resources + custom pages, and the 9 student-portal Livewire pages
├── Models/         17 Eloquent models (Student, CourseOffering, StudentEnrollment, Grade, …)
├── Observers/      StudentObserver (cascade), GradeObserver (GPA), AuditObserver (audit trail)
├── Policies/       One policy per resource
└── Services/       GPA calc, enrollment numbers, notifications, audit, WhatsApp
resources/views/
├── filament/       Panel page + resource Blade overrides (admin + student)
└── prints/         mPDF documents (clearance, certificates, rosters, forms)
_brain/             Project knowledge base — architecture, decisions, gotchas, daily notes
```

---

## 📝 License

This project is built on the Laravel framework, which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). The graduate-studies application itself is an academic graduation project.
