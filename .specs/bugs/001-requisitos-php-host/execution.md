# Code Execution

## B001-01 — Attempt 1

- **Kind:** Bug task
- **Linked IDs:** B001-01
- **Status:** SUCCESS
- **Changed paths:** `README.md`, `.specs/bugs/001-requisitos-php-host/execution.md` (pre-existing workspace changes remain untouched: `.specs/project-context.md`, other contents under `.specs/bugs/`, `frontend/coverage/`)
- **Checks:** Manual documentation regression check passed: PHP `^8.4`, `PDO`, and `pdo_mysql` are stated before `create-project`; host runtime `mbstring` is stated and still appears in Requirements. Compared PHP and extension prerequisites with `composer.json`; package requires `^8.4`, `ext-pdo`, and `ext-pdo_mysql`, while `mbstring` remains an application runtime requirement. README links, command examples, and the existing Requirements section were inspected for consistency. `git diff --check -- README.md .specs/bugs/001-requisitos-php-host/execution.md` passed.
- **Environment limits:** none identified.
- **Remaining work / next step:** none for B001-01; ready for a separate SDD Lite Review invocation.

## B001-01 — Attempt 2

- **Kind:** User-clarified documentation task
- **Linked IDs:** B001-01
- **Status:** SUCCESS
- **User clarification:** Paulo Bezerra clarified on 2026-10-25 that the requested documentation belongs in both the Vue site home and the repository README. This expands the original README-only bug scope to include the homepage installation section; acceptance is to state the host PHP `^8.4`, `PDO`, `pdo_mysql`, and host runtime `mbstring` requirements before the installation command in both places and both available locales.
- **Changed paths:** `public/assets/i18n/en.json`, `public/assets/i18n/pt-BR.json`
- **Checks:** Both locale JSON files parsed successfully and each home installation paragraph contains PHP `^8.4`, `PDO`, `pdo_mysql`, and `mbstring`. Inspected `Home.vue` to confirm the paragraph renders before the Composer command block. `git diff --check` passed for the README, locale files, and execution record. An initial ad hoc assertion had its ordering condition inverted and failed; corrected validation passed.
- **Environment limits:** none identified.
- **Remaining work / next step:** none for B001-01; both the Vue homepage and README now state the prerequisites. Ready for a separate SDD Lite Review invocation.

## B001-01 — Attempt 3

- **Kind:** User-clarified documentation task
- **Linked IDs:** B001-01
- **Status:** SUCCESS
- **User clarification:** Paulo Bezerra stated on 2026-10-25 that both the website and README must show the concrete command `sudo apt install php8.4-mysql php8.4-mbstring` so future installers know how to install the required extensions.
- **Changed paths:** `README.md`, `public/assets/i18n/en.json`, `public/assets/i18n/pt-BR.json`
- **Checks:** Confirmed the exact `sudo apt install php8.4-mysql php8.4-mbstring` command appears before `composer create-project` in the README and before the Composer command in both localized homepage command blocks. Both translation files parse as valid JSON; the Debian/Ubuntu context appears in each localized install paragraph, which renders before the command block in `Home.vue`. `git diff --check` passed.
- **Environment limits:** none identified.
- **Remaining work / next step:** none for B001-01; both install guides now include the package command. Ready for a separate SDD Lite Review invocation.

## R-01 — Attempt 1

- **Kind:** Review correction
- **Linked IDs:** R-01, B001-01
- **Status:** PENDING
- **Changed paths:** `.specs/bugs/001-requisitos-php-host/execution.md` (existing documentation changes remain untouched)
- **Checks:** Host `composer test` could not start because `dom` and `xmlwriter` are missing. In the project's PHP 8.4.27 app image, `docker compose run --rm --no-deps --entrypoint composer app test` passed (211 tests, 704 assertions). `docker compose run --rm --no-deps --entrypoint composer app quality` completed lint and syntax checks, then exited 1 because PHPUnit reported no code coverage driver with path coverage support; the aggregate gate therefore failed. The direct host `composer quality` likewise stopped at PHPUnit for missing extensions. `docker compose ps -a` confirmed no running or leftover one-off containers after checks; `git diff --check -- .specs/bugs/001-requisitos-php-host/execution.md` passed.
- **Environment limits:** The available app image lacks the path-coverage-capable driver required by the configured quality gate. No database service or volumes were started or changed.
- **Remaining work / next step:** R-01 remains open until `composer quality` passes in an environment with the required coverage driver; then Review can re-audit the correction.
