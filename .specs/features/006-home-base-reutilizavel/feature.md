# FEATURE — Present the home as a reusable developer foundation

**ID:** 006-home-base-reutilizavel · **Status:** proposed · **Date:** 2026-10-18

**Branch:** `feature/006-home-base-reutilizavel`.

**User validation for Spec:** validated. **Statement:** "aprovar a feature". **Name:** Paulo Bezerra · **Date:** 2026-10-18.

## Business need

Developers evaluating `sandbox_php` need to understand quickly what they can reuse and how to start adapting it. The current home contains useful documentation, but its introduction and early feature list emphasize registration, login, and the Posts blog. This can make the project look like a finished blog application rather than a foundation for other PHP products. The README describes the reusable foundation more clearly, while the home lacks a direct link to the repository's main page.

## Desired outcome

The home presents `sandbox_php` first as a reusable PHP/Slim/Vue foundation for developers. In both Brazilian Portuguese and English, a visitor can identify what the foundation provides, reach the [GitHub repository](https://github.com/paulobezerra/sandbox_php), follow the local installation and startup path, understand the project's organization, find guidance for adapting it, and choose the appropriate checks. Registration, authentication, and the Posts blog remain visible as working examples of the foundation, with their existing behavior preserved.

## Boundaries and context

- **Included:** the home page's developer-facing content and navigation in `pt-BR` and `en`; a direct, usable link to the repository; a clear distinction between reusable capabilities and the Auth/Posts examples; practical installation, startup, organization, adaptation, and verification guidance aligned with the README and the customization guide; and any small README correction needed to keep those instructions consistent.
- **Out of scope:** changing registration, login, Posts, API contracts, authorization, persistence, migrations, deployment behavior, or the underlying application architecture; replacing the README or customization guide with the home; adding another language or a JavaScript build step.
- **Product constraints:** preserve the behavior verified in feature 002's review, including the existing Home, Blog, Login, registration, My Posts, API, and documentation paths; keep the browser language experience coherent across `pt-BR` and `en`; describe explicit migrations and checks accurately, including their prerequisites and the fact that formatting changes files. Do not present the demo's missing password recovery or attempt limiting as production-ready security features.

## Success signals

- A developer landing on `/` can tell from the opening content that this is a reusable application foundation and that Auth and Posts demonstrate how to extend it.
- In either supported language, the developer can open the repository directly from the home and find the steps to install, start, inspect, and adapt a local copy without conflicting instructions between the home and README.
- The home explains the project's main areas and directs the developer to the customization guidance and available verification commands, with enough context to choose and run them.
- Existing example journeys and routes described in the feature 002 review remain available and are still represented accurately on the home.
