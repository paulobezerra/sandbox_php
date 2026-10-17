# Validation and quality gates

Validation checks the implemented scope and the risks at its boundaries. Consult the project's configured commands and execution instructions before running checks; do not assume that required services or tools are available. The Plan defines acceptance, focused tests, and applicable gates for each task. Code proves that task; the first Review validates the feature as a whole.

In the [Code](workflow.md) step, record a red and green test for new or corrected behavior. Before refactoring, use characterization tests to capture the existing contract that must remain.

## During implementation

- Run focused tests for changed behavior and its boundary: domain invariants, persistence transactions/concurrency, API contracts/authorization, and client flows when affected. Coverage alone does not prove critical scenarios passed.
- Run the checks relevant to changed files and contracts, including formatting, static checks, API contract checks, integration tests, or browser checks where applicable. Review the diff after any writing formatter. Keep broad formatting separate from functional changes. Check schema changes against existing data and affected deployment paths.
- Test affected boundaries directly: an isolated unit test does not establish that an API, browser flow, database migration, or deployment path works.

## Stage gates

- **Code:** run the Plan's task checks and further checks justified by impact and risk. The complete suite or aggregate quality gate is optional unless the task's explicit gate or a concrete risk requires it. A relevant failure blocks completion; a check reserved for Review is not reported as passed by Code.
- **First Review:** run the complete suite and applicable aggregate quality gates after checking the task evidence and affected boundaries. Record failures and environment limits as findings or pending evidence; do not call an unrun gate passed.
- **Later Reviews:** verify corrections and regressions with checks proportional to their impact. Repeat the complete suite or an aggregate gate when a correction can affect broad behavior, invalidates earlier evidence, or a concrete doubt warrants it. Preserve valid evidence from the first audit and identify it as earlier evidence, not a new run.

An aggregate gate verifies the feature across tasks; it does not replace focused checks. If it includes the complete suite, one run satisfies both requirements. Do not add artificial exclusions, suppressions, or tests that only mirror implementation to make a gate pass. Keep checks that write files separate from verification gates. A gate explicitly required by an existing validated Plan still applies to that task; change its scope through the normal artifact decision process, not by silently omitting it.

| Change | Additional expected evidence |
|---|---|
| HTTP/Auth contract | Relevant positive and negative payload, authentication, authorization, CSRF, rate limit, and session cases; synchronized OpenAPI. |
| Persistence/migration | Integration test with existing data and failure/concurrency when integrity is affected; migration status without database reset. |
| Client application | End-to-end check with the application active, covering affected flows and relevant errors. |
| Deployment/configuration | Configuration validation and representative isolated smoke test, such as TLS behind a proxy when the `Secure` cookie depends on it. |
| Documentation/guidelines only | Check links, examples, consistency with current commands, and diff; run any explicitly applicable gates and record limits. |

Record checks **actually run**, results, relevant environment, and limits. Distinguish product failures from environment blocks and follow the repository's execution instructions for retries. A failing applicable gate prevents marking the task complete or Review accepted. An unrun test is not a pass; explain residual risk and whether it blocks acceptance. Do not repeat a passed gate without a change or concrete doubt.
