---
date: 2026-05-10
tags: [decision, security, storage, files]
related: [[../bugs-and-fixes/2026-05-10-degree-image-403-private-disk]]
---

# File access policy: stay on public disk, rely on ULID obscurity

## Context

While fixing the degree-image 403 bug ([[../bugs-and-fixes/2026-05-10-degree-image-403-private-disk]]), the question came up: should student/applicant documents be auth-gated, or remain publicly accessible to anyone with the URL?

All current uploads in the project (applications, proposals, transcripts, clearance forms, grade sheets, and now student degrees) sit on the `public` disk and are served via the `public/storage` symlink. The only barrier to unauthorized access is the ULID filename (24 chars, ~120 bits of entropy).

## Decision

**Keep the public disk. No auth gate. No signed URLs. ULID-as-obscurity only.**

This applies uniformly to:

- `storage/app/public/students/degrees/*`
- `storage/app/public/applications/*`
- `storage/app/public/proposals/*`
- `storage/app/public/pdfs/*`
- `storage/app/public/grades/sheets/*`

## Alternatives considered

- **Signed temporary URLs** (move to private `local` disk, use `Storage::disk('local')->temporaryUrl($path, now()->addMinutes(15))` everywhere). Rejected for now — adds friction to every URL builder including email templates; current threat model accepts URL-leak risk.
- **Auth-gated controller** (route `/files/{path}` → checks `auth()->user()` and a policy). Rejected for now — most flexible but requires a new policy layer and changes to every URL builder.

## Consequences

- Anyone with a file URL can download it indefinitely — no expiry, no auth check. URLs in screenshots, browser history, server logs, or pasted in chat are effectively permanent leaks.
- Every new `FileUpload` must still explicitly call `->disk('public')` (the env default `local` is private; silent fallthrough breaks display).
- If a sensitive document class is added later (e.g. legal correspondence, signed NDAs), revisit this — the policy is **not** "everything is public forever," it's "the current document set is OK on public disk."
- Reversal path: switch to Option B (signed temporary URLs) is the lighter migration — change every `asset('storage/...')` to `Storage::disk('public')->temporaryUrl(...)`, and move/symlink files into `storage/app/private/`.

## How to apply

- Don't propose auth gates or signed URLs for the existing file types without first asking the user.
- If a new feature stores files that need real access control, raise the question explicitly: "this looks sensitive — should this file class be signed-URL or auth-gated?"
