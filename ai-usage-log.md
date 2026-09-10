# AI Usage Log

Per brief §6.2 (DISCLOSE / REVIEW / VERIFY / TEST).

| Date | Tool | Purpose | Prompt summary (sanitized) | Output used? | Verification performed |
|------|------|---------|------------------------------|----------------|--------------------------|
| 2026-09-03 | Claude Code | Scaffold initial project structure (Docker, folder layout, layered-architecture skeleton, DB schema draft, docs templates) from the uploaded Project Brief PDF | "Init project per brief, generate template structure; use Redis to make dev easier" | Yes - structure kept, to be filled in with real business logic by hand | Manually reviewed folder layout and docker-compose against brief §4.1/§5.1; schema checked against §1.3 entity table; no code has been run yet - functional verification pending first `docker compose up` |
| 2026-09-10 | Claude Code | Implement login/session (AUTH-01), Admin-only user management (USR-01), and a Role/Permission/Gate/MenuRegistry authorization layer; document the RBAC architecture and how to extend it | "Buatkan fitur login dan session management, user management, dan arsitektur role/akses menu beserta cara menambah role/menu baru" | Yes - code + `docs/architecture/rbac-and-menu-access.md` + `adr-0004` kept, unit tests added (`AuthServiceTest`, `UserServiceTest`, `GateTest`) | Manually traced each `Gate::allows()` rule against brief §1.2's SoD table; confirmed `Controller::authorize()` throws (not renders) so ERR-01's centralized handling in `public/index.php` covers it; **not yet run inside Docker/PHPUnit** at authoring time - PHP CLI unavailable in this authoring environment. Participant later ran `docker compose exec web composer test` themselves and iterated with this tool to fix real environment issues found along the way (wrong `DB_TEST_HOST`, missing `tester` MySQL user/`ordina_test` DB, `depends_on` not waiting for MySQL's healthcheck) until all tests passed |
| 2026-09-10 | Claude Code | Implement master data: Category/Warehouse/Supplier/Customer CRUD and Product CRUD with search/filter/pagination (FIND-01), image upload (PRD-01), and per-warehouse stock detail view (WH-01) | "Lanjutkan proses developmentnya" (continue development, following the brief's build order after unit tests passed) | Yes - code + `ProductServiceTest`/`InMemoryCategoryRepository` kept; docs updated (`tech-debt.md`, `test-scenarios.md`, `class-diagram-as-built.md`, `user-stories.md`, README) | Manually re-derived the `paginateForListing()` SQL and caught/fixed a real bug before it shipped: the `HAVING total_stock < p.reorder_point` clause referenced an alias not present in the `COUNT(*)` subquery's SELECT list, which would have thrown "Unknown column" in MySQL. **Not run against real MySQL/PHPUnit** at authoring time (no Docker daemon available here) - participant should run `composer test` and click through `/categories`, `/warehouses`, `/suppliers`, `/customers`, `/products` (including an image upload) before trusting this slice |

## Notes
- All architecture decisions in `docs/architecture/adr-*.md` must be
  re-explained by the participant unaided during technical defense,
  regardless of how they were drafted.
- Update this table every time an AI tool materially contributes code,
  docs, or design decisions - including "no AI used this session" entries
  if applicable, per the brief's requirement to disclose even non-use.
