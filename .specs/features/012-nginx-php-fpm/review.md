# REVIEW — Secure local PHP runtime with Nginx and PHP-FPM

**Source:** [feature.md](feature.md) · [spec.md](spec.md) · [plan.md](plan.md) · **Date:** 2026-10-25 · **Status:** complete

## Baseline and limits

The reviewed branch is `feature/012-nginx-php-fpm`; `HEAD` is `4fcda934eb33a7db00228a773b1fa8d9e73a9b6b`, equal to `origin/main`. The feature implementation remains uncommitted in the working tree, so publication is not proven. The working tree also contains pre-existing unrelated modifications in `.specs/features/011-cobertura-testes/review.md` and generated `frontend/coverage/`; they were not included in this review's feature conclusions or changed by Review. This file is the only artifact changed by this Review.

Both T012 task entries claim `SUCCESS`. I compared their paths and criteria with the actual working-tree diff and checked current behavior. The recorded explicit scope decision in `spec.md` covers maintaining the referenced Docker production example despite the original Feature exclusion; the Apache shared-hosting deployment remains out of scope and its files are unchanged. The production template's target-environment/VPS behavior remains unverified by design, as the documentation states.

Checks run on 2026-10-25:

- `docker compose exec -T app composer quality` — passed: lint, 211 PHPUnit tests / 704 assertions, PHP line coverage 96.93%, branch coverage 91.03%, PHPStan, and the remaining configured quality checks. PHP-CS-Fixer printed a Git safe-directory warning for the container bind mount, but scanned 86 files and the command exited successfully.
- `npm --prefix frontend run test` — passed: 6 files, 32 tests.
- `docker compose config -q` and production `docker compose -f compose.prod.yaml config -q` with non-secret placeholder environment values — passed.
- Production `docker compose ... build app nginx` — passed. Running PHP from the newly built image reported `8.4.27`; the Dockerfile guard is visible in both PHP build stages.
- HTTP through the running local Nginx: `/` returned 200, a static locale asset returned 200, a representative API path returned 200, an unknown `/api/*` path returned JSON 404, and `/missing.php` returned 404.
- `git diff --check` — passed. The current Compose service state shows Nginx publishing port 8080 and PHP-FPM without a published host port.

The execution record additionally reports successful development image build, FPM configuration/process checks, a deliberately rejected PHP 8.4.26 build, read-only PDO counts and matching MySQL container/volume identity before and after, frontend production build, and production Nginx configuration validation. These are inherited Code-stage evidence, not rerun here. The current Compose files and Nginx/PHP configuration independently match the relevant structural criteria. No production VPS, shared-hosting deployment, production database, migration, or volume operation was used.

## Verified requirements

- **T012-01 / S012-01, S012-03–05:** `compose.yaml` assigns HTTP to Nginx on 8080 and leaves FPM internal; the Nginx config serves `public/`, passes only `/index.php` to `app:9000`, preserves query strings in the front-controller fallback, denies other PHP paths, and blocks dotfiles. The local HTTP checks passed. The PHP image is explicitly set to 8.4.27, its build guard rejects versions below the fixed threshold or outside PHP 8.4, and the image reports 8.4.27. MySQL and `mysql_data` remain configured; the execution record supplies the read-only persistence evidence and confirms the same volume identity.
- **T012-02 / S012-02–06:** Production Compose config resolves with only Nginx publishing HTTP and the existing MySQL named volume retained. Both production images built, and the PHP image reports 8.4.27. Nginx copies the public tree from the production PHP stage, so both images use the same build output. README and both Home locales describe the Docker file as an optional adopter template, state its target-environment validation limit, and distinguish it from active Apache shared-hosting releases. Apache configuration, installer, and release workflow are unchanged in the diff.
- **Application regression gates:** The complete backend aggregate quality gate and frontend test suite pass. No application behavior was changed.

## Findings

No findings.

## Conclusion

T012-01 and T012-02 meet their recorded acceptance criteria based on current configuration, local runtime checks, image build/version evidence, and the recorded MySQL preservation checks. Review is complete with no code corrections requested. Publication remains unverified because the feature changes are uncommitted. Compatibility with a real adopter VPS remains outside the evidence gathered and is clearly assigned to the adopter in project guidance.
