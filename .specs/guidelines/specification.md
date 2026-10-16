# Specification guidelines

Spec turns a validated product Feature into a technical contract. It does not implement the feature or divide it into Plan tasks.

## Understand before choosing

- Read the Feature, repository rules, and the current code/tests/contracts directly affected. Separate verified current behavior, assumptions, and the requested result.
- Clarify only missing product decisions that change the behavior, permissions, data, compatibility, or boundaries. Do not invent a contract from silence.
- Present real technical alternatives only when meaningful tradeoffs exist. Include retaining the current design when viable; do not fabricate options or rank them mechanically. If one solution is clearly indicated, briefly justify it with repository evidence and proceed.
- For each useful option, state low/moderate/high code impact, affected components, concrete costs and risks, and relevant security, data, performance, compatibility, maintenance, and operational differences.
- When the user must decide between real options, wait for their explicit choice before writing the final Spec. A recommendation is not approval.

## Write a verifiable contract

- Give requirements stable IDs and describe initial state, action, observable result, relevant failures, and data that must remain unchanged.
- Define routes, payloads, status codes, permissions, session/CSRF behavior, persistence, migrations, and consumers only to the level needed for acceptance.
- Link every requirement to a verification appropriate to its risk. Include browser or database integration when unit tests cannot prove the boundary.
- Keep Feature immutable. Record later decisions that diverge from it in Spec with source, impact, and explicit user validation; do not silently rewrite the original requirement.
- Record the technical decision and reasoning, then leave validation for Plan pending until the user explicitly validates the Spec. The choice itself does not count as that validation.
