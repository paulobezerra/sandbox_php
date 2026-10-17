# Review guidelines

Review checks the delivered behavior against the validated Feature, Spec, and Plan. It reads actual diffs and execution evidence; it does not redo Code or silently repair historical decisions.

## Audit principles

- Check task checkboxes against implementation and tests. A checked box alone is not proof; a failed or unrun gate remains pending.
- Separate direct observation, test results, inherited earlier evidence, and inference. Record the commands and environment that produced evidence.
- Inspect only the branch, commit, diff, and remote state that can be verified. Missing publication evidence is a limit, not an automatic push request. Review never commits or pushes.
- Prioritize data integrity and security, then HTTP/API contracts, compatibility, and predictable maintenance. Report reproducible conditions and concrete effects.
- Do not call missing evidence a proven defect. State the uncertainty and whether it prevents acceptance.

## Findings and re-audits

Use one `review.md` per new feature. Give each finding a stable ID and record evidence, impact, the smallest sufficient correction, acceptance, and a verification method. Re-audit corrections in the same file, preserving IDs and historical decisions while updating status and current evidence. Do not change Feature, Spec, or Plan during Review.
