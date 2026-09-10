# Class Diagram - As-Built

**Partial.** Covers what exists today (auth, RBAC, user management, product
availability, and master data: Category/Warehouse/Supplier/Customer/Product).
Regenerate/extend once PO/SO/dashboard/report Services are built, then finish
the "what changed vs. initial" note below.

```mermaid
classDiagram
    class Controller {
        <<abstract>>
        #view(view, data)
        #json(data, status)
        #redirect(path)
        #currentUser() array
        #currentRole() Role
        #authorize(Permission)
        #requireLogin()
    }

    class AuthController
    class DashboardController
    class UserController
    class ProductAvailabilityController
    Controller <|-- AuthController
    Controller <|-- DashboardController
    Controller <|-- UserController
    Controller <|-- ProductAvailabilityController

    class Role {
        <<enum>>
        Admin
        Sales
        WarehouseStaff
        +manageable() Role[]
    }

    class Permission {
        <<enum>>
        ManageUsers
        ApproveSalesOrder
        ProcessGoodsIssue
        ...
    }

    class Gate {
        <<static>>
        +allows(Role, Permission) bool
    }

    class MenuRegistry {
        <<static>>
        +forRole(Role) array
    }

    Controller --> Gate : authorize()
    Gate --> Permission
    Gate --> Role
    MenuRegistry --> Gate
    DashboardController --> MenuRegistry

    class AuthService {
        -UserRepositoryInterface users
        +attempt(email, password) User
    }

    class UserService {
        -UserRepositoryInterface users
        +list() User[]
        +find(id) User
        +create(input) User
        +update(id, input) User
        +setActive(id, bool)
    }

    AuthController --> AuthService
    UserController --> UserService

    class UserRepositoryInterface {
        <<interface>>
        +findById(id) User
        +findByEmail(email) User
        +findAll() User[]
        +emailExists(email, excludingId) bool
        +save(User) User
        +setActive(id, bool)
    }

    class MysqlUserRepository
    class InMemoryUserRepository
    UserRepositoryInterface <|.. MysqlUserRepository
    UserRepositoryInterface <|.. InMemoryUserRepository

    AuthService --> UserRepositoryInterface
    UserService --> UserRepositoryInterface

    class User {
        +int id
        +string name
        +string email
        +string passwordHash
        +Role role
        +bool isActive
        +toSessionArray() array
    }

    UserRepositoryInterface --> User
    UserService --> Role
```

## Master data (PRD-01, WH-01, FIND-01)

```mermaid
classDiagram
    class CategoryController
    class WarehouseController
    class SupplierController
    class CustomerController
    class ProductController
    Controller <|-- CategoryController
    Controller <|-- WarehouseController
    Controller <|-- SupplierController
    Controller <|-- CustomerController
    Controller <|-- ProductController

    class ProductService {
        -ProductRepositoryInterface products
        -CategoryRepositoryInterface categories
        -ProductStockRepositoryInterface stocks
        -ProductImageUploader images
        +paginate(filters, page) array
        +detail(id) array
        +create(input, imageFile) Product
        +update(id, input, imageFile) Product
        +setActive(id, bool)
        +isLowStock(totalStock, reorderPoint)$ bool
    }

    class ProductImageUploader {
        +upload(file) string
    }

    ProductController --> ProductService
    ProductService --> ProductImageUploader
    ProductService --> ProductRepositoryInterface
    ProductService --> CategoryRepositoryInterface
    ProductService --> ProductStockRepositoryInterface

    class ProductRepositoryInterface {
        <<interface>>
        +findById(id) Product
        +findBySku(sku) Product
        +skuExists(sku, excludingId) bool
        +paginateForListing(filters, page, perPage) array
        +save(Product) Product
        +setActive(id, bool)
    }

    class MysqlProductRepository
    class InMemoryProductRepository
    ProductRepositoryInterface <|.. MysqlProductRepository
    ProductRepositoryInterface <|.. InMemoryProductRepository

    class CategoryService
    class WarehouseService
    class SupplierService
    class CustomerService
    CategoryController --> CategoryService
    WarehouseController --> WarehouseService
    SupplierController --> SupplierService
    CustomerController --> CustomerService
    CategoryService --> CategoryRepositoryInterface
    WarehouseService --> WarehouseRepositoryInterface
    SupplierService --> SupplierRepositoryInterface
    CustomerService --> CustomerRepositoryInterface
```

Category/Warehouse/Supplier/Customer each follow the exact same
Interface + Mysql implementation shape as `ProductRepositoryInterface`
above (omitted here for brevity - see `app/Repository/Contracts/` and
`app/Repository/Mysql/`). Only `ProductRepositoryInterface` also has an
`InMemory` fake, since it's the one exercised by `tests/Unit/ProductServiceTest.php`
(ARCH-01 requires this for at least one repository, not all of them).

## What changed vs. initial, and why
- The initial diagram only sketched `ProductAvailabilityService`. Building
  login required a parallel `User`/`UserRepositoryInterface` stack, which
  turned out to need its own authorization layer (`Role`, `Permission`,
  `Gate`, `MenuRegistry`) rather than being folded into `Controller` -
  splitting it out kept `Controller` a thin base class instead of growing
  business rules into it (see `docs/architecture/adr-0004-rbac-fixed-roles-vs-dynamic.md`).
- Error handling moved from ad-hoc `http_response_code()` calls inside each
  Controller action to three typed exceptions
  (`UnauthenticatedException`/`AuthorizationException`/`NotFoundException`)
  caught once in `public/index.php` - not in the initial sketch, added so
  ERR-01's "no stack trace leaks" rule has exactly one enforcement point.
- `ProductService` ended up depending on three repositories plus an uploader
  (Category for FK validation, ProductStock for the WH-01 detail view,
  ProductImageUploader for PRD-01's upload rule) - wider than a typical
  Service in this codebase. Considered splitting further, but the extra
  interfaces would only ever have one real caller each; kept as one Service
  with a 4-argument constructor rather than introducing seams nothing else
  uses yet.

## Still to add once built
PurchaseOrder/SalesOrder Services + status-transition rules, the
StockLedger writer, and the ARCH-02 concurrency mechanism.
