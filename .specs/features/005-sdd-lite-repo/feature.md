# FEATURE — Adopt the standalone SDD Lite skill

**ID:** 005-sdd-lite-repo · **Status:** proposed · **Date:** 2026-10-18

**Branch:** `feature/005-sdd-lite-repo`.

**Historical validation of previous Feature version:** validated. **Statement:** "aprove". **Name:** Paulo Bezerra · **Date:** 2026-10-18. This record applies to the earlier version only.

**User validation for Spec:** validated. **Statement:** "Feita correção na Feature; revise e corrija a Spec." **Name:** Paulo Bezerra · **Date:** 2026-10-18.

**Authorized scope amendment:** Paulo Bezerra authorized this amendment on 2026-10-18. It clarifies the treatment of duplicate local workflows, historical records, and human-facing documentation; the previous validation remains historical and does not validate this amended version.

## Business need

Maintainers of this project currently use SDD Lite from workflow commands and guidelines stored in this repository's `.specs/` directory, with separate discovery adapters for Codex, Claude Code, and Junie. SDD Lite now has a standalone GitHub repository distributed as a globally installable skill through `npx skills`. Keeping the workflow embedded in this application repository duplicates the independently maintained SDD tool and makes it harder to use its shared commands and improvements across projects. Project-specific operating knowledge is also mixed with the workflow itself, while the standalone skill provides dedicated project-context and implementation artifacts through `$sdd-lite init`.

## Desired outcome

SDD Lite continues as an independently maintained project in its own repository, and this repository follows the workflow and structure defined by its globally installed skill. The `.specs/` directory contains the artifacts prescribed by the skill alongside the immutable historical records for features 001–004, which remain exactly as they are even where their format differs from the current standard. Project-specific context and implementation conventions are recorded in `project-context.md` and `implementation.md`, created by `$sdd-lite init`. Duplicate local workflow definitions are removed, along with active adapters and instructions that direct agents to those flows. Human-facing documentation such as README and Home may explain the independent SDD Lite skill, provided it does not point to obsolete local instructions or define a competing workflow. References inside historical feature records 001–004 remain part of the historical record and are not current instructions.

## Boundaries and context

- **Included:** transitioning this repository to the workflow and directory structure of the independently maintained [paulobezerra/sdd-lite](https://github.com/paulobezerra/sdd-lite) project; recording project-specific context and implementation conventions in `project-context.md` and `implementation.md` created by `$sdd-lite init`; removing duplicate local workflow definitions and active adapters or instructions that direct agents to those flows; preserving feature records 001–004 exactly as immutable history, including references that remain part of those records; and allowing human-facing documentation such as README and Home to explain the independent skill without directing readers to obsolete local instructions or defining a competing workflow.
- **Out of scope:** merging the SDD Lite project into this application's repository or changing its technical architecture; changing the PHP application product or its deployment workflow; migrating unrelated repositories; and rewriting historical product decisions or validation statements.
- **Product constraints:** the global SDD Lite skill is already installed in the maintainer's environment. SDD Lite remains a separate project and the source of its shared workflow and artifact structure. The repository's PHP/Docker development setup remains independent of the skill installation. Project-specific knowledge needed by future SDD work belongs in the two artifacts created by `$sdd-lite init`. Feature records 001–004 are immutable historical records and remain exactly as they are even if their format differs from the current standard; references in them are not active instructions. Human-facing documentation may describe the independent skill, but must not direct agents or maintainers to obsolete local instructions or define a competing workflow.

## Success signals

- A maintainer can invoke the globally installed SDD Lite skill for this repository and complete the supported workflow stages against this repository's project context and records.
- Starting or continuing SDD work uses the standalone skill as the source of workflow instructions and artifact structure.
- Project-specific SDD knowledge is recorded in `project-context.md` and `implementation.md`; new workflow artifacts follow the standalone skill's supported structure alongside the immutable historical records 001–004.
- Duplicate local workflow definitions and active adapters or instructions that direct agents to those flows have been removed.
- A fresh SDD Lite session can recover this project's context and implementation conventions from `project-context.md` and `implementation.md` created by `$sdd-lite init`.
- Feature records 001–004 remain unchanged as historical records even where their format differs from the current standard; references in those records are not treated as current instructions.
- Human-facing documentation such as README and Home may explain the independent SDD Lite skill without pointing to obsolete local instructions or defining a competing workflow.

## Product questions
