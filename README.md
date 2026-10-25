# PHP + Slim + Vue Skeleton

A reusable base for PHP 8.4 applications with Slim 4, MySQL/PDO, Phinx, and Vue 3. PHP serves the application shell and API; Vite serves the frontend during development and builds production assets into `public/assets/build/`.

## Create a project

Use [Composer 2](https://getcomposer.org/doc/03-cli.md#create-project) to create a project from Packagist in a new or empty destination directory. The package currently has a `dev-main` version, so include `--stability=dev`; `--remove-vcs` removes the skeleton's source Git metadata:

```sh
composer create-project --stability=dev paulobezerra/sandbox-php my-project --remove-vcs
cd my-project
git init
cp .env.example .env
# Replace all example passwords before starting.
docker compose up -d --build
docker compose exec -T app composer migrate
```

The command creates a missing destination and Composer refuses a destination that already contains files. After `git init`, the project has a new history with no upstream remote.

Open `http://localhost:8080`. The migration is explicit and does not run at startup. The Home page is at `/`, the blog at `/blog`, login at `/login`, registration at `/register`, the personal Posts area at `/my-posts`, the API at `/api`, and the OpenAPI documentation at `/docs` in the local environment. The development MySQL service publishes port `127.0.0.1:3306` on the loopback interface.

Create an account using the registration link on the login page. Each authenticated account can create, edit, publish, and remove only its own Posts. The demo has no password recovery or authentication attempt limit; define a recovery procedure and abuse controls before using important accounts or exposing it publicly.

## Requirements

- Docker Engine with Docker Compose v2 (recommended), or PHP 8.4, Composer 2, the `pdo_mysql` and `mbstring` extensions, MySQL 8.4, and Apache with `mod_rewrite`.
- Node.js 22 and npm for frontend development, tests, and production builds.

## Frontend development and production build

Run the PHP app as well as Vite during development. Nginx serves the page and forwards application requests to PHP-FPM; frontend API requests use that same origin.

```sh
npm --prefix frontend ci
npm --prefix frontend run dev
```

Build before building or deploying the production images. The Vite build writes compiled JavaScript, CSS, and its manifest under `public/assets/build/`; the PHP-FPM image contains the application and the Nginx image serves the matching `public/` tree, so runtime images do not need Node.js.

```sh
npm --prefix frontend run build
docker compose --env-file .env -f compose.prod.yaml up -d --build
```

Run the frontend behavior suite without starting a browser or PHP application:

```sh
npm --prefix frontend run test
```

Vitest checks selected authentication and API-client behavior with mocked dependencies. The suite does not make network requests and is limited to 20 seconds in CI.

## Checks

```sh
docker compose exec -T app composer format
docker compose exec -T app composer lint
composer test
docker compose exec -T app composer quality
docker compose exec -T app composer migrate:status
npm --prefix frontend run test
npm --prefix frontend run build
```

`composer format` applies PSR-12 to project PHP files; `composer lint` checks style and syntax without writing files. `composer test` runs the service-free PHPUnit suite; run it directly where PHP and Composer dependencies are installed. `composer quality` also runs lint, tests with coverage, the minimum coverage check, PHPStan, and the OpenAPI check. The frontend Vitest suite and production build run directly with the npm commands above. CI runs each test suite separately with a 20-second limit. `composer migrate:status` lists migration status. `composer openapi:generate` regenerates `docs/openapi.json` from PHP attributes.

## Optional Docker deployment for adopters

`compose.prod.yaml` is a reusable example for adopters who choose to host the skeleton on a VPS or other infrastructure they control. It runs Nginx in front of a private PHP-FPM service, using PHP 8.4.27 or a later 8.4 patch, and keeps MySQL on the internal Compose network. Only Nginx publishes `${APP_PORT:-8080}`. The optimized PHP image has no Xdebug, bind mounts, or enabled OpenAPI documentation. This example has not been fully validated in a real target environment; adopters must validate it for their own proxy/TLS, secrets, firewall, persistence, and backup setup before relying on it. It does not describe this repository's active production deployment, which remains Apache shared hosting through the release workflow below.

Use a production `.env` outside version control with unique credentials and `APP_URL` set to the public HTTPS domain. The application sets the `Secure` cookie flag based on this URL. Compose serves HTTP; terminate TLS at a reverse proxy or load balancer. Set `APP_PORT` if the published Nginx port should differ from 8080.

```sh
cp .env.example .env
# Edit .env: strong secrets, APP_ENV=production, APP_DEBUG=false,
# OPENAPI_ENABLED=false, and APP_URL=https://www.example.com.
docker compose --env-file .env -f compose.prod.yaml up -d --build
docker compose --env-file .env -f compose.prod.yaml exec -T app composer migrate --no-interaction
```

Back up the database before upgrading the application and apply migrations as a controlled deployment step. Do not expose the MySQL port to the internet. Keep `.env` and backups outside the public directory. For shared hosting, publish only `public/` as the web root and keep `src/`, `vendor/`, and `.env` outside it.

## Shared-hosting configuration

PHP reads the ignored project-root `.env` as a fallback, so shared hosting does not need provider-specific environment-variable support. Start from the existing example and set unique production credentials:

```sh
cp .env.example .env
chmod 600 .env
```

Set `APP_ENV=production`, `APP_DEBUG=false`, `OPENAPI_ENABLED=false`, a public `APP_URL=https://www.example.com`, and the database settings `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. Use a database user created by the hosting provider; `DB_ROOT_PASSWORD` is only for the Docker MySQL service and is not read by PHP. Keep the `.env` file in the application root with private permissions, outside `public/`, and configure the site's document root to point to `public/`.

The application and Phinx read the same settings through `Config::fromEnvironment()`. PHP process variables take precedence one key at a time; `.env` supplies keys that are absent from the process. An explicitly empty process value also takes precedence and is checked by the normal configuration validation. For values containing `#`, `$`, or `=`, use quotes; single quotes preserve a literal dollar sign consistently with Docker Compose, for example:

```dotenv
DB_PASSWORD='a#long$secret=value'
```

Comments may follow a value after whitespace. Compose continues to pass its configured process variables to PHP, so those values override a bind-mounted `.env`; the production image does not need `.env` copied into it. PHP reads only application settings. Compose-only values such as `DB_ROOT_PASSWORD` and `APP_PORT` are ignored by the PHP configuration loader.

If startup reports `DB_DATABASE is required` or `DB_USERNAME is required`, check those keys in `.env` and confirm that the file is readable by PHP. A malformed or unreadable file reports its filename without printing its contents. Other validation errors identify the invalid setting; do not paste credentials into support logs.

### Shared-hosting deployment

The installer receives a published release package and installs its source and production frontend build into the existing checkout. Back up the database first and confirm that the existing database and its database-scoped user are ready. The user configured by `DB_USERNAME` needs DDL and DML privileges on that database, including permission to create/alter tables, indexes, foreign keys, and Phinx's `phinxlog` table. MySQL root and `DB_ROOT_PASSWORD` are not used by the installer.

On Apache shared hosting, keep the provider's existing directives in `public/.htaccess` and add the following rewrite block so page routes, `/api/*`, and `/docs` reach `public/index.php`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

The local `public/.htaccess` remains ignored because cPanel may write account-specific PHP handler directives into it. Configure the domain's PHP version through the hosting control panel and do not delete those generated directives when adding the rewrite block. In production, `/docs` is available only when `OPENAPI_ENABLED=true`; keep it disabled unless the API reference is intended to be public.

Optional `PHP_BIN` selects an absolute PHP executable path and `COMPOSER_BIN` selects an absolute path to a Composer PHAR. The installer checks process environment values first, then literal assignments in the target's `.env`, then defaults to PHP compatible with `^8.4` and the Composer PHAR in the release. For example, set `PHP_BIN=/opt/cpanel/ea-php84/root/usr/bin/php` in `.env` when the host's default PHP is older. These values are paths only; do not use shell syntax, interpolation, or relative paths. The installer never sources `.env` as a shell script.

For a manual redeploy, select a published tag in **Actions → Quality and release → Run workflow**. New releases use `vMAJOR.MINOR.PATCH` tags; older `release-*` tags remain valid for redeployment. The workflow downloads and verifies the release package, then invokes the installer included with that tag. For a host-side installation from an already transferred package, run:

```sh
cd ~/sites/sandbox-php
./tools/deploy-shared-hosting.sh --release v1.4.2 --package /path/to/sandbox-php-v1.4.2.tar.gz --target "$PWD"
```

The installer validates the selected tag, commit metadata, Vite manifest, and referenced assets before changing application files. It preserves `.env`, `vendor/`, and the host's `public/.htaccess`, transfers release files without importing temporary-directory permissions, and sets the application directory to `711` so the web server can traverse it without listing its contents. It keeps `public/` readable by Apache, installs production Composer dependencies with an optimized autoloader, then applies only pending migrations in the `production` Phinx environment. A zero exit status means these steps completed. A failure after file transfer can leave a partial code update; resolve the reported cause and rerun the same release, keeping the database backup. The installer never rolls back migrations. Older code may not support the current schema, so check compatibility before selecting an earlier release.

The repository pins Composer 2.10.3 as `composer.phar`; its SHA-256 is `7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6`, published on the [official Composer download page](https://getcomposer.org/download/). Update it only through a reviewed repository change: obtain a pinned PHAR from the official Composer site, verify the official digest or signature, then commit that verified file. `.dockerignore` keeps this host tool out of Docker builds; Docker continues to install Composer through its existing image setup.

## Pull request checks and production releases

The `Quality and release` workflow runs frontend and PHP tests as separate service-free jobs, builds the production Vite assets, and validates the release label for pull requests to `main`. Create the `patch`, `minor`, and `major` labels once in the repository's **Settings → Labels**, then add exactly one to each PR in the GitHub PR sidebar: use `patch` for compatible fixes, `minor` for backward-compatible features, and `major` for breaking changes. After a passing merge, the workflow finds that PR, calculates the next `vMAJOR.MINOR.PATCH` from the latest SemVer tag, publishes the matching source and `public/assets/build/` as a GitHub release package, then deploys it with the installer from that package. If no SemVer tag exists yet, `v0.0.0` is used as the calculation baseline; the first published version is determined by the PR label. Existing `release-*` tags remain available for manual redeployment. The host runs Composer install and forward-only Phinx migrations; it does not run Node.js.

Configure Actions secrets `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`, and `DEPLOY_KNOWN_HOSTS`. `DEPLOY_KNOWN_HOSTS` must contain the server's verified OpenSSH `known_hosts` entry; for a nonstandard SSH port, its host field must be `[DEPLOY_HOST]:DEPLOY_PORT`. Verify the host-key fingerprint with the hosting provider before saving the entry. The workflow checks that the entry matches the configured host and port and keeps strict host-key checking enabled. The host needs `rsync`, `tar`, PHP 8.4, Composer 2, and database access for Phinx. The target `~/sites/sandbox-php` must already exist and contain the production `.env`; the account needs write access to it. Repository administrators should require `Frontend tests`, `PHP tests`, `Production build`, and `Release version label` in the `main` branch rules, and protect both `v*` and the legacy `release-*` tag patterns from changes. These repository rules cannot be configured by workflow YAML. Tag redeploys never reverse migrations; check schema compatibility before selecting an earlier release.

## What this skeleton includes

The default application provides registration and login with server-side PHP sessions, a public Posts blog, and authenticated Post creation, editing, publishing, and deletion scoped to each Post's owner. It also includes a JSON API, an OpenAPI contract, CSRF checks for authenticated mutations, a Vue 3 frontend built with Vite, and English and Brazilian Portuguese catalogs. Phinx migrations are run explicitly. The demo has no user profiles, password recovery, or login attempt limiting; derived projects should decide how to provide profiles, account recovery, and abuse controls before public use.

The frontend uses [Vue](https://vuejs.org/), [Vue Router](https://router.vuejs.org/), [Vite](https://vite.dev/), [Tailwind CSS](https://tailwindcss.com/), [shadcn-vue](https://www.shadcn-vue.com/), and [Lucide](https://lucide.dev/). The main application dependencies are [PHP](https://www.php.net/docs.php), [Slim](https://www.slimframework.com/), [Slim PSR-7](https://github.com/slimphp/Slim-Psr7), [PHP-DI](https://php-di.org/), [Slim Routing](https://github.com/juliangut/slim-routing), [Phinx](https://phinx.org/), [MySQL](https://dev.mysql.com/doc/), [CommonMark](https://commonmark.thephpleague.com/), and [PHP dotenv](https://github.com/phpdotenv/phpdotenv). Local development and deployment use [Docker Compose](https://docs.docker.com/compose/) and [Apache](https://httpd.apache.org/docs/). Development checks use [PHPUnit](https://docs.phpunit.de/), [PHP-CS-Fixer](https://cs.symfony.com/), [PHPStan](https://phpstan.org/user-guide/getting-started), [swagger-php](https://zircote.com/swagger-php/), and [Swagger UI](https://swagger.io/open-source/swagger-ui/).

## Structure and extension

```text
src/Domain/             Entities, invariants, and repository contracts
src/Application/        Application operations
src/Infrastructure/     HTTP, PDO, session, and view adapters
src/Bootstrap/          Dependency composition and routes
database/migrations/    Initial schema and explicit migrations
frontend/               Vue SPA and API source, Vite configuration, and npm scripts
public/assets/build/    Compiled frontend assets and Vite manifest served by PHP/Apache
tests/                  Unit, integration, and architecture tests
docs/                   OpenAPI and skeleton guides
```

Dependencies flow from `Infrastructure → Application → Domain`; Bootstrap composes the adapters. Read the [customization and extension guide](docs/customization.md) before adapting User/Auth or adding a domain. The default application contains Auth and Posts.

## Languages

The application negotiates English (`en`) and Brazilian Portuguese (`pt-BR`) from the browser's `Accept-Language` header. Set `SUPPORTED_LOCALES` to a comma-separated list of canonical locale tags and `FALLBACK_LOCALE` to one of them. Each supported locale needs a complete JSON catalog under `public/assets/i18n/`; the catalogs must contain matching keys. Add a language by copying `public/assets/i18n/en.json`, translating every value, and setting both configuration variables. Startup rejects duplicate, malformed, unsupported, missing, or incomplete locale configuration. Localized HTML and JSON responses set `Content-Language` and vary shared caches by `Accept-Language`.

Vitest checks the API client's locale and CSRF headers with mocked requests. Run the frontend suite with `npm --prefix frontend run test`; no browser or running PHP application is required.

## Security and maintenance

- Passwords are stored with `password_hash` and checked with `password_verify`. Sessions are server-side, regenerated at login, and use HttpOnly cookies; authenticated mutations require CSRF, and the backend checks ownership of every Post.
- Registration is open. This demo has no password recovery or authentication attempt limit; before exposing it publicly, define abuse controls and a recovery procedure suited to the project.
- Posts store Markdown and escape raw HTML during rendering. Views use `v-html` only with HTML produced by `MarkdownRenderer`, which rejects raw HTML and unsafe links; preview and publishing share this renderer. Vue templates are compiled by Vite; do not provide Vue templates or user-renderable HTML.
- To change the schema of an existing installation, create a new migration; do not edit a migration that has already been applied.
- Never put real credentials, tokens, or database dumps in the repository.

## Additional documentation

- [Customize User/Auth and add domains](docs/customization.md)
- [OpenAPI contract](docs/openapi.json)
- [AGENTS.md](AGENTS.md): project engineering and operations guidance
- [SDD Lite](https://github.com/paulobezerra/sdd-lite) is an optional, separately maintained workflow. Follow that repository's current installation instructions if you want to use it; it is not required to install or run this application.
