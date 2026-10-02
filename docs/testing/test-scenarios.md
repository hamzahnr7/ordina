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
| REPORT-01 CSV export produces correct rows for a given date range, against real MySQL | TBD | Not started (see `docs/quality/tech-debt.md` #18) |

## REPORT-01 evidence (manual, per brief - "File CSV hasil ekspor dengan rentang tanggal berbeda")
| Scenario | Expected | Actual |
|----------|----------|--------|
| Admin downloads `/reports/stock-ledger.csv` for a range with known receipts/issues | CSV rows match `stock_ledger` rows in that range | TBD - needs manual click-through with real data |
| Admin downloads `/reports/orders.csv` | Includes both PO and SO rows, all creators | TBD |
| Sales downloads `/reports/orders.csv` | Only their own SO rows, no PO rows at all | TBD |
| Warehouse Staff visits `/reports` | Only the stock-ledger download is offered, no orders download | TBD |
| Two different date ranges on the same report | Different row counts/content | TBD |

## Validation scenarios (VAL-01)
| Field/Rule | Invalid input tried | Expected result | Actual result |
|------------|----------------------|------------------|----------------|
| Product SKU | Duplicate SKU | Rejected, form re-shown with data intact | Rejected (`ProductServiceTest`); form re-fill via `Session::flash('old', ...)` not yet manually verified in-browser |
| Product reorder_point | Negative number | Rejected | Rejected (`ProductServiceTest`) |
| User email | Duplicate email | Rejected | Rejected (`UserServiceTest`) |
| SO qty | 0 or negative | Rejected | Rejected (`SalesOrderServiceTest`'s item validation, same pattern as PO) |
| PO/SO item rows | Submit a 2-row PO (or SO) with an invalid header field | Rejected, and **both item rows come back** with product, qty and price intact | Verified by rendering the views with `old['items']` (2 PO rows restored, product selected, hidden price kept; SO keeps the `min` price floor). In-browser click-through still TBD |
| SO sell price | Lower than the product's sell price | Rejected | Rejected (`SalesOrderServiceTest`), and the restored row keeps `min=<product price>` |

## Failure paths (ERR-01)
| Path | Expected | Actual |
|------|----------|--------|
| Access protected page without session | Redirect to /login | TBD |
| Sales calls approve endpoint on own order | 403 (lacks `ApproveSalesOrder` entirely - `GateTest`); Admin approving their own SO is separately rejected by `SalesOrderService::approve()` (`SalesOrderServiceTest`) | Rejected at both layers; manual click-through in browser not yet done |
| Unknown SKU via API-01 | 404 JSON | TBD |

## How to run
```
docker compose exec web composer test            # unit + integration
docker compose exec web composer test:unit
docker compose exec web composer test:integration
docker compose exec web composer test:coverage    # unit + coverage -> coverage/html/index.html
```

## Code coverage (Unit suite)
| Date | Lines | Methods | Classes | Notes |
|------|-------|---------|---------|-------|
| 2026-10-02 | 17.42% (414/2376) | 20.62% (80/388) | 7.69% (6/78) | Whole `app/`, including controllers, views wiring and Mysql repositories that unit tests never load by design (Mysql repos are covered by TEST-02 instead). Service layer, where the business rules live: AuthService 100%, ProductAvailabilityService 100%, ProfileService 92%, PurchaseOrderService 71%, SalesOrderService 68%, UserService 55%, ProductService 40% lines |
