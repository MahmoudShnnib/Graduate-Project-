---
date: 2026-05-03
tags: [bug, sample-data, validation, dev-experience, public-form]
related: [[../daily-notes/2026-05-03-test-execution]]
status: fixed
---

# Sample data generator produces English names with hyphens that fail apply-form validation

> Discovered during APPLY-001/022 of full system test run.

## Symptoms

Click "توليد بيانات تجريبية" on `/apply` → form fills with `name_en` like `"Maryam Ali Yousef Al-Obeidi"`. Submit the form → validation rejects with "الاسم الإنجليزي يجب أن يحتوي على حروف إنجليزية فقط" because the regex `/^[a-zA-Z\s]+$/` doesn't allow hyphens.

The sample-data feature is supposed to make the form trivial to test — instead it produces a payload that always fails. Anyone clicking the button + submit will see this failure and have to manually delete hyphens.

## Root cause

`app/Support/SampleApplicantData.php` uses surname pools that include patronymic-style English transliterations like `Al-Obeidi`, `Al-Misrati`, `Al-Trabelsi`, etc. (Arabic surnames with the definite article). These are linguistically correct transliterations but conflict with the controller validation rule `'regex:/^[a-zA-Z\s]+$/'` (letters and spaces only).

## Two ways to fix (pick one)

**Option A — relax the validation:** allow hyphens (and apostrophes) in English names, since they're standard in real names.

```php
'name_en' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\']+$/'],
```

**Option B — clean the sample pool:** strip hyphens / use space-separated transliterations (`Al Obeidi`).

Option A is more robust (real applicants have names like "O'Connor" or "Smith-Jones"). Either way, the sample data button + validation should agree.

## Impact

- Low severity — only affects testing workflow.
- But it makes the sample-data button useless for "click + submit" smoke testing, which is precisely its job.
- Real applicants with hyphenated English names also blocked — the validation is too strict regardless of the sample data issue.

## Regression guard

```php
it('sample data generator produces a payload that passes /apply validation', function () {
    $sample = SampleApplicantData::forPublicApplication();
    $rules = (new RegistrationController)->validationRules(); // requires extracting

    $v = Validator::make(array_merge($sample, ['password' => 'secret123', 'password_confirmation' => 'secret123']), $rules);
    expect($v->fails())->toBeFalse();
});
```

Or add a simpler invariant: never produce hyphens in the `name_en` output.

## Related

- The sample button on `/admin/students/create` shares pools (per [[../daily-notes/2026-04-21-apply-form-sample-data-button]]), so if the admin form has stricter validation it could fail there too. Worth checking.
