# FEATURE — Fast frontend tests without a browser

**ID:** 010-testes-vitest · **Status:** proposed · **Date:** 2026-10-20

**Branch:** `feature/010-testes-vitest`.

**User validation for Spec:** Paulo Bezerra approved this Feature by stating “aprovar” on 2026-10-20.

## Business need

The project maintainer needs frontend behavior checked consistently on pull requests. The current frontend test command runs a Vitest integration suite that launches Chromium through Playwright, making the checks slower and harder to run in the GitHub Actions workflow. This delay reduces the usefulness of routine feedback for contributors.

## Desired outcome

Frontend regressions in the supported application flows are detected by fast unit or integration tests that run without launching a browser. Existing browser-dependent tests are removed from the project, and the behavior they cover that remains relevant is covered by Vitest. Contributors can run the same checks locally and GitHub Actions can run them as part of the pull request workflow.

## Boundaries and context

- **Included:** Remove existing browser-dependent frontend tests from the project and replace relevant coverage with Vitest unit or integration tests; make the resulting test command suitable for local use and GitHub Actions pull request checks.
- **Out of scope:** Changing application behavior, retaining or adding browser-based tests, or changing the PHP test suite and production deployment workflow.
- **Product constraints:** Keep the tests compatible with the existing PHP/Apache application and native ES module frontend. The requested test runner is Vitest. The current `frontend/tests/integration.test.js` imports Playwright, and `tools/test-frontend.sh` invokes `npm run test`.

## Success signals

- No browser-dependent tests remain in the project, and the frontend test command completes without starting Chromium or another browser.
- The frontend checks finish quickly enough to provide routine pull request feedback and pass in GitHub Actions.
- Automated coverage detects regressions in the selected frontend behavior without requiring a browser session.
