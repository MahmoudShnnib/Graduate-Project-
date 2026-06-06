---
date: 2026-06-04
tags: [documentation, use-cases, uml, report]
related: [[use-case-descriptions]], [[project-overview]]
---

# Use Case Descriptions for the Report (§2.5)

## What was done
- Authored English, report-ready **use case description tables** for all 12 use cases shown
  in the Applicant + Student use case diagram, matching the standard Login-example format
  (Use Case ID / Name / Primary Actor / Goal / Preconditions / Trigger / Postconditions
  Success+Failure) plus **Main Success Scenario**, **Alternative Flows**, and **Exceptions**.
- Output: [[use-case-descriptions]] in `_brain/project-context/`.
- Use cases covered: UC-001 Submit Application, UC-002 Track Application, UC-003 Upload
  Documents, UC-004 View Academic Status, UC-005 Submit Course Survey, UC-006 View Grades,
  UC-007 Add/Drop Request, UC-008 Suspension Request, UC-009 Thesis Registration,
  UC-010 Discussion Request, UC-011 Clearance Request, UC-012 Download PDFs.

## How it was derived
- Flows, preconditions, limits, and error messages were extracted from the real
  implementation (RegistrationController, ApplicationTrackingController, student Filament
  pages, CreateApplication action, services) — not invented. Arabic in-app messages were
  translated to English for the report.

## Decisions
- "Submit / Track Application" (one diagram bubble) split into two use cases (UC-001, UC-002)
  for cleaner descriptions → gives 12 total.
- Discussion Request documented as its own use case (UC-010) even though it lives inside the
  Thesis Registration page in code.

## Diagrams
- Added `use-case-diagrams.html` (project root) — one **activity diagram per use case** (UC-001
  to UC-012), English, built with Mermaid `flowchart TD`. Reuses the exact CSS + Mermaid init
  from `diagrams.html` (same color key: green=start/end, yellow=decision, red=error/rejected,
  blue=action) so screenshots match the existing report figures. Each diagram visualises the
  main flow plus alternative/exception branches from [[use-case-descriptions]].

## Loose ends / next steps
- Optional: an English version of the use case diagram itself (current report figure is Arabic).
