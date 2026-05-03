---
date: 2026-05-03
tags: [bug, tracking, public-form, error-handling, http-status]
related: [[../daily-notes/2026-05-03-test-execution]]
status: fixed
---

# `/apply/track` returns HTTP 500 on invalid lookup (should be 4xx)

> Discovered during TRACK-002/003 of full system test run.

## Symptoms

POST `/apply/track` with a wrong tracking code or wrong national_id renders a friendly Arabic error page ("لم يتم العثور على طلب بهذه البيانات. تأكد من صحة رقم التتبع والرقم الوطني.") — but the underlying HTTP response status is **500 Internal Server Error**.

Verified by issuing 12 rapid POSTs with a deliberately wrong `tracking_code='APP-9999-9999'` + `national_id='999999999999'`:

```
statuses: [500,500,500,500,500,500,500,500,500,500,500,500]
```

All twelve returned 500. The error message itself is correct UX; only the status code is wrong.

## Root cause (likely)

`ApplicationTrackingController` (or its lookup logic) probably uses `Application::where(...)->firstOrFail()` and lets the `ModelNotFoundException` propagate without converting it to a friendly redirect/422. Laravel's exception handler then renders the message but defaults the status to 500 because the exception isn't a typed HTTP exception.

Need to confirm by reading `app/Http/Controllers/ApplicationTrackingController.php`.

## Impact

- **Looks broken** — anyone monitoring HTTP logs sees 500s, false-positive incident pings.
- **Wrong semantic** — "not found" = 404, "invalid input" = 422, "server bug" = 500. Mixing them defeats observability.
- **Combined with no rate limit** ([[../daily-notes/2026-05-03-test-execution|TRACK-004]]), an attacker enumerating IDs gets repeated 500 responses that flood ops dashboards.

## Fix sketch

Replace `firstOrFail()` with explicit handling:

```php
public function show(Request $request)
{
    $request->validate([
        'tracking_code' => 'required|string',
        'national_id' => 'required|string',
    ]);

    $application = Application::where('tracking_code', $request->tracking_code)
        ->where('national_id', $request->national_id)
        ->first();

    if (! $application) {
        return back()->withInput()->withErrors([
            'tracking_code' => 'لم يتم العثور على طلب بهذه البيانات. تأكد من صحة رقم التتبع والرقم الوطني.',
        ]);
    }

    return view('registration.track', compact('application'));
}
```

This returns 200 with a flash error (or 422 if you prefer JSON-style explicit codes).

## Regression guard

```php
it('returns 200/422 not 500 on invalid tracking code', function () {
    $response = $this->post('/apply/track', [
        'tracking_code' => 'APP-9999-9999',
        'national_id' => '999999999999',
    ]);
    expect($response->status())->toBeIn([200, 302, 422]);
    expect($response->status())->not->toBe(500);
});
```

## Related

- [[../project-context/gotchas]] — should add an entry: "Public-form lookups must convert ModelNotFound to friendly 4xx"
- [[2026-05-03-no-rate-limit-on-track]] — sister issue (track endpoint has no throttle)
