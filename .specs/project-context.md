# PROJECT CONTEXT — PHP + Slim + Vue Skeleton

**Updated:** 2026-10-18 · **Origin:** existing project (analyzed) · **Artifact language:** English (decided by Paulo Bezerra on 2026-10-18, to maximize the project's reach and make it accessible to as many potential contributors as possible)

## Purpose

A reusable skeleton for PHP applications, with registration, login, and a Posts blog. It serves backend and frontend from one PHP/Apache deployment. (README.md; composer.json)

## Stack and constraints

- **Languages/frameworks/runtime:** PHP `^8.4` (container verified: 8.4.26); Slim 4; Vue 3 and Vue Router through local browser assets; Composer 2 (container verified: 2.10.3); PHPUnit 11.
- **Data/infrastructure:** PDO/MySQL, MySQL 8.4 in Docker Compose, Phinx migrations, Apache. The web application is served by one PHP/Apache service; MySQL is a separate service.
- **Hard constraints:** no Node.js, npm, bundler, transpilation, or JavaScript build step; browser code uses native ES modules. Deploy with `public/` as document root. Keep secrets and `.env` outside version control/public web root. (README.md; compose.yaml; Dockerfile)

## Structure and boundaries

- `src/Domain/`: entities and repository contracts; `src/Application/`: application services; `src/Infrastructure/`: HTTP controllers/middleware, PDO repositories, views and localization; `src/Bootstrap/`: configuration and composition.
- `database/migrations/`: Phinx migrations. `public/`: entry point and static Vue/JavaScript/CSS/localization assets. `tests/`: Unit, Integration, and Architecture suites. `docs/`: application docs and generated OpenAPI.
- Observed dependency direction: Domain and Application are checked not to reference Infrastructure, Slim, PSR HTTP, or PDO by `tests/Architecture/DependencyRulesTest.php`; infrastructure implementations are wired in `src/Bootstrap/App.php`.

## Commands

PHP and Composer commands run in the active `app` container using `docker compose exec -T app ...`; Docker Compose also starts the MySQL service. PHP/Composer versions and Compose service state were checked on 2026-10-18. Project scripts below are defined in `composer.json` and documented in `README.md`; they were not run as part of init.

- Install: `docker compose up -d --build` (starts services; Composer install runs in the app service startup command).
- Run: open `http://localhost:8080` after services are active.
- Test: `docker compose exec -T app composer test` · focused: `docker compose exec -T app composer test -- --filter <TestName>` (defined; not run).
- Lint/format/static analysis: `docker compose exec -T app composer format` (writes formatting changes); `docker compose exec -T app composer lint` (read-only checks); static analysis is included in `composer quality`.
- Full quality gate: `docker compose exec -T app composer quality` (defined; not run).
- Browser checks for frontend changes: `./tools/test-all.sh` on the host with the app active and Chrome/Chromium installed; it also runs PHPUnit in the container (defined; not run).
- Migration commands: `docker compose exec -T app composer migrate` applies migrations; `... composer migrate:status` reports status. Neither was run during init.

## Conventions

Implementation boundaries, security, persistence and testing conventions are recorded in [implementation.md](implementation.md).

## Authorization limits

Do not reset or roll back databases, remove Docker volumes, delete project data, or deploy without explicit authorization. Back up production data before upgrades. Migrations are explicit; do not run them without task authorization and confirming the target environment. Do not expose MySQL publicly or commit secrets. (README.md; compose.yaml; AGENTS.md)

## Current state and known risks

- Registration/login use PHP sessions; Posts support public listing and authenticated ownership-managed authoring; routes and API documentation are present. (src/Infrastructure/Http/Controller/; tests/Integration/)
- The SPA includes localized English and Brazilian Portuguese assets and a Markdown preview/rendering path. (public/assets/; src/Infrastructure/View/MarkdownRenderer.php)
- Test suites include Unit, Integration, and Architecture tests. Browser checks require host Chrome/Chromium. (tests/; README.md)
- Feature records 001–004 intentionally retain their original historical shapes and references. Their embedded workflow references are not current guidance, and these records must not be normalized; new work follows the standalone SDD Lite artifact layout. After Feature 005, change this file or `implementation.md` only at the user's explicit request or through a new canonical `$sdd-lite init` run.
