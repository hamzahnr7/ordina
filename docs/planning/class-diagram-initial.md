# Class Diagram - Initial (before coding)

Per DESIGN-01, this snapshot must predate implementation. Compare against
`docs/architecture/class-diagram-as-built.md` at the end and note what changed.

```mermaid
classDiagram
    class Controller {
        <<abstract>>
        #view(view, data)
        #json(data, status)
        #redirect(path)
        #requireLogin()
    }

    class AuthController
    class ProductAvailabilityController
    Controller <|-- AuthController
    Controller <|-- ProductAvailabilityController

    class ProductAvailabilityService {
        -ProductRepositoryInterface products
        -ProductStockRepositoryInterface stocks
        +availabilityBySku(sku) array
    }

    class ProductRepositoryInterface {
        <<interface>>
        +findBySku(sku) Product
        +findAll() Product[]
        +save(product)
    }

    class ProductStockRepositoryInterface {
        <<interface>>
        +findByProductId(id) array
    }

    class MysqlProductRepository
    class InMemoryProductRepository
    ProductRepositoryInterface <|.. MysqlProductRepository
    ProductRepositoryInterface <|.. InMemoryProductRepository

    class MysqlProductStockRepository
    class InMemoryProductStockRepository
    ProductStockRepositoryInterface <|.. MysqlProductStockRepository
    ProductStockRepositoryInterface <|.. InMemoryProductStockRepository

    ProductAvailabilityService --> ProductRepositoryInterface
    ProductAvailabilityService --> ProductStockRepositoryInterface
    ProductAvailabilityController --> ProductAvailabilityService

    class Product {
        +int id
        +string sku
        +string name
        +float buyPrice
        +float sellPrice
        +int reorderPoint
    }

    ProductRepositoryInterface --> Product
```

## Planned but not yet modelled here
PurchaseOrder/SalesOrder services and their status-transition rules,
StockLedger writer, and the concurrency-safe stock-mutation mechanism
(ARCH-02) - to be added as those slices are built, then reconciled in the
as-built diagram.
