# ADR-0002: Preventing oversell on concurrent goods issue (ARCH-02)

## Context
Two goods-issue requests for the same product+warehouse processed nearly
simultaneously must not both succeed if there isn't enough stock for both -
`ProductStock.quantity` must never go negative, and no update may silently
overwrite another.

## Decision
**Chosen: optimistic conditional `UPDATE`** (option 2 below), implemented in
`ProductStockRepositoryInterface::decrementIfAvailable()`:

```sql
UPDATE product_stocks
SET quantity = quantity - :qty
WHERE product_id = :product_id AND warehouse_id = :warehouse_id AND quantity >= :qty_check
```
(`:qty` and `:qty_check` are the same value under two names - MySQL's native
prepared statements, which this app uses, can't bind one value to a named
placeholder used twice in the same query.)

The method returns `$stmt->rowCount() > 0`. If nothing matched - either the
row didn't exist, or `quantity >= qty` was false at the moment MySQL
evaluated it - the caller (`SalesOrderService::processGoodsIssue()`) treats
it as "insufficient stock" and rolls back the whole goods-issue transaction.

**Why this over a `SELECT ... FOR UPDATE` + separate `UPDATE`:** the check
and the decrement are the *same* SQL statement here. InnoDB takes the
row-level exclusive lock as part of executing the `UPDATE`, so two
concurrent goods-issue requests against the same `product_stocks` row are
serialized by the storage engine itself - the second one physically cannot
start its `UPDATE` until the first's transaction commits or rolls back, and
when it does run, it re-evaluates `quantity >= :qty_check` against
whatever `quantity` the first request left behind. There is no window where
both requests read a stale `quantity` and both decide they have enough
stock - that race is exactly what a naive "SELECT then UPDATE if enough" (a
non-locking read followed by a write) would allow. A manual
`SELECT ... FOR UPDATE` would reach the same correctness with an extra
round-trip and an explicit lock the code has to remember to take; letting a
single conditional `UPDATE` do the atomic check-and-write was preferred here
as the simpler, harder-to-get-wrong option that's still provably race-free.

Candidates considered:

1. **Pessimistic row lock** - `SELECT ... FOR UPDATE` on the `product_stocks`
   row inside the transaction before checking/decrementing quantity, so a
   second concurrent transaction blocks until the first commits or rolls
   back. Correct, but needs a separate locked read before the write; not
   chosen only because the single conditional `UPDATE` below achieves the
   same guarantee more simply.
2. **Optimistic concurrency (chosen)** - see above.
3. **Database CHECK constraint as a safety net** - `chk_product_stocks_quantity`
   (already in `database/schema-and-seed.sql`) rejects any write that would
   take quantity below zero, regardless of which app-level mechanism is used.
   Kept as defense-in-depth alongside (2), not a replacement for it.

## Consequences
- `ProductStock` update + `StockLedger` insert happen inside one
  `beginTransaction()/commit()/rollBack()` block (via
  `TransactionManagerInterface`, see below) for every item in the Sales
  Order - if any one item's stock is insufficient, the whole goods issue
  rolls back, including items already decremented earlier in the same loop.
- Proven by `tests/Unit/SalesOrderServiceTest.php` (sequential, controlled
  scenario per the brief's explicit allowance - real parallel threads not
  required): first goods issue exhausts stock, a second goods issue for the
  same product+warehouse is rejected with the Sales Order left `Approved`
  (not silently oversold, not left half-issued).

## What's built

`App\Core\Transaction\TransactionManagerInterface` (+ `PdoTransactionManager`
for production, `NullTransactionManager` for unit tests) carries both
PO-01's goods receipt and SO-01's goods issue:
`SalesOrderService::processGoodsIssue()` calls
`transactions->transactional(fn () => ...)`, looping items and calling
`ProductStockRepositoryInterface::decrementIfAvailable()` for each; the
first item that comes back insufficient throws, rolling back every write
made so far in that same goods issue.

Goods **receipt** (PO-01) never needed option (1) or (2): receiving can only
ever move `ProductStock.quantity` up, so there is no oversell-style race to
prevent - its transaction exists purely for atomicity (so a crash/exception
between the stock update and the ledger insert can't leave them
inconsistent), not for concurrency control. The one race specific to
receipt - two staff submitting a receipt for the same PO item at once,
potentially exceeding `qty_ordered` - is instead caught by
`PurchaseOrderService::receiveGoods()`'s remaining-quantity check plus the
`chk_poi_quantities` CHECK constraint as a backstop; that's a softer,
contractual limit, not a stock-integrity invariant, so a full pessimistic
lock was judged unnecessary scope for PO (see `docs/quality/tech-debt.md`).
Goods **issue** (SO-01) is where the decision above is actually load-bearing.
