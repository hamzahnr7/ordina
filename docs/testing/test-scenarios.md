# Test Scenarios & Results

## Unit tests (TEST-01) - target: >=6 cases across >=3 logic areas
| Area | Scenario | Test file | Status |
|------|----------|-----------|--------|
| Product availability | Known SKU returns per-warehouse stock | `tests/Unit/ProductAvailabilityServiceTest.php` | Passing (scaffold) |
| Product availability | Unknown SKU returns null | `tests/Unit/ProductAvailabilityServiceTest.php` | Passing (scaffold) |
| PO validation | Order date cannot be in the past | TBD | Not started |
| SO status transition | Draft -> PendingApproval -> Approved -> Fulfilled is the only valid path | TBD | Not started |
| SO authorization | Sales cannot approve their own order | TBD | Not started |
| Low-stock calculation | Product below reorder_point is flagged, at/above is not | TBD | Not started |

## Integration tests (TEST-02) - target: >=3, real MySQL in Docker
| Scenario | Test file | Status |
|----------|-----------|--------|
| DB connectivity smoke test | `tests/Integration/ExampleConnectionTest.php` | Passing (scaffold) |
| Goods receipt increases ProductStock + writes Receipt ledger row | TBD | Not started |
| Second goods issue rejected once stock exhausted by the first (ARCH-02) | TBD | Not started |

## Validation scenarios (VAL-01)
| Field/Rule | Invalid input tried | Expected result | Actual result |
|------------|----------------------|------------------|----------------|
| Product SKU | Duplicate SKU | Rejected, form re-shown with data intact | TBD |
| Product reorder_point | Negative number | Rejected | TBD |
| SO qty | 0 or negative | Rejected | TBD |

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
