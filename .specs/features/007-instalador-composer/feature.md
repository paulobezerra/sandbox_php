# FEATURE — Start a reusable project from Composer

**ID:** 007-instalador-composer · **Status:** proposed · **Date:** 2026-10-18

**Branch:** `feature/007-instalador-composer`.

**User validation for Spec:** validated. **Statement:** "aprova a feature". **Name:** Paulo Bezerra · **Date:** 2026-10-18.

## Business need

Developers who start from this PHP skeleton currently need to clone its repository, inheriting the full development history and project-specific setup. That makes it harder to treat the code as a clean foundation for a new application and leaves developers to prepare repository hygiene themselves.

## Desired outcome

A developer can use Composer to create a new project from the maintained skeleton, receive its application source and SDD Lite workflow, and begin work in a fresh Git repository with suitable ignore rules. The new project is ready for its owner to customize without carrying the skeleton's development history.

## Boundaries and context

- **Included:** a Composer-based installation path for creating a new project; delivery of the reusable application source and SDD Lite materials; initialization of a new Git repository with appropriate ignore rules; clear first-use guidance.
- **Out of scope:** changing the skeleton's existing Auth/Posts behavior or architecture; automating deployment, database creation, or production configuration; synchronizing future upstream changes into projects already created.
- **Product constraints:** preserve the PHP 8.4, Slim 4, Vue 3 native ES module, and no-build-step constraints; generated projects must not inherit the skeleton's repository history or committed secrets.

## Success signals

- A developer can follow the documented Composer flow to create a working project without manually copying source files.
- The created project contains the reusable application and SDD Lite workflow, starts with a fresh Git history, and ignores local environment and generated/dependency files appropriately.
- The setup guidance makes the first steps and required environment configuration clear, while leaving project-specific customization under the developer's control.

## Product questions

- Which project naming and destination options should the Composer installation support, and how should it behave when the destination already contains files?
- Should SDD Lite be copied into each generated project as local workflow files, or installed as a separately managed dependency?
