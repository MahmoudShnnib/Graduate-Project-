---
date: 2026-06-04
tags: [dev-environment, gotcha, artisan-serve]
related: [[2026-06-04-production-hardening-execution]]
---

# "Unable to connect" to /apply/track — wrong dev-server port

#dev-environment #gotcha

## Symptom

`http://127.0.0.1:8000/apply/track` → "unable to connect" (curl returns `000`,
connection refused).

## Root cause

**No dev server was running for this project at all.**

- Port `8000`: nothing listening.
- Port `6969`: a `php artisan serve` *was* running — but its cwd was
  `/media/ppsan/d_drive/cowods/mobile pos`, i.e. a **completely different
  project**, not Graduate-Project. Easy to mistake one running `artisan serve`
  for "the" server when several projects are open.
- Herd's `https://graduate-project.test` was also unreachable at the time.

So the URL was fine; there was simply no server for *this* app bound to that port.

## Fix

Start a server for this project explicitly:

```bash
php artisan serve --port=8000
```

Then `/apply` and `/apply/track` both return `200`.

## How to diagnose next time

```bash
ss -ltnp | grep php            # what ports have a PHP server?
ls -l /proc/<pid>/cwd          # which PROJECT does that pid belong to?
```

Confirm the listening pid's cwd is *this* repo before assuming the server is
ours. Alternatively, use Herd at `https://graduate-project.test` (ensure Herd is
running) — that's the URL the app config expects.

## Guard

No code change — this is an environment/operator note, not an app bug.
