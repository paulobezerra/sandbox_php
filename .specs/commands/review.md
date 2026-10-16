# SDD Lite · Review

Audit a feature after its Code tasks or re-audit requested corrections. Read `AGENTS.md`, [workflow](../guidelines/workflow.md), [review](../guidelines/review.md), [quality](../guidelines/quality.md), the [template](../templates/review.md), Feature, Spec, and Plan. For the historical skeleton, use its existing artifacts. This step records evidence and corrections in `review.md`; it does not implement corrections in the same execution.

Before starting, apply the [multi-agent chat isolation rule](../guidelines/workflow.md#recommended-isolation-one-command-per-new-chat). If this conversation has already been used for another command, warn about the quality risk, recommend a new chat, and do not block the user. If they explicitly insist, proceed and state in the delivery that they authorized the exception. End after Review.

## 1. Fix the audit baseline

1. Identify the audited scope and check the Plan checklist, recorded evidence, and actual diff. **Review does not require validation of each Code iteration.** Separate pre-existing changes from feature changes. Confirm later decisions that replaced earlier contracts without rewriting Feature or Spec.
2. Check the local branch, `git status --short`, `HEAD`, and the remote when accessible. Record what could be verified; missing commit, push, or network access is an audit limitation, not an automatic publication task. Do not commit or push.
3. Trace requirement → task → code → test. Look for requirements without delivery, unchecked proof behind a checked box, divergent implementation, and contract regressions. Check affected boundaries as relevant.

## 2. Gather evidence proportional to risk

1. Run focused checks that can confirm or refute the main risks, subject to environment and authorization. Record command, result, and environment/date. Distinguish tests run now, direct code observations, evidence inherited from an earlier review, and inference. An old test is not a rerun; an unrun test is not a pass.
2. For a failure, record reproduction conditions and effect. For missing evidence, state what remains uncertain and whether it blocks acceptance; do not automatically call it a proven defect. Prioritize integrity and security, then contract and predictability.

## 3. Record the conclusion

1. Create `review.md` for the first audit; for re-audits, update that same file, preserving relevant IDs and decisions. Give each finding a stable ID, evidence, impact, sufficient correction, acceptance, and test. Reassess earlier findings in the file using current evidence. Do not commit or push during Review.
2. **Do not change Feature, Spec, or Plan.** Record proven and pending requirements in `review.md`; if there are no findings, record the conclusion and its limits. Do not ask the user to validate every correction or Code round. Deliver the link, state, and checks. Stop before Code.
