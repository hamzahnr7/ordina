# Test Scenarios & Results

## Unit tests (TEST-01) - target: >=6 cases across >=3 logic areas
| Area | Scenario | Test file | Status |
|------|----------|-----------|--------|
| Product availability | Known SKU returns per-warehouse stock | `tests/Unit/ProductAvailabilityServiceTest.php` | Passing |
| Product availability | Unknown SKU returns null | `tests/Unit/ProductAvailabilityServiceTest.php` | Passing |
| Auth (AUTH-01) | Valid credentials return the user | `tests/Unit/AuthServiceTest.php` | Passing |
| Auth (AUTH-01) | Wrong password rejected | `tests/Unit/AuthServiceTest.php` | Passing |
| Auth (AUTH-01) | Unknown email rejected | `tests/Unit/AuthServiceTest.php` | Passing |
| Auth (AUTH-01) | Inactive user rejected even with correct password | `tests/Unit/AuthServiceTest.php` | Passing |
| User management (USR-01) | Create hashes the password and defaults to active | `tests/Unit/UserServiceTest.php` | Passing |
| User management (USR-01) | Duplicate email rejected | `tests/Unit/UserServiceTest.php` | Passing |
| User management (USR-01) | Admin role rejected from this screen | `tests/Unit/UserServiceTest.php` | Passing |
| User management (USR-01) | setActive toggles the account | `tests/Unit/UserServiceTest.php` | Passing |
| Authorization (§1.2 SoD) | Sales cannot approve Sales Order | `tests/Unit/GateTest.php` | Passing |
| Authorization (§1.2 SoD) | Admin can approve Sales Order | `tests/Unit/GateTest.php` | Passing |
| Authorization (§1.2 SoD) | Only Admin can manage users | `tests/Unit/GateTest.php` | Passing |
| Authorization (§1.2 SoD) | Sales has no warehouse (goods issue/receipt) permissions | `tests/Unit/GateTest.php` | Passing |
| Low-stock calculation (FIND-01) | Total stock below reorder_point is flagged low | `tests/Unit/ProductServiceTest.php` | Passing |
| Low-stock calculation (FIND-01) | Total stock at/above reorder_point is not flagged low | `tests/Unit/ProductServiceTest.php` | Passing |
| Product validation (PRD-01) | Duplicate SKU rejected | `tests/Unit/ProductServiceTest.php` | Passing |
| Product validation (PRD-01) | Unknown category_id rejected | `tests/Unit/ProductServiceTest.php` | Passing |
| Product validation (PRD-01) | Negative reorder_point rejected | `tests/Unit/ProductServiceTest.php` | Passing |
| PO validation (PO-01) | Create rejects an empty item list | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO validation (PO-01) | Create rejects an unknown supplier_id | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO status transition (PO-01) | New PO starts as Draft | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO status transition (PO-01) | markOrdered requires Draft; rejects a second call | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO goods receipt (PO-01/ARCH-02) | Rejected while PO is still Draft | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO goods receipt (PO-01/ARCH-02) | Partial receipt: status -> PartiallyReceived, ProductStock incremented, one StockLedger row written | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO goods receipt (PO-01/ARCH-02) | Receiving the remainder: status -> Received, stock matches qty_ordered, two StockLedger rows total | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO goods receipt (PO-01) | Receiving more than what's left on a still-open PO is rejected | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO goods receipt (PO-01) | Receiving anything against an already fully-Received PO is rejected | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| PO cancellation (PO-01) | Cannot cancel once fully Received | `tests/Unit/PurchaseOrderServiceTest.php` | Passing |
| Own profile (§1.2) | Password change succeeds with the correct current password | `tests/Unit/ProfileServiceTest.php` | Passing |
| Own profile (§1.2) | Wrong current password rejected; old password still works | `tests/Unit/ProfileServiceTest.php` | Passing |
| Own profile (§1.2) | New password shorter than 8 characters rejected | `tests/Unit/ProfileServiceTest.php` | Passing |
| Own profile (§1.2) | Confirmation that doesn't match rejected | `tests/Unit/ProfileServiceTest.php` | Passing |
| SO validation (SO-01) | Create rejects an empty item list | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO validation (SO-01) | Create rejects an unknown customer_id | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO validation (SO-01) | Sell price below the product's own registered price is rejected; at/above it is accepted (price may only be raised, never cut) | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO status transition (SO-01) | submit(): Draft -> PendingApproval; rejects a second submit | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO status transition (SO-01) | reject(): PendingApproval -> Cancelled | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO status transition (SO-01) | cancel() rejected once Fulfilled | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO authorization (SO-01 SoD) | The creator cannot approve their own Sales Order, even as Admin | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO authorization (SO-01 SoD) | A different user can approve it | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO goods issue (SO-01/ARCH-02) | Rejected unless status is Approved | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO goods issue (SO-01/ARCH-02) | Decrements ProductStock, writes one Issue StockLedger row, sets Fulfilled | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO goods issue (SO-01/ARCH-02) | **The core scenario**: second Sales Order's goods issue rejected once the first exhausted the same product+warehouse's stock - left Approved, not oversold, no ledger row written | `tests/Unit/SalesOrderServiceTest.php` | Passing |

## Integration tests (TEST-02) - target: >=3, real MySQL in Docker
| Scenario | Test file | Status |
|----------|-----------|--------|
Each test truncates every table in `ordina_test` and loads the same small
fixture set in `setUp()` (`DatabaseTestCase`), then wires the **real** Mysql
repositories and `PdoTransactionManager`. That means transactions, CHECK
constraints and the conditional `UPDATE` really execute.

| Scenario | Test file | Status |
|----------|-----------|--------|
| Goods receipt end-to-end: partial (4/10) then full (6/10). ProductStock 5 -> 9 -> 15, status Ordered -> PartiallyReceived -> Received, 2 Receipt ledger rows, ledger balance == stock | `tests/Integration/GoodsReceiptIntegrationTest.php` | Passing (2026-10-02, MySQL 8.0 / PHP 8.2 in Docker) |
| Goods receipt for a product with no stock row in that warehouse creates the row (`incrementQuantity` insert path) | `tests/Integration/GoodsReceiptIntegrationTest.php` | Passing (2026-10-02, MySQL 8.0 / PHP 8.2 in Docker) |
| **ARCH-02**: stock 10, SO#1 issues 8, SO#2 asks for 5 and is rejected. Stock stays 2, SO#2 stays Approved, 0 ledger rows for SO#2 | `tests/Integration/GoodsIssueIntegrationTest.php` | Passing (2026-10-02, MySQL 8.0 / PHP 8.2 in Docker) |
| **ARCH-02 rollback**: 2-item SO where item 1 is sufficient and item 2 is not. The item-1 decrement is rolled back, so stock is unchanged and no ledger rows remain | `tests/Integration/GoodsIssueIntegrationTest.php` | Passing (2026-10-02, MySQL 8.0 / PHP 8.2 in Docker) |
| SoD: service refuses the creator approving their own SO; status stays PendingApproval and approved_by stays NULL | `tests/Integration/SegregationOfDutiesIntegrationTest.php` | Passing (2026-10-02, MySQL 8.0 / PHP 8.2 in Docker) |
| SoD backstop: a direct `UPDATE ... approved_by = created_by` is rejected by `chk_so_approver_not_creator` | `tests/Integration/SegregationOfDutiesIntegrationTest.php` | Passing (2026-10-02, MySQL 8.0 / PHP 8.2 in Docker) |
| REPORT-01 date-range filtering with fixtures on the exact range edges (00:00:00 / 23:59:59), inclusive on both days; Admin gets PO + SO merged by date; Sales gets only their own SO and no PO | `tests/Integration/ReportIntegrationTest.php` (6 tests) | Passing (2026-10-06, MySQL 8.0 / PHP 8.2 in Docker) |

## Running the evidence scenarios

These scenarios produce the brief's **Bukti** for REPORT-01, AUTH and ERR-01.
Each case has a **Method**:

- **Auto-IT**: can become an integration test that runs with `composer test`.
- **Auto-HTTP**: a deterministic HTTP check (status code, redirect, CSV row
  count) against the running app. All of them are scripted in
  `scripts/smoke-test.php` (18 checks, a few seconds), and each can also be
  done by hand in the browser.
- **Manual**: needs a person in the browser, usually to take a screenshot
  for `docs/testing/screenshots/`.

**Precondition P0 (all cases):** fresh seed data and a healthy stack.
```
docker compose down -v
docker compose up -d --build
docker compose ps          # mysql, redis, web all "healthy"
```
All accounts use password `admin123` (see README). Expected numbers below
come from `database/schema-and-seed.sql` and only hold on a fresh seed.
Orders or stock movements created through the UI afterwards change them.

Two date ranges are used throughout:
- **Range A** = `from=2026-08-01&to=2026-08-15`
- **Range B** = `from=2026-08-16&to=2026-09-30`

Both bounds are inclusive (`DATE(...) BETWEEN :from AND :to`).

## REPORT-01 evidence ("File CSV hasil ekspor dengan rentang tanggal berbeda")

Download a CSV in the browser at `/reports`: pick the dates, then click the
download button. Or open the URL directly while logged in. "Rows" means data
rows; every file also has 1 header row.

| ID | Role (login) | Request | Expected result | Method | Status |
|----|--------------|---------|-----------------|--------|--------|
| RPT-01 | Admin (`admin@ordina.test`) | `/reports/stock-ledger.csv?from=2026-08-01&to=2026-08-15` | **65 rows**: 58 `Adjustment` (opening stock, dated 2026-08-01), 4 `Receipt` (PO-3 ×2, PO-4, PO-5), 3 `Issue` (SO-3 ×2, SO-4) | Auto-IT + Auto-HTTP | Passing 2026-10-06 (smoke test + `ReportIntegrationTest`) |
| RPT-02 | Admin | `/reports/stock-ledger.csv?from=2026-08-16&to=2026-09-30` | **5 rows**: 3 `Receipt` (PO-8, PO-10 ×2), 2 `Issue` (SO-8 ×2). Different from RPT-01, which proves the range filter works | Auto-IT + Auto-HTTP | Passing 2026-10-06 (smoke test + `ReportIntegrationTest`) |
| RPT-03 | Admin | `/reports/orders.csv?from=2026-08-01&to=2026-08-15` | **10 rows**: PO 1–6 and SO 1–4, sorted by date, all creators | Auto-IT + Auto-HTTP | Passing 2026-10-06 (smoke test + `ReportIntegrationTest`) |
| RPT-04 | Admin | `/reports/orders.csv?from=2026-08-16&to=2026-09-30` | **16 rows**: PO 7–13 and SO 5–13 | Auto-IT + Auto-HTTP | Passing 2026-10-06 (smoke test + `ReportIntegrationTest`) |
| RPT-05 | Sales Satu (`sales1@ordina.test`) | `/reports/orders.csv?from=2026-08-16&to=2026-09-30` | **5 rows**, all `SO`: 5, 7, 10, 11, 13 (only orders Sales Satu created). **No `PO` rows** | Auto-IT + Auto-HTTP | Passing 2026-10-06 (smoke test + `ReportIntegrationTest`) |
| RPT-06 | Sales Dua (`sales2@ordina.test`) | same as RPT-05 | **4 rows**, all `SO`: 6, 8, 9, 12. None overlap with RPT-05 | Auto-IT + Auto-HTTP | Passing 2026-10-06 (smoke test + `ReportIntegrationTest`) |
| RPT-07 | Admin | `/reports/stock-ledger.csv?from=2026-09-30&to=2026-08-16` (dates reversed) | Same 5 rows as RPT-02: the app swaps reversed dates instead of failing | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| RPT-08 | Warehouse Staff (`wh1@ordina.test`) | Open `/reports` | Only the stock-ledger download is offered; no orders download | Manual (screenshot) | Not run |
| RPT-09 | Warehouse Staff | `/reports/orders.csv?from=2026-08-01&to=2026-09-30` | **403** Akses Ditolak page, no CSV | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| RPT-10 | Sales Satu | `/reports/stock-ledger.csv?from=2026-08-01&to=2026-09-30` | **403**, no CSV (Sales has no stock-report permission) | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| RPT-11 | Admin | Open the RPT-01 and RPT-02 files | Both CSVs open in Excel/Sheets with readable columns (Waktu, SKU, Produk, Gudang, Tipe, Qty, Referensi, Oleh); keep both files as evidence | Manual (keep files) | Not run |

## Validation scenarios (VAL-01)
| Field/Rule | Invalid input tried | Expected result | Actual result |
|------------|----------------------|------------------|----------------|
| Product SKU | Duplicate SKU | Rejected, form re-shown with data intact | Rejected (`ProductServiceTest`); form re-fill via `Session::flash('old', ...)` not yet manually verified in-browser |
| Product reorder_point | Negative number | Rejected | Rejected (`ProductServiceTest`) |
| User email | Duplicate email | Rejected | Rejected (`UserServiceTest`) |
| SO qty | 0 or negative | Rejected | Rejected (`SalesOrderServiceTest`'s item validation, same pattern as PO) |
| PO/SO item rows | Submit a 2-row PO (or SO) with an invalid header field | Rejected, and **both item rows come back** with product, qty and price intact | Verified by rendering the views with `old['items']` (2 PO rows restored, product selected, hidden price kept; SO keeps the `min` price floor). In-browser click-through still TBD |
| SO sell price | Lower than the product's sell price | Rejected | Rejected (`SalesOrderServiceTest`), and the restored row keeps `min=<product price>` |

## Auth and failure paths (AUTH-01/02, API-01, ERR-01)
Precondition P0 applies. "No session" means a private/incognito window, or
`curl` without a cookie.

| ID | Precondition | Steps | Expected result | Method | Status |
|----|--------------|-------|-----------------|--------|--------|
| ERR-01 | No session | Open `/dashboard` | **302** redirect to `/login`; the login page is shown | Auto-HTTP + Manual (screenshot) | Passing 2026-10-06 (smoke test); screenshot pending |
| ERR-02 | No session | Log in with `admin@ordina.test` / `salah123` | Stays on `/login` with "Email atau password salah." The message is the same for a wrong email, so it never reveals which part was wrong | Auto-HTTP + Manual (screenshot) | Passing 2026-10-06 (smoke test); screenshot pending |
| ERR-03 | No session | Log in as Admin, Sales Satu and Gudang Satu in turn (`admin123`) | Each lands on its own dashboard, and the sidebar shows only that role's menus | Manual (screenshot per role) | Not run |
| ERR-04 | Logged in | Click Logout, then open `/dashboard` again (also with the browser Back button) | Redirected to `/login`; the session is gone | Auto-HTTP + Manual | Passing 2026-10-06 (smoke test); browser Back-button check pending |
| ERR-05 | No session | `GET /api/products/SKU-0001/availability` | **401** JSON `{"error":"Unauthenticated"}` | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| ERR-06 | Logged in (any role) | `GET /api/products/SKU-9999/availability` | **404** JSON `{"error":"Product not found"}` | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| ERR-07 | Logged in (any role) | `GET /api/products/SKU-0001/availability` (positive control) | **200** JSON with `sku`, `name` and per-warehouse `quantity` (Wireless Mouse: Warehouse Jakarta 15, Warehouse Surabaya 50) | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| ERR-08 | Logged in as Sales Satu | Send `POST /sales-orders/7/approve` (SO-7 is Sales Satu's own, `PendingApproval`). In the UI the Setujui button is not shown, so use `curl` or browser devtools | **403** Akses Ditolak; SO-7 stays `PendingApproval`. Also covered by `GateTest` and `SalesOrderServiceTest` | Auto-HTTP | Passing 2026-10-06 (smoke test) |
| ERR-09 | Logged in as Gudang Satu | Open `/users` | **403** Akses Ditolak (only Admin manages users) | Auto-HTTP + Manual (screenshot) | Passing 2026-10-06 (smoke test); screenshot pending |
| ERR-10 | Any | Open `/halaman-tidak-ada` | **404** page in the app layout, with no stack trace or file path in the response | Auto-HTTP | Passing 2026-10-06 (smoke test) |

## Screenshots (Manual)
Save to `docs/testing/screenshots/` with the case ID in the filename, e.g.
`ERR-03-dashboard-sales.png`. Minimum set for the brief's Bukti:
- [ ] ERR-01 login page after the redirect, ERR-02 the failed-login message
- [ ] ERR-03 dashboard of each of the 3 roles
- [ ] ERR-09 403 page
- [ ] RPT-08 Warehouse Staff `/reports` page, RPT-11 the two CSV files opened
- [ ] VAL-01: a PO form that failed validation with its item rows still filled in
- [ ] PO-01: StockLedger rows on a PO detail page after goods receipt
- [ ] SO-01: an SO detail page after goods issue (Fulfilled + ledger rows)

## Known bugs
| ID | Bug | Impact | Status |
|----|-----|--------|--------|
| BUG-01 | The MySQL healthcheck in `compose.yaml` pings with `-h localhost`, i.e. through the socket. On an empty volume, the temporary init server already answers on that socket while the schema and seed are still loading, so `mysql` can turn `healthy` and `web` can start before the seed has finished | Only right after `docker compose down -v`; early requests may fail or see partial data | Open. Fix: ping with `-h 127.0.0.1` (TCP only comes up after init) |

No other known functional bugs as of 2026-10-05.

## How to run
```
docker compose exec web composer test            # unit + integration
docker compose exec web composer test:unit
docker compose exec web composer test:integration
docker compose exec web composer test:coverage    # unit + coverage -> coverage/html/index.html

# HTTP smoke test against the running app (RPT-* / ERR-* Auto-HTTP cases).
# Read-only; RPT-* numbers need a fresh seed. Exit code 0 = all passed.
docker compose exec web php scripts/smoke-test.php
php scripts/smoke-test.php http://localhost:8080   # same, from the host
```

## Code coverage (Unit suite)
| Date | Lines | Methods | Classes | Notes |
|------|-------|---------|---------|-------|
| 2026-10-02 (first run) | 17.42% (414/2376) | 20.62% (80/388) | 7.69% (6/78) | 45 tests; whole `app/` measured, including layers unit tests never load |
| 2026-10-02 | **100% (758/758)** | **100% (139/139)** | **100% (35/35)** | 162 tests, PCOV in Docker (PHP 8.2); scope below |

**What is measured:** Service, Domain, Entity, Core/Authorization,
Core/Transaction, Router, Config and View, i.e. every place a business rule
lives.

**What is excluded** (`phpunit.xml` `<source><exclude>`), and how each is
verified instead:

| Excluded | Why not unit-tested | Verified by |
|----------|---------------------|-------------|
| `app/Repository/Mysql/` | Needs a real database | TEST-02 integration tests (real MySQL) |
| `app/Repository/InMemory/` | Test fakes, not production code | Used by every unit test |
| `app/Controller/`, `app/Core/Controller.php`, `app/routes.php` | HTTP wiring; `redirect()` calls `exit`, which would end the PHPUnit process | Manual click-through (ERR-01 / VAL-01 tables above) |
| `app/Core/Session.php`, `RedisSessionHandler.php`, `Database.php` | Thin wrappers around `session_*()`, Redis and `new PDO` | Every login and request; integration tests connect through `Database::connect()` |

**One ignored line:** `ProductImageUploader.php` `move_uploaded_file()` is
marked `@codeCoverageIgnore`. `is_uploaded_file()` is only true for a file
received through a real HTTP POST, so the line cannot run under PHPUnit. The
`rename()` branch next to it, the oversize/type/mkdir/move-failure checks, and
the happy path are all covered.

**Platform note:** `ProductImageUploaderTest::test_file_that_cannot_be_moved_is_an_error`
uses `/proc` as an unwritable directory, so it runs on Linux (Docker) and is
skipped on a Windows host PHP. Coverage numbers come from Docker.

New unit test files in this pass:
- Domain/Entity: `StatusEnumTest` (full status-rule truth table), `EntityHydrationTest`
- Core: `MenuRegistryTest`, `PdoTransactionManagerTest`, `RouterTest`, `ConfigTest`, `ViewTest`
- Services: `CategoryServiceTest`, `CustomerServiceTest`, `SupplierServiceTest`,
  `WarehouseServiceTest`, `DashboardServiceTest`, `ReportServiceTest`,
  `ProductImageUploaderTest`
- Extended: `GateTest`, `ProductServiceTest`, `UserServiceTest`,
  `ProfileServiceTest`, `PurchaseOrderServiceTest`, `SalesOrderServiceTest`
