# Static Analysis Report (TEST-03)

Regenerate before submission:

```
docker compose exec web composer analyse
# = vendor/bin/phpstan analyse --memory-limit=1G   (config: phpstan.neon)
```

## Latest run
- Date: 2026-10-02
- Tool: PHPStan 1.12.34
- Level: 5 (`phpstan.neon`)
- Paths: `app/`, `scripts/` (98 PHP files)
- Command: `vendor/bin/phpstan analyse --memory-limit=1G`
- Result:
  ```
  [OK] No errors
  ```

## Errors found and fixed in this pass
The first run of this pass reported **4 errors**. All four were fixed in the
code, not suppressed - there is no `ignoreErrors` entry and no baseline file.

| File:Line | Error | Fix |
|-----------|-------|-----|
| `app/Core/Authorization/Gate.php:47` | `?? []` on `ROLE_PERMISSIONS[$role->value]` - offset always exists, so the fallback is dead code | Removed `?? []`. Every `Role` case has an entry in the map, so the lookup can never miss |
| `app/Core/Authorization/Gate.php:53` | Same as above in `permissionsFor()` | Same fix |
| `app/Repository/InMemory/InMemoryPurchaseOrderRepository.php:38` | `paginateForListing()` returned `PurchaseOrder` entities, but the interface contract is rows (`array<string, mixed>`) | **Real contract bug in the test fake.** Now returns the same row shape as `MysqlPurchaseOrderRepository` (`po.*` columns + `supplier_name`/`warehouse_name`) |
| `app/Repository/InMemory/InMemorySalesOrderRepository.php:43` | Same contract mismatch for Sales Orders | Same fix, matching `MysqlSalesOrderRepository` (`so.*` + `customer_name`/`warehouse_name`) |

## Remaining warnings
None at level 5.

## Notes
- `composer analyse` previously ran `phpstan analyse app --level=5`, which
  passed `app` on the command line and so silently skipped `scripts/` (where
  JOB-01 lives) even though `phpstan.neon` lists it. It now uses the config
  file's paths, so both folders are checked.
- PHPStan prints an "old version" notice for the 1.12.x line; upgrading to
  2.x is optional and not required by the brief (level 5+ report only).
