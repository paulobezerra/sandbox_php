# Feature development workflow

The lifecycle for a new feature is **Feature → Spec → Plan → Code → Review**, followed by **Code → Review** for corrections. Each execution performs only the requested step. A new feature uses only four files in `features/NNN-name/`: `feature.md`, `spec.md`, `plan.md`, and `review.md`. Code updates the Plan checklist; Review records findings and re-audits in the same `review.md`. The historical `001` skeleton does not need retroactive conversion.

The five executable procedures live in [commands](../commands/). [Guidelines](.) define shared rules, [templates](../templates/) define artifacts, and agent-specific discovery files point to the canonical commands. Change workflow behavior here and in the relevant command first; do not copy rules across adapters.

**Multi-agent premise:** SDD Lite must work with any agent that can read repository commands and artifacts. Do not implement workflow rules only through hooks, plugins, session state, or agent-specific commands. Adapters may improve discovery, but cannot be the only way to enforce a rule, record a decision, or verify a gate.

## Immutable inputs between steps

Earlier decisions are not rewritten by later stages: **Spec does not change Feature; Plan does not change Feature or Spec; Code does not change Feature or Spec and only updates the Plan checklist; Review does not change Feature, Spec, or Plan.** Explicit validation stays in the validated artifact. The Plan is an operational checklist: Code may update task status without changing scope or acceptance. Review may update `review.md` during re-audits or correction tracking, but must not create another record.

If the current step finds a conflict, gap, or later decision that contradicts an input, record it **in the current step's artifact**: source, divergence, impact, and the user's explicit statement approving the change. Without that validation, leave it pending and do not treat the changed contract as approved. Code records only a decision affecting its current task in the Plan, without changing the original requirement text. Review records new findings and re-audit results in the same `review.md`.

Validation is recorded only in the validated artifact: Feature in `feature.md`, Spec in `spec.md`, and Plan in `plan.md`. Record the explicit statement, the name supplied by the user, and the date obtained from the OS. **Code does not require user validation for every task.** Its progress is tracked by Plan checkboxes; test commands/results are reported in the session delivery and checked during Review, without an extra file. Existing validation fields remain valid.

**User-authorized exceptions:** do not use an SDD Lite preference or stage to block an explicit request. For any request that breaks the workflow, explain the rule, why the recommended path protects quality, and the concrete risk of the exception. If the user explicitly insists, proceed within the authorized scope and state in the delivery that the workflow was broken with their authorization. Do not invent validation, choices, tests, or passed gates; keep unproven items pending and describe the real limitation. This does not authorize destructive operations or waive restrictions outside SDD Lite.

## Recommended isolation: clean context for each execution

**Recommend starting each SDD Lite command or Code ID with clean context, and run only the requested step.** Clearing the current conversation's context (for example, with `/clear`) and opening a new conversation are equally valid ways to do this; SDD Lite does not prefer either. No automatic clearing is required. Reconstruct the required state from versioned artifacts and the actual repository state, regardless of how context was cleared. The current request supplies the scope and any new user decision; memory of an earlier execution is not evidence. A response needed to finish the current command, such as a technical choice during Spec or validation of the result, stays in the same execution.

**Check before each execution:** if the available conversation contains a completed or blocked Feature, Spec, Plan, Code, or Review execution and its context has not been cleared, warn before proceeding: **accumulated assumptions and provisional decisions may compromise the quality and traceability of the next step**. Briefly explain the risk and recommend clearing the context, either in the current conversation or by opening a new one. This warning is not a block. If the current request explicitly insists on continuing with accumulated context, proceed; otherwise wait for the user's decision. When proceeding, execute only the requested command, reconstruct inputs from versioned artifacts and actual repository state, and do not treat conversation history as validation, a technical choice, or implementation evidence. In the delivery, state that the recommended isolation was waived with the user's explicit authorization. The warning does not replace required validation between stages.

### Why isolate executions

Feature, Spec, Plan, Code, and Review have different inputs, decisions, and exit criteria. With context carried across executions, an agent may mistake assumptions or rejected alternatives for authorization for the next stage without checking repository state. Starting with clean context supports reconstruction from recorded evidence: Feature validation feeds Spec; validated Spec feeds Plan; Plan checklist and actual code feed Code; completed Plan and diff feed Review. This reduces reliance on transient memory and makes decisions easier to audit.

Clearing context by either method is an isolation measure, not a technical guarantee. Each command still checks validation, branch, prerequisites, and evidence under its own rules. The cost is reconstructing context at each execution; the benefit is finding divergence before it spreads.

## Entry premise: explicit validation of the previous step

Before Spec, Plan, or the first Code task, check explicit user validation in the previous delivery artifact: Feature → Spec, Spec → Plan, Plan → Code. The record includes the **user-supplied name** and a **date obtained from the operating system when recording it**. A checkbox, gate, or lack of objection does not prove validation. A technical choice during Spec does not replace validation of the completed Spec.

If the record is missing, explain which delivery needs validation and wait for the user to explicitly say it is validated. Ask for their name if it has not been supplied; run `date +%Y-%m-%d` on the OS when recording the statement, and add it only to the validated artifact. Do not substitute a user-supplied date or file creation date. If the user insists on proceeding without validation, follow the exception rule above: do not claim the gate passed, and record the pending validation in the new artifact and delivery. Feature starts the lifecycle and requires no prior validation.

Between Code IDs, follow Plan order and dependencies. Before starting a task, confirm that prerequisites are marked complete **and** that code, diff, and tests support those marks. A checkbox alone is insufficient. If the requested task is not ready, explain the actual dependency and do not proceed; without an ID, choose the first eligible task. For Review corrections, use findings in `review.md` without creating a per-correction record.

## Feature branch and publication before Review

At the start of Feature, after choosing the ID and before writing `feature.md`, create and switch to a local `feature/NNN-name` branch from the integration base; for an independent fix handled as a Feature, use `fix/NNN-name`. Reuse the correct branch if it already exists. Check workspace state before switching branches to avoid mixing other work. Record the branch in `feature.md`. Creating the branch is Feature preparation, not a functional Plan task; use the same branch through Spec, Plan, Code, and Review. For an older feature without a recorded branch, establish it and record it in `feature.md` before the next stage, without changing validated decisions. For the historical skeleton without `feature.md`, record the working branch in that round's artifact without rewriting historical files.

Before Review, check branch state, diff, local commit, and remote when accessible. Record whether publication is proven; if remote access is unavailable or no push is verified, state that as an audit limit. Review does not create commits or push. Do not create an automatic publication task or require push for a Code task.
