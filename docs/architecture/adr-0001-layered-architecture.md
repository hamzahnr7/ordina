# ADR-0001: Layered architecture with Repository interfaces instead of PDO in Controllers

## Context
ARCH-01 requires business logic to be independent of PDO/session/superglobals,
with a Controller -> Service -> Repository flow and Dependency Inversion at
the repository boundary, so that Service logic is unit-testable without a
real database.

## Decision
- Three pragmatic layers: `app/Controller` (HTTP/routing), `app/Service`
  (business rules), `app/Repository` (data access), dependency direction
  Controller -> Service -> Repository only.
- Each Repository is defined as an interface under
  `app/Repository/Contracts`, with a `Mysql*` implementation (real PDO) and an
  `InMemory*` implementation (fake, used only in `tests/Unit`).
- Services receive their Repository dependencies via constructor injection
  (no DI container - manual wiring in Controllers is enough per the brief's
  FAQ #2).

## Consequences
- Unit tests can exercise business rules (status transitions, stock math)
  without Docker/MySQL running.
- Adding a second Repository implementation (e.g. a caching decorator) is a
  drop-in replacement, not a rewrite.
- Slightly more files/boilerplate than "PDO calls directly in the
  Controller" - accepted deliberately per the brief's warning against
  under-engineering as much as over-engineering; this is the minimum
  structure needed to satisfy ARCH-01's testability requirement.
