# PROJECT CONTEXT — PHP + Slim + Vue Skeleton

**Updated:** 2026-10-18 · **Origin:** existing project (analyzed) · **Artifact language:** English (decided by Paulo Bezerra on 2026-10-18, to maximize the project's reach and make it accessible to as many potential contributors as possible)

## Purpose

A reusable skeleton for PHP applications, with registration, login, and a Posts blog. It serves backend and frontend from one PHP/Apache deployment. (README.md; composer.json)

## Stack and constraints

- **Languages/frameworks/runtime:** PHP `^8.4` (container verified: 8.4.26); Slim 4; Vue 3, Vue Router, and Vite; Node/npm and Vitest for frontend tests; Composer 2 (container verified: 2.10.3); PHPUnit 11.
- **Data/infrastructure:** PDO/MySQL, MySQL 8.4 in Docker Compose, Phinx migrations, Apache. The web application is served by one PHP/Apache service; MySQL is a separate service.
- **Frontend tooling:** Vue 3 source is built with Vite and tested with Vitest. Production assets are generated under `public/assets/build/`; the deployed PHP/Apache application serves them. Frontend tests require no browser or live service. Deploy with `public/` as document root. Keep secrets and `.env` outside version control/public web root. (README.md; frontend/package.json; frontend/vite.config.js)

## Structure and boundaries

- `src/Domain/`: entities and repository contracts; `src/Application/`: application services; `src/Infrastructure/`: HTTP controllers/middleware, PDO repositories, views and localization; `src/Bootstrap/`: configuration and composition.
- `database/migrations/`: Phinx migrations. `public/`: entry point and static Vue/JavaScript/CSS/localization assets. `tests/`: Unit, Integration, and Architecture suites. `docs/`: application docs and generated OpenAPI.
- Observed dependency direction: Domain and Application are checked not to reference Infrastructure, Slim, PSR HTTP, or PDO by `tests/Architecture/DependencyRulesTest.php`; infrastructure implementations are wired in `src/Bootstrap/App.php`.

## Commands

Application and database commands run in the active `app` container using `docker compose exec -T app ...`; Docker Compose also starts the MySQL service. The default PHPUnit suite is service-free and can run directly on a host with PHP/Composer dependencies installed. PHP/Composer versions and Compose service state were checked on 2026-10-18. Project scripts below are defined in `composer.json` and documented in `README.md`; they were not run as part of init.

- Install: `docker compose up -d --build` (starts services; Composer install runs in the app service startup command).
- Run: open `http://localhost:8080` after services are active.
- Test: `composer test` (service-free PHPUnit suite; defined; not run) · frontend: `npm --prefix frontend run test` (Vitest; defined; not run).
- Lint/format/static analysis: `docker compose exec -T app composer format` (writes formatting changes); `docker compose exec -T app composer lint` (read-only checks); static analysis is included in `composer quality`.
- Full quality gate: `docker compose exec -T app composer quality` (defined; not run).
- Production frontend build: `npm --prefix frontend run build` (defined; not run). CI runs each test suite directly with a 20-second limit.
- Migration commands: `docker compose exec -T app composer migrate` applies migrations; `... composer migrate:status` reports status. Neither was run during init.

## Conventions

Implementation boundaries, security, persistence and testing conventions are recorded in [implementation.md](implementation.md).

## Authorization limits

Do not reset or roll back databases, remove Docker volumes, delete project data, or deploy without explicit authorization. Back up production data before upgrades. Migrations are explicit; do not run them without task authorization and confirming the target environment. Do not expose MySQL publicly or commit secrets. (README.md; compose.yaml; AGENTS.md)

## Current state and known risks

- Registration/login use PHP sessions; Posts support public listing and authenticated ownership-managed authoring; routes and API documentation are present. (src/Infrastructure/Http/Controller/; tests/Integration/)
- The SPA includes localized English and Brazilian Portuguese assets and a Markdown preview/rendering path. (public/assets/; src/Infrastructure/View/MarkdownRenderer.php)
- Test suites include Unit, Integration, and Architecture tests; PHPUnit and Vitest default suites require no browser, live app, or database. (tests/; frontend/tests/; README.md)
- Feature records 001–004 intentionally retain their original historical shapes and references. Their embedded workflow references are not current guidance, and these records must not be normalized; new work follows the standalone SDD Lite artifact layout. After Feature 005, change this file or `implementation.md` only at the user's explicit request or through a new canonical `$sdd-lite init` run.
