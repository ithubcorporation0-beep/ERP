# ERP

A Laravel 11 ERP application built with Sail (Docker) + MySQL, Breeze (Blade) auth, spatie/laravel-permission (RBAC), spatie/laravel-medialibrary (private docs), spatie/laravel-activitylog (audit), and Pest for testing.

## ERP Setup

### Prerequisites

- Docker Desktop running
- This repo cloned locally

### First-time setup

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Visit [http://localhost](http://localhost).

### Sail (Docker)

```bash
./vendor/bin/sail up -d      # start containers in the background
./vendor/bin/sail down       # stop containers
./vendor/bin/sail restart    # restart containers
```

### Database

```bash
./vendor/bin/sail artisan migrate          # run migrations
./vendor/bin/sail artisan migrate:fresh    # drop all tables and re-migrate
./vendor/bin/sail artisan db:seed          # run seeders
./vendor/bin/sail artisan migrate:fresh --seed
```

### Frontend assets

```bash
./vendor/bin/sail npm run dev     # Vite dev server with hot reload
./vendor/bin/sail npm run build   # production build
```

### Tests

This project uses [Pest](https://pestphp.com). Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so they don't require Sail's MySQL container to be running.

```bash
./vendor/bin/sail artisan test
# or, without Sail:
./vendor/bin/pest
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
