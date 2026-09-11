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
| SO validation (SO-01) | Create rejects an empty item list | `tests/Unit/SalesOrderServiceTest.php` | Passing |
| SO validation (SO-01) | Create rejects an unknown customer_id | `tests/Unit/SalesOrderServiceTest.php` | Passing |
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
| DB connectivity smoke test | `tests/Integration/ExampleConnectionTest.php` | Passing (scaffold) |
| Goods receipt increases ProductStock + writes Receipt ledger row against real MySQL | TBD | Not started (unit-tested against InMemory fakes in `PurchaseOrderServiceTest`, not yet against real MySQL) |
| Second goods issue rejected once stock exhausted by the first (ARCH-02), against real MySQL | TBD | Not started (unit-tested against InMemory fakes in `SalesOrderServiceTest`; `decrementIfAvailable()`'s actual InnoDB locking behavior not yet verified end-to-end - see `docs/quality/tech-debt.md` #16) |
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
```
