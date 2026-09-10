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
| PO validation | Order date cannot be in the past | TBD | Not started |
| SO status transition | Draft -> PendingApproval -> Approved -> Fulfilled is the only valid path | TBD | Not started |

## Integration tests (TEST-02) - target: >=3, real MySQL in Docker
| Scenario | Test file | Status |
|----------|-----------|--------|
| DB connectivity smoke test | `tests/Integration/ExampleConnectionTest.php` | Passing (scaffold) |
| Goods receipt increases ProductStock + writes Receipt ledger row | TBD | Not started |
| Second goods issue rejected once stock exhausted by the first (ARCH-02) | TBD | Not started |

## Validation scenarios (VAL-01)
| Field/Rule | Invalid input tried | Expected result | Actual result |
|------------|----------------------|------------------|----------------|
| Product SKU | Duplicate SKU | Rejected, form re-shown with data intact | Rejected (`ProductServiceTest`); form re-fill via `Session::flash('old', ...)` not yet manually verified in-browser |
| Product reorder_point | Negative number | Rejected | Rejected (`ProductServiceTest`) |
| User email | Duplicate email | Rejected | Rejected (`UserServiceTest`) |
| SO qty | 0 or negative | Rejected | TBD (SO not implemented yet) |

## Failure paths (ERR-01)
| Path | Expected | Actual |
|------|----------|--------|
| Access protected page without session | Redirect to /login | TBD |
| Sales calls approve endpoint on own order | 403 | TBD |
| Unknown SKU via API-01 | 404 JSON | TBD |

## How to run
```
docker compose exec web composer test            # unit + integration
docker compose exec web composer test:unit
docker compose exec web composer test:integration
```
