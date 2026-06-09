---
date: 2026-06-09
tags: [daily, apply, registration, validation, ui, alpine]
related:
  - "[[2026-06-09-nationality-country-picker-convention]]"
  - "[[../bugs-and-fixes/2026-06-09-arabic-regex-crashed-applyform]]"
  - "[[2026-06-08-apply-landing-ui-refresh]]"
---

> **Post-test bugfix:** the JS Arabic regex used PCRE syntax `\p{Arabic}`, which is a
> parse error in JavaScript and crashed the entire `applyForm()` Alpine component (blank
> nationality picker, dead dropdown, missing national-id label, no Arabic guard). Fixed by
> switching to `\p{Script=Arabic}`. See [[../bugs-and-fixes/2026-06-09-arabic-regex-crashed-applyform]].

# 2026-06-09 — `/apply` applicant-form enhancements

Branch: **`feature/apply-form-enhancements`** (off `main`). Not yet committed — user is
testing locally before deploy.

## What was done

1. **Labels:** `الاسم الرباعي واللقب` → `الاسم الرباعي (باللقب)`; `تاريخ الميلاد` → `تاريخ ميلادك`;
   `مكان العمل` → `جهة العمل`; `مكان الإقامة` → `محل الإقامة`.
2. **Real-time Arabic-only validation** on `mother_name`, `birth_place`, `residence`,
   `permanent_address`, `profession`, `workplace`. New Alpine `arabicGuard(event, key,
   allowNumbers)` strips non-Arabic chars on input and flashes
   *"يرجى إدخال البيانات باللغة العربية فقط"* for 3s. Server side: `profession`/`workplace`
   gained `new ArabicOnly` (nullable). Mirrors the `ArabicOnly` rule's char class.
3. **Searchable country picker** with emoji flags replacing the old select +
   `nationality_other`. Stores the Arabic country name. Libya preselected. See
   [[2026-06-09-nationality-country-picker-convention]].
4. **Dynamic national-number rule:** Libyan → 12 digits (live `/12` counter, digits-only
   guard); non-Libyan → alphanumeric passport/ID 4–20 (label + hint swap). Server rule
   keyed on `config('nationalities.default')`.
5. **Remedial statement (كشف المواد التكميلية)** stays optional; added `(اختياري)` tag + a
   hint that it's needed for diploma holders / specializations requiring remedial courses.
6. **Success popup modal** (Alpine, behind `session('success') && session('tracking_code')`):
   centered, checkmark, *"تم تقديم طلبك بنجاح"*, prominent *"رقم الطلب: {code}"*, description,
   primary *"حسناً"*, secondary *"نسخ رقم الطلب"* → toast *"تم نسخ رقم الطلب"*. Code comes from
   the saved record's `tracking_code`. Replaced the old inline success banner.
7. **Duplicate-submit guard:** `onSubmit()` sets `submitting`, disables the button, swaps to
   a spinner + *"جاري الإرسال..."*. Coexists with the OTP lock (imperative disable, not
   `x-bind`, to avoid un-locking the OTP gate).

## Files touched

- `config/nationalities.php` (new — single source of truth, ~40 countries + Libya default)
- `app/Http/Controllers/RegistrationController.php` (nationality `Rule::in`, conditional
  national_id closure, profession/workplace ArabicOnly, removed nationality_other)
- `app/Actions/CreateApplication.php` (fallback → `config('nationalities.default')`)
- `resources/views/registration/create.blade.php` (picker, guards, modal, labels, submit)
- `tests/Feature/ApplicationNationalityValidationTest.php` (new — 8 tests, all pass)
- `tests/Feature/WhatsAppOtpVerificationTest.php` (helper `ليبية → ليبيا`)

## Tests

- New 8 pass. Full suite: **229 pass, 4 fail** — the 4 are **pre-existing** and unrelated
  (StudentPortal 403→302, ExamListPrint PDF, ProductionHardening); confirmed they fail
  identically on a clean `git stash`ed tree.

## Next steps / loose ends

- **User to test locally** at `http://pgsms.test/apply` (Tailwind CDN, no build needed).
- After confirmation: commit on the feature branch, then merge → push → Dokploy redeploy.
- Possible follow-up: unify admin-side nationality defaults (`ليبية`) with the new `ليبيا`
  convention if desired (intentionally left untouched for now).
