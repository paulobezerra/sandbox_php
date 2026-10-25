# REVIEW — Document PHP host requirements before project creation

**Source:** bug: [bug.md](bug.md) · **Date:** 2026-10-25 · **Status:** corrections pending

## Baseline and limits

- Audited branch: `fix/001-requisitos-php-host`; `HEAD` is `03da246cc959d6ab3a4f0713fc792a431c6ffbb5`, the same commit as the locally tracked `origin/main`. The host read-only check `git ls-remote origin refs/heads/main` also returned that commit. The branch has no task commit or published change; the implementation is present as uncommitted workspace changes.
- `git status --short` showed modified `.specs/project-context.md`, `README.md`, and both locale JSON files, plus untracked `.specs/bugs/` and `frontend/coverage/`. The project-context edit and coverage output are outside this bug. The README and locale diffs match the scope described in the execution entries. No staged changes were present.
- The original B001-01 contract names the README. Attempts 2 and 3 in `execution.md` record Paulo Bezerra's later clarifications (dated 2026-10-25) expanding the documentation to the Vue home in both locales and requesting the concrete Debian/Ubuntu package command. The delivered home changes are therefore assessed against those recorded scope decisions as well as the original bug contract.
- The `execution.md` claims for earlier manual checks are historical claims, not checks rerun for this review. Current checks and their outcomes are listed below.

## Verified requirements

- **B001-01 — Host PHP prerequisites before Composer.** The README states PHP `^8.4`, PDO, `pdo_mysql`, and host-runtime `mbstring` before `composer create-project`. It includes `sudo apt install php8.4-mysql php8.4-mbstring` under an explicit Debian/Ubuntu heading sentence before the create command. `composer.json` requires `^8.4`, `ext-pdo`, and `ext-pdo_mysql`; `mbstring` remains identified as a host runtime requirement, matching the bug contract.
- **Clarified home-page scope.** Both `en.json` and `pt-BR.json` place PHP `^8.4`, PDO, `pdo_mysql`, and host `mbstring` in the installation paragraph and begin their displayed command block with the apt command, followed by `composer create-project`. `Home.vue` renders the paragraph and then the command block, so the prerequisites and package command precede the Composer command in the page.
- **Current manual/documentation checks:** a Node assertion parsed both locale files and verified the stated prerequisites, command ordering, and Composer extension requirements; it passed. `git diff --check -- README.md public/assets/i18n/en.json public/assets/i18n/pt-BR.json` passed. `npm --prefix frontend run test` passed (6 files, 32 tests). `npm --prefix frontend run build` passed.
- **PHP test and quality gates:** `composer test` on the host could not start because host PHP lacks `dom` and `xmlwriter`. A Docker status check initially showed `app` running, but it stopped before the sequential retry; `docker compose exec -T app composer test` then reported `service "app" is not running`. The host `composer quality` also could not complete: PHP-CS-Fixer failed to listen on its local TCP socket (`Failed to listen on "tcp://127.0.0.1:0": Success`). These are environment limitations; they do not demonstrate a product defect. The first-review PHP suite and aggregate quality gate remain unverified.

## Findings

- **R-01 — PHP review gates remain unverified.** **Status:** open. **Evidence:** the host PHPUnit command lacked `dom` and `xmlwriter`; the app container was no longer running when the documented container retry was made; host `composer quality` stopped in PHP-CS-Fixer while opening a local TCP socket. **Impact:** the first-review validation gate required by the project workflow has not been completed, so the overall review cannot be marked complete even though the documentation criteria and frontend checks pass. **Correction:** rerun `composer test` and `composer quality` in the project's supported PHP/container environment when the app service is available. **Acceptance and test:** both commands complete successfully; record their actual results and confirm no regression to the reviewed documentation diff.

## Conclusion

The README and clarified bilingual home-page requirements are implemented and passed their direct documentation checks; the frontend suite and production build also pass. No documentation defect was found in the reviewed diff. Review remains **corrections pending** because the PHP suite and aggregate quality gate required for a first Review could not run successfully. No implementation or other SDD artifact was changed during this Review. Publication is not proven: the remote branch points to the base commit and the task changes are uncommitted.
