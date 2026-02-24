# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ICCommand is a Symfony 8 / PHP 8.5 multi-module internal web application for Eastern Michigan University. It consists of several sub-applications: Interactive Map, URL Redirects, Academic Programs catalog, Department Directory, Crime/Fire Log, Emergency Notices, and Photography Requests. The frontend uses Vue 3 with Webpack Encore.

## Development Commands

### Docker
```bash
docker compose up -d          # Start all services (web on :8080, db on :3306, mailhog on :8027)
docker compose down           # Stop services
```

### PHP / Symfony
```bash
composer install              # Install PHP dependencies
php bin/console cache:clear   # Clear Symfony cache
php bin/console doctrine:migrations:migrate   # Run database migrations
php bin/console make:entity   # Generate a new entity
```

### Frontend
```bash
npm install                   # Install JS dependencies
npm run dev                   # Build assets once (development)
npm run watch                 # Build assets with file watching
npm run build                 # Build assets for production
```

### Tests
```bash
php bin/phpunit               # Run all tests
php bin/phpunit tests/path/to/TestFile.php          # Run a single test file
php bin/phpunit --filter testMethodName              # Run a single test method
```

## Architecture

### Multi-Database Setup

Three separate MySQL/MariaDB connections configured in `config/packages/doctrine.yaml`:

| Connection | Entity Directory | Purpose |
|---|---|---|
| `default` | `src/Entity/` (excluding Programs, CrimeLog) | Main IC application |
| `programs` | `src/Entity/Programs/` | Acalog academic programs catalog |
| `dps` | `src/Entity/CrimeLog/` | DPS crime & fire log |

Services that use a non-default entity manager (Programs, CrimeLog) must inject the correct EntityManager explicitly rather than relying on the autowired default.

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
- Serialization uses JMS Serializer with serialization groups for API responses.
- Form validation uses Symfony Validator constraints on entity properties.
- Frontend HTTP requests use Axios with CSRF token configured in `assets/js/bootstrap.js`.
- jQuery is globally available via Webpack `autoProvidejQuery()`.
- Vue 3 runs with the runtime compiler enabled and Options API support.
