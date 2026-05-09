# SYSTEM_SPEC.md — بوابة الطالب (Student Portal)

A mobile app for graduate students at a Libyan university faculty.

The app is the student-facing side of an academic management system covering the full graduate-studies lifecycle — from enrollment through coursework to thesis defense and final clearance. All UI must be in Arabic with right-to-left layout, since both the students and the institution are Arabic-speaking.

---

## WHO THE USER IS

A graduate student enrolled in one of the faculty's specializations (Computer Science, Information Systems, etc.). Each student has:

- An Arabic full name and an English full name
- A unique enrollment number (e.g. "26CS101") encoding their admission year, specialization, and whether they're a transfer student
- A specialization (one fixed track)
- An academic status that controls what they can do in the app
- A cumulative GPA computed from their published grades
- A history of enrollments, grades, requests, warnings, and (eventually) a thesis

---

## ACADEMIC STATUS — THE MASTER GATE

Every student is in exactly one of these states at any time:

- **مستمر (Active)** — normal state, can use every feature
- **قيده موقوف (On Hold)** — suspension was approved, blocked from all write actions until manually reactivated
- **منقطع (Inactive)** — failed to submit the course survey on time, blocked from write actions until next semester
- **منتقل (Transferred)** — transferred to another faculty, account effectively closed
- **متخرج (Graduated)** — completed all requirements
- **مفصول (Dismissed)** — dismissed for repeated failures or repeated inactivity, account closed

Most action buttons (submit survey, request add/drop, request suspension, register thesis, etc.) require status = Active. Other statuses should make this visible to the student so they understand why they can't act.

A student can also accumulate warnings (course failure warnings, consecutive inactivity warnings). A second warning of the same type triggers automatic dismissal.

---

## SEMESTERS

The system runs on semesters (autumn / spring of a given year). Exactly one semester is "active" at any time. Within each active semester, the admin can independently open or close:

- The course survey window
- The add/drop request window

The student needs to know the current semester, what's open right now, and the deadlines.

---

## WHAT THE STUDENT CAN DO

### 1. VIEW CURRENT ENROLLMENTS

The list of courses the student is currently enrolled in for the active semester. Each enrollment has a status (enrolled, dropped, withdrawn) and is tied to a course offering (course + instructor + semester).

### 2. BROWSE THE COURSE TREE

The full curriculum for the student's specialization, split into mandatory and elective courses. For each course the student can see whether they've already passed it, are currently enrolled in it, failed it, or haven't taken it yet.

### 3. SUBMIT A COURSE SURVEY

Before each semester, students rank/wishlist the courses they want to take. The admin uses these surveys to decide which courses to actually offer. The student picks from courses available in their specialization that they haven't already passed. There's no hard cap on how many they can wishlist. Submitting requires the survey window to be open AND status = Active. Students who don't submit before the window closes get auto-marked Inactive (this is a system rule, not something they do — but they should be warned about it).

### 4. REQUEST ADD/DROP

During the add/drop window of an active semester, students can request to add or drop courses. Limits: max 3 add requests and max 3 drop requests per semester. Each request has a status (pending, approved, rejected) decided by admin. Students see a history of all their past requests.

### 5. VIEW GRADES

Each enrollment eventually gets a grade, broken into coursework (أعمال السنة) and final exam (الامتحان النهائي), summed into a total + letter grade + grade points. Grades have a two-stage publishing process:

- Coursework can be published first (midterm visibility)
- The full grade (with final exam) is published later

Until the admin publishes, the student sees a "not yet published" placeholder. The cumulative GPA is recomputed automatically when grades are published.

### 6. REQUEST A SUSPENSION (إيقاف قيد)

A student can request to suspend their enrollment for a specific semester (e.g. medical reasons, military service). Rules:

- If a previous suspension request was approved → no further requests ever allowed
- If a request is currently pending → no new requests until resolved
- If a request was rejected for a specific semester → can't re-request for that same semester

The student picks a semester, writes a reason, submits. Approved suspension flips their status to On Hold globally.

### 7. REGISTER A THESIS, REQUEST DISCUSSION, GET FINAL APPROVAL

This is a multi-phase pipeline that ends in graduation:

- **Phase 1 — Thesis Registration:** The student becomes eligible once they've passed enough courses and meet a GPA threshold. They pick a supervisor (internal faculty member or external person) and register their thesis title.
- **Phase 2 — Discussion Request:** Once the thesis work is done, the student requests a discussion (defense). Admin schedules a date.
- **Phase 3 — Discussion Session:** Admin records the outcome — discussed, accepted with edits, or rejected.
- **Phase 4 — Final Approval:** After all edits, admin grants final approval. This unlocks clearance.

The student needs visibility into which phase they're in, what's blocking them, and any admin notes.

### 8. EXCEPTIONAL OPPORTUNITY REQUESTS (فرص استثنائية)

For courses the student has failed, they can request a special re-examination opportunity. They pick the failed course, choose the opportunity type, write a justification, submit. Each request has a status (pending, approved, rejected).

### 9. CLEARANCE (إخلاء الطرف)

Only available once the student's thesis discussion pipeline reaches final completion. When unlocked, the student can view and download/print their official clearance form (PDF), which proves they've completed all requirements and can graduate.

### 10. NOTIFICATIONS

The student receives notifications for: application status changes, grade publishing, request approvals/rejections, warnings issued, suspension/thesis updates, dismissal, etc. Each notification has a timestamp and read/unread state.

### 11. PROFILE

View their personal info (Arabic name, English name, national ID, phone, email, specialization, enrollment number, admission date, status, GPA, supervisor if assigned). Possibly change password, manage notification preferences.

---

## DATA THE STUDENT INTERACTS WITH

- Their student profile (name, IDs, contact, status, GPA)
- Their specialization and the full course tree under it
- Semesters (with windows open/closed)
- Course offerings (course + instructor + semester + capacity)
- Their enrollments (with statuses)
- Their grades (with publishing state)
- Their course surveys (wishlists)
- Their add/drop requests (with limits and history)
- Their suspension requests (with blocking rules)
- Their thesis registration + discussion pipeline state
- Their exceptional opportunity requests
- Their warnings (course failure, consecutive inactivity)
- Their clearance form (when unlocked)
- Their notifications

---

## TONE AND CONTEXT

This is an official academic institution, not a consumer product. Students use this for high-stakes actions (registering for a defense, requesting suspension, viewing grades). The experience should feel trustworthy and clear. Many students will be older (graduate students are typically 22+, often working professionals).
