# Suspension-request view backup — 2026-05-14

Original files before adding the Figma view page + clickable rows.

To revert:
```
cp _backups/suspension-request-view-2026-05-14/SuspensionRequestResource.php app/Filament/Resources/SuspensionRequests/SuspensionRequestResource.php
cp _backups/suspension-request-view-2026-05-14/ListSuspensionRequests.php   app/Filament/Resources/SuspensionRequests/Pages/ListSuspensionRequests.php
rm app/Filament/Resources/SuspensionRequests/Pages/ViewSuspensionRequest.php
rm resources/views/filament/resources/suspension-requests/pages/view-suspension-request.blade.php
```
