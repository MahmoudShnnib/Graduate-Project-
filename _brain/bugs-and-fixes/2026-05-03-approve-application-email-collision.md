---
date: 2026-05-03
tags: [bug, application, approval, error-handling, hard-crash]
related: [[../daily-notes/2026-05-03-test-execution]], [[2026-05-03-duplicate-applications-allowed]]
status: fixed
---

# `ApproveApplication` crashes (UniqueConstraintViolationException) when application email already exists in `users`

## Symptoms

When an admin approves an application whose `email` matches an existing `User.email`, the action throws an unhandled SQLite/MySQL unique-constraint exception:

```
SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.email
INSERT INTO users (..., email, ...) VALUES (..., 'mohammed.zawai@example.com', ...)
```

This happens at `User::create([...])` inside `ApproveApplication::handle()`. The exception propagates back to the caller (admin UI's Filament action), which surfaces it as a generic Filament error toast or 500 page.

## Root cause

`app/Actions/ApproveApplication.php::handle()` calls `User::create([...])` without first checking whether a user with the same email already exists. The seeded data set has duplicates (e.g. `mohammed.zawai@example.com` exists as both User #4 and Application #1), and there's no guard.

## Why this is more than a one-off

Combined with [[2026-05-03-duplicate-applications-allowed]] (no uniqueness on apply intake), the same applicant can have many `Application` rows. When the **second** is approved, this crash fires. Production scenario:

1. Applicant submits the form on Monday
2. Admin approves it Tuesday → User+Student created
3. Applicant forgets, submits again Wednesday → new Application row (no guard)
4. Admin approves the Wednesday submission → crash

## Fix sketch

Inside `ApproveApplication::handle()`, pre-check + branch:

```php
$existingUser = User::where('email', $application->email)->first();
if ($existingUser) {
    throw new ApplicantAlreadyEnrolledException($application, $existingUser);
}
```

The Filament resource action then catches and shows an Arabic notification:
> "مقدم الطلب مسجل بالفعل كطالب. لا يمكن قبول الطلب مرة أخرى."

## Regression guard

```php
it('refuses to approve an application whose email already belongs to a User', function () {
    $existing = User::factory()->create(['email' => 'dup@example.com']);
    $application = Application::factory()->create(['email' => 'dup@example.com', 'status' => ApplicationStatus::Pending]);

    expect(fn () => app(ApproveApplication::class)->handle($application))
        ->toThrow(ApplicantAlreadyEnrolledException::class);

    expect(User::count())->toBe(1);          // no duplicate created
    expect($application->fresh()->status)->toBe(ApplicationStatus::Pending);  // status unchanged
});
```

## Related

- [[2026-05-03-duplicate-applications-allowed]] — the upstream cause
- [[../project-context/architecture#Approval path]] — the action sits inside a `DB::transaction`, so the partial state from this crash IS rolled back. But the user sees a stack trace, not a friendly error
