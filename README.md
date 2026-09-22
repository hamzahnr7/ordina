# Ordina - Order Management & Inventory System

Final project for Neuronworks' Intermediate Programmer program. Web app for
multi-warehouse inventory, purchase orders, and sales orders across three
roles (Admin, Sales, Warehouse Staff). See `Project Brief - Programmer.pdf`
for the full specification this repo implements.

> **Status: every WAJIB functional requirement in the brief's §2 is
> implemented.** Login/session, role-based authorization, User management
> (USR-01), master data CRUD with search/filter/pagination (PRD-01, WH-01,
> FIND-01), Purchase Order + goods receipt (PO-01), Sales Order + approval +
> goods issue with concurrency-safe oversell prevention (SO-01, ARCH-02),
> per-role dashboard aggregation (DASH-01), and date-ranged CSV reports
> (REPORT-01) are all done. What's left is polish and evidence, not
> features - see `docs/quality/tech-debt.md` and the backlog in
> `docs/planning/user-stories.md` for the honest remaining list (seed data
> volume, a few TEST-02 integration tests, the refactor log/static analysis
> report content, etc.).

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

## Purchase Order & goods receipt (PO-01, ARCH-02)
Admin and Warehouse Staff (Sales has no PO permission at all) can create a
Purchase Order (`/purchase-orders`) as a `Draft`, send it to the supplier
(`Ordered`), and process goods receipt against it - partial or full. FIND-01
applies here too: search by PO number/supplier, filter by status, sort by
order date, 10-per-page pagination.

Goods receipt writes `ProductStock` + `StockLedger` (+ recomputes the PO's
status) inside one real transaction, via a new
`App\Core\Transaction\TransactionManagerInterface` seam - `PdoTransactionManager`
in production, `NullTransactionManager` in `tests/Unit/PurchaseOrderServiceTest.php`
so the whole flow (partial receipt, full receipt, over-receipt rejection,
status transitions) is unit-tested without touching MySQL. See
`docs/architecture/adr-0002-concurrency-safe-stock.md` for why PO's receipt
only needed atomicity, not the concurrency mechanism ARCH-02 asks for -
that's SO-01's job, below.

## Sales Order, approval & goods issue (SO-01, ARCH-02)
Sales creates a Sales Order (`/sales-orders`) as a `Draft` and submits it for
approval (`PendingApproval`); Admin approves or rejects it - **never their
own order**, enforced server-side in `SalesOrderService::approve()`
regardless of role (mirrors the `chk_so_approver_not_creator` CHECK
constraint, checked here first for a clean error message); Warehouse Staff
(or Admin) processes goods issue once `Approved`, moving it to `Fulfilled`.
Sales only ever sees/acts on their own orders (§1.2 "milik sendiri") -
`SalesOrderController::assertOwnsOrAdmin()`.

**This is where ARCH-02's oversell prevention is actually load-bearing**
(unlike PO's goods receipt, which can only ever increase stock):
`ProductStockRepositoryInterface::decrementIfAvailable()` does the
check-and-decrement as one atomic conditional `UPDATE`
(`... WHERE quantity >= :qty`), relying on InnoDB's row lock during that
single statement to make it race-free - see
`docs/architecture/adr-0002-concurrency-safe-stock.md` for the full
reasoning. `processGoodsIssue()` loops every item inside one transaction and
rolls back entirely the moment any item's stock is insufficient - proven by
`tests/Unit/SalesOrderServiceTest.php`'s controlled, sequential scenario
(first order exhausts stock, second order's goods issue is rejected, not
oversold - real parallel threads aren't required per the brief).

## Dashboard (DASH-01)
Every number on `/dashboard` comes from a live aggregation query via
`DashboardRepositoryInterface`/`MysqlDashboardRepository` - never a static
value:
- **Admin**: total inventory value (stock × buy_price), count of products
  below reorder point (+ a preview list), Purchase Order counts per status,
  Sales Order counts per status (all orders).
- **Sales**: their own Sales Order counts per status only
  (`salesOrderStatusCounts($ownerId)`).
- **Warehouse Staff**: pending goods-receipt count (POs `Ordered`/`PartiallyReceived`),
  pending goods-issue count (SOs `Approved`), and the same low-stock preview
  Admin sees.

## Reports (REPORT-01)
`/reports` - date range picker (`from`/`to`), then a button per report the
signed-in role may download (mirrors §1.2's "Mengunduh laporan" row exactly
via `ReportController::index()`'s `canStock`/`canOrders` flags):
- **Stock ledger CSV** (`/reports/stock-ledger.csv`) - every `StockLedger`
  movement in the range. Admin and Warehouse Staff only.
- **Orders CSV** (`/reports/orders.csv`) - PO + SO status rows in the range.
  Admin sees both order types, all creators; Sales sees only their own SO
  rows (no PO rows - Sales has no PO involvement at all).

`ReportService` reads from the same `PurchaseOrderRepositoryInterface`/
`SalesOrderRepositoryInterface`/`StockLedgerRepositoryInterface` (via new
`findForReport()`/`findByDateRange()` methods) that `DashboardService`
already uses, per the brief's explicit "dihasilkan dari query agregasi/rekap
yang sama dengan dashboard" - not a separate one-off query path.

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
- No "edit items" action for a Draft PO or SO, and validation errors on
  either create form don't restore the dynamic item rows (tracked in
  `docs/quality/tech-debt.md`).
- `decrementIfAvailable()`'s oversell prevention, `MysqlDashboardRepository`'s
  aggregation queries, and the REPORT-01 CSV endpoints are unit-tested/
  hand-reviewed only - not yet verified as TEST-02 integration tests against
  real MySQL (tracked in `docs/quality/tech-debt.md` #16/#17/#18).
- Seed data is intentionally small; must grow to the §7.1 minimums (30
  products, 25 combined orders, 2 warehouses, 2+2 non-Admin accounts)
  before final submission.
- No password-reset action for existing users yet (see `docs/quality/tech-debt.md`).
- Replacing a product's image on edit doesn't delete the old file from
  `public/uploads/products/` yet (tracked in `docs/quality/tech-debt.md`).
- `docs/quality/refactor-log.md` and `docs/quality/static-analysis.md` are
  still templates awaiting real content (a genuine refactor did happen this
  session - see `docs/architecture/class-diagram-as-built.md`'s
  `View::render()` extraction - but it hasn't been written up there yet, and
  `composer analyse` hasn't been run in an environment with PHP available).

## AI usage
Disclosed in `ai-usage-log.md`.
