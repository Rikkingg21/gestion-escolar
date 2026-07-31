# AGENTS.md — gestión-escolar

Laravel 12.x school management system (Peru). Single app, not a monorepo.

## Commands

```sh
composer run dev           # Full dev stack (serve + queue + logs + Vite)
vendor/bin/phpunit         # PHP tests (or `php artisan test`)
vendor/bin/pint            # Format PHP code
npm run build/dev          # Vite frontend build / dev server
php artisan migrate        # Run database migrations
php artisan db:seed        # Seed database
php artisan sessions:clear # Clear stale sessions
```

## Architecture

- **Active routes:** `routes/web.php` (session-selection auth flow).
- **Auth flow:** login → `/select-session` (pick role/identity) → dashboard. Uses `session('current_role')` for authorization.
- **Permission system:** Custom `App\Models\Role` + `role_modules` pivot + `ModuleService`. **Spatie was removed** (was never wired).
- **"Maya"** namespace = curriculum planning: Bimestre → Unidad → Semana → Clase → Tema → Criterio. Controllers/Models under `Maya/`.
- Core modules under `app/`: `Asistencia/`, `Tramite/`, `Materia/`, `Metodopago/`, `Reporte/`.
- `User` model has `nombre_completo` accessor and `canAccessModule()` for authorization.

## Testing

- PHPUnit 11, SQLite `:memory:` in testing env. Feature tests use `RefreshDatabase`.
- To write tests: extend `Tests\TestCase`, place in `tests/Feature/` or `tests/Unit/`.
- CI via `.github/workflows/tests.yml` (runs lint + tests on push/PR).

## Cleanup applied

- **Removed:** Spatie (`laravel-permission`), `routes/web2.php`, 14 unused controllers, orphaned Maya views, Sass files, empty models, backup `* copy.php` files, unused npm deps (Tailwind, Bootstrap, axios, Popper), AuthServiceProvider, ImpersonationMiddleware.
- **Fixed:** `MateriacriterioController` typo in route, ModuleService duplicate methods, ExampleTest assertion, ApoderadoController empty methods.
- **Added:** `getNombreCompletoAttribute()` on User, CI workflow, `metodopago.show` view (missing).

## Gotchas

- **`.env` is committed** with plaintext credentials and `APP_KEY`. Do not commit secrets you add.
- Vite + `laravel-vite-plugin` are configured but **not wired to any Blade view** (assets load via CDN).
- No JS linter/formatter, no pre-commit hooks, no Docker config.
- `database/database.sqlite` is committed (test artifact).
