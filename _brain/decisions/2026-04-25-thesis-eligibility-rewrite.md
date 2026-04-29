---
date: 2026-04-25
tags: [thesis, eligibility, domain-rule]
related: [[2026-04-25-filters-tree-logs-offerings-print]]
---

# Thesis Eligibility: Specialization-Aware Rules

## Context
The original `isEligibleForThesis()` checked `completedCoursesCount() >= 10 && gpa >= 3.0` — a flat count of any 10 passed courses regardless of type or specialization.

## Decision
Eligibility now requires **all three** of:
1. **All** المقررات العامة (general) in the student's specialization — completed
2. **All** المقررات التخصصية (specialized) in the student's specialization — completed
3. **At least 2** المقررات الاختيارية (elective) from the student's specialization — any 2
4. **GPA ≥ 3.0**

"Completed" = approved grade with letter ≠ F, OR a passing transfer course (معادلة).

## Alternatives Considered
- Keep the flat count (simpler but wrong per domain rules)
- Count credits instead of courses (over-engineering for now)

## Consequences
- `Student::isEligibleForThesis()` now queries the `course_specialization` pivot
- `Student::getThesisEligibilityBreakdown()` exposes per-type progress for the UI
- `Student::getCompletedCourseIds()` is a reusable helper
- Thesis registration page shows a 4-card breakdown (general, specialized, elective, GPA)
- Admin approve action re-validates with the same logic
- Tests rewritten to create courses attached to specialization pivots
