---
date: 2026-06-19
tags: [bug, fix, telegram, polling, network, resilience]
related:
  - "[[../project-context/telegram-integration-guide]]"
  - "[[../decisions/2026-06-18-telegram-notification-channel]]"
---

# `telegram:poll --loop` crashed on a transient network timeout

## Symptom
`php artisan telegram:poll --loop` died with an uncaught
`Illuminate\Http\Client\ConnectionException` — `cURL error 28: Connection timed out
after 5002 milliseconds` reaching `api.telegram.org/.../getUpdates` (long-poll
`timeout=50`). The 5002ms = the `connectTimeout(5)` firing on a single slow connect.

## Root cause
Two issues:
1. The long-running `--loop` worker had **no error handling** around `getUpdates()`, so
   the first transient blip killed the whole worker (in prod Supervisor would restart it,
   but a single flap shouldn't tear it down).
2. `getUpdates` used an aggressive `connectTimeout(5)` with no retry — fine for a quick
   one-shot, too brittle for a continuous poller on a flaky link.

Connectivity itself was fine (`getMe`/`getUpdates` succeeded before and after) — it was a
momentary network hiccup.

## Fix
- `PollTelegramUpdates::handle()` — wrap the drain in `try/catch (\Throwable)`: `report()`
  + warn + `sleep(3)` then continue in `--loop` mode; a single-drain run just stops (the
  next scheduled run retries).
- `TelegramService::getUpdates()` — `connectTimeout(15)`, `timeout($timeout + 15)`, and
  `->retry(2, 1000, throw: false)` so brief blips self-heal before surfacing.

## Verification
- New test `telegram:poll survives a connection timeout without crashing`
  (`Http::fake` throws `ConnectionException` → command still `assertSuccessful()`).
- 16 telegram-verification tests green; a live single-drain (`php artisan telegram:poll`)
  exits 0.

## Note
Outbound polling is the no-webhook path; this only hardens it. The command-level catch is
the real safety net — the service `retry` just reduces how often a blip is even seen.
