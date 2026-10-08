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
> `docs/planning/user-stories.md` for the honest remaining list.

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
The `mysql` service auto-runs `database/schema-and-seed.sql` and
`database/test-db-init.sql` on first boot (empty volume). The app is at
http://localhost:8080 once `docker compose ps` shows all services `healthy`.

To start again from the original seed data, recreate the database volume.
This **deletes all data** entered through the app:
```bash
docker compose down -v
docker compose up -d --build
```

### Demo accounts
All demo accounts use the password **`admin123`**. These are local demo
credentials from the seed data only; never reuse them outside a local
environment.

| Role | Name | Email |
|------|------|-------|
| Admin | Admin Utama | admin@ordina.test |
| Sales | Sales Satu / Sales Dua | sales1@ordina.test / sales2@ordina.test |
| Warehouse Staff | Gudang Satu / Gudang Dua | wh1@ordina.test / wh2@ordina.test |

Every user can change their own password at `/profile`. To put a different
password into the seed itself, generate a hash and replace the
`password_hash` values in `database/schema-and-seed.sql`, then recreate the
volume as above:
```bash
docker compose exec web php scripts/hash-password.php "NewPassword123"
```

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
docker compose exec web composer test:coverage      # unit test + code coverage report
docker compose exec web composer analyse            # PHPStan level 5
```

### HTTP smoke test
`scripts/smoke-test.php` logs in as each role over real HTTP and runs 18
checks against the running app: report CSV row counts per date range and
role, 403s, the login redirect, logout, and API 401/404/200. It is
read-only. The report numbers assume a fresh seed. Exit code 0 means
everything passed. Scenarios: `docs/testing/test-scenarios.md` (RPT-*, ERR-*).
```bash
docker compose exec web php scripts/smoke-test.php
php scripts/smoke-test.php http://localhost:8080   # from the host
```

### SonarQube scan
Static analysis with SonarQube Community Build 26.9.0.129388, run fully in
Docker. One-time setup takes about 10 minutes: start the server, create the
`ordina` project and put a token in `.env` as `SONAR_TOKEN`. After that a
scan is one command:
```powershell
docker compose -f compose.sonar.yaml up -d                     # SonarQube at http://localhost:9000
powershell -ExecutionPolicy Bypass -File scripts/sonar-scan.ps1
```
Step-by-step guide, macOS/Linux commands and troubleshooting:
`docs/quality/sonarqube.md`.

### Code coverage
`composer test:coverage` runs the Unit suite with the PCOV driver switched on
for that run only (it's installed in the image but disabled by default, so
normal requests and `composer test` aren't slowed down). It prints a summary
to the terminal and writes:
- `coverage/html/index.html` - browsable per-class/per-line report
- `coverage/clover.xml` - machine-readable, for CI or other tools

`coverage/` is gitignored. PCOV was added to `docker/php/Dockerfile`, so an
existing container needs a rebuild once: `docker compose up -d --build web`.

Running `composer test:coverage` straight from a host PHP (e.g. Windows)
instead of inside the container fails with "No code coverage driver
available" unless that PHP has PCOV or Xdebug installed. The script works
with either: it passes `-d pcov.enabled=1` for PCOV and sets
`XDEBUG_MODE=coverage` for Xdebug.

**Current result: 100% lines / methods / classes** (758 lines, 162 unit
tests) over the business-logic layers. The coverage scope is set in
`phpunit.xml` `<source>`, which names what is **excluded** and why:
- `Repository/Mysql`, which needs a database and is covered by `tests/Integration`
- `Repository/InMemory`, the test fakes themselves
- `Controller/`, `Core/Controller.php` and `routes.php`: HTTP wiring, and
  `redirect()` calls `exit`
- the session, Redis and PDO-connect wrappers

One line is marked `@codeCoverageIgnore`: `move_uploaded_file()` in
`ProductImageUploader`, which only runs for a real HTTP upload. See
`docs/testing/test-scenarios.md`.

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
Prints every active product whose **total** stock across all warehouses is
below its reorder point, with its shortfall. It uses the same query as the
dashboard (`MysqlDashboardRepository`), so the two always agree. Against the
seed data it lists 6 products. In production this would run from cron, e.g.
`0 7 * * * php /var/www/html/scripts/check-low-stock.php`.

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
- No "edit items" action for a Draft PO or SO (tracked in `docs/quality/tech-debt.md`).
- `MysqlDashboardRepository`'s aggregation queries and the REPORT-01 CSV
  endpoints are hand-reviewed only, with no integration test yet (tracked in
  `docs/quality/tech-debt.md` #17/#18).
- The ARCH-02 integration test runs the two goods issues one after another
  on one connection, not truly in parallel.
- Users can change their own password at `/profile`, but there is no Admin
  "reset password" for another user yet (see `docs/quality/tech-debt.md` #3).
- Replacing a product's image on edit doesn't delete the old file from
  `public/uploads/products/` yet (tracked in `docs/quality/tech-debt.md`).

## AI usage
Disclosed in `ai-usage-log.md`.
