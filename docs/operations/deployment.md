# Deployment & Go-Live Runbook

> How to put SparesPro on the shop's server and go live safely. Pair this with [data-migration.md](data-migration.md) (opening balances), [backup-and-resilience.md](backup-and-resilience.md) (backup discipline) and [hardware-and-printing.md](hardware-and-printing.md) (tills, printers). The one gate that decides go/no-go is **`php artisan app:preflight`** and the **System Health** screen — both must be green.

---

## 0. What you are installing

A Laravel 12 + Inertia/React app with a MySQL database. It runs as a normal PHP web app plus **two background processes that must always run**: a **queue worker** (sends email/SMS, prints) and the **scheduler** (nightly backup, nightly health check, housekeeping). If those two are not running, the system still trades but backups and emails silently stop — System Health will turn amber to tell you.

## 1. Server requirements

| Need | Minimum |
|------|---------|
| PHP | 8.3+ with extensions: pdo_mysql, mbstring, openssl, gd, zip, bcmath, fileinfo |
| MySQL / MariaDB | MySQL 8.0+ / MariaDB 10.6+ |
| Node (build only) | 18+ to run `npm run build` once; not needed at runtime |
| Disk | 10 GB+ free (database + backups + part images) |
| `mysqldump` | On PATH, or set `MYSQLDUMP_PATH` in `.env` (backups fall back to a slower PHP dump if absent) |

A single mid-range PC or a small VPS handles one branch comfortably. Keep it on a **UPS** — see hardware doc.

## 2. First install

```bash
git clone <repo> sparespro && cd sparespro
composer install --no-dev --optimize-autoloader
npm install && npm run build          # builds the front-end once
cp .env.example .env                  # then edit per section 3
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force           # clean Mokone go-live seed — NO demo data
php artisan storage:link
```

Never run `php artisan db:seed --class=DemoSeeder` (or `DemoDataSeeder`) on the live server — they load fabricated parts, customers and transactions.

## 3. `.env` for production

```
APP_NAME="Mokone Auto Spares"
APP_ENV=production
APP_DEBUG=false
DEBUGBAR_ENABLED=false
APP_URL=https://erp.mokone.co.za        # the real HTTPS address (QR verification uses it)

DB_CONNECTION=mysql
DB_DATABASE=sparespro
DB_USERNAME=sparespro
DB_PASSWORD=<strong-password>

QUEUE_CONNECTION=database               # a worker must run (section 5)
SESSION_DRIVER=database
CACHE_STORE=database

MAIL_MAILER=smtp                        # real SMTP, not "log", or no email goes out
MAIL_HOST=...  MAIL_PORT=587  MAIL_USERNAME=...  MAIL_PASSWORD=...  MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@mokone.co.za"

MYSQLDUMP_PATH="C:/xampp/mysql/bin/mysqldump.exe"   # Windows example; omit on Linux if on PATH
```

Then cache the config: `php artisan config:cache route:cache`.

## 4. Web server

- Point the document root at `public/`. HTTPS only (Let's Encrypt or the host's cert) — the document-verification QR codes and sessions depend on it.
- Grant the web user write access to `storage/` and `bootstrap/cache/`.

## 5. The two background processes (do not skip)

**Queue worker** — keep it running with a supervisor:

- **Linux (systemd or supervisor):** run `php artisan queue:work --sleep=1 --tries=3 --max-time=3600` and restart on exit.
- **Windows (Task Scheduler / NSSM):** run the same command as a service that auto-restarts.

**Scheduler** — one job every minute runs everything else:

- **Linux cron:** `* * * * * cd /path/to/sparespro && php artisan schedule:run >> /dev/null 2>&1`
- **Windows Task Scheduler:** a task triggered **every 1 minute** running `php artisan schedule:run` in the project folder.

System Health's "Scheduler is running" and "Queue healthy" checks confirm both are alive. The scheduler drives: nightly backup (22:00), nightly health check (06:00), weekly failed-job prune, and the per-minute heartbeat.

## 6. Backups (see backup-and-resilience.md)

- The nightly job writes a gzipped dump to `storage/app/backups` and prunes older than `backup.retention_days` (Configuration Centre › Operations).
- **A backup on the same disk is not a backup.** Set up a daily off-site copy: sync `storage/app/backups` to an external drive or cloud folder (robocopy / rclone / a cloud agent). Admin › Backup Management lets you take and download one on demand.
- **Drill the restore once before go-live:** create a scratch database, `gunzip` the newest dump into it, point a copy of `.env` at it, and confirm the app boots and the trial balance matches.

## 7. Go-live day checklist

1. `git pull` / deploy the release; `composer install --no-dev`, `npm run build`, `php artisan migrate --force`, `php artisan config:cache route:cache`.
2. Capture **opening balances** per [data-migration.md](data-migration.md): load parts + opening stock through Data Upload, enter opening customer/supplier balances, post the opening trial balance; then use System Health's one-click fix to post opening inventory to the GL.
3. Enter **Company** VAT number, registration number and banking (they print on every invoice).
4. **Change the admin password** (first login forces it) and create real staff users with the right roles; review each role's permissions under Roles.
5. Run **`php artisan app:preflight`** — it must report READY (no FAIL rows).
6. Open **System Health** — every row green or a known, accepted amber.
7. Take one **manual backup** and copy it off-site.
8. Print a test invoice and an 80 mm receipt; scan the verification QR.

## 8. `app:preflight` — the readiness gate

```bash
php artisan app:preflight
```

Checks environment (production, debug off, app key, HTTPS URL), queue/mail config, the scheduler heartbeat, backup freshness, disk space, storage writability, mysqldump availability, that every route is in the permission matrix, that the default admin password was changed, and that company VAT/registration/banking and the base currency are set. Exit code 1 on any blocker, so a deploy script can gate on it.

## 9. Updates (new release)

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm run build
php artisan migrate --force
php artisan config:cache route:cache
php artisan queue:restart            # so workers pick up new code
php artisan up
php artisan app:preflight
```

## 10. Rollback

1. `php artisan down`.
2. Restore the database from the pre-update backup (section 6).
3. `git checkout <previous-tag>`, `composer install --no-dev`, `npm run build`, `php artisan config:cache`.
4. `php artisan queue:restart && php artisan up`; confirm with `app:preflight` and System Health.

Because posted documents are immutable and all money moves through the ledger, restoring a consistent database snapshot is always a safe rollback — never hand-edit posted rows.
