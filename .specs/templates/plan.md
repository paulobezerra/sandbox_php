# PLAN — <feature name>

**Feature:** [feature.md](feature.md) · **Spec:** [spec.md](spec.md) · **Date:** YYYY-MM-DD · **Status:** open

**User validation to start Code:** pending. After an explicit statement, record it here with the user's supplied name and the date obtained from the OS, without changing task scope.

## Delivery strategy

<order and dependencies; how each increment delivers usable behavior without breaking existing behavior>

## Tasks

- [ ] **TNNN-01 — <delivered functional outcome>.** **Requirements:** <SNNN-XX covered>. **Precondition:** <required state>. **Scope:** <complete path, including relevant data/API/SPA/docs>. **Acceptance:** given <state>, when <action>, then <observable result and relevant failures>. **TDD/characterization:** <test expected to fail before the change; legacy behavior to preserve, if any>. **Gate:** <focused tests and quality.md>. **Dependencies:** <IDs or none>.

<Repeat the full format for each task. Do not add tasks for branches, isolated scaffolding, or formatting.>

## Risks and final verification

<migration, compatibility, security, operations, or browser behavior that needs an integrated test on feature completion; omit if tasks already cover it>

<Before Code, check that every Spec requirement appears in at least one task and every task delivers an identifiable requirement. Record only real gaps or dependencies between increments.>

<Code marks only an item completed after acceptance and gates are verified. Before the next task, also check predecessor diffs, code, and tests; a checkbox alone is insufficient. Do not create per-task execution records.>
