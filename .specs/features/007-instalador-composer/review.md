# REVIEW — Start a reusable project from Composer

**Source:** [feature.md](feature.md) · [spec.md](spec.md) · [plan.md](plan.md) · **Date:** 2026-10-19 · **Status:** corrections pending

## Baseline and limits

Audited branch: `feature/007-instalador-composer`. `HEAD` is `69a7d61` (`Merge pull request #5 from paulobezerra/feature/006-home-base-reutilizavel`), the same commit advertised by `origin/HEAD`. The feature branch has no published remote branch. The implementation and this review are uncommitted; no commit or push was made. The worktree also contains a modified root `AGENTS.md`, which is outside Feature 007's delivery scope.

The reviewed Home, README, Composer metadata, archive exclusions, locale catalogs, SVG assets, and focused Composer fixture match the recorded scope. Packagist registration/publication remains an explicit release prerequisite and was not part of this implementation. The local and Git source archive paths were tested through a fixture; a public Packagist install was not verified.

## Verified requirements

- **S007-01–04 / T007-01:** `composer.json` identifies `paulobezerra/sandbox-php`; Composer archive exclusions and `.gitattributes` omit `.env`, `.specs`, `vendor`, and generated Swagger UI. The focused package scenario passed for archive contents, installation into a fresh destination, refusal of a populated destination while preserving its sentinel, `--remove-vcs`, and `git init` with no inherited commits or remotes. The test used the local fixture, so public Packagist availability remains unverified.
- **S007-05–09 / T007-02:** README documents project creation, first use, explicit migrations, development and production commands, shared hosting, demo limitations, official technology references, and optional SDD Lite installation. The distributed README no longer links to skeleton-only `.specs` files. Source review confirmed the guidance corresponds to the repository commands and configuration.
- **S007-06–10 / T007-03:** [Home.js](/home/paulo/Projetos/sandbox_php/public/assets/js/pages/Home.js:21) presents the bilingual quick start, OpenAPI route and generation command, operational checks, deployment guidance, capability/security summary, and local technology marks with visible labels and alternative text. `test-all.sh` rendered and checked the Home in English and Brazilian Portuguese, confirmed the required content and references, and fetched all six local SVGs. The catalogs were exercised in both locales by that browser check.
- **Plan checklist:** T007-01, T007-02, and T007-03 are marked complete. The package mechanics and Home behavior passed their checks, but the documented public install command depends on Packagist registration, which is outside the implementation scope. Finding R-02 tracks the gap between that release prerequisite and an install flow usable before publication.

## Findings

- **R-01 — Composer package check is not directly executable.** **Status:** accepted, corrected 2026-10-19. **Initial evidence:** mode `664` caused `./tools/test-composer-package.sh` to fail with `Permission denied`; invoking through Bash succeeded. **Correction:** Paulo ran `chmod +x tools/test-composer-package.sh`. **Re-audit evidence:** `stat` now reports mode `775`, and `docker compose exec -T app ./tools/test-composer-package.sh` completed successfully, including archive exclusions, project creation, populated-destination preservation, VCS removal, and fresh Git initialization. **Acceptance:** passed.
- **R-02 — Documented installation requires Packagist publication.** **Status:** open. **Evidence:** the README and both Home locales show `composer create-project paulobezerra/sandbox-php ...` without a custom repository, so Composer searches Packagist for the skeleton package. The Spec explicitly leaves registering and publishing this package on Packagist outside the implementation scope. T007-01's fixture proves installation when a local repository is explicitly supplied; it does not prove the documented public command works without Packagist. **Impact:** a developer following the current documentation cannot create the project until someone performs the separate Packagist publication. The feature's stated Composer installation path is therefore not usable under the current release state. **Correction:** document the GitHub VCS repository directly with Composer's `--repository` option in the README and both Home locales, for example `composer create-project --repository='{"type":"vcs","url":"https://github.com/paulobezerra/sandbox_php.git"}' paulobezerra/sandbox-php my-project --remove-vcs`. This avoids registering the skeleton package itself with Packagist; Composer still resolves third-party dependencies normally. The referenced GitHub revision must contain the package metadata and files. **Acceptance and test:** follow the documented command against the GitHub VCS repository without a Packagist entry for `paulobezerra/sandbox-php`; verify package creation, dependency installation, `--remove-vcs`, and documented first-use steps. Check the Home in English and pt-BR and verify both show the same working command. If direct VCS installation is not adopted, Packagist publication remains required before the existing command can be called usable.

## Conclusion

The initial Review validated the aggregate quality gate, browser checks for both locales, Composer package fixture, metadata validation, and whitespace check. Re-audit accepted R-01 after its permission correction. R-02 remains open because the public install command shown to developers still requires Packagist publication, which the implementation excludes. The working tree is uncommitted and the feature branch is not published; a GitHub VCS installation has not yet been verified against a remote revision containing this feature.

### Checks run

- `docker compose exec -T app composer quality` — passed: lint, 90 PHPUnit tests (465 assertions), 87.24% executable-line coverage against an 80% threshold, PHPStan, and OpenAPI checks (2 tests, 41 assertions).
- `./tools/test-all.sh` — passed: browser checks for English and pt-BR, local technology assets, and the included PHPUnit suite (90 tests, 465 assertions).
- `docker compose exec -T app bash tools/test-composer-package.sh` — passed: archive exclusions, create-project installation, populated destination preservation, VCS removal, and fresh Git initialization.
- `docker compose exec -T app composer validate --no-check-publish` — valid; Composer emitted only the existing recommendation to declare a license.
- `git diff --check` — passed.
- `./tools/test-composer-package.sh` — not runnable directly because the file lacks its executable bit; it passed when invoked with Bash in the app container.
- **Re-audit, 2026-10-19:** `stat -c '%A %a %n' tools/test-composer-package.sh` — mode `775`; `docker compose exec -T app ./tools/test-composer-package.sh` — passed.
- `git ls-remote origin HEAD` — confirmed remote `HEAD` at `69a7d61`; `git ls-remote --heads origin feature/007-instalador-composer` returned no matching branch.
- **Review finding R-02:** the existing fixture uses a local Composer/VCS repository and passed, but the public GitHub `--repository` command proposed above has not been run. The local feature branch has not been published to GitHub, so its current remote revision does not yet contain this implementation.
