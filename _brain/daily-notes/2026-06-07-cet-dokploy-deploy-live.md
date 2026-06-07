---
date: 2026-06-07
tags: [daily, dokploy, deployment, postgres, cet]
related:
  - "[[2026-06-07-dokploy-application-setup]]"
  - "[[2026-06-07-docker-compose-deploy]]"
---

# 2026-06-07 — CET Dokploy stack live

User added GitHub repo in Dokploy UI; asked to add Postgres, wire `.env`, and deploy registration project under **CET** org.

## What was done

1. **New project** — `CET Registration` (`4tvIR46tLglEm-qs6EG5d`) moved to **CET** org (`AFzHcBQ8C5e85KMCTVQXi`), env `production` (`wg6TzJ2hG8HByUMdHGGS6`).
2. **Postgres** — `graduate-db` (`tlqG8fGwt3o-ulbJCiA7r`), internal host `cet-graduate-db-mreo53`, DB `graduate_project`, user `graduate`, postgres:16, deployed.
3. **Application** — `graduate-app` (`gMXWoSzDjX__eJ755ImsL`), container `cet-graduate-app-nftkqd`, Dockerfile build from `mahmoud-shanab/Graduate-Project-` `main`, GitHub provider `qbVIHMidpf1GMZqqQqoiD`.
4. **Env** — production Laravel vars; `DB_HOST=cet-graduate-db-mreo53`, `APP_URL=https://cet.itsyosefali.cloud`, Qonvo restored.
5. **Domain** — `cet.itsyosefali.cloud` → app port **8080**, Let's Encrypt (migrated from orphaned app `wSwnX0-0pf7UEf41KwUcE`).
6. **Volume** — uploads at `/var/www/storage/app/public`.
7. **Deploy** — status **done**; `/up`, `/apply`, `/admin/login` all HTTP 200.

## Notes

- Old app/postgres in env `4Oia5dmBF4InpF_nbqeoh` was orphaned (not in `project-all`); replaced with fresh stack in CET org project.
- `postgres-create` failed on old environment ID (auth/403); succeeded on new environment.

## Admin (fresh DB after seed)

- `admin@college.edu` / `admin123`

## Next steps

- Remove old orphaned application `wSwnX0-0pf7UEf41KwUcE` from Dokploy if still visible.
- Confirm WhatsApp notifications on production.
