# Refactoring Log

Per DESIGN-03: minimum 3 entries (smell -> technique -> before/after), one
SRP audit note, and at least one `refactor:` commit on real prior code
(Boy Scout Rule) - not on the feature currently being written.

All three entries below change structure only, not behavior. Verified by
running the Unit suite before and after (41 tests, 66 assertions, green both
times) and PHPStan level 5 (`[OK] No errors`).

---

### 1. Duplicate Code - `per_page` parsing in three controllers
- **Files**: `ProductController::index()`, `PurchaseOrderController::index()`, `SalesOrderController::index()`
- **Technique**: Extract Method (pulled up into the `Controller` base class)
- **Why**: The same two lines - and the same `[10, 25, 50]` allow-list - were
  copied into three controllers. This copy-paste had already caused a real
  bug once: the first version re-read `$_GET['per_page']` without `??` in
  the ternary's true branch, and the identical mistake had to be fixed in all
  three files separately. Adding a 100-rows option would also have needed
  three edits.
- **Before** (repeated in each of the three controllers):
  ```php
  $requestedPerPage = (int) ($_GET['per_page'] ?? 10);
  $perPage = in_array($requestedPerPage, [10, 25, 50], true) ? $requestedPerPage : 10;
  ```
- **After** - one method in `app/Core/Controller.php`, one call per controller:
  ```php
  private const DEFAULT_PER_PAGE = 10;
  private const PER_PAGE_OPTIONS = [10, 25, 50];

  protected function perPageFromQuery(): int
  {
      $requested = (int) ($_GET['per_page'] ?? self::DEFAULT_PER_PAGE);

      return in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : self::DEFAULT_PER_PAGE;
  }

  // in each index():
  $perPage = $this->perPageFromQuery();
  ```
- **Commit**: `refactor: extract shared per-page, item-row and status-badge logic` (hash: TBD)

---

### 2. Duplicate Code / Shotgun Surgery - status-to-badge mapping in four views
- **Files**: `views/purchase-orders/index.php`, `views/purchase-orders/show.php`, `views/sales-orders/index.php`, `views/sales-orders/show.php`
- **Technique**: Move Method (into the `PurchaseOrderStatus` / `SalesOrderStatus` enums)
- **Why**: Each view had its own `match()` deciding which badge colour a
  status gets. Adding or recolouring a status meant finding and editing four
  templates (Shotgun Surgery). The copies had also already drifted: the PO
  list spelled out all five statuses with a `default => 'badge-muted'`,
  while the PO detail page used `default => 'badge-success'` - same result
  today, but a new status would have shown different colours on the two
  pages. The enums already own the other status rules (`label()`,
  `canBeCancelled()`), so this is where the knowledge belongs.
- **Before** (`views/purchase-orders/index.php`; similar `match()` in the other three):
  ```php
  $statusBadge = static fn (string $status): string => match ($status) {
      'Draft' => 'badge-muted',
      'Ordered' => 'badge-success',
      'PartiallyReceived' => 'badge-success',
      'Received' => 'badge-success',
      'Cancelled' => 'badge-danger',
      default => 'badge-muted',
  };
  // ...
  <span class="badge <?= $statusBadge($po['status']) ?>">
  ```
- **After** (`app/Domain/PurchaseOrderStatus.php`; `SalesOrderStatus` has the same method):
  ```php
  public function badgeClass(): string
  {
      return match ($this) {
          self::Draft => 'badge-muted',
          self::Cancelled => 'badge-danger',
          self::Ordered, self::PartiallyReceived, self::Received => 'badge-success',
      };
  }

  // list view:
  <span class="badge <?= \App\Domain\PurchaseOrderStatus::from($po['status'])->badgeClass() ?>">
  // detail view:
  <span class="badge <?= $purchaseOrder->status->badgeClass() ?>">
  ```
  The new `match` has no `default` arm on purpose: if a status case is ever
  added to the enum, PHP throws `UnhandledMatchError` instead of silently
  picking a colour.
- **Commit**: same commit as entry 1

---

### 3. Duplicate Code - `parseItemsFromPost()` in two controllers
- **Files**: `PurchaseOrderController`, `SalesOrderController`
- **Technique**: Extract Method (pulled up into the `Controller` base class, parameterized by field names)
- **Why**: Both controllers had a 20-line private method doing the same job -
  turning the form's parallel `items[field][]` arrays into one row per order
  line and skipping the blank row - differing only in the field names
  (`qty_ordered`/`buy_price` vs `qty`/`sell_price`). A fix to how blank rows
  are detected would have had to be made twice.
- **Before** (`SalesOrderController`; the PO version was identical apart from field names):
  ```php
  private function parseItemsFromPost(): array
  {
      $productIds = $_POST['items']['product_id'] ?? [];
      $qtys = $_POST['items']['qty'] ?? [];
      $prices = $_POST['items']['sell_price'] ?? [];

      $items = [];

      foreach ($productIds as $index => $productId) {
          if ((string) $productId === '') {
              continue;
          }

          $items[] = [
              'product_id' => $productId,
              'qty' => $qtys[$index] ?? null,
              'sell_price' => $prices[$index] ?? null,
          ];
      }

      return $items;
  }
  ```
- **After** (`app/Core/Controller.php`):
  ```php
  protected function itemRowsFromPost(string ...$fields): array
  {
      $productIds = $_POST['items']['product_id'] ?? [];
      $items = [];

      foreach ($productIds as $index => $productId) {
          if ((string) $productId === '') {
              continue;
          }

          $row = ['product_id' => $productId];

          foreach ($fields as $field) {
              $row[$field] = $_POST['items'][$field][$index] ?? null;
          }

          $items[] = $row;
      }

      return $items;
  }

  // PurchaseOrderController::store()
  $this->itemRowsFromPost('qty_ordered', 'buy_price')
  // SalesOrderController::store()
  $this->itemRowsFromPost('qty', 'sell_price')
  ```
  This stays in the Controller layer on purpose: reading `$_POST` is an
  HTTP concern, and ARCH-01 forbids superglobals in Services.
- **Commit**: same commit as entry 1

---

## SRP audit

- **Class**: `App\Core\Controller` (as drawn in `docs/planning/class-diagram-initial.md`)
- **Responsibilities it had**: In the initial design, `Controller::view()`
  did two jobs. It was the HTTP base class (redirects, JSON responses,
  reading the session user, authorization checks) **and** the template
  engine: it extracted the view data, buffered the view file, and wrapped
  it in `views/layouts/app.php`.
- **Why it was a problem**: Rendering had more than one caller. The central
  error handler in `public/index.php` and the `Router`'s 404 fallback also
  needed to render a page, but they are not controllers. They ended up with
  their own copy of the render code, and those copies skipped the layout, so
  the 403/404/500 pages appeared without CSS or the sidebar. One responsibility
  living inside an unrelated class forced the duplication.
- **How it was split**: Rendering moved into a new single-purpose class,
  `App\Core\View::render()`. `Controller::view()` is now a one-line delegate,
  and `public/index.php` and `Router` call `View::render()` directly. Every page,
  including error pages, now goes through one render path and always gets the
  layout. Recorded in `docs/architecture/class-diagram-as-built.md` under
  "What changed vs. initial".
