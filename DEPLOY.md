# Deploying the ERP with Docker

Production deploy files, separate from Sail's dev setup (`php artisan serve`
+ SQLite, used for local work throughout this project):

| File | Purpose |
|---|---|
| `Dockerfile` | Multi-stage build → one runtime image running PHP-FPM + Nginx (via supervisord), Opcache on. |
| `docker/nginx.conf` | Nginx server block serving `public/`, proxying `*.php` to PHP-FPM. |
| `docker/php/*.ini` | Opcache + production `php.ini` overrides (errors hidden, upload limits). |
| `docker/supervisord.conf` | Runs `php-fpm` and `nginx` as sibling processes in the one container. |
| `docker/entrypoint.sh` | Runs `config:cache`/`route:cache`/`view:cache`/`event:cache` at container start, then execs supervisord. |
| `docker-compose.prod.yml` | `app` (the image above) + `mysql`, with a named volume for `storage/` and one for MySQL's data. |

**Why one container, not separate Nginx/PHP-FPM containers?** Two
containers sharing a `public/` folder need a volume kept in sync between
Nginx and PHP-FPM, and a plain named volume only auto-populates from the
image on its *first* mount - it goes stale on every deploy after that
unless you add extra sync tooling. Running both processes in the one
container sidesteps that whole class of bug: a fresh deploy is always a
fresh container with consistent files, and it's still all this app's
traffic needs. Split them later if you ever need to scale Nginx and
PHP-FPM independently.

## 1. Prerequisites

- Docker + the Compose plugin on the target host.
- A production `.env`, based on `.env.example`, with at minimum:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://your-domain.example
  APP_KEY=                     # filled in by step 2 below
  DB_CONNECTION=mysql
  DB_HOST=mysql                # the compose service name, not localhost
  DB_PORT=3306
  DB_DATABASE=erp
  DB_USERNAME=erp
  DB_PASSWORD=<a real password>
  SESSION_DRIVER=database
  CACHE_STORE=database
  QUEUE_CONNECTION=database    # see "About the queue" below
  ```
  `docker-compose.prod.yml` reads this same file twice: once as the `app`
  container's runtime environment (`env_file:`), and once for `${DB_*}`
  substitution inside the compose YAML itself (so the `mysql` service is
  created with matching credentials without repeating them). Both need
  the file to be named `.env` and sit next to `docker-compose.prod.yml`.

## 2. Generate an APP_KEY

Do this once, before the first deploy - `APP_KEY` must stay the same
across every future deploy (it's what encrypts sessions and any encrypted
columns; rotating it invalidates all of them):

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php -r \
  "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Paste the output into `.env` as `APP_KEY=...`.

## 3. Build and start

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

The `app` service won't report healthy until `mysql` does (compose's
`depends_on: condition: service_healthy`), so the first boot takes a few
extra seconds while MySQL initializes.

## 4. First-time setup

Run these once, against the freshly-started stack:

```bash
# Apply the schema.
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Laravel convention - creates the public/storage symlink. This app's own
# uploads (avatars, documents, the company logo) all live on the private
# disk behind authenticated routes and never touch the public disk, so
# nothing here breaks without it, but it's a one-line, harmless step to
# run in case that ever changes.
docker compose -f docker-compose.prod.yml exec app php artisan storage:link

# Seed roles only - NOT `db:seed`. DatabaseSeeder's demo data (a test
# user, demo customers/projects/invoices) is guarded behind
# app()->environment('local') and won't run under APP_ENV=production
# regardless, but RolesAndAdminSeeder is the one seeder this app actually
# needs in production: it creates the six roles (SUPER_ADMIN, ADMIN,
# MANAGER, ACCOUNTANT, EMPLOYEE, CLIENT) policies and routes check
# against. It does NOT create a demo admin account outside `local`.
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=RolesAndAdminSeeder --force
```

Then create your first real admin account with a real password (there is
no seeded admin account in production - on purpose, since the demo one
uses the well-known password `password`):

```bash
docker compose -f docker-compose.prod.yml exec app php artisan tinker
>>> $u = \App\Models\User::create(['name' => 'Your Name', 'email' => 'you@example.com', 'password' => 'a-strong-unique-password', 'email_verified_at' => now()]);
>>> $u->assignRole(\App\Support\Roles::SUPER_ADMIN);
>>> exit
```

Visit `https://your-domain.example` and log in.

## 5. About the queue

This app does **not** run a queue worker, on purpose: every
`Notification` class is deliberately synchronous (see the classes under
`app/Notifications/` - none use `ShouldQueue`/`Queueable`), because a
queued job with no worker consuming it just sits in the `jobs` table
forever and the notification never appears. `QUEUE_CONNECTION=database`
in `.env` only matters if you later add a queued job yourself.

If you do add one, add a worker to `docker-compose.prod.yml`:

```yaml
  queue:
    build:
      context: .
      dockerfile: Dockerfile
      target: app
    restart: unless-stopped
    env_file: [.env]
    volumes: [app-storage:/var/www/html/storage]
    depends_on: { mysql: { condition: service_healthy } }
    command: ["php", "artisan", "queue:work", "--tries=3"]
```

## 6. Redeploying after a code change

```bash
git pull
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml up -d app
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

`entrypoint.sh` re-runs `config:cache`/`route:cache`/`view:cache`/
`event:cache` on every container start, so a new container always
reflects the code and `.env` it was actually started with - there's
nothing else to re-cache by hand. `storage/` is a named volume, so
uploaded documents/avatars/logos and the MySQL data volume both survive
the redeploy untouched.

## 7. Logs

Nginx and PHP-FPM both log to the container's stdout/stderr (see
`docker/nginx.conf` and `docker/supervisord.conf`), so:

```bash
docker compose -f docker-compose.prod.yml logs -f app
```

Laravel's own log (`storage/logs/laravel.log`, `LOG_CHANNEL=stack` in
`.env`) lives on the `app-storage` volume - `docker compose exec app tail
-f storage/logs/laravel.log` to follow it directly.
