# SPEC — Adopt the standalone SDD Lite skill

**ID:** 005-sdd-lite-repo · **Status:** proposed · **Date:** 2026-10-18

**User validation for Plan:** validated. **Statement:** "validar". **Name:** Paulo Bezerra · **Date:** 2026-10-18.

## Problem, baseline, and goal

The amended, user-validated Feature 005 makes the standalone `$sdd-lite` skill the source of workflow rules and artifact structure. The project context and implementation conventions already exist in `.specs/project-context.md` and `.specs/implementation.md`; Feature 005 itself uses the canonical `feature.md` and `spec.md` layout.

The repository still contains duplicate local commands, guidelines, templates, an index, and adapters for Codex, Claude Code, and Junie. `AGENTS.md` actively points agents to these local workflow sources and gates. Those files can direct agent behavior and conflict with the installed skill. `README.md` and the SPA Home also describe the previous local workflow, but are human-facing documentation; they can continue to explain SDD Lite if their content describes the independent skill and does not send people to obsolete local instructions.

Feature records 001–004 are immutable historical records. They may contain references and instructions from their original workflow, but those are not current directions and must not be edited or treated as active during cleanup. The project context must explain this exception so future maintenance does not normalize those directories.

## Scope and constraints

- **Included:** remove duplicate local workflow definitions and active adapters or instructions that direct agents to them; retain independent application guidance in `AGENTS.md`; update or remove human-facing SDD descriptions as needed to avoid obsolete local directions; preserve feature records 001–004 exactly; transfer useful verified project knowledge found during cleanup to the appropriate init artifact; document the historical layout exception in `project-context.md`.
- **Out of scope:** modifying any file under `.specs/features/001-skeleton/` through `.specs/features/004-hospedagem-compartilhada/`; changing application architecture, product behavior, API contracts, schema, or data; changing the standalone SDD Lite project; rewriting historical decisions or validation statements.
- **Constraints:** the standalone skill is the only workflow source. Human documentation may identify or link to the independent skill but must not define a competing process or link to removed local instructions. Historical references inside 001–004 remain unchanged and are not considered active instructions. During this feature, `.specs/project-context.md` and `.specs/implementation.md` may be corrected or expanded when repository evidence identifies useful project knowledge that would otherwise be lost. After Feature 005, change those files only at the user's explicit request or by rerunning the canonical `$sdd-lite init`.

## Technical solution

**Technical decision:** remove local workflow sources and adapters that point to them; remove active SDD rules from `AGENTS.md` while retaining independent engineering and operational rules; retain or update human-facing documentation to describe the standalone skill accurately; preserve every feature record 001–004 byte-for-byte. This is the solution stated in the amended Feature and authorized by Paulo Bezerra on 2026-10-18. No technical alternatives remain open.

**Reference classification and treatment:**

- **`.specs/commands/`, `.specs/guidelines/`, `.specs/templates/`, `.specs/README.md`:** local workflow sources and their index. Remove them; do not copy generic workflow rules elsewhere in the repository.
- **`.agents/skills/sdd-*`, `.claude/commands/sdd-*`, `.junie/commands/sdd-*`:** active adapters that redirect commands to local files being removed. Remove them so they cannot shadow or fail in place of `$sdd-lite`.
- **`AGENTS.md`:** active agent instructions. Remove SDD lifecycle rules, local command and guideline paths, and gates that belong to the standalone skill. Retain application engineering, security, Docker, quality-command, and change-safety guidance that remains independently useful. A short note may identify the installed `$sdd-lite` skill as the workflow source and point to the two project context files, without defining stages or gates.
- **`README.md`:** maintainer documentation. Replace its link to the removed local index with a concise explanation and, if useful, a link to the independent SDD Lite project. Keep links to historical feature artifacts only as historical references.
- **SPA Home and locale catalogs:** human-facing documentation, not workflow controls. The SDD section may remain or be revised to describe the independent skill and its invocation. Remove obsolete local command examples and links to the removed local guide. Keep English and Brazilian Portuguese content aligned, and update the browser checks to verify the chosen content and navigation.
- **Feature records 001–004:** preserve all paths and bytes. This explicitly includes numbered review filenames, missing canonical-stage files, and old references within those artifacts. Do not rename, translate, merge, complete, or normalize these records. Treat their contents only as historical evidence, never as current workflow instructions.
- **Project context and implementation artifacts:** retain them as the project-specific knowledge source. During this feature, classify material being retired as generic workflow, verified project fact/convention, or obsolete/duplicated content. Move useful verified project knowledge to the appropriate artifact. Add a durable note to `project-context.md` explaining that 001–004 are intentionally preserved historical records in their original shapes, that their embedded old references are not active guidance, and that their shapes must not be normalized. New work, including Feature 005, follows the standalone skill's canonical layout. After this feature, update either init artifact only on explicit user request or through a new canonical `$sdd-lite init` run.

**Reason for the decision:** active adapters and `AGENTS.md` can affect command discovery and agent behavior, so removing their links to local workflow files prevents conflicts. README and Home inform people without controlling skill execution, so accurate references to the independent skill remain useful. The immutable history requirement means old references will remain inside 001–004; the project-context note and active-file checks make that boundary clear without rewriting history. Keeping 001–004 intact is explicitly authorized in the amended Feature and no longer represents an unresolved deviation.

## Open questions for validation

None. The amended Feature explicitly resolves the treatment of historical artifacts, active instructions/adapters, and human-facing documentation.

## Requirements

- **S005-01 — Keep the standalone skill authoritative:** remove local workflow commands, guidelines, templates, and their index; no active repository instructions or adapters may define a competing workflow or direct users to those removed files. Consumers: agents and maintainers.
- **S005-02 — Preserve historical feature records:** every file under feature directories 001–004 remains byte-for-byte unchanged. Their embedded references are treated as historical evidence, not active instructions. Consumers: maintainers and future SDD executions.
- **S005-03 — Remove obsolete active adapters:** remove repository adapters under `.agents/skills/sdd-*`, `.claude/commands/sdd-*`, and `.junie/commands/sdd-*` that dispatch to the local workflow. The installed `$sdd-lite` skill is the workflow entry point. Consumers: Codex, Claude Code, Junie, and other agents.
- **S005-04 — Keep agent guidance compatible:** remove local SDD lifecycle, gates, and obsolete paths from `AGENTS.md`; retain its independent project engineering and safety guidance. Any retained SDD note only identifies the standalone skill and project context files. Consumers: agents loading repository instructions.
- **S005-05 — Keep human-facing documentation accurate:** README and Home may describe the independent skill, but must not contain old local command examples or links to removed local instructions. Keep Home content aligned in English and Brazilian Portuguese. Consumers: maintainers and SPA visitors.
- **S005-06 — Preserve useful project knowledge:** during this feature, verify project-specific facts and conventions found in material being removed and record useful information in the appropriate init artifact; do not copy generic workflow rules. Consumers: future SDD executions.
- **S005-07 — Explain the legacy layout:** `project-context.md` states that 001–004 intentionally retain their original historical shapes, are not to be normalized, and may contain old references that are not current guidance; new work follows the standalone layout. Consumers: future agents and maintainers.
- **S005-08 — Bound later init-artifact edits:** after Feature 005, modify `project-context.md` or `implementation.md` only at the user's explicit request or through a new canonical `$sdd-lite init` run. Consumers: maintainers and future agents.

## Contracts, data, and security

No application API, database schema, migration, authentication, session, authorization, or server-side data changes. Human-facing documentation changes do not alter runtime contracts. Active agent instructions and adapters can affect command selection and behavior; removing local workflow directions prevents them from competing with the standalone skill. Historical feature artifacts remain unchanged.

## Acceptance and verification criteria

- **Historical immutability:** compare every file in feature directories 001–004 with the pre-change baseline. They must be byte-for-byte unchanged, including filenames and embedded references. Do not use a search for old references inside those directories as a cleanup failure.
- **No active local workflow:** inventory `.specs/` and agent adapter paths. Local commands, guidelines, templates, the local index, and adapters are absent; init artifacts and all feature records remain. Search active guidance and adapters for removed command/guideline paths. Exclude frozen feature records 001–004 from active-reference assertions.
- **Agent guidance compatibility:** `AGENTS.md` retains applicable project rules but does not require local SDD files or duplicate the standalone workflow. Review its remaining SDD note, if present, against the installed skill and project context.
- **Human-facing documentation:** README and Home, in English and Brazilian Portuguese, explain the independent skill accurately or omit the topic; neither points to obsolete local instructions or defines another workflow. Verify links and text; run browser checks for the chosen Home behavior with the app and Chromium available.
- **Project knowledge and history note:** verify project facts transferred from removed materials against current files and commands. Confirm `project-context.md` explains why 001–004 remain unchanged and that their embedded references are historical. Confirm no unrequested edits were made to init artifacts beyond this feature's scope.

**Applicable gate:** `git diff --check`; compare file hashes and diff for feature directories 001–004; inventory `.specs/` and agent adapters; run directed searches for obsolete paths in active instructions/documentation while excluding immutable history; run `./tools/test-all.sh` if Home or other SPA behavior changes, with the app active and Chromium available. No checks have been run in this Spec step.

## Decisions and history

- 2026-10-18 — Feature 005 was amended and explicitly authorized by Paulo Bezerra. Historical feature records 001–004 remain byte-for-byte unchanged; active local workflow sources and adapters are removed; human-facing documentation may describe the independent skill; references embedded in historical records are not treated as current guidance.
- 2026-10-18 — The user explicitly requested that the Spec be revised to align with the amended Feature and correct the earlier ambiguity. This Spec replaces the earlier proposed history consolidation and broad reference-removal interpretation. The previous Spec draft was not validated for Plan and is superseded by this version.
