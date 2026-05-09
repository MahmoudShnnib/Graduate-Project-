---
date: 2026-05-03
tags: [bug, application, validation, security, public-form]
related: [[../daily-notes/2026-05-03-test-execution]]
status: fixed
---

# Duplicate applications can be created indefinitely (no email/national_id uniqueness check)

> Discovered during APPLY-019 of full system test run.

## Symptoms

A user can submit the public `/apply` form with the same email + national_id any number of times. Each submission creates a new `Application` row with a fresh `tracking_code`. Tested via:

```php
$svc = app(App\Actions\CreateApplication::class);
$data = ['email'=>'apply.test.b2@example.com', /*...*/ ];
$svc->handle($data);  // first call → ok
$svc->handle($data);  // second call → also ok, creates row #11
```

Result: 2 (or more) `applications` rows for the same applicant, all in `status=pending`. Admin sees both in the review queue.

## Root cause

Neither layer enforces uniqueness:

- `RegistrationController::store` validation rules (read at lines 90–115) — no `unique:applications,email` or `unique:applications,national_id` rule. No domain check beforehand.
- `app/Actions/CreateApplication.php::handle()` — just calls `Application::create([...])` with no pre-check.

Test plan §APPLY-019/019b/019c expected:
- pending or initially_approved → block re-submit
- accepted → block permanently
- rejected → allow new submission

None of these are implemented.

## Impact

- **Admin workload pollution** — same applicant appears 2+ times; admin must manually deduplicate.
- **Approval ambiguity** — if admin approves two duplicate applications, the **second** crashes with `UniqueConstraintViolationException` on `users.email` (verified during ADM-APP-005). See [[2026-05-03-approve-application-email-collision]].
- **Spam vector** — combined with no rate limit on `/apply` itself (separate from `/apply/sample` which is throttled), a script could fill the queue.

## Fix sketch

Add a domain rule layered in `RegistrationController::store` validation:

```php
'email' => [
    'required', 'email', 'max:255',
    function ($attr, $value, $fail) {
        $existing = Application::where('email', $value)->latest()->first();
        if ($existing && in_array($existing->status?->value, ['pending', 'initially_approved'])) {
            $fail('يوجد طلب قيد المراجعة بهذا البريد الإلكتروني — يرجى انتظار قرار الإدارة.');
        } elseif ($existing && $existing->status?->value === 'accepted') {
            $fail('تم قبول طلبك سابقاً — يمكنك تسجيل الدخول كطالب من بوابة الطالب.');
        }
        // rejected → allow
    },
],
```

Same rule on `national_id`. Should also handle the case where `email` matches a `users` row (already-enrolled student).

Even better: extract to `app/Rules/ApplicationNotDuplicate.php` so the action layer can call it too (defense in depth).

## Regression guard

```php
it('blocks duplicate application while a pending one exists', function () {
    $svc = app(CreateApplication::class);
    $data = [/* valid */];
    $svc->handle($data);
    expect(fn () => $svc->handle($data))->toThrow(DuplicateApplicationException::class);
});

it('allows new application after the previous was rejected', function () {
    $svc = app(CreateApplication::class);
    $first = $svc->handle($data);
    $first->update(['status' => ApplicationStatus::Rejected]);
    $second = $svc->handle($data);  // should succeed
    expect($second->id)->not->toBe($first->id);
});
```

## Related

- [[2026-05-03-approve-application-email-collision]] — the downstream symptom when admin approves a duplicate
