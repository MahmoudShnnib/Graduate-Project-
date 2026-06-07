---
date: 2026-06-07
tags: [deploy, dokploy, domain, cet]
related: [[2026-06-07-cet-dokploy-deploy-live]]
---

# Domain switch to cet-pgms.ly

## Done (Dokploy)

- Domain `cet-pgms.ly` → `graduate-app` port **8080**, Let's Encrypt, HTTPS on.
- Old domain `cet.itsyosefali.cloud` removed from app domains (returns 404 externally).
- `APP_URL=https://cet-pgms.ly` set in Dokploy env.
- Redeploy triggered so running container picks up new `APP_URL`.

## Blocker

HTTPS to `cet-pgms.ly` fails with **SSL handshake failure** at Cloudflare edge. DNS resolves to Cloudflare proxy IPs (orange cloud). HTTP → 308 redirect to HTTPS works; HTTPS does not.

Likely causes: Universal SSL not provisioned yet for `.ly` zone, or Cloudflare SSL mode vs Traefik LE cert mismatch.

## User action (Cloudflare)

1. Confirm `cet-pgms.ly` A record → server IP (proxied OK after cert).
2. SSL/TLS → **Full** (use Full Strict only after Traefik has valid LE cert).
3. If LE on Dokploy fails: grey-cloud DNS briefly, wait for Traefik cert, re-enable proxy.
4. Edge Certificates → confirm Universal SSL active for `cet-pgms.ly`.

## Verify after SSL works

```bash
curl -I https://cet-pgms.ly/up
docker exec <container> php artisan about | grep URL
```

Expected: `200` on `/up`, `URL ... cet-pgms.ly`.
