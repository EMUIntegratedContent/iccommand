# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ICCommand is a Symfony 8 / PHP 8.5 multi-module internal web application for Eastern Michigan University. It consists of several sub-applications: Interactive Map, URL Redirects, Academic Programs catalog, Department Directory, Crime/Fire Log, Emergency Notices, and Photography Requests. The frontend uses Vue 3 with Webpack Encore.

## Development Commands

### Docker
```bash
docker compose up -d          # Start all services (web on :8080, db on :3306, mailhog on :8027)
docker compose down           # Stop services
docker exec iccommand-web-1 php bin/console <cmd>   # DB host `db` only resolves inside Docker
```

### PHP / Symfony
```bash
composer install              # Install PHP dependencies
php bin/console cache:clear   # Clear Symfony cache
php bin/console doctrine:migrations:migrate [--dry-run]   # Migrations live in src/Migrations/
php bin/console make:entity   # Generate a new entity
```

### Frontend
```bash
npm install                   # Install JS dependencies
npm run dev                   # Build assets once (development)
npm run watch                 # Build assets with file watching
npm run build                 # Production build; public/build is committed (prod deploys by git pull)
bin/check-frontend-build      # Run before committing public/build
```

### Tests
The `test` env is stale since the Symfony 8 upgrade: it needs `SYMFONY_PHPUNIT_VERSION=9.6`, temporary
patches to `config/packages/test/*.yaml`, and the `.env` vars exported with `APP_ENV=test`. Tests run
against the real `ic` DB, so clean up the rows they create.
```bash
php bin/phpunit tests/Api/Admin                     # Run a directory/file (inside the web container)
php bin/phpunit --filter testMethodName             # Run a single test method
```

## Architecture

### Multi-Database Setup

Three separate MySQL/MariaDB connections configured in `config/packages/doctrine.yaml`:

| Connection | Entity Directory | Purpose |
|---|---|---|
| `default` | all of `src/Entity/` (auto_mapping) | Main IC application |
| `programs` | `src/Entity/Programs/` | Same physical `ic` DB as default (Programs moved in June 2026) |
| `dps` | `src/Entity/CrimeLog/` | DPS crime & fire log |

Services that use a non-default entity manager (Programs, CrimeLog) must inject the correct EntityManager explicitly rather than relying on the autowired default.

### Shared Colleges & Departments

`ic_` prefix = table shared by more than one app. `ic_colleges` / `ic_departments` (entities in
`src/Entity/Ic/`, managed at /admin/colleges and /admin/departments) feed the Programs and Scholarships
dropdowns. A program's colleges and departments live ONLY in `program_college_link` / `program_inter_dept`
(several per program). See `docs/shared-colleges-departments.md`. Much of the Programs module is raw DBAL
SQL in `ProgramsRepository` (many program tables have no entity).

### Backend Structure

- **Controllers** (`src/Controller/Api/`): REST API controllers organized by module. Use PHP 8 `#[Route]` attributes with `#[IsGranted]` for authorization.
- **Services** (`src/Service/`): Business logic layer. One service per module (MapItemService, RedirectService, ProgramsService, etc.).
- **Entities** (`src/Entity/`): Doctrine ORM with PHP 8 attributes. MapItem uses JOINED inheritance with 10+ subclasses. Gedmo extensions provide `@Timestampable`, `@Sluggable`, and `@Blameable` behaviors.
- **Repositories** (`src/Repository/`): Extend `ServiceEntityRepository` with custom query methods.

### Frontend Structure

- **Entry point**: `assets/js/app.js` registers all Vue components globally.
- **Components** (`assets/js/components/`): Vue 3 SFCs organized by module (map/, redirect/, directory/, programs/, crimelog/, photorequest/, emergency/, admin/).
- **Utilities** (`assets/js/utils/`): Shared helpers.
- **Webpack alias**: `@` maps to `assets/js/`.

Templates are Twig files in `templates/` — each module has a subdirectory. Vue components mount into Twig-rendered pages.

### API Routing

Routes are defined in `config/routes.yaml` mapping controllers to prefixes:
- `/api/external/*` — Public endpoints (no auth required)
- `/api/*` — Authenticated endpoints (role-based)

### Security & Roles

Configured in `config/packages/security.yaml`. Hierarchical role system with per-module role chains (e.g., `ROLE_MAP_VIEW → ROLE_MAP_CREATE → ROLE_MAP_EDIT → ROLE_MAP_DELETE → ROLE_MAP_ADMIN`). `ROLE_GLOBAL_ADMIN` has access to all modules.

**Important:** `ROLE_GLOBAL_ADMIN` does not inherit module-specific roles in the hierarchy — it only inherits `ROLE_USER`. Every `#[IsGranted]` attribute on a route must include `ROLE_GLOBAL_ADMIN` via Expression syntax (e.g., `#[IsGranted(new Expression('is_granted("ROLE_GLOBAL_ADMIN") or is_granted("ROLE_MODULE_ROLE")'))]`) so global admins are never locked out.

Auth method is selected automatically via Symfony `when@` blocks: `dev` uses local form login, `staging` and `prod` use LDAP, `test` uses form login without UserChecker.

### Rate Limiting

`RateLimitSubscriber` (`src/EventSubscriber/`) applies bot detection and rate limiting (50 requests/60 min) to external redirect API endpoints.

### Image Handling

Upload directories configured as parameters in `services.yaml`:
- Profile images: `/uploads/profile`
- Map item images: `/uploads/map`

Liip Imagine Bundle handles image manipulation.

### CORS

Environment-specific CORS in `config/packages/nelmio_cors.yaml`. Dev allows `*.emich.edu`; production restricts to specific subdomains.

## Key Conventions

- PHP routes use `#[Route]` and `#[IsGranted]` attributes (not YAML or annotation routing).
- Serialization uses Symfony Serializer (`SerializerInterface`) with `#[Groups]` attributes (jms/serializer is installed but unused).
- Form validation uses Symfony Validator constraints on entity properties.
- Frontend HTTP requests use Axios with CSRF token configured in `assets/js/bootstrap.js`.
- jQuery (`$` / `jQuery`) is auto-provided to bundled modules via Webpack `autoProvidejQuery()`, but is not on `window` (so not usable from the browser console or inline scripts).
- Vue 3 runs with the runtime compiler enabled and Options API support.
- New API controllers must be registered in `config/routes.yaml` with their `/api/...` prefix; otherwise
  `config/routes/attributes.yaml` serves them at a bare path, outside the `^/api` firewall rule.
- `^/admin` is ROLE_GLOBAL_ADMIN-only via access_control.
- Migrations are hand-written and safe to re-run: check `information_schema` before each change and use
  `warnIf`/`abortIf`. MariaDB can't roll back schema changes, so back up before migrating. Under
  `--dry-run`, earlier migrations' SQL hasn't run, so checks must tolerate the old schema.
- Delete modals: don't clear `deleteConfirm` inside the click handler before the request finishes; doing so
  disables the button before Bootstrap's `data-dismiss` handler runs, and the modal stays open.
- Public API docs: `docs/scholarship-search-api.md`, `docs/shared-colleges-departments.md`.
