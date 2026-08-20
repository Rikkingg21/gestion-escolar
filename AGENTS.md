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

- **Active routes:** `routes/web.php` (session-selection auth flow) + `routes/api.php` (registered in `bootstrap/app.php`).
- **Auth flow:** login → `/select-session` (pick role/identity) → dashboard. Uses `session('current_role')` for authorization.
- **Session principal:** `session('sessionmain')` stores the **user ID (int)** of the primary identity. Resolve the model in views via the `$sessionMainUser` variable injected by a global `View::composer` in `AppServiceProvider`.
- **Permission system:** Custom `App\Models\Role` + `role_modules` pivot + `ModuleService`. **Spatie was removed** (was never wired).
- **"Maya"** namespace = curriculum planning: Bimestre → Unidad → Semana → Clase → Tema → Criterio. Controllers/Models under `Maya/`.
- Core modules under `app/`: `Asistencia/`, `Tramite/`, `Materia/`, `Metodopago/`, `Reporte/`.
- **"Pension"** namespace = pensiones por periodo: `PensionConfig` (plantilla periodo+grado) → `PensionConfigCuota` (cuotas mensuales) → `Pension` (cuota generada por matrícula; estado `pendiente|pagado|anulado`, "atrasado" es accessor `estado_efectivo`, no se almacena) → `PensionPago` + `PensionPagoRegistro`. Montos en céntimos. Generación automática vía `MatriculaObserver` + `PensionService`. Módulos 24 (admin, `pensiones-admin.*`) y 25 (apoderado, `pensiones.*`).
- Services live under `app/Services/` (PSR-4 `App\Services`). Trámites tables/seeders were added as guarded migrations + idempotent seeders (DB was created manually).
- `User` model has `nombre_completo` accessor and `canAccessModule()` (takes numeric module id) for authorization.

## Testing

- PHPUnit 11, SQLite `:memory:` in testing env. Feature tests use `RefreshDatabase`.
- To write tests: extend `Tests\TestCase`, place in `tests/Feature/` or `tests/Unit/`.
- CI via `.github/workflows/tests.yml` (runs lint + tests on push/PR).

## Cleanup applied

- **Removed:** Spatie (`laravel-permission`), `routes/web2.php`, 14 unused controllers, orphaned Maya views, Sass files, empty models, backup `* copy.php` files, unused npm deps (Tailwind, Bootstrap, axios, Popper), AuthServiceProvider, ImpersonationMiddleware, dead Auth controllers (ConfirmPassword/ForgotPassword/ResetPassword/Verification), `TestCoreCommand`, dead methods in `RoleController` (`selectRole`/`switchRole`) and `ModuleService`, `maatwebsite/excel`.
- **Fixed:** `MateriacriterioController` typo in route, ModuleService duplicate methods, ExampleTest assertion, ApoderadoController empty methods, `sessionmain` now stores user ID (was a stale model object).
- **Added:** `getNombreCompletoAttribute()` on User, CI workflow, `metodopago.show` view, `routes/api.php` registration, trámites migrations (guarded with `hasTable`) + idempotent `EstadosTramiteSeeder`/`EstadosPagoSeeder`, global `$sessionMainUser` view composer, `GradoController::getDatosEstudiante`/`getCompetenciasDetalle` (AJAX de `gradoestudiantes`), `periodo_id` column on `maya_curso_grado_sec_niv_anios`, `app/services/` → `app/Services/`.
- **Security (Phase 1):** ownership checks in `ReporteController`, `TramiteController` (store + `subirComprobante` monto tope), `TramiteadminController::updateEstadoPago` (IDOR), `UserController` authorization, `NotaController` matrícula/pertenencia + `Hash::check` against `auth()->user()`, `SessionSelectionController` validates `user_id` against allowed list and real roles, `ApoderadoController` module middleware.

## Gotchas

- **`.env` is NOT tracked** (it's in `.gitignore`); do not commit secrets you add.
- Vite + `laravel-vite-plugin` are configured but **not wired to any Blade view** (assets load via CDN).
- No JS linter/formatter, no pre-commit hooks, no Docker config.
- `database/database.sqlite` is committed (test artifact).
- `migrate:status` shows all migrations as "Pending" on the local MySQL DB because tables were created manually; the guarded migrations are no-ops there but run on fresh installs.
- On Windows, `git mv app/services app/Services` needs a two-step rename (case-only rename fails).
