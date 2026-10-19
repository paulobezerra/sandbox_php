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
- Chrome or Chromium on the host for the frontend browser tests and `tools/test-all.sh`.
- Node.js and npm for frontend development, builds, and browser tests.

## Frontend development and production build

Run the PHP app as well as Vite during development. PHP continues to serve the page and API, and frontend API requests use that same PHP origin.

```sh
npm --prefix frontend ci
npm --prefix frontend run dev
```

Build before building or deploying the production PHP image. The Vite build writes compiled JavaScript, CSS, and its manifest under `public/assets/build/`; PHP/Apache serves those files in production, so the production container does not need Node.js.

```sh
npm --prefix frontend run build
docker compose --env-file .env -f compose.prod.yaml up -d --build
```

With the PHP app running and Playwright's Chromium available, run the frontend browser suite with:

```sh
npm --prefix frontend run test
```

The suite covers the PHP-served app and its same-origin API. `./tools/test-all.sh` runs this frontend suite before PHPUnit.

## Checks

```sh
docker compose exec -T app composer format
docker compose exec -T app composer lint
docker compose exec -T app composer test
docker compose exec -T app composer quality
docker compose exec -T app composer migrate:status
./tools/test-all.sh
```

`composer format` applies PSR-12 to project PHP files; `composer lint` checks style and syntax without writing files. `composer test` runs PHPUnit in the container, and `composer quality` also runs lint, tests with coverage, the minimum coverage check, PHPStan, and the OpenAPI check. `./tools/test-all.sh` runs the frontend browser suite with headless Chrome/Chromium on the host, then PHPUnit in the app container. `composer migrate:status` lists migration status. `composer openapi:generate` regenerates `docs/openapi.json` from PHP attributes.

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

The browser checks exercise both supported locales. With the app running and Chrome or Chromium installed on the host, run `./tools/test-all.sh` for the browser and PHP checks.

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
