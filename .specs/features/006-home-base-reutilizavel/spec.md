# SPEC — Present the home as a reusable developer foundation

**ID:** 006-home-base-reutilizavel · **Status:** proposed · **Date:** 2026-10-18

**User validation for Plan:** validated. **Statement:** "aprovar". **Name:** Paulo Bezerra · **Date:** 2026-10-18.

## Problem, baseline, and goal

The validated [Feature 006](feature.md) asks that a developer arriving at / understand sandbox_php as a reusable PHP/Slim/Vue foundation and find a practical route to install, inspect, and adapt it. On the current main baseline, Home.js already renders sections in Portuguese and English through the two JSON catalogs. Its first capabilities paragraph and list, however, mainly describe registration, login, and Posts. The page has no direct link to the repository root or the customization guide. Its checks section omits the combined test-all.sh command documented in README.md, and its local-only wording for .env conflicts with the README's production guidance.

The [Feature 002 review](../002-simplificacao-skeleton/review.md) is historical evidence for the example journeys and routes to preserve. Later changes on main added bilingual content, shared-hosting instructions, the standalone SDD Lite workflow, and project-context/implementation artifacts. The branch and origin/main were verified at the same commit, eb9daf9, on 2026-10-18. The current code and documentation, rather than the older review alone, define the baseline. The goal is to make the Home a clear developer entry point while retaining the example journeys.

## Scope and constraints

- **Included:** Home content, hierarchy, navigation and links in pt-BR and en; a small README correction only if needed for consistency; focused browser and documentation checks.
- **Out of scope:** changing Auth, Posts, API contracts, authorization, persistence, migrations, deployment behavior or architecture; replacing README.md or docs/customization.md; adding a language or JavaScript build step.
- **Constraints:** keep native ES modules and the existing Vue page/catalog structure; preserve complete matching locale keys; follow the current README, compose.yaml, composer.json, tools/, .specs/project-context.md and .specs/implementation.md. Do not portray missing password recovery or attempt limiting as existing features.

## Technical solution

**Technical decision:** The existing Home component and its two catalogs are the clearly indicated solution, confirmed against current code on 2026-10-18. Reorder and edit their content, add a prominent HTTPS link to https://github.com/paulobezerra/sandbox_php and a contextual HTTPS link to https://github.com/paulobezerra/sandbox_php/blob/main/docs/customization.md, and update relevant browser assertions. Correct README.md only for an actual inconsistency discovered during implementation. Estimated code impact: **low**, limited to the Home, catalogs, browser checks and possibly README.

**Reason for the decision:** The SPA already provides the page, localization and internal anchors. No local route serves docs/customization.md: unknown page paths reach the SPA not-found view, while /docs serves API documentation only when enabled. Direct repository links meet the Feature without a new route, dependency or deployment rule. Data integrity, API performance and migration concerns do not distinguish another in-scope design. The continuing maintenance cost is keeping both languages and command examples aligned with the README.

## Open questions for validation

None. Explicit validation of this completed Spec is still required before Plan.

## Requirements

- **S006-01 — Identity and hierarchy:** Given / loaded in pt-BR or en, the opening content presents sandbox_php first as a reusable PHP/Slim/Vue foundation serving a site and API from one deployment. It separates reusable capabilities, such as the layered structure, existing session protections, API, build-free frontend, explicit migrations and checks, from Auth and Posts as working examples. It does not claim absent demo features.
- **S006-02 — Repository and navigation:** In either locale, a prominent link with understandable text points directly to https://github.com/paulobezerra/sandbox_php. A contextual link points to https://github.com/paulobezerra/sandbox_php/blob/main/docs/customization.md. Internal links and Home anchors have existing destinations and do not promote removed institutional routes. External link availability is not required to mount or navigate the Home.
- **S006-03 — Local setup and first use:** Given Docker Engine and Compose v2, the Home explains cloning the repository, copying and editing .env with project credentials, starting the services, explicitly running composer migrate, checking migration status and opening http://localhost:8080. It identifies the active app container as the location for Composer commands. It presents registration, login, draft creation and publication as example journeys on existing routes. Instructions agree with README.md and do not imply automatic migrations or versioned secrets.
- **S006-04 — Structure and adaptation:** The Home explains the responsibilities of Domain, Application, Infrastructure, Bootstrap, public/assets, migrations and tests in terms consistent with current code. It directs developers to the customization guide before changing User/Auth or adding a domain, and states that model/contract changes require coordinated changes and a new migration for an existing installation.
- **S006-05 — Checks and operational limits:** The Home distinguishes composer format (writes files), composer lint, composer test, composer quality, composer migrate:status, tools/test-frontend.sh and tools/test-all.sh. It states the prerequisites: active app service for Composer; running application and host Chrome/Chromium for browser checks; test-all.sh combines browser checks and PHPUnit. Production/security notes stay consistent with README.md, including HTTPS, project secrets, controlled migrations and the demo's missing recovery and attempt limiting.
- **S006-06 — Locale and route compatibility:** Essential information and links in S006-01 through S006-05 appear in pt-BR and en with matching catalog keys. The Home and anchors mount in both locales. Home, Blog, Login, registration, My Posts, API and /docs when enabled retain their existing paths and behavior. No HTTP payload/status, session, CSRF, permission, persisted data or schema contract changes.

## Contracts, data, and security

The current Vue routes remain /, /blog, /blog/:slug, /login, /register and /my-posts. Server behavior for /api/* and /docs remains unchanged. New links are HTTPS destinations with local text; no remote content is injected into the DOM. A GitHub outage affects opening that external destination only. Instructions require replacing example passwords and keeping real .env files out of version control. No migration or data transition is involved.

## Acceptance and verification criteria

- **S006-01 / S006-06:** With the app running, open / in Chromium using en and pt-BR. Verify that Vue mounts, the foundation is introduced before the examples, the examples remain visible, and both catalogs expose matching keys. Extend the existing browser assertions for the new content.
- **S006-02 / S006-06:** Inspect the mounted DOM in both locales for accessible link text, the exact HTTPS destinations, valid Home anchor targets and absence of links to removed routes. Inspect repository paths for the external destinations; failure to fetch GitHub must not affect local rendering.
- **S006-03 / S006-05:** Compare commands, prerequisites and production/security wording with README.md, compose.yaml, composer.json and tools/. Verify explicit migration and the statement that format writes files. Documentation checks do not require running a migration or formatter.
- **S006-04:** Compare the structural description and customization advice with src/, README.md and docs/customization.md; verify that the guide destination corresponds to the tracked file.
- **S006-06:** Run a browser check with the application active. The current host script ./tools/test-all.sh is justified for this SPA content change and also runs PHPUnit; run it once at the appropriate implementation/review stage. Cover the affected Home content in both locales and existing example routes. Do not claim the browser or PHPUnit checks passed until they actually run.

**Applicable gate:** Focused review of content, links, command examples and diff, plus one browser run for the SPA boundary using ./tools/test-all.sh when the app and host browser are available. Its PHPUnit run supplies complete-suite evidence. An additional aggregate composer quality gate is justified only by a concrete implementation risk or a Plan requirement; it is not automatic for this content-only change. Record actual results and environment limits in Code/Review. No application tests were run during this Spec revision.

## Decisions and history

- 2026-10-18 — Feature 006 was validated with the goal of presenting the reusable foundation while preserving Auth/Posts examples. The existing localized Home supports a concentrated change without a new route.
- 2026-10-18 — Updated baseline assessment against main/origin/main at eb9daf9: project-context.md and implementation.md are present, the project artifact language is English, and the SDD Lite guidance is standalone. This revision replaces outdated baseline statements in the unvalidated Spec; it does not change the validated Feature or its product scope.
- 2026-10-18 — Verification is tailored to the changed browser/documentation boundary under the current project guidance. The historical Feature 002 review remains a regression reference, not proof of the future 006 implementation.
