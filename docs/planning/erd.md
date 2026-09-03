# ERD (Initial)

Matches `database/schema-and-seed.sql`. Redraw with the diagram tool of your choice
(draw.io, dbdiagram.io, Mermaid) and export an image/PDF here once finalised -
this Mermaid block is a placeholder so the relationships are visible from day one.

```mermaid
erDiagram
    USERS ||--o{ PURCHASE_ORDERS : creates
    USERS ||--o{ SALES_ORDERS : creates
    USERS ||--o{ SALES_ORDERS : approves
    USERS ||--o{ STOCK_LEDGER : performs

    CATEGORIES ||--o{ PRODUCTS : classifies
    PRODUCTS ||--o{ PRODUCT_STOCKS : has
    WAREHOUSES ||--o{ PRODUCT_STOCKS : holds

    SUPPLIERS ||--o{ PURCHASE_ORDERS : receives_from
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_ITEMS : contains
    PRODUCTS ||--o{ PURCHASE_ORDER_ITEMS : ordered_as

    CUSTOMERS ||--o{ SALES_ORDERS : places
    SALES_ORDERS ||--o{ SALES_ORDER_ITEMS : contains
    PRODUCTS ||--o{ SALES_ORDER_ITEMS : sold_as

    PRODUCTS ||--o{ STOCK_LEDGER : moves
    WAREHOUSES ||--o{ STOCK_LEDGER : records_at
```
