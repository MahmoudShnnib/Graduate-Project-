---
date: 2026-04-17
tags: [meta, readme, project-context]
related: [[../../CLAUDE]], [[project-overview]], [[architecture]], [[gotchas]]
---

# Project Context

Stable, long-lived notes about the project itself — domain model, key entities, business rules, stakeholders, and cross-cutting concepts that don't belong in code comments but are essential for understanding *why the system is shaped the way it is*.

Unlike [[daily-notes]], these notes don't need date prefixes — name them by topic (e.g. `enrollment-lifecycle.md`, `grade-distribution-rules.md`). Tag with `#context` plus the domain, and link from [[decisions]] and [[research]] that reference the concepts.

## Core notes

- [[project-overview]] — what the system does, how the pieces fit, key files, what's incomplete
- [[architecture]] — text diagram of components, data layer, observer wiring, communication flows
- [[gotchas]] — non-obvious behavior, landmines, and things to check before editing
