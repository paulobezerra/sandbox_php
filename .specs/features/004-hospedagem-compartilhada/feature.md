# FEATURE — Production configuration for shared hosting

**ID:** 004-hospedagem-compartilhada · **Status:** proposed · **Date:** 2026-10-16

**Branch:** `feature/004-hospedagem-compartilhada`.

**User validation for Spec:** validated. **Statement:** "aprovado". **Name:** Paulo Bezerra · **Date:** 2026-10-17.

## Business need

Operators deploying the application to shared PHP hosting, such as cPanel, cannot rely on Docker Compose to load `.env` and pass its values to PHP. In this environment PHP may not receive the database settings, and the application stops with `DB_DATABASE is required`. The operator currently has no provider-independent way to supply production configuration through the repository's deployment guidance. Updating production also requires coordinating the Git checkout, Composer dependencies, and database migrations by hand, which can leave a release incomplete.

## Desired outcome

An operator can deploy the application to a typical shared PHP host and provide the required production settings using a documented, portable process that works without relying on provider-specific manual environment setup. A repeatable installer updates the server's checkout from the remote `main` branch, brings installed dependencies up to date for that revision, and applies pending database migrations. The application starts with the configured database and other required production services, while secrets remain private and outside the public web root.

## Boundaries and context

- **Included:** production configuration for shared PHP hosting where PHP does not inherit values from Docker Compose or a provider-managed environment; guidance for securely supplying required settings and diagnosing missing configuration; a shell installer that checks for PHP 8.4, Git, and Composer, pulls the remote `main` branch into the server's local checkout, runs Composer to install or update dependencies, and applies pending database migrations. The installer can use portable deployment configuration, including `.env` when appropriate, to select the PHP executable and other necessary settings, and can run non-interactively when invoked by future SSH automation.
- **Out of scope:** automating hosting-account creation or SSH access; building a GitHub agent or its orchestration; authoring new database migrations; changing the Docker-based local development workflow; provider-specific integrations.
- **Product constraints:** preserve the existing Docker Compose workflow and production security expectations. Configuration must not expose credentials through the public web root or repository. The installer must report failures clearly and must not hide an incomplete deployment as a success.

## Success signals

- An operator following repository guidance can deploy to a standard shared PHP host without configuring variables through provider-specific control panels or scripts.
- With valid production settings supplied by the documented process, PHP can connect to the configured database and serve the application.
- Missing or invalid settings produce a useful diagnostic without exposing secrets.
- The installer clearly reports missing or incompatible PHP, Git, or Composer requirements. An operator can select a suitable PHP executable through documented, portable configuration when the host offers more than one PHP version.
- With prerequisites and settings available, one non-interactive invocation pulls `main`, brings installed Composer dependencies into line with that revision, and applies any pending migrations. A repeated invocation with no new changes completes safely.
- A future SSH-based automation can invoke the installer and determine whether every deployment step succeeded from its process result and output.
- Existing Docker-based local and production deployments continue to use their current configuration process.
