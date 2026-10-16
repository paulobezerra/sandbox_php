# Quality gate

The gate checks the implemented scope; it does not replace tests for specific risks. Check current scripts in `composer.json` and execution instructions in `AGENTS.md`/`README.md` before running commands. The project may require Docker Compose: check `docker compose ps -a` and run Composer inside the active `app` service. Do not assume Docker, MySQL, SMTP, or Chrome is available.

In the [Code](workflow.md) step, record a red and green test for new or corrected behavior. Before refactoring, use characterization tests to capture the existing contract that must remain.

## During implementation

- Run focused tests for changed behavior and its boundary: domain invariants, MySQL transactions/concurrency, HTTP contract/authorization, and browser mounting/flows for SPA changes. Coverage alone does not prove critical scenarios passed.
- For PHP, run `composer format` after edits and review the diff; run `composer lint` for style and syntax without writing. Keep broad formatting separate from functional changes. Do not edit applied migrations to change schema; use an additive migration and check empty and existing data when onboarding/distribution is affected.
- When changing the API, update attributes, generate `docs/openapi.json`, and check routes, methods, bodies, responses, and authentication. When changing SPA JavaScript/CSS/routes, run `./tools/test-all.sh` on the host with `app` active: isolated PHPUnit cannot detect ES module or Vue mount failures.

## Close the scope

Current `composer quality` runs, in order, `composer lint`, PHPUnit with Clover coverage, a total `src/` line coverage minimum of 80%, PHPStan level 6, and `composer openapi:check`. It is a **verification** gate: `composer format` is excluded because it writes files. Do not add artificial exclusions, suppressions, or tests that only mirror implementation to reach the threshold; a real technical exclusion needs justification. Run the complete gate after focused checks and fixes. `composer validate --no-check-publish`, `git diff --check`, and `migrate:status` are complementary checks when relevant, not substitutes for the gate.

| Change | Additional expected evidence |
|---|---|
| HTTP/Auth contract | Relevant positive and negative payload, authentication, authorization, CSRF, rate limit, and session cases; synchronized OpenAPI. |
| Persistence/migration | Integration test with existing data and failure/concurrency when integrity is affected; migration status without database reset. |
| SPA | Headless browser with the app active, covering affected flows and relevant errors; `./tools/test-all.sh`. |
| Deployment/configuration | Configuration validation and representative isolated smoke test, such as TLS behind a proxy when the `Secure` cookie depends on it. |
| Documentation/guidelines only | Check links, examples, consistency with current scripts, and diff; run repository-required gates and record environment limits. |

Record commands **actually run**, results, relevant versions/environment, and limits. Distinguish product failures from environment blocks (such as Chromium/Crashpad); retry with the permission described in `AGENTS.md` when applicable. A failing gate prevents marking the task complete. An unrun test is not a pass; explain residual risk and whether it blocks acceptance. Do not repeat an approved gate without a change or concrete doubt.
