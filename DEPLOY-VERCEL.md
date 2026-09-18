# Deploying to Vercel

This is a **second, separate** deploy path from `DEPLOY.md` (a normal
Docker host/VPS via `docker-compose.prod.yml`). Use this one only if
Vercel specifically is where you need this app to run.

Vercel doesn't run PHP-FPM - `package.json` having a `vite build` script
makes Vercel's default build detect this as a static site and look for a
`dist/` folder, which is why you saw that build error. The actual fix is
what's in this file: a container-backed [Vercel Function](https://vercel.com/kb/guide/laravel-php-with-docker)
running [FrankenPHP](https://frankenphp.dev/) (PHP + the Caddy web server,
one process), built from `Dockerfile.vercel` and wired up via
`vercel.json`. `Dockerfile.vercel`, `docker/Caddyfile`, and `vercel.json`
are all new, dedicated to this path - nothing here touches the normal
Docker deploy in `DEPLOY.md`.

## Why this needs more than a build setting

Vercel's container Functions have **no durable local filesystem** -
anything written to disk while an instance is running can vanish the
moment that instance recycles, and is never shared with other instances
running the same deployment. Two things in this app depend on durable
local state, so both had to move to external services before this can
work at all:

1. **The database.** Vercel doesn't host MySQL. You need an external
   managed MySQL host (you said you already have one - see step 2).
2. **File uploads** (avatars, documents, the company logo). These used to
   live on `storage/app/private` and be served with
   `response()->file(...)`, which only works against a real local path.
   They now go through `Storage::disk($media->disk)->response(...)` /
   `->download(...)` in `AvatarController`, `DocumentController`, and
   `SettingController` instead - Laravel's own disk-agnostic streaming,
   unchanged whether the `private` disk is local (Sail/dev, and the
   `DEPLOY.md` Docker path) or S3-compatible (here). Set
   `PRIVATE_FILESYSTEM_DRIVER=s3` and point the `AWS_*` vars at
   **Cloudflare R2** (or any S3-compatible provider) to make this work on
   Vercel - see step 3.

Nothing else in the app changed - the same codebase runs against either
deploy target, chosen entirely by env vars.

## 1. Set up Cloudflare R2

1. Create an R2 bucket in the Cloudflare dashboard (**not** the same as
   an R2 bucket meant for public web assets - this one should stay
   private; every request to it in this app already goes through an
   authenticated, policy-checked controller action).
2. Create an R2 API token scoped to that bucket (Account → R2 → Manage
   API Tokens), not a general Cloudflare account API key.
3. You'll need, for step 4 below:
   - `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` - the R2 token's
     access key ID / secret access key.
   - `AWS_BUCKET` - the bucket name.
   - `AWS_ENDPOINT` - `https://<account_id>.r2.cloudflarestorage.com`
     (find `<account_id>` on the R2 dashboard's overview page).

## 2. Point at your MySQL host

Add the matching PHP extension if your host isn't MySQL-compatible on
the wire (it already installs `pdo_mysql` in `Dockerfile.vercel`, which
covers MySQL, MariaDB, PlanetScale, etc.). You'll need `DB_HOST`,
`DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for step 4.

## 3. Set environment variables in the Vercel project

`vercel env add <NAME>` for each of these (or the dashboard's
Settings → Environment Variables), scoped to Production:

```
APP_KEY=                     # see below - generate once, never rotate
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
SESSION_DRIVER=database       # already the default - keeps sessions
CACHE_STORE=database          # working across every instance, since
                               # both point at the one shared MySQL host
PRIVATE_FILESYSTEM_DRIVER=s3  # already the default in Dockerfile.vercel,
                               # setting it here too costs nothing
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_BUCKET=
AWS_ENDPOINT=
AWS_DEFAULT_REGION=auto
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Generate `APP_KEY` once, locally:

```bash
php artisan key:generate --show
```

Use the same key for every future deploy - rotating it invalidates every
existing session and anything encrypted with the old one.

`QUEUE_CONNECTION=sync` is what this app already runs everywhere (see
`DEPLOY.md`'s "About the queue" - no `Notification` class here uses
`ShouldQueue`), which happens to be exactly what Vercel's guide
recommends for its own container Functions too, so there's nothing to
change for that.

## 4. Deploy

```bash
vercel deploy --prod
```

Vercel builds `Dockerfile.vercel`, pushes the image to its container
registry, and starts Function instances from it on traffic. The env vars
from step 3 are injected into each instance at start - never baked into
the image (`Dockerfile.vercel`'s only baked-in env is the throwaway
build-time `APP_KEY` used solely so `artisan` can boot far enough to run
`event:cache`/`view:cache` during the build; it's discarded before the
final image).

## 5. First-time setup

Vercel doesn't run migrations for you, and **migrations should never run
automatically at container startup** here - multiple instances can start
concurrently, and two `migrate` runs racing each other against the same
table is exactly the kind of bug that's hard to reproduce and easy to
ship. Run it as its own step instead, from your own machine or CI,
against the same DB credentials from step 3:

```bash
DB_CONNECTION=mysql DB_HOST=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=... \
  php artisan migrate --force
```

Then seed roles only (not a blanket `db:seed` - see `DEPLOY.md` for why:
`DatabaseSeeder`'s demo data is guarded behind `local` and won't run
under `APP_ENV=production` regardless, but there's no reason to depend on
that guard when you can just call the one seeder this app actually needs
in production directly):

```bash
DB_CONNECTION=mysql DB_HOST=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=... \
  php artisan db:seed --class=RolesAndAdminSeeder --force
```

Create your first real admin account the same way `DEPLOY.md` describes -
via `tinker`, with your own password, against the same DB credentials.
There is no seeded admin account in production.

## Redeploying

```bash
git pull
vercel deploy --prod
```

Then run `php artisan migrate --force` (same as step 5) if the deploy
included new migrations. Every new deploy is a fresh image and fresh
instances - there's no cache to invalidate by hand, and `Dockerfile.vercel`
never bakes `config:cache`/`route:cache` (only `event:cache`/`view:cache`,
which don't depend on the runtime secrets from step 3), so there's
nothing that could go stale from an old build's env values either.
