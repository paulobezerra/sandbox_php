# FEATURE — Reliable production deployment

**ID:** 009-deploy-producao · **Status:** proposed · **Date:** 2026-10-19

**Branch:** `feature/009-deploy-producao`.

**User validation for Spec:** Paulo Bezerra validated this Feature by stating “concluir” on 2026-10-19.

## Business need

The project maintainer cannot reliably publish the application to the production site because the frontend needs a Vite production build, while the hosting server does not have Node.js available for builds over SSH. The production URL responds with the HTML shell, but its frontend references `localhost:5173`; browser checks confirm that the Vue application does not mount for visitors. The repository already has a shared-hosting deployment script that updates PHP dependencies and migrations on the server, but it does not build or publish the frontend assets. Maintaining a working production copy should not depend on building frontend assets directly on that server.

## Desired outcome

Pull requests run the required tests and cannot be merged while a check fails. Merging a change into `main` automatically publishes an application version with frontend assets built for Vite's production asset path and served from `public/assets/build/`.

## Boundaries and context

- **Included:** Required test checks for pull requests, merge blocking when a check fails, and automatic production publication after merge into `main`. Publication prepares the Vite frontend assets in the expected `public/` output location and updates the application at the specified hosting destination.
- **Out of scope:** Changing application features, production data, or the hosting provider and server setup.
- **Product constraints:** The target is `~/sites/sandbox-php` on the server. The user requested that the frontend build run before transfer and that the production public configuration and output path be correct. Current code defines `npm --prefix frontend run build` and writes assets to `public/assets/build/`; deployment must preserve that layout. The project context document still says there is no JavaScript build step, so it is stale relative to `origin/main` and should be reconciled in a future `$sdd-lite init` as allowed by project guidance.

## Success signals

- Pull requests run the required tests, and a failing check blocks merge into `main`.
- Merging into `main` automatically publishes the application without running a frontend build on the production server.
- After publication, visitors can load the application from the production domain, and its frontend does not depend on the visitor's `localhost:5173`.
