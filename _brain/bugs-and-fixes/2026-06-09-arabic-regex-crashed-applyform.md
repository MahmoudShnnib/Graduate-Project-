---
date: 2026-06-09
tags: [bug, apply, alpine, javascript, regex, validation]
related:
  - "[[../daily-notes/2026-06-09-apply-form-enhancements]]"
---

# `\p{Arabic}` in a JS regex crashed the whole `applyForm()` Alpine component

## Symptoms

On `/apply` after the form-enhancements work: the nationality picker button was
**blank** (no flag/name) and wouldn't open, the national-number field had **no label**
("another empty field"), and the **real-time Arabic-only validation did nothing**.

## Root cause

`arabicGuard()` used `/[^\p{Arabic}\d\s،,\.\-]/gu`. PHP/PCRE accepts `\p{Arabic}` as the
Arabic **script**, but **JavaScript `RegExp` does not** — for scripts it requires
`\p{Script=Arabic}`. The bare `\p{Arabic}` throws *"Invalid property name in character
class"* at **parse time**, so the entire `applyForm()` function body failed to define →
Alpine couldn't initialise the component → every `x-text`/`x-show`/`@click`/`@input`
binding on the form silently died (button text, dropdown, dynamic national-id label,
Arabic guards). The top progress bar still looked partly filled only because of its
default static markup, masking that Alpine was dead.

## Fix

Replace both patterns in `arabicGuard` with the script-property syntax:

```js
const pattern = allowNumbers
    ? /[^\p{Script=Arabic}\d\s،,\.\-]/gu
    : /[^\p{Script=Arabic}\s]/gu;
```

(`resources/views/registration/create.blade.php`.) Note `،` U+060C is *Common* script,
not Arabic, so it stays as an explicit literal in the allow-numbers pattern.

## Regression guard

- Verified the rendered `applyForm()` script now parses (`new Function(code)`), and all 6
  inline scripts on the page are error-free.
- **Lesson (added to the mental anti-pattern list):** PCRE `\p{Arabic}` ≠ JS
  `\p{Arabic}`. When porting an Arabic-only rule from PHP to client JS, always use
  `\p{Script=Arabic}` with the `u` flag. A single bad inline regex literal takes down the
  **entire** Alpine component, not just the one field — so a JS parse check of inline
  scripts is the fastest way to diagnose "Alpine bindings stopped working".
