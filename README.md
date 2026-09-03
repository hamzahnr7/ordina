# Ordina - Inventory & Order Management System

Final project for Neuronworks' Intermediate Programmer program. Web app for
multi-warehouse inventory, purchase orders, and sales orders across three
roles (Admin, Sales, Warehouse Staff). See `Project Brief - Programmer.pdf`
for the full specification this repo implements.

> **Status: scaffold only.** Folder structure, Docker setup, the
> Controller/Service/Repository skeleton, a draft DB schema, and doc
> templates are in place. Business logic (auth, PO/SO workflows, dashboard,
> reports) is not implemented yet - see `docs/quality/tech-debt.md` and the
> backlog in `docs/planning/user-stories.md`.

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

## Tests & static analysis
```bash
docker compose exec web composer install   # first time / after dependency changes
docker compose exec web composer test              # unit + integration
docker compose exec web composer test:unit
docker compose exec web composer test:integration
docker compose exec web composer analyse            # PHPStan level 5
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
- Auth, PO/SO business logic, dashboard, reports, and the ARCH-02
  concurrency mechanism are not implemented yet (template stage).
- Seed data is intentionally small; must grow to the §7.1 minimums (30
  products, 25 combined orders, 2 warehouses, 2+2 non-Admin accounts)
  before final submission.

## AI usage
Disclosed in `ai-usage-log.md`.
