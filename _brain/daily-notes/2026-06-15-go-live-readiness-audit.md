---
date: 2026-06-15
tags: [daily, production, go-live, audit, security, ops, blocker]
related:
  - "[[../project-context/go-live-readiness-audit]]"
  - "[[../project-context/deployment-checklist]]"
  - "[[../project-context/production-hardening-backlog]]"
---

# 2026-06-15 — Go-live readiness audit (testing data → real students)

User (system live on Dokploy, final testing phase) asked: deep-research what must change/fix for
**final production with real students**, not testing. Planning only — **no code changed**.

## What was done
- Fresh 5-dimension read-only audit against **current** code (not trusting the 2026-06-04 notes):
  security/authz, infra/deploy, data-integrity/Postgres, real-user readiness, test health. Ran 5
  parallel subagents, then hand-verified the blockers (entrypoint, AdminUserSeeder, nginx, mPDF).
- Full report: [[../project-context/go-live-readiness-audit]].

## Headline findings
- **3 blockers + auto-migrate-without-backup:**
  - B1 `AdminUserSeeder` (entrypoint:74) seeds `admin123`/`secretary123` + 4 fake faculty + fake
    student "ppsan" into prod.
  - B2 prod DB already has dummy data; fresh-install gate means a redeploy won't clean it → manual
    `migrate:fresh` + clean re-seed needed.
  - B3 applicant PII (certificates/national-ID scans) on the **public** disk, served by nginx
    statically → world-readable by URL (the long-deferred "H7").
  - B4 `migrate --force` on every deploy with **no backup**.
- **High:** WhatsApp-only password recovery (H1); `MAIL_MAILER=log` silently drops welcome email
  (H2); semester add/drop window stuck in Sept 2025 → enrollment dead (H3); **mPDF tempDir not
  writable → PDFs likely 500 in prod** (H4); ephemeral logs + no monitoring (H5); enrollment-number
  race + NULL path (H6); unverified phone/email change = takeover vector (H7).
- **Confirmed GOOD (don't redo):** all 06-04 Docker hardening present; no IDOR; no mass-assignment;
  no committed secrets; login throttled; notifications can't corrupt business actions; **test suite
  318 passed / 0 failed**; the 403→302 wrong-panel redirect is intentional & correct.

## Decisions / open questions for the user
- M4: is frozen-GPA-on-grade-approval for non-Active students intended or a bug?
- Email in scope for prod (welcome + recovery fallback) or WhatsApp-only?
- Storage: persistent volume + gated route, or S3/R2 signed URLs?

## Next steps
- Get user sign-off on the execution order (B1+B2 → H3 → B4+H5 → B3 → H4 → H1+H2 → rest).
- Then implement (separate session, code changes) — start with B1/B2 (seeder split + clean-prod path).
- Caveat: green suite + test fixes are **uncommitted** on `docs-dev-test-data`; commit/merge or
  `main` regresses.
</content>
