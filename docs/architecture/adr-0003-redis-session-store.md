# ADR-0003: Redis for session storage only (not for business data)

## Context
The user asked to use Redis on this project to make development easier. The
brief (§4 Ketentuan Teknis) requires MySQL 8 as the database and explicitly
disallows "NoSQL sebagai penyimpanan utama" (NoSQL as primary storage), and
separately warns that unjustified extra layers are marked down as
over-engineering just like messy code.

## Decision
Scope Redis to **PHP session storage only**, toggled by `REDIS_ENABLED` in
`.env` (defaults to `false`, i.e. plain file-based PHP sessions):
- `App\Core\RedisSessionHandler` implements `SessionHandlerInterface` using
  the native `redis` PECL extension (installed in `docker/php/Dockerfile`) -
  no Composer package, keeping it inside the brief's "Composer for
  autoload/dev-dependency only" boundary for backend libraries.
- MySQL remains the single system-of-record for every business entity
  (users, products, stock, orders, StockLedger). Redis holds nothing that
  the brief's `database/schema-and-seed.sql` doesn't already own.
- If Redis is ever repurposed for caching (e.g. memoising dashboard
  aggregation queries), that needs its own ADR entry, since it changes the
  argument for why it isn't "NoSQL as primary storage."

## Consequences
- One extra Docker Compose service (`redis`) beyond the brief's minimum
  (web + MySQL) - acceptable because it's optional (`REDIS_ENABLED=false`
  still works with zero code changes) and does not replace any required
  MySQL table.
- Assessors can ignore Redis entirely and the grading criteria in §2/§3
  are unaffected, since none of them depend on it.
- If asked "why Redis" during technical defense: it's a session store swap,
  not an architectural pillar - the safe answer is "removable without
  touching business logic."
