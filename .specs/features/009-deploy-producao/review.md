# REVIEW — Reliable production deployment

**Source:** [feature.md](feature.md) · [spec.md](spec.md) · [plan.md](plan.md) · **Date:** 2026-10-20 · **Status:** complete

## Baseline and limits

Reviewed branch `feature/009-deploy-producao` at `aec05ef35d9868b62106bcb118571f33e22754bb`; the local branch is clean and matches `origin/feature/009-deploy-producao`. The branch is published. `origin/main` was fetched and is `c812f1cf218485465a7c0273ad14ca0a453ff566`, which merges this feature; its tree differs only in the T009-01 checkbox, which is unchecked there. No commit or push was made for this review.

The Dockerfile uses a Node build stage to create `public/assets/build/`, copies those assets into the PHP production image, and does not include Node in the production stage. The workflow and installer changes are present in the branch diff. This is direct code observation; a production image build was not run.

Checks run on 2026-10-20:

- `docker compose exec -T app composer quality` — passed: lint, 95 PHPUnit tests (428 assertions), 87.60% executable-line coverage, PHPStan, and OpenAPI checks.
- `docker compose config -q` — passed.
- `bash -n tools/deploy-shared-hosting.sh tools/verify-deploy-package.sh tools/test-all.sh tools/test-frontend.sh` — passed.
- `git diff --check 25ee954...HEAD` — passed.
- `npm ci --prefix frontend` — could not complete because DNS resolution for `registry.npmjs.org` failed (`EAI_AGAIN`); consequently, the production frontend build and browser tests were not run.

GitHub Actions workflow execution, repository branch protection settings, release/tag identity, package transfer, and selected-tag redeployment were not independently verified in this review. The maintainer confirmed on 2026-10-20 that the site is in production and accepts that result as sufficient deployment evidence; no production deployment was attempted during this review. No workflow-specific syntax validator was available in the workspace.

## Verified requirements

- **S009-01 / T009-01:** The workflow defines PHP tests, a production Vite build, and a PR release-label check. Paulo Bezerra explicitly confirmed on 2026-10-20 that frontend browser tests are deliberately excluded because their current runtime is too slow for the PR workflow. This is accepted as the maintainer's decision for this delivery. Branch protection settings were not independently inspected.
- **S009-02 / T009-01:** The workflow contains a merge job that derives a SemVer tag from the merged PR label, packages the merged source with the built assets, and calls the shared installer. The Dockerfile's build and copy stages match the required asset path. Paulo Bezerra confirmed on 2026-10-20 that the site is in production; this is accepted as sufficient evidence of the deployment outcome. The workflow run and deployed tag identity were not independently inspected.
- **S009-03 / T009-01:** The manual workflow accepts a tag, downloads its release package, checks the package against the tag's commit, then invokes the same installer. A selected-tag redeployment and invalid-tag failure were not exercised. Paulo Bezerra accepted the production deployment result as sufficient for this review, so these additional scenarios are recorded as unverified limits rather than blockers.

## Findings

- **R-01 — PR browser-test omission and branch-protection inspection.** **Status:** accepted by the maintainer on 2026-10-20. **Evidence:** Paulo Bezerra stated that browser tests are deliberately omitted because their current execution time is too high for the PR workflow. The workflow still runs PHP tests and the production build. The branch-protection setting itself was not independently inspected. **Impact:** browser behavior is not covered by the PR gate, and this review does not prove the repository's required-check configuration. **Correction:** none requested; the maintainer accepts this limitation for this delivery. **Acceptance and test:** the workflow definition contains the agreed PHP/build checks; the browser-test exclusion is an explicit maintainer decision.
- **R-02 — Release and redeployment scenarios were not independently exercised.** **Status:** accepted by the maintainer on 2026-10-20. **Evidence:** package/installer tests were removed by the recorded plan decision, and this review did not run a selected-tag redeployment or inspect a workflow run. Paulo Bezerra confirmed that the site is in production and accepted that as sufficient evidence for this delivery. **Impact:** this review does not independently establish the deployed tag identity or selected-tag failure behavior. **Correction:** none requested; production availability is accepted by the maintainer as sufficient. **Acceptance and test:** owner-confirmed production deployment, plus the passing PHP quality gate and static inspection of the workflow, installer, and Dockerfile.

## Conclusion

The PHP quality gate and local configuration/shell checks pass. Static review supports the Dockerfile's intended multi-stage asset flow. The maintainer accepted the deliberate browser-test exclusion and confirmed that the site is in production, accepting production availability as sufficient deployment evidence. The review is complete with those decisions recorded. Workflow execution, branch-protection settings, deployed tag identity, selected-tag redeployment, frontend build/browser checks in this environment, and isolated/staging failure scenarios remain unverified limits; the Plan checkbox still conflicts with its recorded pending gates.
