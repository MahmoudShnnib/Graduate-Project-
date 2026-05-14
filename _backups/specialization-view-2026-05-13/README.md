# Specialization view backup — 2026-05-13 (round 2)

Pre-redesign snapshot. Revert with:

```bash
cp _backups/specialization-view-2026-05-13/ViewSpecialization.php \
   app/Filament/Resources/Specializations/Pages/ViewSpecialization.php
cp _backups/specialization-view-2026-05-13/SpecializationResource.php \
   app/Filament/Resources/Specializations/SpecializationResource.php
rm -rf resources/views/filament/resources/specializations
```
