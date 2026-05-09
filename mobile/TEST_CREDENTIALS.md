# TEST_CREDENTIALS.md

These are the credentials and profile for the test student that the seeders must create.
Claude Code uses this to verify the auth flow end-to-end against the real Laravel API.

---

## Test student login

- **Enrollment number:** `26CS101`
- **Password:** `password`

## Test student profile (seeders should match)

- **Arabic full name:** محمود علي الترهوني
- **English full name:** Mahmoud Ali Alterhouni
- **Specialization:** علوم الحاسوب (Computer Science)
- **Admission year:** 2026
- **Status:** مستمر (Active)
- **Phone:** +218910000000
- **Email:** test.student@example.ly

## Required seeded data for this student

To exercise every screen, the seeders must produce:

- The student profile above
- Enrollments across at least 2 semesters (one active, one previous)
- Some published grades from the previous semester (mix of letter grades, including at least one failed course so the exceptional request screen has data)
- Coursework-only published grades for the active semester (to exercise the two-stage publishing UI)
- A course tree under the Computer Science specialization with at least 6 mandatory and 4 elective courses
- 2 unread notifications
- 1 warning (course failure type)
- A second test student (`26CS102` / `password`) with status = `قيده موقوف` (On Hold) so the OnHold banner is verifiable

## Active semester

- Name: الفصل الدراسي الأول 2026
- Survey window: open
- Add/drop window: open
