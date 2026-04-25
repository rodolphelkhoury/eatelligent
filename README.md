# Eatelligent

Eatelligent is a Laravel-based nutrition, product and ordering platform that provides user accounts, product/catalog management, ordering, payments, wallets and integrations (email/WhatsApp). This repository contains the application backend and related services.

**Tech stack:** PHP 8.2, Laravel 12, MySQL/SQLite, Stripe for payments, Sanctum for API authentication.

**Key areas of the codebase:**
- `app/Models` — Eloquent models (User, Product, Order, Wallet, BodyComposition, etc.)
- `app/Actions` — domain actions used across the app
- `app/Integrations` — external integrations (WhatsApp, mail)
- `database/migrations`, `database/factories`, `database/seeders`
- `routes/api.php` — API routes

**Note:** This README focuses on project-specific setup and usage. For general Laravel concepts, see the official docs at https://laravel.com/docs.

## Quick Start

1. Install dependencies:

```bash
composer install
npm install
```

2. Copy the example environment and set environment variables:

Windows:

```powershell
copy .env.example .env
```

Unix/macOS:

```bash
cp .env.example .env
```

3. Generate app key and run migrations:

```bash
php artisan key:generate
php artisan migrate --seed
```

4. Start the app (development):

```bash
composer run dev
```

Or run the provided setup script (installs deps, copies env, migrates):

```bash
composer run setup
```

## Environment variables

Populate at minimum the following in your `.env`:

- `APP_NAME`, `APP_URL`, `APP_ENV`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`
- `STRIPE_KEY`, `STRIPE_SECRET` (for payments)
- Any WhatsApp integration keys configured in `app/Integrations/WhatsApp`

Refer to `.env.example` for full list of variables.

## Running Tests

Run the test suite with PHPUnit via the composer script:

```bash
composer run test
```

## Helpful Composer scripts

- `composer run setup` — installs dependencies, copies `.env.example`, generates key, runs migrations and builds front-end assets (see `composer.json` scripts).
- `composer run dev` — starts development servers (artisan serve, queue listener, etc.) via the dev script.
- `composer run test` — runs automated tests.

## Database

The project supports the typical Laravel database drivers (MySQL, Postgres, SQLite). For quick local development you can use SQLite by creating `database/database.sqlite` and setting `DB_CONNECTION=sqlite` in `.env`.

## Seeding data

Seeders and factories live in `database/seeders` and `database/factories`. To seed after migrating:

```bash
php artisan db:seed
```

Or run migrations with seeds enabled:

```bash
php artisan migrate --seed
```

## Common tasks

- Run the queue worker: `php artisan queue:work`
- Rebuild autoload files: `composer dump-autoload`
- Run Pint code style: `./vendor/bin/pint` (or `vendor\bin\pint` on Windows)

## Contributing

1. Fork the repository and create a feature branch.
2. Ensure tests pass and code is formatted.
3. Submit a PR describing your changes.

## Security

If you find a security issue, please open an issue or contact a repo administrator privately.

## License

This project is available under the MIT license.
