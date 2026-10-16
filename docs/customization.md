# Customization and new domains

This repository is an application base that can be adapted to a derived product. The default installation includes registration, login, logout, and Posts: each authenticated account manages only its own Posts. There are no user profiles, password recovery, or authentication attempt limits. Define abuse controls and a recovery procedure before exposing a derived application publicly.

## Adapt User and Auth

`src/Domain/User/User.php` contains the basic identity and normalized email; `src/Application/Auth/AuthService.php` implements registration, login, and logout. To add product-specific data, prefer a related profile entity when that keeps authentication independent from the CMS. If the project needs a different identity, adapt User/Auth and their contracts together.

When adapting:

1. Keep `password_hash`/`password_verify`, server-side sessions, session regeneration at login, secure cookies, and CSRF on authenticated mutations.
2. Define an account recovery procedure with identity verification and abuse controls suited to the environment. The demo does not provide recovery or attempt limits.
3. Update the User contract, its PDO implementation, migration, and tests together. Do not put SQL in a controller or add a PDO dependency to Domain/Application.
4. Update the OpenAPI schemas when the API changes, then run `composer openapi:generate` and `composer quality`.

## Add a domain

Entities and persistence contracts belong in `src/Domain/<Name>/`; application operations belong in `src/Application/<Name>/`; MySQL implementations belong in `src/Infrastructure/Database/Repository/`. A persisted domain exposed through the API also needs a migration, Bootstrap binding, controller with explicit route attributes, and tests. Keep contracts in Domain and adapters in Infrastructure; do not change connection internals, controllers, or repositories from other domains to accommodate the new entity.

## Conventions

- Entities protect invariants; application services coordinate contracts and use-case rules.
- Use parameterized PDO queries and explicit Phinx migrations. For an existing installation, create an additive migration and preserve the data the project needs.
- Slim controllers are thin HTTP adapters. Only methods with route attributes are exposed.
- Add unit tests for invariants and use cases; use integration tests when a change involves SQL, HTTP, sessions, or configuration.
- Do not create an interface for every service, generic plugin abstractions, or empty directories. Use the shortest path that preserves the boundaries above.
