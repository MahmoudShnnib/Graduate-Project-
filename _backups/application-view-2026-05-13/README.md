# Application view backup — 2026-05-13

Revert with:

```bash
cp _backups/application-view-2026-05-13/ViewApplication.php       app/Filament/Resources/Applications/Pages/ViewApplication.php
cp _backups/application-view-2026-05-13/ApplicationResource.php   app/Filament/Resources/Applications/ApplicationResource.php
cp _backups/application-view-2026-05-13/ApplicationInfolist.php   app/Filament/Resources/Applications/Schemas/ApplicationInfolist.php
rm -rf resources/views/filament/resources/applications
```
