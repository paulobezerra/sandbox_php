# IMPLEMENTATION CONVENTIONS — PHP + Slim + Vue Skeleton

**Updated:** 2026-10-18 · **Origin:** observed in code/tests and repository documentation

Starting point for Spec, Code, Bug and Review; the feature Spec defines the contract and justifies exceptions. Each rule carries its **status**: `observed` (found in code/tests, cite the evidence), `decided` (confirmed by the user, with date), or `assumption` (unconfirmed; do not enforce in Review). Keep rules short and testable; drop sections that do not apply. Stack, commands and structure live in [project-context.md](project-context.md), not here.

## Boundaries and composition

- Domain owns entities and repository contracts; Application coordinates those contracts; Infrastructure contains PDO implementations and HTTP adapters. Bootstrap wires implementations. — `observed`, `src/Domain/Post/PostRepository.php`, `src/Application/Post/PostService.php`, `src/Infrastructure/Database/Repository/MysqlPostRepository.php`, `src/Bootstrap/App.php`
- Domain and Application must not reference Infrastructure, Slim, PSR HTTP, or PDO; an architecture test checks these restrictions. — `observed`, `tests/Architecture/DependencyRulesTest.php`
- Controllers use route attributes and adapt requests/responses around application services. OpenAPI attributes are colocated with routes. — `observed`, `src/Infrastructure/Http/Controller/PostController.php`, `tests/Architecture/OpenApiRoutesTest.php`

## Contracts and security

- Passwords are created with `password_hash` and checked with `password_verify`; login regenerates the PHP session ID. — `observed`, `src/Application/Auth/AuthService.php`, `src/Infrastructure/Http/Controller/AuthController.php`, `tests/Integration/AuthControllerTest.php`
- Mutating authenticated requests require CSRF validation, and access to Posts is scoped to the authenticated author. — `observed`, `src/Infrastructure/Http/Middleware/CsrfMiddleware.php`, `src/Application/Post/PostService.php`, `tests/Integration/CsrfMiddlewareTest.php`, `tests/Integration/PostOwnershipTest.php`
- User-authored Markdown is rendered with raw HTML escaped and unsafe links disabled. — `observed`, `src/Infrastructure/View/MarkdownRenderer.php`, `tests/Unit/Infrastructure/MarkdownRendererTest.php`
- OpenAPI definitions should stay aligned with attribute-defined routes. — `observed`, `src/Infrastructure/Http/Controller/`, `tests/Architecture/OpenApiRoutesTest.php`, `composer.json` (`openapi:generate`, `openapi:check`)

## Data and persistence

- PDO repositories use prepared statements for parameterized values. — `observed`, `src/Infrastructure/Database/Repository/MysqlPostRepository.php`, `src/Infrastructure/Database/Repository/MysqlUserRepository.php`
- Schema changes use Phinx migration files under `database/migrations/`; the repository currently has one initial schema migration. Applied migration history must be examined before schema changes. — `observed`, `database/migrations/20261016000001_create_initial_schema.php`, `phinx.php`

## Client and interface

- Frontend source uses Vue and Vite; production assets are generated under `public/assets/build/`, and Vitest tests run without a browser or live service. — `observed`, `frontend/package.json`, `frontend/vite.config.js`, `frontend/vitest.config.js`
- Third-party frontend libraries should be delivered from version-pinned CDNs by default. If a CDN integration fails, correct that integration rather than adding a local copy, unless the user makes a later decision. — `decided`, Paulo Bezerra, 2026-10-19
- Frontend behavior checks use `npm --prefix frontend run test`; production assets are checked separately with `npm --prefix frontend run build`. — `observed`, `frontend/package.json`, `.github/workflows/quality-and-release.yml`

## Testing and quality gates

- Tests are organized into Unit, Integration, and Architecture suites; PHPUnit discovers tests from `tests/`. — `observed`, `phpunit.xml`, `tests/`
- `composer lint` runs PHP-CS-Fixer in dry-run mode and `tools/lint-php.php`; `composer test` runs PHPUnit. `composer quality` adds coverage, coverage threshold, PHPStan and OpenAPI checks. — `observed` (configured, not run during init), `composer.json`, `tools/check-coverage.php`
- Each Code execution runs focused checks for the changed behavior and layer. Broader suites, aggregate quality gates, and browser/end-to-end checks are not automatic in Code or Review; run them only when the changed behavior and concrete risk justify them, once at the appropriate stage (normally Review). Reuse valid evidence and avoid duplicate checks. — `decided`, Paulo Bezerra, 2026-10-18

## Prohibited patterns

- Do not introduce a Node.js/build pipeline for frontend assets; preserve the native ES-module delivery model. — `observed`, `README.md`, `compose.yaml`
- Do not place SQL in HTTP controllers or let Domain/Application depend on PDO, HTTP, Slim, or Infrastructure. — `observed`, `tests/Architecture/DependencyRulesTest.php`, `src/Infrastructure/Database/Repository/`, `src/Infrastructure/Http/Controller/`
- Do not place secrets in the repository or expose MySQL publicly. — `observed`, `README.md`, `compose.yaml`

## Open questions

- No additional implementation conventions could be confirmed beyond the evidence above.
