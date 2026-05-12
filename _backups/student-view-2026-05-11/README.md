# Backup — `/admin/students/{id}` view page

**Date:** 2026-05-11
**Reason:** Pre-Figma-redesign snapshot. Restore from here if the new design isn't kept.

## Files backed up

| Backup path | Live path |
|---|---|
| `app/Filament/Resources/Students/Pages/ViewStudent.php` | `app/Filament/Resources/Students/Pages/ViewStudent.php` |
| `app/Filament/Resources/Students/StudentResource.php` | `app/Filament/Resources/Students/StudentResource.php` |
| `resources/views/filament/resources/students/pages/view-student.blade.php` | `resources/views/filament/resources/students/pages/view-student.blade.php` |

## Why these three

- `ViewStudent.php` — the page class. Defines header actions, the enrollment-history table, the activity timeline, and the custom-Blade `$view`.
- `StudentResource.php` — contains the `infolist(Schema $schema)` method (the cards at the top of the page) plus the create/edit form schema. Backed up whole because the infolist isn't extracted into a `Schemas/StudentInfolist.php` file.
- `view-student.blade.php` — the actual Blade layout that composes `$this->infolist`, `$this->table`, and the timeline.

## How to revert

From the project root:

```bash
cp _backups/student-view-2026-05-11/app/Filament/Resources/Students/Pages/ViewStudent.php \
   app/Filament/Resources/Students/Pages/ViewStudent.php

cp _backups/student-view-2026-05-11/app/Filament/Resources/Students/StudentResource.php \
   app/Filament/Resources/Students/StudentResource.php

cp _backups/student-view-2026-05-11/resources/views/filament/resources/students/pages/view-student.blade.php \
   resources/views/filament/resources/students/pages/view-student.blade.php
```

Then refresh the admin page — no migration, no cache clear needed.
