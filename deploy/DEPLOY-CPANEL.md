# Deploying SparesPro to cPanel (shared hosting)

Target: `https://mokone.inspirelabs.co.zw` — cPanel user `mokonegwese`, host `199.250.192.13`.
The app lives at `/home/mokonegwese/spares`, the domain's document root points at its `public/` folder, code deploys via `git pull` from a GitHub **deploy key** (read-only) on `TanakaEvans/spares`. No `npm` on the server — the compiled frontend (`public/build`) is committed to the repo, so always `npm run build` before pushing.

## 1. PHP 8.3+

cPanel → search **Select PHP Version** → pick **8.3** (or newer) → Apply.

- **Extensions** (tick): `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `iconv`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- **Options**: `upload_max_filesize` = `16M`, `post_max_size` = `16M`, `memory_limit` = `256M`

(`gd` is needed by DomPDF/QR codes, `intl` by number formatting, `zip` by PhpSpreadsheet exports.)

## 2. Database

cPanel → **MySQL Database Wizard**:

- Database name: `mokone` → becomes `mokonegwese_mokone`
- User: `mokone` → becomes `mokonegwese_mokone`; use the **Password Generator** and save the password
- Tick **ALL PRIVILEGES**

Note whether the cPanel home page says **MySQL** or **MariaDB** — if MariaDB, `DB_CONNECTION=mariadb` in `.env`.

## 3. Shell access

cPanel → **Terminal**, or from PowerShell:

```bash
ssh -p 21098 mokonegwese@199.250.192.13
```

Check the CLI PHP version:

```bash
php -v
```

If it is below 8.3, prefix every `php` below with `/opt/cpanel/ea-php83/root/usr/bin/php`, or set a session alias:

```bash
alias php=/opt/cpanel/ea-php83/root/usr/bin/php
```

## 4. SSH key → GitHub deploy key

On the server:

```bash
ssh-keygen -t ed25519 -C "spares-cpanel" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

Copy the whole line. GitHub → `TanakaEvans/spares` → **Settings → Deploy keys → Add deploy key**, paste, leave *Allow write access* unticked, save. Then:

```bash
ssh -T git@github.com
```

Answer `yes` to the fingerprint prompt. You want "Hi TanakaEvans/spares! You've successfully authenticated".

## 5. Clone and install

```bash
cd ~ && git clone git@github.com:TanakaEvans/spares.git spares
cd ~/spares && composer install --no-dev --optimize-autoloader --no-interaction
```

If `composer` isn't found, try `/opt/cpanel/composer/bin/composer`, or `php ~/composer.phar` (download: `curl -sS https://getcomposer.org/installer | php`). **No `npm` step** — `public/build` is in the repo.

## 6. Environment

```bash
cd ~/spares && cp deploy/env-production.txt .env && php artisan key:generate --force
nano .env
```

Fill in `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from step 2. If the host runs MariaDB, set `DB_CONNECTION=mariadb`. Set `SUPERUSER_PASSWORD` to the admin password you want (used only on first seed). Save with Ctrl+O, Enter, Ctrl+X. Leave `APP_DEBUG=false`.

## 7. Build the database

```bash
cd ~/spares && php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chmod -R ug+rwX ~/spares/storage ~/spares/bootstrap/cache
```

`db:seed` is the clean go-live seed: roles & route permissions, ZAR currencies, number sequences, chart of accounts + open periods, SA vehicle/parts reference packs, Retail + Trade price lists, Cash Customer, workshop labour pack, bin grid — no fake parts, suppliers or transactions. Optional demo data (only while the DB has no real records):

```bash
php artisan db:seed --class=DemoSeeder --force
```

## 8. Document root

cPanel → **Domains** → `mokone.inspirelabs.co.zw` → Manage → **Document Root** = `/home/mokonegwese/spares/public` → Save.

## 9. HTTPS

cPanel → **SSL/TLS Status** → tick the domain → **Run AutoSSL**. The HTTP→HTTPS redirect is already in `public/.htaccess` (host-scoped to `mokone.inspirelabs.co.zw`, so it's a no-op in local dev) — nothing else to do once the cert is issued.

## 10. Cron jobs (two, both "Once Per Minute")

cPanel → **Cron Jobs** → Common Settings → **Once Per Minute** (`* * * * *`), add as two separate jobs:

```bash
cd /home/mokonegwese/spares && php artisan schedule:run >> /dev/null 2>&1
```

```bash
cd /home/mokonegwese/spares && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

Cron ignores shell aliases and PATH. If step 3 needed a full PHP path, use it in both lines, e.g. `/opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run`.

The scheduler drives: health heartbeat, nightly DB backup → `storage/app/backups` (22:00 **UTC** = 00:00 SAST), nightly integrity check (06:00 UTC), failed-job pruning, password-reset cleanup. The queue worker sends queued mail/notifications.

## 11. First sign-in

Open `https://mokone.inspirelabs.co.zw`, sign in as `admin` with your `SUPERUSER_PASSWORD`. The app **forces a password change on first login** (`password_changed_at` is null until then) — that's expected, not a bug.

Then check **System Admin → Health**: scheduler heartbeat green within ~2 minutes of the cron going in, stock integrity 0 mismatches. And confirm `https://mokone.inspirelabs.co.zw/.env` returns 403/404.

## Updating later

On your PC:

```bash
npm run build && git add -A && git commit -m "..." && git push
```

On the server:

```bash
bash ~/spares/deploy/update.sh
```

Composer not on PATH: `COMPOSER=/opt/cpanel/composer/bin/composer bash ~/spares/deploy/update.sh` (or `COMPOSER="php $HOME/composer.phar"`). Shell PHP wrong: prefix `PHP=/opt/cpanel/ea-php83/root/usr/bin/php`.

The script puts the site in maintenance mode, pulls, installs PHP deps, runs migrations, rebuilds config/route/view/event caches, brings it back up.

By hand:

```bash
cd ~/spares && git pull --ff-only && composer install --no-dev --optimize-autoloader --no-interaction && php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

## Troubleshooting

- **500 / white screen** — `tail -50 ~/spares/storage/logs/laravel.log`. Usually missing `APP_KEY`, wrong DB creds, or `storage/` not writable.
- **404 on every page except /** — document root isn't `…/spares/public`, or `public/.htaccess` is missing.
- **Vite manifest error / unstyled page** — `public/build` wasn't committed/pushed. Run `npm run build` locally, commit, push, `git pull` on the server.
- **Composer memory fatal** — `php -d memory_limit=512M /opt/cpanel/composer/bin/composer install ...`.
- **`419 Page Expired` on login** — sessions not persisting: confirm the `sessions` table exists (`php artisan migrate --force`) and `SESSION_SECURE_COOKIE=true` matches HTTPS being on.
- **cron not running** — `crontab -l` on the server; use the full ea-php binary path; check the Health screen heartbeat.
- **Backups filling quota** (4.88 GB) — `ls -lh ~/spares/storage/app/backups`, prune old ones.
