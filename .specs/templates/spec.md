# SPEC — <feature name>

**ID:** NNN-name · **Status:** proposed · **Date:** YYYY-MM-DD

**User validation for Plan:** pending. After an explicit statement, record it here with the user's supplied name and the date obtained from the OS, without changing requirements or decisions. The technical decision below is not validation.

## Problem, baseline, and goal

<who is affected; verified current behavior and code/contract reference; expected outcome>

## Scope and constraints

- **Included:** <outcomes in this Spec>
- **Out of scope:** <explicit boundaries>
- **Constraints:** <compatibility, stack, deployment, dependencies, and relevant data; consult AGENTS.md>

## Technical solution

**Technical decision:** <user's choice and date when meaningful alternatives exist; or clearly evident single solution, date, and code-based reason. Do not attribute a choice to the user without their statement.>

<Summarize only real options: the clearly evident solution or the selected option and relevant alternatives, in separate blocks. For each, record estimated code impact (low, moderate, or high), affected components, and decision-relevant fit or caveats. Identify criteria that do not apply. Avoid extensive comparison without a real tradeoff.>

**Reason for the decision:** <verified evidence, decisive benefit, and accepted cost; for a single solution, explain why no relevant alternative exists>.

## Open questions for validation

<List product decisions or Feature deviations the user still needs to assess. For each, cite the prior contract, real options, impact, and current proposal. If none remain, write “None” so the check is visible before validation. Do not treat an earlier technical choice as Spec validation.>

## Requirements

- **SNNN-01** — <observable outcome; affected consumers: API, SPA, OpenAPI, database, operations>
- **SNNN-02** — <another outcome; dependency on SNNN-01, if any>

<Plan will turn these requirements into deliverable tasks. Code and Review track progress outside this Spec.>

## Contracts, data, and security

<Existing routes/payloads/statuses, invariants, authentication/authorization/CSRF, secrets/sessions, migrations, and compatibility. Omit irrelevant areas and justify decisions that change contracts.>

## Acceptance and verification criteria

- **Success:** given <initial state>, when <action>, then <observable result>. Test: <level and scenario>.
- **Failure/boundary:** given <relevant invalid input, denied access, or partial failure>, when <action>, then <response/preserved state>. Test: <level and scenario>.
- **Integration/compatibility:** <affected existing data, API/SPA/OpenAPI, or clean installation>. Test: <scenario and environment>.

**Applicable gate:** <commands from ../guidelines/quality.md and risk-based additional checks; consult ../guidelines/implementation.md and record execution/limits in Review>.

## Decisions and history

- YYYY-MM-DD — <decision, reason, prior contract replaced, effects, and reference to earlier Review/Spec; do not rewrite history>
