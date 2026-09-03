# Tech-Debt Register

Per DESIGN-03: record shortcuts taken because of time constraints, honestly,
with the ideal fix noted. Update as the project progresses - do not let this
go stale.

| # | Area | Shortcut taken | Why | Ideal fix | Risk if unresolved |
|---|------|-----------------|-----|-----------|----------------------|
| 1 | Seed data | Only ~4 products / 0 orders seeded so far | Schema was being sanity-checked before business logic existed | Expand to >=30 products, >=25 PO+SO before final submission (§7.1) | FIND-01/pagination demo will look empty |
| 2 | Auth | `AuthController::login()` redirects without checking credentials yet | Scaffolding stage - AuthService not written | Implement `password_verify()` check, session regen, generic failure message (AUTH-01) | Login is not actually enforced |
| 3 | Router | No wildcard/method-not-allowed handling, only literal + `{param}` segments | Kept minimal for template stage per brief's "no framework" + "no over-engineering" guidance | Add a 405 response path if two methods share a pattern | Low - current route set doesn't collide |
