# PHP + Slim + Vue Skeleton

A reusable base for PHP 8.4 applications with Slim 4, MySQL/PDO, Phinx, and Vue 3. The backend and frontend are served by the same PHP/Apache deployment. JavaScript uses native ES modules and local files: there is no Node.js or build step.

## Repository

This project is published as [`paulobezerra/sandbox_php`](https://github.com/paulobezerra/sandbox_php).

## Requirements

- Docker Engine with Docker Compose v2 (recommended), or PHP 8.4, Composer 2, the `pdo_mysql` and `mbstring` extensions, MySQL 8.4, and Apache with `mod_rewrite`.
- Chrome or Chromium on the host only for `tools/test-all.sh`.

## Local setup

```sh
git clone https://github.com/paulobezerra/sandbox_php.git my-project
cd my-project
cp .env.example .env
# Replace all example passwords before starting.
docker compose up -d --build
docker compose exec -T app composer migrate
```

Open `http://localhost:8080`. The migration is explicit and does not run at startup. The documentation Home is at `/`, the blog at `/blog`, registration and login at `/login`, the personal Posts area at `/my-posts`, the API at `/api`, and the OpenAPI documentation at `/docs` in the local environment. The development MySQL service publishes port `127.0.0.1:3306`.

Create an account using the registration link on the login page. Each authenticated account can create, edit, publish, and remove only its own Posts. The demo has no password recovery; define an operational recovery procedure before using important accounts.

## Checks

```sh
docker compose exec -T app composer format
docker compose exec -T app composer lint
docker compose exec -T app composer test
docker compose exec -T app composer quality
docker compose exec -T app composer migrate:status
./tools/test-all.sh
```

`composer format` applies PSR-12 to project PHP files; `composer lint` checks style and syntax without writing files. `composer test` runs PHPUnit in the container, and `composer quality` also runs lint, tests with coverage, the minimum coverage check, PHPStan, and the OpenAPI check. `./tools/test-all.sh` also opens the main routes with headless Chrome/Chromium on the host. To run only the browser checks, use `./tools/test-frontend.sh`; set `CHROME_BIN` if the executable is not on `PATH`. `composer migrate:status` lists migration status. `composer openapi:generate` regenerates `docs/openapi.json` from PHP attributes.

## Docker production

`compose.prod.yaml` uses an optimized image without Xdebug, bind mounts, or enabled OpenAPI documentation. Use a production `.env` outside version control, with unique credentials and `APP_URL` set to the public HTTPS domain. The application sets the `Secure` cookie flag based on this URL. Compose serves HTTP; terminate TLS at a reverse proxy or load balancer.

```sh
cp .env.example .env
# Edit .env: strong secrets, APP_ENV=production, APP_DEBUG=false,
# OPENAPI_ENABLED=false, and APP_URL=https://www.example.com.
docker compose --env-file .env -f compose.prod.yaml up -d --build
docker compose --env-file .env -f compose.prod.yaml exec -T app composer migrate --no-interaction
```

Back up the database before upgrading the application and apply migrations as a controlled deployment step. Do not expose the MySQL port to the internet. Keep `.env` and backups outside the public directory; Apache uses `public/` as its DocumentRoot. The image contains the application code and production dependencies. For shared hosting, publish only `public/` as the web root and keep `src/`, `vendor/`, and `.env` outside it.

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

The installer expects the repository checkout to be on a clean local `main` branch that tracks a remote `main`, with Git authentication already configured for non-interactive use. It resolves the checkout from its own location, so it can be run from any working directory. Back up the database first and confirm that the existing database and its database-scoped user are ready. The user configured by `DB_USERNAME` needs DDL and DML privileges on that database, including permission to create/alter tables, indexes, foreign keys, and Phinx's `phinxlog` table. MySQL root and `DB_ROOT_PASSWORD` are not used by the installer.

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

Optional `PHP_BIN` and `GIT_BIN` settings select absolute executable paths; `COMPOSER_BIN` selects an absolute path to a Composer file. The installer checks process environment values first, then literal assignments in the project-root `.env`, then defaults: a PHP version compatible with the project's `^8.4` requirement and Git from `PATH`, plus the Composer PHAR tracked in this repository. For example, set `PHP_BIN=/opt/cpanel/ea-php84/root/usr/bin/php` in `.env` when the host's default PHP is older. These values are paths only; do not use shell syntax, interpolation, or relative paths. The installer never sources `.env` as a shell script.

After the backup and access checks, run:

```sh
cd /path/to/application
./tools/deploy-shared-hosting.sh
```

The command does not prompt. It verifies the branch and working tree, fast-forward pulls remote `main`, installs production dependencies with an optimized autoloader, then applies only pending migrations in the `production` Phinx environment using the same PHP and database settings. A zero exit status means all three deployment steps completed. Repeating it when there are no new commits or migrations is safe. A nonzero status names the failed step; fix the reported Git, PHP/Composer, or database privilege problem and run the command again. The installer does not switch branches or roll back a partial update. If the pull succeeded but dependency installation or migration failed, the checkout may already contain the new code: keep the backup, correct the cause, and rerun. Restore code or database from backup only through the operator's normal recovery process.

The repository pins Composer 2.10.3 as `composer.phar`; its SHA-256 is `7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6`, published on the [official Composer download page](https://getcomposer.org/download/). Update it only through a reviewed repository change: obtain a pinned PHAR from the official Composer site, verify the official digest or signature, then commit that verified file. `.dockerignore` keeps this host tool out of Docker builds; Docker continues to install Composer through its existing image setup.

## Structure and extension

```text
src/Domain/             Entities, invariants, and repository contracts
src/Application/        Application operations
src/Infrastructure/     HTTP, PDO, session, and view adapters
src/Bootstrap/          Dependency composition and routes
database/migrations/    Initial schema and explicit migrations
public/assets/          Vue, Vue Router, CSS, and local libraries
tests/                  Unit, integration, and architecture tests
docs/                   OpenAPI and skeleton guides
```

Dependencies flow from `Infrastructure → Application → Domain`; Bootstrap composes the adapters. Read the [customization and extension guide](docs/customization.md) before adapting User/Auth or adding a domain. The default application contains Auth and Posts.

## Languages

The application negotiates English (`en`) and Brazilian Portuguese (`pt-BR`) from the browser's `Accept-Language` header. Set `SUPPORTED_LOCALES` to a comma-separated list of canonical locale tags and `FALLBACK_LOCALE` to one of them. Each supported locale needs a complete JSON catalog under `public/assets/i18n/`; the catalogs must contain matching keys. Add a language by copying `public/assets/i18n/en.json`, translating every value, and setting both configuration variables. Startup rejects duplicate, malformed, unsupported, missing, or incomplete locale configuration. Localized HTML and JSON responses set `Content-Language` and vary shared caches by `Accept-Language`.

The browser checks exercise both supported locales. With the app running and Chrome or Chromium installed on the host, run `./tools/test-all.sh` for the browser and PHP checks.

## Security and maintenance

- Passwords are stored with `password_hash` and checked with `password_verify`. Sessions are server-side, regenerated at login, and use HttpOnly cookies; authenticated mutations require CSRF, and the backend checks ownership of every Post.
- Registration is open. This demo has no password recovery or authentication attempt limit; before exposing it publicly, define abuse controls and a recovery procedure suited to the project.
- Posts store Markdown and escape raw HTML during rendering. Views use `v-html` only with HTML produced by `MarkdownRenderer`, which rejects raw HTML and unsafe links; preview and publishing share this renderer. Vue templates use ES modules without a build step and are compiled in the browser, so the CSP retains `unsafe-eval`; do not provide Vue templates or user-renderable HTML.
- To change the schema of an existing installation, create a new migration; do not edit a migration that has already been applied.
- Never put real credentials, tokens, or database dumps in the repository.

## Additional documentation

- [Customize User/Auth and add domains](docs/customization.md)
- [OpenAPI contract](docs/openapi.json)
- [AGENTS.md](AGENTS.md): agent workflow conventions for this repository
- [SDD Lite](.specs/README.md): specifications and reviews index
- [Skeleton specification](.specs/features/001-skeleton/spec.md): requirements and phase history
