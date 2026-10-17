# SDD Lite · Feature

Turn a product need into `.specs/features/NNN-name/feature.md`. Read `AGENTS.md`, [workflow](../guidelines/workflow.md), and the [template](../templates/feature.md). This step defines **why**, **for whom**, and **what outcome**; it does not choose architecture or begin Spec/Plan/Code.

Before starting, apply the [context isolation rule](../guidelines/workflow.md#recommended-isolation-clean-context-for-each-execution). If a previous execution's context is still present, briefly warn about the quality and traceability risk and recommend clearing the context, either here or in a new conversation. Do not block the user; if they explicitly insist on continuing with accumulated context, proceed and state in the delivery that they authorized the exception. Reconstruct state from versioned artifacts and the actual repository. End after Feature.

## 1. Understand the request

1. Identify the affected user or operator, the current problem, its consequence, and the desired outcome. Separate observed product facts from assumptions. Consult only the necessary context; if the idea concerns already specified behavior, find the existing feature/spec before opening another ID.
2. Define what is included now and what is excluded. Record known product and compatibility constraints without turning implementation details into product requirements. When product paths differ, clarify only the choice that prevents defining the outcome; other questions may remain explicitly open.

## 2. Prepare the branch, write, and check

1. Use the ID/folder supplied by the user or the next free `NNN` in `.specs/features/`; preserve existing files. Check the branch and workspace state. Before writing `feature.md`, create and switch to `feature/NNN-name` from the integration branch, or `fix/NNN-name` for an independent fix. Reuse the correct branch if it exists; do not mix another task's changes. Record the effective branch in the artifact. If an older Feature was delivered without a branch, establish it and complete this metadata in `feature.md` without changing decisions.
2. Fill the template with the need, outcome, boundaries, observable success signals, and open questions. A success signal should show the effect on a user or operator without prescribing a class, library, or technical test.
3. Check whether someone outside the conversation can tell who benefits and how, what is excluded, and which product choices remain. Remove premature technical solutions, vague promises, and duplicate features. Leave validation pending in `feature.md`; creating the Feature does not imply approval. When the user validates it, fill only that field with their statement, name, and the OS date. Deliver the link, branch, expected outcome, and pending questions. Stop at this step.
