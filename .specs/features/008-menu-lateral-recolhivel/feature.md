# FEATURE — Collapsible sidebar menu

**ID:** 008-menu-lateral-recolhivel · **Status:** proposed · **Date:** 2026-10-19

**Branch:** `feature/008-menu-lateral-recolhivel`.

**User validation for Spec:** validated. **Statement:** "aprovar". **Name:** Paulo Bezerra · **Date:** 2026-10-19.

## Business need

People using the application need more room for the page they are viewing and a clear way to reach the existing navigation. The sidebar is currently always visible and takes up part of the screen.

## Desired outcome

The sidebar starts hidden. A hamburger control lets a user show it, and an X control lets the user hide it again. The user can reveal navigation when needed and return the screen space to the current page afterward.

## Boundaries and context

- **Included:** visibility controls for the existing sidebar; hamburger and X icons supplied through an icon library; hidden initial state.
- **Out of scope:** changing navigation items, routes, or the destination pages; changing authentication or authorization behavior.
- **Product constraints:** preserve the existing PHP and Vue application without introducing a JavaScript build step.

## Success signals

- On initial page load, the sidebar is hidden.
- The hamburger control reveals the sidebar, and the X control hides it.
- Users can still access the existing navigation destinations after opening the sidebar.
