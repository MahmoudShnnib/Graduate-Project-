---
date: 2026-06-08
tags: [bug, docker, nginx, deployment, apply, upload]
related: [[2026-06-08-apply-500-docker-storage-permissions]], [[2026-06-07-apply-500-error]]
---

# `/apply` 500 in production — the REAL root cause: nginx client_body temp not writable

## TL;DR

Every `/apply` submission **with a file upload** returned a **500 from nginx itself**
(not Laravel). The nginx worker (running as `www-data`) could not spool the upload
request body to `/var/lib/nginx/tmp/client_body`, which `apk add nginx` creates owned by
the `nginx` user → `EACCES` → nginx returns its own 500 **before the request reaches
PHP-FPM**.

## How we know it's nginx, not PHP

The captured response body was:
```
<html><head><title>500 Internal Server Error</title></head>
<body><center><h1>500 Internal Server Error</h1></center>
<hr><center>nginx/1.28.3</center></body></html>
```
That is nginx's default error page. A PHP exception would render Laravel's error page,
and on current `main` the `/apply` handler is wrapped in `try/catch` and would show an
Arabic message instead. So the failure is upstream of PHP entirely.

## Why earlier fixes didn't help

- The hardened `RegistrationController` (try/catch, `$path === false` guard) and the
  `chown storage` in `entrypoint.sh` all operate **inside PHP**. The request never gets
  there — nginx 500s while buffering the body. See [[2026-06-08-apply-500-docker-storage-permissions]]
  (storage permissions was a real gap but NOT what triggered this 500).

## Mechanism

- `docker/nginx.conf` line 1: `user www-data;` → workers run as `www-data`.
- `apk add nginx` (Alpine) creates `/var/lib/nginx/` (incl. `tmp/client_body`) owned by
  the `nginx` user, mode 755.
- Uploads larger than `client_body_buffer_size` (~16 KB) are spooled to that temp dir.
  A bachelor-certificate PDF/image always exceeds this → write fails → 500.
- The Dockerfile only chowned `/var/www/storage` + `bootstrap/cache`, never `/var/lib/nginx`.
- GET `/apply` (no body) works; only the multipart POST fails → matches the symptom exactly.

## Confirmation

```sh
docker logs <container> 2>&1 | grep -i "client_body\|permission denied"
# open() ".../client_body/0000000001" failed (13: Permission denied)
```

## Fix (branch `fix/nginx-body-temp-500` off main)

1. **Dockerfile** — after `apk add nginx`:
   ```dockerfile
   RUN mkdir -p /var/lib/nginx/tmp/client_body /var/lib/nginx/logs \
       && chown -R www-data:www-data /var/lib/nginx
   ```
2. **docker/entrypoint.sh** — re-assert on every boot (self-healing):
   ```sh
   mkdir -p /var/lib/nginx/tmp/client_body /var/lib/nginx/logs
   chown -R www-data:www-data /var/lib/nginx
   ```

## Instant runtime workaround (no rebuild)

```sh
docker exec <container> sh -c 'mkdir -p /var/lib/nginx/tmp/client_body && chown -R www-data:www-data /var/lib/nginx && nginx -s reload'
```

## Regression guard

Permanent via image build; self-heals via entrypoint on every container start.
