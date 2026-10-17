# FEATURE — English baseline and browser-aware localization

**ID:** 003-globalizacao · **Status:** proposed · **Date:** 2026-10-16

**Branch:** `feature/003-globalizacao`.

**User validation for Spec:** validated. **Statement:** "validar feature". **Name:** Paulo Bezerra · **Date:** 2026-10-16.

## Business need

People using the skeleton currently encounter Portuguese text throughout the site, API messages, documentation, and source files. This limits the usefulness of the project for English-speaking visitors and maintainers. Portuguese-speaking visitors should still be able to use the application without changing the language of the source repository. The current page declares `pt-BR`, and visible application text is written directly in the page template, Vue pages, and PHP responses; no language preference behavior is evident in the inspected product.

## Desired outcome

An installation presents its own interface and user-facing messages in English by default. When a visitor's browser prefers Portuguese, the same product experience appears in Portuguese automatically. The languages and fallback behavior are configurable for projects derived from the skeleton, while English remains the supplied default. A visitor sees a coherent language across navigation, page content, forms, feedback, errors, page metadata, and API messages that the application displays. The language declared by the page matches the language shown.

Repository-owned source code and maintainer-facing material are in English, including comments, examples, configuration descriptions, Dockerfile comments, README and other Markdown files, and API reference prose. This applies to active and historical project documentation while preserving the meaning, decisions, and validation records of historical artifacts. Text intended for Portuguese visitors remains available as translation content.

## Boundaries and context

- **Includes:** English and Portuguese for application-owned UX text in the public site, authentication and Post flows, relevant API feedback, accessibility labels, metadata, and application documentation shown on the site; configurable language support and fallback for derived installations; English wording in repository-owned code, comments, configuration examples, scripts, tests, operational documentation, and SDD material.
- **Out of scope:** automatic translation of visitor-authored Posts, stored user data, third-party dependencies, and generated or externally owned files. Adding languages beyond English and Portuguese is a future product choice.
- **Product constraints:** preserve existing product flows, permissions, data, and API structure. Language selection must not depend on a user account. A browser with no usable preference, or with an unsupported preference, receives the configured fallback; the supplied fallback is English. Translation must preserve the meaning of technical instructions and historical records rather than silently changing decisions.

## Success signals

- A new visitor with an English or unsupported browser preference can use the site and understand its messages in English without changing settings.
- A visitor whose browser prefers Portuguese can use the same flows and read application feedback in Portuguese, including errors shown by the interface.
- The language shown in the page and its declared language stay consistent across navigation and authentication state changes.
- A project maintainer can configure the supported language behavior and can read repository-owned documentation and source guidance in English, including the Dockerfile.
- Existing authentication, Post management, and public reading behavior still work in both supported languages.

## Product questions

- Should a visitor also be able to override the browser preference from within the application, with that choice remembered for later visits? The requested automatic browser-based behavior does not establish a manual override or its precedence.
