# FEATURE — Behavioral path coverage for PHP and frontend code

**ID:** 011-cobertura-testes · **Status:** proposed · **Date:** 2026-10-20

**Branch:** `feature/011-cobertura-testes`.

**User validation for Spec:** Paulo Bezerra approved the revised Feature by stating “aprove” on 2026-10-21. Previous validation: Paulo Bezerra stated “validei a feature, continue” on 2026-10-20.

## Business need

Project contributors and maintainers need confidence that changes to PHP, Vue, and JavaScript are tested across the behaviors and execution paths they can produce. Line coverage alone can report code as covered while important branches, outcomes, and failure paths remain untested. The project also needs a clear standard for AI agents so new work and behavior-preserving refactors receive appropriate tests.

## Desired outcome

Agents and contributors follow a project-wide testing standard that recommends 100% line coverage for PHP and frontend code. New behavior is developed with tests first whenever practical, and behavior-preserving refactors use characterization tests to capture existing results. CI enforces at least 95% line coverage and 85% branch coverage separately for both backend and frontend; falling below either threshold fails CI.

## Boundaries and context

- **Included:** Testing expectations for all PHP, Vue, and JavaScript code; guidance for test-first development and characterization tests during behavior-preserving refactors; separate line and branch coverage minimums for backend and frontend CI.
- **Out of scope:** Changing application behavior or HTTP contracts; treating line coverage alone as sufficient evidence that relevant behavior paths are tested.
- **Product constraints:** 100% line coverage is the recommendation for agents; no 100% branch coverage target is set. CI must fail if either backend or frontend falls below 95% line coverage or 85% branch coverage. The standard applies to both existing and new code as it is changed or maintained.

## Success signals

- Contributors and AI agents are advised to test all relevant PHP, Vue, and JavaScript behavior, targeting 100% line coverage.
- New behavior is ordinarily accompanied by tests written before implementation; behavior-preserving refactors have characterization tests showing that externally observable results remain the same.
- CI reports backend and frontend line and branch coverage independently and fails if either layer is below 95% line coverage or 85% branch coverage.
- Tests exercise relevant alternate outcomes and failure paths; line coverage alone is not presented as proof of behavioral coverage.

## Post-validation decision

- **Source:** Paulo Bezerra's statements “podemos abrandar um pouco a cobertura, vamos manter 95% de linhas e 85% de branchs no CI e 100% a recomendação para os agentes” and “100% de linhas, não de branch para os agentes” on 2026-10-21.
- **Divergence:** The previously validated Feature set a 95% CI threshold without distinguishing line and branch coverage and described 100% path coverage as the target. The revised decision sets separate 95% line and 85% branch minimums for each layer, and recommends 100% line coverage for agents without a 100% branch target.
- **Impact:** Spec must define how backend and frontend line and branch coverage are measured and enforced. The changed Feature is pending explicit revalidation before Spec.
