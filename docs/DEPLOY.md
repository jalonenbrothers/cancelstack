# CancelStack — Deploy & Run (myapp5mvp.earth-turtle.com)

Audience: the operator. Every command below was tested on this machine
(Linux, Docker) against commit `962b1df` before being written down.

The repo root IS the web root — no build steps, no composer, no npm.
PHP 8+ with `pdo_mysql`, MySQL 8, Apache with `mod_rewrite` (or any
server honoring `.htaccess`).

---

## 1. Run locally (tested 2026-11-02)

You need `docker` (images `php:8.3-apache`, `mysql:8`) and `curl`.

### 1.1 Database (MySQL 8)

```bash
docker run -d --name cancelstack-mysql \
  -e MYSQL_ROOT_PASSWORD=rootpw \
  -e MYSQL_DATABASE=cancelstack \
  -e MYSQL_USER=csuser \
  -e MYSQL_PASSWORD=cspass \
  mysql:8
# wait ~25s for first boot, then verify:
docker exec cancelstack-mysql mysql -ucsuser -pcspass cancelstack -e 'SELECT 1'
```

### 1.2 Apply migrations

```bash
docker exec -i cancelstack-mysql mysql -ucsuser -pcspass cancelstack \
  < migrations/001_cancelstack.sql
docker exec cancelstack-mysql mysql -ucsuser -pcspass cancelstack -e 'SHOW TABLES'
# expect: attempts, cases
```

### 1.3 Web server (Apache + PHP 8.3, real `.htaccess` honored)

```bash
docker run -d --name cancelstack-web -p 18085:80 \
  --mount type=bind,source="$(pwd)",target=/var/www/html \
  php:8.3-apache \
  sh -c 'a2enmod rewrite >/dev/null && docker-php-ext-install pdo_mysql >/dev/null 2>&1; exec apache2-foreground'
```

### 1.4 Config

Find the MySQL container IP and put it in the config:

```bash
docker inspect cancelstack-mysql --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'
cp lib/config.example.php lib/config.php
# edit lib/config.php: host = <IP from above>, name/user/pass as in 1.1,
# app_url = http://localhost:18085
```

### 1.5 Smoke test (all verified)

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:18085/          # 200
# create a case (JSON):
curl -s -X POST http://localhost:18085/api/case -H 'Content-Type: application/json' \
  -d '{"merchant":"Acme","plan":"Pro","amount":9.99,"cycle":"monthly","started":"2026-01-01"}'
# -> {"ok":true,"token":"<10 chars>","case_url":"..."}  — note the token
curl -s http://localhost:18085/api/case/<TOKEN>                            # case + status
curl -s -X POST http://localhost:18085/api/case/<TOKEN>/attempt -H 'Content-Type: application/json' \
  -d '{"date":"<today>","channel":"phone","outcome":"still-charged"}'     # status -> Disputed
```

Unit tests inside the container:

```bash
docker exec cancelstack-web php /var/www/html/tests/run.php   # 30 passed
```

Fresh-database check: repeat 1.2 on a brand-new MySQL container and the
smoke test passes unchanged — migration is the only DB step.

### 1.6 Teardown

```bash
docker rm -f cancelstack-web cancelstack-mysql
```

---

## 2. Deploy to Hostinger (myapp5mvp.earth-turtle.com)

Operator runs this. No git access needed from the server side beyond
the normal auto-deploy on push.

1. **Subdomain**: hPanel → Domains → create subdomain
   `myapp5mvp.earth-turtle.com` (directory `domains/myapp5mvp.earth-turtle.com`,
   auto-SSL on).
2. **Database**: hPanel → Databases → MySQL → create database + user,
   e.g. `u<account>_cancelstack` / `u<account>_csuser`. Note host (usually
   `localhost` on shared hosting), name, user, password.
3. **Deploy code**: git push to the repo connected to the subdomain's
   web root (auto-deploy), or upload the repo contents so that the repo
   root IS the web root — `index.php`, `.htaccess`, `lib/`, `src/`,
   `views/`, `assets/`, `migrations/` all sit directly in it.
4. **Config**: in the web root run
   `cp lib/config.example.php lib/config.php`, then edit:
   - `db.host` = `localhost` (Hostinger preset), port 3306
   - `db.name`, `db.user`, `db.pass` from step 2
   - `app_url` = `https://myapp5mvp.earth-turtle.com`
   - leave `debug` = false
5. **Migrations**: follow `migrations/README.md` — phpMyAdmin
   (hPanel → Databases → phpMyAdmin → select DB → SQL tab → paste
   contents of `migrations/001_cancelstack.sql` → Run), or
   `mysql -u <user> -p <dbname> < 001_cancelstack.sql`.
   Safe to re-run (idempotent).
6. **Cron jobs**: none.
7. **Verify**: open `https://myapp5mvp.earth-turtle.com/` — landing page
   renders; create a case; open its `/c/<token>` link; log one attempt.

### Post-deploy verification checklist

- `/` renders (200), `/case/new`, `/about` work
- case creation reveals a 10-char token; `/c/<token>` shows the case
- logging a `still-charged` attempt flips status to `Disputed`
- `/c/<token>/export` and `/c/<token>/bundle?type=bank` render
- blocked paths 404: `/lib/config.php`, `/src/...`, `/tests/...`,
  `/migrations/...`, `/config.example.php` (verified under real Apache)
- deleting `lib/config.php` shows the setup message, never a stack trace

### Files never served

`.htaccess` blocks `/lib/`, `/src/`, `/tests/`, `/migrations/`,
`/data/` and `config.example.php` with 404. `lib/config.php` (contains
DB credentials) is additionally gitignored — make sure it exists on the
server but never enters the repo.

---

## 3. Environment notes

- PHP extensions required: `pdo_mysql` (Hostinger default has it).
- `mod_rewrite` required for pretty URLs (`/case/new`, `/c/<token>`);
  baseline `.htaccess` includes a `RedirectMatch` fallback for blocked
  paths that works even if rewriting is off.
- PHP timezone: the app derives statuses from DB timestamps; set PHP
  `date.timezone` (Hostinger default `Europe/Vilnius` is fine).
- HTTP `PATCH` is accepted on `/api/attempt/{id}` via a POST alias —
  no Hostinger config needed.
- Debug mode: set `'debug' => true` in `lib/config.php` only while
  diagnosing; restore to `false` afterwards.

## 4. Costs

- External APIs: none. Runtime cost $0.
- Infrastructure: existing Hostinger plan + subdomain; no new spend.

## 5. Rollback

Delete the subdomain's files / disconnect auto-deploy, drop the
database in hPanel. There is no state outside the two MySQL tables
(`cases`, `attempts`) and users' locally stored tokens.
