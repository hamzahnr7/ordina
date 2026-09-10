# ADR-0004: Fixed Role enum + static Permission map, not a dynamic roles/permissions database

## Context
The application needs three things to work together: (1) a `users.role`
column, (2) server-side enforcement of the brief's §1.2 segregation-of-duties
table (e.g. Sales can never approve their own Sales Order), and (3) a nav
menu that only shows what a role can actually do. A natural-looking
alternative was a fully dynamic RBAC schema (`roles`, `permissions`,
`role_permissions` tables) with an admin UI to create roles and assign menu
access at runtime.

## Decision
Use a static, code-level model instead:
- `App\Domain\Role` - a PHP backed enum with exactly the three values the
  brief allows (`Admin`, `Sales`, `WarehouseStaff`), matching the
  `users.role` ENUM column 1:1.
- `App\Core\Authorization\Permission` - one enum case per protected action
  from §1.2 (e.g. `ApproveSalesOrder`, `ProcessGoodsIssue`).
- `App\Core\Authorization\Gate::ROLE_PERMISSIONS` - the single Role ->
  Permission[] map, consulted by both `Controller::authorize()` (real
  enforcement) and `MenuRegistry` (nav visibility), so they can't drift.

Full design + "how to extend" steps: `docs/architecture/rbac-and-menu-access.md`.

## Consequences
- Adding a role or a new protected action is a code change + PR (with a
  `GateTest` case proving the new rule), not a runtime admin action -
  intentional, since the brief treats the three roles as fixed ("Nilai
  tetap") and requires SoD to be enforced on the server, not just hidden in
  a UI that itself could be reconfigured.
- No `roles`/`permissions` tables, no admin screen for assigning menu access
  - avoids exactly the kind of unjustified extra layer the brief's
  "Peringatan" section marks down as over-engineering.
- Trade-off: a genuinely dynamic-roles requirement later would mean
  migrating this enum-based map into a database-backed one; acceptable
  because the brief scopes roles as fixed for this project.
