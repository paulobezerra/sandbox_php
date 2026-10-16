# Verified implementation conventions

These conventions summarize decisions proven in [review-001](../features/001-skeleton/review-001.md) and [review-002](../features/001-skeleton/review-002.md). Use them as a starting point for new features; the feature Spec defines the contract and justifies exceptions. `AGENTS.md` remains the repository rule, and [quality.md](quality.md) defines the gates.

## Boundaries and composition

- Entities protect invariants during database hydration too. Repository contracts belong in Domain; PDO implementations belong in Infrastructure. Application coordinates use cases and carries no HTTP status; the HTTP adapter parses input, calls the service, and translates errors. Verified in R001-08/09/10.
- Compose modules through explicit bindings and route sources in Bootstrap. A new domain is added through traceable files and bindings; it does not require changing unrelated connection internals, controllers, or repositories. Default composition and the optional legacy module were exercised in R001-06.
- Expose only methods with route attributes. Keep unknown API routes as JSON 404 and use the SPA fallback for pages. If routes are cached, the cache key must reflect the files actually registered and their contents, including optional composition (R001-07).
- Reserve static route slugs before accepting dynamic Page URLs; test collisions when adding an institutional page or root route (R001-17).

## HTTP contracts and security

- Distinguish malformed JSON (400), a body of the wrong type/invalid fields (422), and a missing field. Do not convert invalid input to `[]`, an empty string, or a different slug without an explicit contract choice. Map application errors to HTTP in the adapter while preserving documented responses (R001-08/09).
- Every routed mutation requires CSRF by default. Anonymous exceptions are exact method/path pairs declared in composition and do not exempt authenticated sessions from CSRF. Also check the unknown `/api/*` response (R001-05).
- Check authorization on the backend for every protected operation; the router/frontend only improves navigation. Public registration does not assign ADMIN. Regenerate sessions after login and revoke them according to credential policy; tests with two sessions demonstrate password/code changes (R001-16).
- Recovery secrets use sufficient randomness, are shown once, stored as hashes, and consumed once. Consumption and password change form a transaction: a write failure must not lose the secret, and success must prevent reuse. Test concurrency and induced database failure (R001-01/02).
- Rate limits cover all sensitive endpoints and consider origin and account without revealing whether an account exists. Use `REMOTE_ADDR` as the basis; accept `X-Forwarded-For` only from configured proxies and process the chain correctly. For `Secure` cookies, use the validated public HTTPS URL, not an untrusted forwarded header. Check local HTTP configuration and isolated TLS proxy behavior (R001-03/04).
- Render Markdown with raw HTML escaped and unsafe links blocked. `v-html` receives only trusted `MarkdownRenderer` output. CSP must reflect the no-build Vue runtime; any relaxation such as `unsafe-eval` needs a reason and XSS test (R001-13).

## Data, frontend, and published contracts

- Use parameterized queries. Schema changes for existing installations use a new additive migration when possible, with a data preservation test. Do not edit an applied migration to change behavior; check empty and existing installations when onboarding/distribution is affected (R001-01/15/18).
- Keep native ES modules and local vendors; do not add a build step. Separate HTTP calls into `services/` and screens into `pages/`. The Fetch client should report network failure, refresh CSRF after 419, and retry a mutation at most once; verify this in a browser (R001-12).
- Update OpenAPI when routes, bodies, responses, authentication, or cookies change. Generate the artifact after source changes and check it against routes. Generated documentation does not replace an HTTP flow test (R001-11/14).

These practices do not require every Spec to implement Auth, CMS, or every test described here. Select scenarios that cross the changed boundary and record in the acceptance criteria why each is needed.
