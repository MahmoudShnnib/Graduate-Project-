# StudentEnrollments resource removal — 2026-05-14

The Filament Resource at `/admin/student-enrollments` (read-only roster browser
keyed by course offering) was removed per user request. The StudentEnrollment
model, policy, and Shield permissions stay — they're used by
`CourseOfferings/Pages/EnrollStudents`.

To revert:
```
cp -r _backups/student-enrollments-resource-2026-05-14/Resource app/Filament/Resources/StudentEnrollments
cp -r _backups/student-enrollments-resource-2026-05-14/views    resources/views/filament/resources/student-enrollments
```
