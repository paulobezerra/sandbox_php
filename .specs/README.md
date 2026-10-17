# SDD Lite

Each new feature uses four files in `features/NNN-name/`: `feature.md`, `spec.md`, `plan.md`, and `review.md`. The Plan contains the checklist that Code updates; Review records the audit and re-audits in the same file. The [historical skeleton](features/001-skeleton/spec.md) and its [older audits](features/001-skeleton/review-001.md) remain unchanged and are not converted retroactively.

Workflow: **FEATURE → SPEC → PLAN → CODE → REVIEW**, with **CODE → REVIEW** for corrections. Read the [procedure and boundaries for each step](guidelines/workflow.md). Spec compares real technical alternatives and waits for the user's choice when meaningful tradeoffs exist; a clearly evident single solution may be justified and applied directly. Code executes one ID per execution with focused tests and applicable task checks, marks the task in the Plan only after proving it, and does not ask the user to validate every iteration. The first Review runs the complete suite and applicable quality gates; later reviews verify corrections according to impact and reuse valid earlier evidence. Review records corrections in `review.md`.

**Multi-agent premise:** SDD Lite rules live in shared commands and artifacts; Codex, Claude Code, and Junie CLI adapters only make them discoverable. No workflow control may depend exclusively on one agent.

**Workflow exceptions:** the agent explains which rule would be broken, the technical risk, and the recommended path. The notice does not block the user. If the user explicitly insists, the agent proceeds within the authorized scope and records the exception in the delivery without claiming pending validations or gates passed.

**Recommended isolation:** start each SDD Lite command or Code ID with clean context. Clearing the current conversation's context (for example, `/clear`) and opening a new conversation are equivalent options. If a new execution starts with accumulated context, the agent briefly warns that earlier assumptions may compromise quality and traceability and recommends clearing the context. The notice is not a block: if the user explicitly insists, proceed with the requested step, reconstruct state from versioned artifacts and the actual repository, and record in the delivery that the isolation recommendation was waived with explicit authorization. See the [workflow isolation rule](guidelines/workflow.md#recommended-isolation-clean-context-for-each-execution).

Feature, Spec, and Plan require explicit validation before the next step, recorded in the artifact with the user's name and a date obtained from the OS. Between Code tasks and before Review, no new user validation is needed: the agent checks the checklist, prerequisites, diff, code, and tests. See the [entry rule](guidelines/workflow.md#entry-premise-explicit-validation-of-the-previous-step).

Feature creates the `feature/NNN-name` branch before writing the artifact (`fix/NNN-name` for an independent fix). Review checks local state and, when possible, the remote; unproven publication is an audit limitation, with no automatic commit or push. See the [branch and publication rule](guidelines/workflow.md#feature-branch-and-publication-before-review).

Use the [specification](guidelines/specification.md), [implementation](guidelines/implementation.md), [review](guidelines/review.md), and [quality](guidelines/quality.md) guidelines with the [feature](templates/feature.md), [spec](templates/spec.md), [plan](templates/plan.md), and [review](templates/review.md) templates. `docs/` contains application documentation only. If present, `todolist.md` remains historical.

## Commands and single source of truth

The executable instructions for the five stages are in [`commands/`](commands/). [`guidelines/`](guidelines/) defines shared rules, [`templates/`](templates/) defines the artifacts, and [`features/`](features/) stores feature work. `AGENTS.md` contains repository-wide rules. Edit a command or guideline in `.specs/` to change the workflow; agent-specific files are entry points only and must not duplicate the rules.

| Stage | Codex | Claude Code | Junie CLI | Canonical instruction |
|---|---|---|---|---|
| Feature | `$sdd-feature` | `/sdd-feature` | `/sdd-feature` | [feature.md](commands/feature.md) |
| Spec | `$sdd-spec` | `/sdd-spec` | `/sdd-spec` | [spec.md](commands/spec.md) |
| Plan | `$sdd-plan` | `/sdd-plan` | `/sdd-plan` | [plan.md](commands/plan.md) |
| Code | `$sdd-code` | `/sdd-code` | `/sdd-code` | [code.md](commands/code.md) |
| Review | `$sdd-review` | `/sdd-review` | `/sdd-review` | [review.md](commands/review.md) |

Pass the feature or task ID when available: `$sdd-code T002-03`, `/sdd-code T002-03` in Claude Code, or `/sdd-code T002-03` in Junie CLI. Junie also accepts free text. Adapters live in `.agents/skills/` (Codex), `.claude/commands/` (Claude Code), and `.junie/commands/` (Junie CLI). Discovery and syntax depend on tool version; restart the session if newly created files do not appear. An agent without a native command can be told directly: “execute `.specs/commands/<stage>.md` for `<ID>`.”

Adopted improvements include reviewing ambiguity before choosing a solution, inspired by the clarification stage in [Spec Kit](https://github.github.com/spec-kit/reference/agentic-sdd.html) and requirements analysis in [Kiro](https://kiro.dev/docs/specs/feature-specs/); explicit requirement → task → verification links, present in [Spec Kit](https://github.github.com/spec-kit/reference/agentic-sdd.html) and [OpenSpec](https://github.com/Fission-AI/OpenSpec/blob/main/docs-lab/reference/schemas/spec-driven/index.md); and a final consistency check across artifacts. These improvements are incorporated into the existing five steps, preserving one Code task per execution and the user's technical decision before the final Spec.
