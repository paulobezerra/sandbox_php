# AGENTS.md — Project guidance

## Mission

This repository provides a reusable PHP/Slim/Vue skeleton with registration, login, and a Posts blog. The standalone `$sdd-lite` skill is the workflow source; project facts and implementation conventions are recorded in `.specs/project-context.md` and `.specs/implementation.md`.

## Priorities, in order

1. Do not break existing behavior or tests.
2. Security and data integrity.
3. Simplicity and predictability for other developers and agents.
4. Domain organization and low boilerplate.
5. Optional automation improvements only when they reduce maintenance.

## Non-negotiable rules

- PHP 8.4, Slim 4, Vue 3, Vue Router, PDO/MySQL, Phinx, and PHPUnit; maintain compatibility unless explicitly decided otherwise.
- No Node.js, npm, Vite, Webpack, transpilers, compiled SFCs, bundling, or JavaScript build step.
- One PHP/Apache deployment for the web application; required external services may be configured separately.
- Always use `declare(strict_types=1);` in new PHP; type properties, parameters, and return values.
- Prefer small methods, explicit names, deterministic validation, and DTOs only when useful.
- **Do not create a custom framework**, router, DI container, ORM, or extension abstractions when existing libraries solve the problem with less code.
- Do not create an interface for every service. Repository contracts belong in Domain; PDO implementations belong in Infrastructure.
- Domain must not import Slim, PDO, HTTP, or Infrastructure. Application must not import Slim or PDO. Controllers are thin adapters.
- No SQL in controllers; no business rules in repositories; do not instantiate infrastructure dependencies inside domain services.
- Do not use `float` for money. Do not silently turn invalid input into default values.
- Do not change HTTP contracts, applied migrations, or existing data without analysis and tests.
- Do not add credentials, a real `.env`, tokens, sensitive logs, or database dumps to the repository.
- Do not add dependencies without checking compatibility, support, and need, and reporting the decision.
- Do not create subagents or autonomous execution loops without the user's explicit authorization.

## Target structure and dependency direction

```text
src/Domain/{User,Post,Page}/       entities, rules, repository interfaces
src/Application/{Auth,User,Post,Page}/  application services
src/Infrastructure/Http/          controllers, middleware, errors, responses
src/Infrastructure/Database/      connection and PDO repositories
src/Infrastructure/{Auth,Mail,View}/ technical adapters
src/Bootstrap/                    configuration, wiring, startup
database/{migrations,seeds}/
public/assets/{js,css,vendor}/
tests/{Unit,Integration,Architecture}/
```

Do not create empty directories. A controller parses HTTP → calls a service → turns its result into HTTP. A service coordinates rules and contracts. An entity protects invariants. A repository performs persistence. The default User is customizable by derived projects; prefer related business profiles, while allowing User/Auth to be adapted when needed.

## Guidance for reducing context and credits

- Work on a bounded task; do not reanalyze the entire repository for every prompt.
- Use `rg`/`find`/local scripts to locate files and patterns before opening large files.
- Do not read `vendor/`, `.idea/`, `.git/`, minified JavaScript dependencies, or lock files in full without need.
- Avoid cosmetic edits to files outside the scope.
- Keep formatting-only diffs separate from functional changes; the initial formatting of all PHP is tracked by `R001-18` in the active review.
- Prefer a cost-efficient agent/model for mechanical work; use a stronger model only for complex analysis, difficult bugs, or security decisions.
- Do not start subagents, parallel work, Ralph loops, or unbounded exploration without consent.
- Avoid lengthy output, repeated unnecessary tests, and redundant documentation.
- Do not assume success: say when a test was not run.

## API and security rules

- Prefer route attributes when stable Slim integration has been verified; public methods without an annotation or attribute do not become endpoints.
- Generate OpenAPI from code and check it against routes; avoid duplicate path definitions when integration cost is reasonable.
- Domain errors do not carry HTTP status codes; the HTTP adapter maps them consistently.
- Server-side sessions with HttpOnly/Secure/SameSite cookies; regenerate after login; use CSRF for mutating operations; always authorize on the backend.
- Use `password_hash`/`password_verify`. The current demo has no user profiles, password recovery, or rate limiting; derived projects must define recovery and abuse controls before public exposure.
- CMS Markdown must be rendered safely with XSS controls; untrusted `v-html` is prohibited.
- Use parameterized PDO queries and controlled, safely reversible migrations.

## Frontend guidance

- Vue 3 without a build; native ES modules and local vendors. Pages live under `public/assets/js/pages/`, HTTP calls in `services/`, and shared components should be reused.
- Do not introduce a custom Vue template generator or complex state system without justification.
- SPA fallback applies only to pages; unknown `/api/*` paths always return JSON 404.
- Frontend routing and authentication improve UX; they are **not authorization**.

## Current project commands (check the environment before running)

```bash
composer install
composer format
composer lint
composer test
composer migrate
composer migrate:status
# Docker: check README.md and compose.yaml before starting services
```

For SPA changes, browser checks can validate ES module loading and Vue mounting. With the `app` service active, `./tools/test-all.sh` on the host opens the main routes in headless Chrome/Chromium, then runs PHPUnit in the container. Chrome/Chromium is required on the host; do not add Node.js or a build step for this check.

Do not assume Compose, MySQL, or SMTP is active. Do not run `migrate:rollback`, reset a database, `docker compose down -v`, clear volumes, or delete files without authorization.

### Docker Compose access for agents

- This project does not require PHP or Composer on the host. First check the state with `docker compose ps -a`.
- When the `app` service is active, run PHP/Composer commands inside it, for example `docker compose exec -T app composer test`, `docker compose exec -T app php -l src/file.php`, and `docker compose exec -T app composer validate`.
- If the sandbox blocks the Docker socket, do not conclude that Docker is unavailable: retry with elevated execution (`require_escalated`) and explain the reason in the approval request.
- If `app` is not active, check `README.md` and `compose.yaml`; start only the required services when that is part of the authorized task. Do not remove or recreate volumes or change existing data without explicit authorization.
- `docker compose exec` works only with active containers; do not use `docker compose down -v`, reset, or destructive rollback.

## Change policy

- Do not create a PR, commit, publish, send email, change a remote environment, or deploy without an explicit request.
- The current installation uses an initial migration for `users` and `posts`; schema changes for existing installations require analysis and a separate migration that preserves data when necessary.

## Operations and distribution

- For development, follow the README quick start; migrations are run explicitly, not at startup.
- Run focused checks for the changed behavior and layer. Broader suites, aggregate quality gates, and browser/end-to-end checks are not automatic in Code or Review; run them only when the changed behavior and concrete risk justify them, once at the appropriate stage (normally Review). Reuse valid evidence and avoid duplicate checks. For SPA changes, use `./tools/test-all.sh` when browser coverage is justified.
- If `./tools/test-all.sh` cannot start Chromium because of `setsockopt: Operation not permitted` or a Crashpad error, retry the same command on the host with elevated execution (`require_escalated`), explaining that headless browser checks need browser resources blocked by the sandbox. Do not remove `--no-sandbox` or disable other Chromium protections to work around the block.
- For production, use `compose.prod.yaml` and an `.env` outside version control with project-specific secrets and an HTTPS `APP_URL`. Terminate TLS at the reverse proxy and do not expose MySQL.
- Read `docs/customization.md` before adapting User/Auth or adding domains. Keep contracts in Domain and adapters in Infrastructure; do not change HTTP/PDO internals to accommodate one entity.
- Keep `docs/` exclusive to application documentation.
