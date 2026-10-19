# SPEC — Reliable production deployment

**ID:** 009-deploy-producao · **Status:** proposed · **Date:** 2026-10-19

**User validation for Plan:** Paulo Bezerra validated this revised Spec by stating “aprovado” on 2026-10-19.

## Problem, baseline, and goal

The current `tools/deploy-shared-hosting.sh` only fast-forward pulls `main`, installs production Composer dependencies, and applies pending production migrations. It cannot deploy a selected tag. `.github/workflows/quality-and-release.yml` runs PHP and frontend checks, builds the Vite output, and creates a tagged release package after changes reach `main`, but it does not deploy to the server. The production Vite output must be available under `public/assets/build/`, and the host does not have Node.js for building it.

The goal is a small release flow: test pull requests; after a passing merge to `main`, tag that commit and deploy that version; allow an operator to select a published tag and run the same deployment script to redeploy it.

## Scope and constraints

- **Included:** Pull request tests; automatic tag and deploy after merge to `main`; manual deployment of a selected published tag through the same deployment script.
- **Out of scope:** Automatic database rollback, production data restoration, repository or hosting provider changes, and deployment features beyond these three flows.
- **Constraints:** Keep the existing target `~/sites/sandbox-php`. The deployed source and Vite assets under `public/assets/build/` must come from the selected tag. The production host does not run Node.js. Deployment keeps using the existing Composer install and forward-only Phinx migration steps. Deployment credentials and identifying server details stay in protected settings or host-local configuration.

## Technical solution

**Technical decision:** Use one GitHub Actions workflow for PR checks and the `main` release/deploy path, plus a manual dispatch that accepts a published tag. On merge, CI builds the frontend, creates a unique immutable tag for the merged commit, and deploys that tagged version. The manual path selects a tag and invokes the same `tools/deploy-shared-hosting.sh` deployment procedure with that version. Keep only the release packaging/transfer needed to make the source and Vite build for that tag available to the host; do not add a separate release-management or rollback system.

**Reason for the decision:** This is the user's explicit direction on 2026-10-19: PRs run tests; a merge creates a tag and deploys it; rollback means selecting a tag and running the same deploy script. A tag identifies source but does not contain ignored Vite build output, and the host cannot build it, so CI must carry the matching generated assets with the selected version. The existing workflow and installer are the starting points. The impact is low to moderate: simplify the workflow around these three paths and minimally adapt the existing installer to deploy a supplied tag/package. Database migration reversal is not part of code rollback.

## Open questions for validation

None. The user selected the three flows and the shared deploy script in the current request.

## Requirements

- **S009-01 — PR checks:** Given a pull request targeting `main`, CI runs the PHP tests and frontend tests; it also verifies the production frontend build needed for deployment. A failing check fails the PR workflow. Repository branch protection requires these checks to block merging. Consumers: pull requests and repository maintainers.
- **S009-02 — Merge deploy:** Given a passing change merged to `main`, CI creates one immutable tag for that commit and deploys that tagged source and its matching Vite build to `~/sites/sandbox-php` through the shared deployment procedure. The host does not run Node.js. Consumers: release workflow and production site.
- **S009-03 — Tag redeploy:** Given a previously published tag, an authorized operator can select it in the manual workflow and run the same deployment procedure to install that tag's source and matching Vite build. An unknown tag or unavailable build fails before the server is changed. This redeploy does not reverse database migrations. Consumers: operator and production site.

## Contracts, data, and security

- The automatic deployment uses the tag created for the triggering merge. Manual deployment accepts a published tag with its matching deployable Vite build.
- The selected source and `public/assets/build/` output must be from the same commit/tag. The PHP host does not run Node.js.
- The existing production install and pending migration steps remain in the deployment script. No migration rollback or data restoration is performed; the operator is responsible for choosing code compatible with the current schema.
- SSH credentials and server details come from protected settings or host-local configuration and are not printed in workflow logs. The workflow must fail when tests, tag selection, asset transfer, dependency installation, or migration fails.

## Acceptance and verification criteria

- **PR checks:** A PR to `main` runs PHP tests, frontend tests, and the production build; a controlled failure makes the workflow fail. Verify the workflow and configured required status checks.
- **Merge deploy:** A passing merge creates a tag pointing to that exact commit and deploys its source/build pair to the configured target through the shared script. Verify tag identity, build manifest, workflow result, and that the host receives the selected version.
- **Tag redeploy and failure:** Selecting a published earlier tag invokes the same deployment script and installs its source/build pair. An unknown tag is rejected before host mutation. Verify against an isolated or staging target; confirm no migration rollback is run.

**Applicable gate:** Check workflow syntax, the PHP/frontend test commands and production build, tag/source/build identity, and the shared script's selected-tag behavior in an isolated or staging target. Production deployment is outside this Spec's authorization. CI and staging results are not available at Spec time.

## Decisions and history

- 2026-10-19 — The prior Spec described a larger release-package workflow, generic file-preservation policy, and extensive failure and staging scenarios. Paulo Bezerra's current request replaces that contract with three flows: PR tests, tag-and-deploy on merge, and tag-selected redeploy through the same script. The prior Spec validation for Plan does not validate this revised contract; Plan validation is pending again.
- 2026-10-19 — The selected tag must be deployed with its matching Vite output because `public/assets/build/` is ignored and the production host has no Node.js. This is the minimum artifact transfer needed to make the requested tag deployable; no separate rollback mechanism is specified.
