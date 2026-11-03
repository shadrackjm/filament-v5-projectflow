# ProjectFlow

The application built in the "Code with SJM" Filament v5 series: a project-management admin panel with
two panels (`/admin` for staff, `/app/{team}` for customers), policies, multi-tenancy, dashboards,
notifications, a custom theme and a branding plugin. Every episode is one commit, tagged `ep01` … `ep20`.

Stack: Laravel 13 · Filament 5.11 · Livewire 4.4 · Pest 4 · Tailwind CSS 4 · PHP 8.4 · SQLite.

## Run it locally

```bash
composer install
npm install
npm run build                 # the custom theme (episode 19) is compiled, not pre-built
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Sign in at `/admin` with the seeded administrator (see `database/seeders/DatabaseSeeder.php`).

Exports (episode 10) and the workload report (episode 15) are queued — run a worker alongside the app:

```bash
php artisan queue:work
```

## Tests

```bash
npm run build && php artisan test
```

`ThemingTest` checks the compiled theme is wired up, so build first. `RefreshDatabase` is applied to
every feature test from `tests/Pest.php`. Tenancy is tested over HTTP on purpose — see `TenancyTest`.

## Deploying

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan filament:optimize
```

Never run `filament:optimize` in development — the component cache hides new resources until
`php artisan filament:optimize-clear`. Rebuild it on every deploy.

### Production checklist

1. `APP_DEBUG=false`.
2. `User::canAccessPanel()` is implemented (it is — episode 16).
3. Every resource has a policy; custom actions use `->authorize()`; editable table columns are disabled
   when the policy says no.
4. A queue worker is running (Supervisor or Horizon).
5. File uploads use real shared storage (S3) on more than one server.
6. N+1 queries checked on production-sized data, including global search.
7. Searchable, filtered and badge-counted columns are indexed.
8. Error reporting is set up (Sentry, Flare…).
9. The database is backed up before deploying a migration.
10. CI runs `npm ci && npm run build` before `php artisan test`.
