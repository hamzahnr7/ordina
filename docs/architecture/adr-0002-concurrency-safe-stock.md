# ADR-0002: Preventing oversell on concurrent goods issue (ARCH-02)

## Context
Two goods-issue requests for the same product+warehouse processed nearly
simultaneously must not both succeed if there isn't enough stock for both -
`ProductStock.quantity` must never go negative, and no update may silently
overwrite another.

## Decision
**TODO once the goods-issue Service is implemented.** Candidate mechanisms
(pick one and document why here before/while building SO-01):

1. **Pessimistic row lock** - `SELECT ... FOR UPDATE` on the `product_stocks`
   row inside the transaction before checking/decrementing quantity, so a
   second concurrent transaction blocks until the first commits or rolls
   back.
2. **Optimistic concurrency** - conditional
   `UPDATE product_stocks SET quantity = quantity - :qty WHERE id = :id AND
   quantity >= :qty`, checking `rowCount()`; zero rows affected means either
   "not enough stock" or "lost the race", both handled by rejecting/retrying
   the goods issue.
3. **Database CHECK constraint as a safety net** - `chk_product_stocks_quantity`
   (already in `database/schema-and-seed.sql`) rejects any write that would
   take quantity below zero, regardless of which app-level mechanism is used.

Either (1) or (2) satisfies ARCH-02 on its own; the CHECK constraint is kept
as defense-in-depth either way.

## Consequences
- Whichever mechanism is chosen, `ProductStock` update + `StockLedger` insert
  must happen inside one `beginTransaction()/commit()/rollBack()` block.
- Needs a test/scenario (TEST-02) showing: first goods issue succeeds and
  exhausts stock, second concurrent-ish goods issue for the same
  product+warehouse is rejected - not silently oversold.
