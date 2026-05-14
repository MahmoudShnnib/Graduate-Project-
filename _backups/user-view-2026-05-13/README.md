# User view backup — 2026-05-13

Revert with:

```bash
cp _backups/user-view-2026-05-13/ViewUser.php       app/Filament/Resources/Users/Pages/ViewUser.php
cp _backups/user-view-2026-05-13/UserResource.php   app/Filament/Resources/Users/UserResource.php
cp _backups/user-view-2026-05-13/UserInfolist.php   app/Filament/Resources/Users/Schemas/UserInfolist.php
rm -rf resources/views/filament/resources/users
```
