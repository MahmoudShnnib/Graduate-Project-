---
date: 2026-06-22
tags: [filament, students, validation, documents, create-form, daily]
related:
  - "[[2026-06-21-student-form-docs-and-validation]]"
---

# Student-create: live language guards, password confirmation, phone required, document consolidation

Branch `feature/student-form-docs-validation`. **Uncommitted.** Full suite **464 green** (+6).
Three user requests against `/admin/students/create`, mirroring the public `/apply` form.

## Task 1 — live Arabic/English input guards + password confirmation
The `/apply` form strips non-Arabic chars *as the user types* (Alpine `arabicGuard`/`englishGuard`)
and has a `password` + `password_confirmation` (`confirmed` rule). The admin form had neither.

- Added two private helpers on `StudentResource`: `arabicGuardAttributes(bool $allowNumbers)` and
  `englishGuardAttributes()` returning an `['oninput' => "this.value=this.value.replace(/…/,'')"]`
  attribute array — the Filament-form mirror of the apply page's JS guards (pure client-side strip,
  no Livewire round-trip since the fields aren't `->live()`). Applied via `->extraInputAttributes()`:
  - Arabic (no digits): `user_name`, `mother_name`, `profession`
  - Arabic (allow digits/separators): `birth_place`, `residence`, `permanent_address`, `workplace`
  - English-only: `user_name_en`
  - Bonus: `user_phone` strips non-digits live (`/\D/g`).
- **Password confirmation**: new `user_password_confirmation` field (password, revealable,
  `visibleOn('create')`, `dehydrated(false)`). The `user_password` field gained
  `->same('user_password_confirmation')` + Arabic `same` message. Mismatch → form error on
  `user_password`.

## Task 2 — consolidate document sections (الوثيقة العلمية removed)
The create form/view had **two** document concepts: `degree_image` ("الوثيقة العلمية", admin-only,
rich preview on the view page) and the admission docs (`bachelor_certificate` /
`remedial_statement`, plain text links). User wanted only the admission docs, but rendered with the
rich image-zoom / PDF-card preview the degree document had.

- **Form**: removed the الوثيقة العلمية `FileUpload(degree_image)` section.
- **Infolist**: removed the الوثيقة العلمية card; the مستندات القبول section now renders each
  document via `\Filament\Schemas\Components\View::make('…partials.document-preview')->viewData([...])`.
- **New partial** `partials/document-preview.blade.php` — generalised from the old `degree-document`
  partial: takes `field` + `title` via `viewData`, reads `$record->{$field}`, renders the empty / PDF
  / image-with-zoom-modal states. Reuses the existing `.fsv-degree-doc*` CSS; added a small
  `.fsv-doc-block__label` rule (per-doc heading) to `view-student.blade.php`.
- Deleted the now-unused `partials/degree-document.blade.php`.
- **Dropped the `degree_image` column** (user chose drop over dormant): migration
  `drop_degree_image_from_students_table` (reversible `down()` re-adds it). Removed from
  `Student::$fillable` and `CreateStudent::handleRecordCreation`. No factory/seeder/test referenced
  it. Ran on dev DB.

## Task 3 — phone required on create
`user_phone` gained `->required()` (the existing Libyan-format closure short-circuits on empty, so
`required` is what blocks blanks). Applies on edit too — acceptable, every student needs a phone.

## Tests
- `StudentCreatePasswordPhoneTest` (new, 4): password match/mismatch, phone required/valid.
- `StudentViewDocumentsTest` (new, 2): view page renders the new `document-preview` partial for both
  the PDF and image branches, and the empty state — confirms the `viewData`/`$record` wiring.

## Follow-up — inline "wrong language" flash (parity with /apply)
The first pass stripped silently. Reworked the guards to also **show a red inline message** for 3s
when a disallowed char is typed (matches the apply page's flash):
- Replaced the attribute-only helpers with `withArabicGuard()` / `withEnglishGuard()` →
  `withLanguageGuard(TextInput, $pattern, $message, $helperText)`. It wires an **Alpine scope on the
  field wrapper** (`->extraAttributes(['x-data' => '{ langErr:false, langTimer:null }'])`), an
  `x-on:input` handler on the input that strips + sets `langErr=true` (auto-clears after 3s via
  `setTimeout`), and an `->helperText(HtmlString)` whose `<span x-show="langErr" x-cloak>` carries
  the message. Any pre-existing helper text (e.g. user_name's name-format hint) is preserved above it.
- Each guarded field is now wrapped: `self::withArabicGuard(TextInput::make(...)->..., allowNumbers, helperText)`.
- **Why `x-on:input` not native `oninput`**: the message needs the Alpine wrapper scope (`langErr`),
  so the handler runs in Alpine and uses `$event.target` instead of `this`.
- Test: `StudentCreateGuardRenderTest` asserts the rendered create-form HTML contains `langErr` +
  both Arabic guard messages.

## Follow-up 2 — guard only on REQUIRED fields
User: the language guard should not restrict the **optional** fields. Removed the Arabic guard +
the server-side `ArabicOnly` rule from `profession` (المهنة) and `workplace` (مكان العمل) on the
**admin** create form — they're now free optional text (label + maxLength only). The guard now
covers only the required fields: `user_name`, `mother_name`, `birth_place`, `residence`,
`permanent_address` (Arabic) + `user_name_en` (English).
- Note: the public `/apply` form still Arabic-validates profession/workplace
  (`ApplicationNationalityValidationTest` unchanged) — this relaxation is admin-form-only, per the
  user's preference. Intentional divergence from strict apply-parity.

## Follow-up 3 — apply-page OTP fixed (Qonvo sender "+" prefix)
Separate bug surfaced same day: `/apply` OTP failed with 422 "sender not registered". Root cause =
`QONVO_SENDER_PHONE` had a leading `+`; Qonvo's registered sender has none. Fixed dev `.env` +
made `WhatsAppService::senderPhone()` strip a leading `+`. Proven live (422 vs 201). Full detail:
[[../bugs-and-fixes/2026-06-22-qonvo-otp-sender-plus-prefix]].

## Notes / loose ends
- The `oninput` guards are client-side only; server-side `ArabicOnly` / phone-closure rules from the
  [[2026-06-21-student-form-docs-and-validation|2026-06-21 batch]] remain the backstop.
- No `npm run build` needed — CSS lives inline in the Blade, guards are inline HTML attributes.
- Uncommitted (per the no-auto-commit rule). Mobile untouched.
