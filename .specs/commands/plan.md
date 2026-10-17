# SDD Lite · Plan

Turn a selected `spec.md` into deliverable `TNNN-XX` tasks. Read `AGENTS.md`, [workflow](../guidelines/workflow.md), [quality](../guidelines/quality.md), the [template](../templates/plan.md), Feature, Spec, and relevant code/tests. One invocation performs Plan only; it does not implement tasks.

Before starting, apply the [context isolation rule](../guidelines/workflow.md#recommended-isolation-clean-context-for-each-execution). If a previous execution's context is still present, briefly warn about the quality and traceability risk and recommend clearing the context, either here or in a new conversation. Do not block the user; if they explicitly insist on continuing with accumulated context, proceed and state in the delivery that they authorized the exception. Reconstruct state from versioned artifacts and the actual repository. End after Plan.

## 1. Check prerequisites

1. Check explicit user validation for Plan in `spec.md`, including name and OS date; the technical choice does not replace it. If missing, apply the [entry rule](../guidelines/workflow.md#entry-premise-explicit-validation-of-the-previous-step). Confirm the technical choice is recorded, requirements have IDs, and acceptance criteria are observable. Check conflicts with Feature, current code, and repository rules. Do not change Feature or Spec, except to record validation in the validated artifact itself. Record any deviation and its impact in the Plan, request explicit user validation for a contract change, and leave affected dependencies pending until decided.
2. Make a short inventory of requirements, consumers, and risks that require work: data/migration, domain, API/OpenAPI, SPA, security, and operations as applicable. Do not create tasks for layers the feature does not touch.

## 2. Divide into increments

1. Order tasks by dependency and risk. Each ID must deliver usable, verifiable behavior with prerequisites, success, relevant failure, and preservation of the prior contract. Include everything needed to complete that slice: migration, backend, API, SPA, tests, and documentation. Technical preparation belongs in the first task that uses it; branches, isolated scaffolding, and formatters are not functional increments.
2. Cite requirements covered by each task and dependencies between IDs. State the test expected to fail before implementation and the applicable gate; for non-behavioral changes, specify a real check instead of a trivial test. If a task is too large for one Code session, divide it into slices that each deliver a usable result.

## 3. Audit the Plan before delivery

1. Check requirement → task → acceptance/verification: every implementable requirement needs a task, and every task must deliver a Spec outcome. Check dependency order and final integration risks, especially data preservation, HTTP contract, authorization, and browser behavior where affected.
2. Write `plan.md` from the template with stable IDs. Leave Plan validation for Code pending until the user explicitly states it; then record it only in that field with their name and the OS date. Deliver the link, execution order, and any gaps that still prevent Code. Stop before implementation.
