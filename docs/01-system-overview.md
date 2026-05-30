# What This Project Is

This is a **graduate-studies management system** for a Libyan university faculty, built as a graduation project using **Laravel 12 + Filament v5**. It replaces manual paperwork that admin secretaries do by hand.

The entire UI is in **Arabic (RTL)**. English only appears in raw identifiers like emails and enrollment numbers. The system is hosted locally via Laravel Herd at `https://graduate-project.test`.

---

## Who Uses It and How

There are four types of users:

**Applicants** (not yet in the system) use the public form at `/apply` to submit their application. They can track its status at `/apply/track` using a tracking code + their national ID.

**Admins and Secretaries** use the Filament admin panel at `/admin`. They review applications, approve them, manage students, create semesters and course offerings, approve grades, handle requests, and generate reports and PDFs.

**Faculty** also log into `/admin` but they only see their own assigned courses ("موادي"). They enter grades for students enrolled in their courses.

**Students** use the Filament student portal at `/portal` (branded "بوابة الطالب"). They can view their grades and course tree, submit course survey preferences, request add/drop, submit suspension or thesis requests, and eventually request clearance.

---

## The Full Student Journey From Start to Finish

1. **Application** — an applicant fills the public form at `/apply`. The system creates an Application row with a tracking code.

2. **Admin review** — a secretary or admin opens the application in `/admin/applications`, sets an exam date and result, then approves it. Approval runs the `ApproveApplication` action in a database transaction: it creates a `User` row, a `Student` row, generates an enrollment number (format `{YY}{SpecCode}{Flag}{3-digit-seq}`), and sends a welcome notification by email (and optionally WhatsApp).

3. **Semester setup** — admin creates a Semester, marks it active, attaches courses to it (so students can request them in the survey), then creates CourseOffering rows linking each course to a faculty member.

4. **Course survey** — while `survey_open = true`, students go to `/portal/course-survey-page` and select which courses they want. No upper cap on selections. Admin sees the demand at `/admin/courses-survey-results` and bulk-opens offerings based on that demand.

5. **Enrollment** — admin manually enrolls students into each offering via `/admin/course-offerings/{id}/enroll`. The system enforces: max 4 enrollments per semester per student, course must be in the student's specialization, offering must be open, student must be Active.

6. **Add/drop** — during the semester's add/drop window, students submit add/drop requests (max 3 of each). Admin approves/rejects them.

7. **Grade entry** — faculty enters coursework and final grades. The system auto-computes total, letter (A/B+/B/C+/C/D+/D/F), and grade points. Faculty submits for admin approval.

8. **Grade approval and publishing** — admin approves grades (quality gate), then publishes them: midterm publish shows only coursework grades; final publish shows everything, triggers GPA recalculation, and fires warnings if any grade is F.

9. **Warnings and dismissal** — two F grades on the same course = dismissed. Two consecutive inactive semesters = dismissed. Dismissed students can submit an exceptional request.

10. **Thesis** — when a student completes all their courses (all general + all specialized + ≥2 electives) with GPA ≥ 3.0, they can submit a thesis registration with a title, supervisor, and proposal PDF. Admin prints it for the ministry, then approves or rejects.

11. **Discussion (مناقشة)** — after thesis approval, the student requests a discussion. Admin goes through a 4-phase pipeline: schedule date → mark discussed → decide result (accept/reject/accept with edits) → mark binding + submission done.

12. **Clearance** — only after discussion is fully completed can the student submit a clearance request. Admin issues clearance, which generates a transcript PDF and clearance certificate PDF.

13. **Graduation** — student status is set to Graduated. Portal access stays read-only forever.

---

## Tech Stack Summary

- PHP 8.5, Laravel 12
- Filament v5 (two panels: `admin` + `student`)
- Livewire 4 (Filament pages + student portal pages)
- Tailwind CSS 4 + Vite 7
- Spatie Laravel Permission + Filament Shield (for RBAC)
- DomPDF for transcripts/forms
- mPDF for Arabic ministry-style print templates (student letters, grade sheets, exam attendance)
- Qonvo API (`qonvo.ly`) for WhatsApp notifications — silently disabled if `QONVO_API_TOKEN` is unset
- Pest 4 + PHPUnit 12 for testing
- MySQL database, 38 migrations

---

## Four Roles

- **super_admin** — bypasses all policies via `Gate::before`. Full access to everything.
- **secretary** — can manage students, applications, courses, grades. Limited user management.
- **faculty** — only sees their own courses and grades.
- **student** — only the `/portal`. Can't access `/admin` at all.

Both a `users.role` string column AND Spatie roles are used — both must stay in sync (the system handles this automatically on user create/edit).

---

## Important Constraints

- Exactly **one active semester** at a time (no DB constraint, admin must manage this manually)
- **StudentEnrollment rows are never deleted** — only their `status` is updated (enrolled / dropped / withdrawn)
- All **notifications run synchronously** (no queue) — slow WhatsApp API = slow HTTP response
- The **grading scale is hardcoded**: 85=A, 75=B+, 70=B, 65=C+, 60=C, 55=D+, 50=D, <50=F
- The `mobile/` directory in this repo is a personal side project (React Native) — it is **not part of this system** and should be ignored
