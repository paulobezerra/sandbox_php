# Execution — Secure local PHP runtime with Nginx and PHP-FPM

## T012-01 — local development stack

- **Kind:** Feature task
- **Links:** S012-01, S012-03, S012-04, S012-05; T012-01
- **Status:** SUCCESS
- **Changed paths:** `Dockerfile`, `compose.yaml`, `docker/nginx/default.conf`, `.specs/features/012-nginx-php-fpm/execution.md`
- **Implementation:** Added a development PHP-FPM image stage pinned by default to PHP 8.4.27, a build-time PHP-version guard, and Nginx routing for `public/`. Compose publishes only Nginx on 8080, keeps FPM internal, and preserves the repository bind mount, Composer install/assets startup, Xdebug, MySQL service, and `mysql_data` volume. The existing Apache production image stage remains available for T012-02.
- **Checks and evidence:**
  - Baseline topology assertions failed as expected for the missing Nginx/FPM/guard pieces; the existing MySQL volume assertion passed.
  - Official PHP release announcement confirms PHP 8.4.27 fixes CVE-2026-109618 and CVE-2026-109619: https://news-web.php.net/php.announce/512.
  - `docker compose config` resolved successfully; Nginx owns `8080:80`, app has no published port, and MySQL still mounts `mysql_data`.
  - Focused topology assertions passed for services, routing, PHP guard, private FPM, MySQL identity, Composer startup, Xdebug, and bind mount.
  - `docker compose build app` succeeded with `php:8.4.27-fpm`; running image reports PHP `8.4.27`. `php-fpm -t` succeeded and process inspection showed the FPM master and workers.
  - Deliberate `docker build --target development --build-arg PHP_VERSION=8.4.26 ...` failed at the version guard with the expected “PHP 8.4.27 or newer PHP 8.4 patch required” error.
  - HTTP checks through `http://localhost:8080`: `/` → 200; `/assets/i18n/en.json` → 200; `/api/auth/csrf?probe=front-controller` → 200; `/api/unknown-path` → JSON 404; `/missing.php` and `/index.php/extra` → 404.
  - Read-only PDO queries returned `users:2` and `posts:1`. No migrations or data-changing operations were run. The MySQL container ID and `sandbox_php_mysql_data` mount matched before and after; MySQL remained healthy.
  - `git diff --check` passed. The Apache shared-hosting configuration and release workflow are unchanged.
- **Environment limits:** No remaining blocker for this task. The Docker app and Nginx services are running as the requested local runtime. Pre-existing workspace changes in `.specs/features/011-cobertura-testes/review.md` and `frontend/coverage/` were left untouched.
- **Remaining work / next step:** T012-01 is complete. T012-02 remains dependent on this task and is outside this Code invocation.

## T012-02 — adopter Docker production example

- **Kind:** Feature task
- **Links:** S012-02, S012-03, S012-04, S012-05, S012-06; T012-02
- **Status:** SUCCESS
- **Changed paths:** `Dockerfile`, `compose.prod.yaml`, `docker/nginx/production.conf`, `README.md`, `public/assets/i18n/en.json`, `public/assets/i18n/pt-BR.json`, `.specs/features/012-nginx-php-fpm/execution.md`
- **Implementation:** Replaced the Docker production Apache image with PHP 8.4-FPM, keeping the build-time fixed-patch guard and production dependencies/settings. Added a production Nginx image containing the matching `public/` tree and built frontend assets; Compose publishes only Nginx and leaves FPM internal. Preserved MySQL and `mysql_data`. Updated README and both Home locales to describe this as an optional adopter/VPS example, disclose that no real target VPS validation was performed, and distinguish the active Apache shared-hosting release path.
- **Checks and evidence:**
  - `docker compose -f compose.prod.yaml config` with non-secret placeholder environment values succeeded; focused assertions confirmed PHP_VERSION `8.4.27`, only Nginx has a published port (8081 in the check), the Nginx production target, and the unchanged `mysql_data` volume.
  - `docker compose -f compose.prod.yaml build app nginx` succeeded. The built PHP image reported `8.4.27`; its exposed port is `9000/tcp`. The Nginx image exposes `80/tcp`, and `nginx -t` succeeded with a temporary local `app` hostname mapping.
  - An expected-failure build using `PHP_VERSION=8.4.26` stopped at the production guard with “PHP 8.4.27 or newer PHP 8.4 patch required; found 8.4.26”. The PHP release/advisory evidence for 8.4.27 and both CVEs is recorded in T012-01 above.
  - `npm --prefix frontend run build` succeeded. Both locale JSON files parsed successfully.
  - `git diff --check` passed. Targeted diff review confirmed `docker/apache/000-default.conf`, `tools/deploy-shared-hosting.sh`, and `.github/workflows/quality-and-release.yml` are unchanged.
- **Environment limits:** Production Compose was configured and both images built locally; no VPS, production host, database, or migrations were used. Existing local development containers were already running and were not started, stopped, or recreated by this attempt. Existing unrelated modifications in `.specs/features/011-cobertura-testes/review.md` and `frontend/coverage/` were left untouched. The optional production example remains unvalidated against any real adopter infrastructure by design.
- **Remaining work / next step:** T012-02 is complete. No remaining work in this task; T012 is ready for its separate Review stage.
