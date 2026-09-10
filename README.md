# Ordina - Inventory & Order Management System

Final project for Neuronworks' Intermediate Programmer program. Web app for
multi-warehouse inventory, purchase orders, and sales orders across three
roles (Admin, Sales, Warehouse Staff). See `Project Brief - Programmer.pdf`
for the full specification this repo implements.

> **Status: auth, user management, and master data implemented; PO/SO
> workflows still pending.** Login/session, role-based authorization, User
> management (USR-01), and Category/Warehouse/Supplier/Customer/Product CRUD
> with search/filter/pagination (PRD-01, WH-01, FIND-01) are done. Purchase
> Order, Sales Order, dashboard aggregation, and reports are not implemented
> yet - see `docs/quality/tech-debt.md` and the backlog in
> `docs/planning/user-stories.md`.

## Tech stack
- PHP 8.2+ native OOP (Controller/Service/Repository/Entity), no framework
- Vanilla JS + Fetch API, hand-written CSS
- MySQL 8 (PDO, prepared statements, explicit transactions)
- Docker Compose (`web` + `mysql`, plus optional `redis` for sessions only -
  see `docs/architecture/adr-0003-redis-session-store.md`)
- PHPUnit (unit + integration), PHPStan (static analysis)

## Getting started
```bash
cp .env.example .env
docker compose up --build
```
The `mysql` service auto-runs `database/schema-and-seed.sql` on first boot
(empty volume). App will be at http://localhost:8080.

Generate real password hashes for the seed users before relying on them:
```bash
docker compose exec web php scripts/hash-password.php "YourPassword123"
# paste the output over the REPLACE_WITH_GENERATED_HASH placeholders in
# database/schema-and-seed.sql, then recreate the mysql volume to reseed.
```

### Demo accounts (after regenerating hashes)
| Role | Email |
|------|-------|
| Admin | admin@ordina.test |
| Sales | sales1@ordina.test / sales2@ordina.test |
| Warehouse Staff | wh1@ordina.test / wh2@ordina.test |

## Auth, roles & menu access
Login (`/login`), session-based auth, and role-based menu/authorization are
implemented: `AuthController` + `AuthService` (AUTH-01), `UserController` +
`UserService` for Admin-managed Sales/Warehouse Staff accounts (USR-01), and
a `Role -> Permission -> Menu` layer (`app/Domain/Role.php`,
`app/Core/Authorization/`, `config/menus.php`) that both drives the
dashboard nav and enforces every protected action server-side. Full
explanation - what each `users` column is for, how Role/Permission/Gate/Menu
fit together, and the exact steps to add a new role or a new menu/permission
- is in `docs/architecture/rbac-and-menu-access.md` (see also
`adr-0004-rbac-fixed-roles-vs-dynamic.md`).

## Master data (PRD-01, WH-01, FIND-01)
Admin-only CRUD for Category (`/categories`), Warehouse (`/warehouses`),
Supplier (`/suppliers`), Customer (`/customers`), and Product (`/products`) -
all soft-deactivate only, never hard-delete. Product additionally supports:
- Search by name/SKU, filter by category and stock status (low/normal),
  10-per-page pagination (`ProductService::paginate()` /
  `MysqlProductRepository::paginateForListing()`).
- Optional image upload validated by type/size and stored under a random
  filename (`ProductImageUploader`), never the original client filename.
- A detail page (`/products/{id}`) showing stock broken down per warehouse,
  reusing the same `ProductStockRepositoryInterface` the `API-01` endpoint
  uses.
- `/products` itself is reachable by all three roles (Admin manages it,
  Sales views the catalog, Warehouse Staff checks stock) via
  `Controller::authorizeAny()` - see `docs/architecture/rbac-and-menu-access.md`.

## Tests & static analysis
`tests/Integration` (TEST-02) connects to a separate `ordina_test` database
with its own `tester` credentials (see `.env.example`'s `DB_TEST_*` vars),
provisioned by `database/test-db-init.sql` - kept apart from the `ordina` dev
database so integration tests never touch dev/demo data.

```bash
docker compose exec web composer install   # first time / after dependency changes
docker compose exec web composer test              # unit + integration
docker compose exec web composer test:unit
docker compose exec web composer test:integration
docker compose exec web composer analyse            # PHPStan level 5
```

`test-db-init.sql` runs automatically on a **fresh** `mysql` volume (same as
`schema-and-seed.sql`). If you already had the stack running before this
file existed, apply it by hand once instead of recreating the volume:
```bash
docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < database/test-db-init.sql
```

## Scheduled script (JOB-01)
```bash
docker compose exec web php scripts/check-low-stock.php
```

## Project structure
```
public/                 entry point & static assets
app/Controller/         HTTP/routing
app/Service/            business rules
app/Repository/         Contracts (interfaces), Mysql (real), InMemory (test fakes)
app/Entity/             plain domain objects
app/Core/               router, DB/session/config wiring shared by all layers
views/                  PHP templates
config/                 typed config derived from .env
database/               schema-and-seed.sql
scripts/                standalone CLI scripts (cron-style jobs, utilities)
tests/Unit/             no DB/session, uses InMemory repositories
tests/Integration/      real MySQL in Docker
docs/planning/          user stories, ERD, initial class diagram
docs/architecture/      as-built class diagram, ADRs
docs/quality/           refactor log, tech-debt register, critique, static analysis report
docs/testing/           test scenarios & results
```

## Known limitations
- PO/SO business logic, dashboard aggregation (DASH-01), reports, and the
  ARCH-02 concurrency mechanism are not implemented yet.
- Seed data is intentionally small; must grow to the §7.1 minimums (30
  products, 25 combined orders, 2 warehouses, 2+2 non-Admin accounts)
  before final submission.
- No password-reset action for existing users yet (see `docs/quality/tech-debt.md`).
- Replacing a product's image on edit doesn't delete the old file from
  `public/uploads/products/` yet (tracked in `docs/quality/tech-debt.md`).

## AI usage
Disclosed in `ai-usage-log.md`.
