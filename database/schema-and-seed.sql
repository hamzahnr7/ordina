-- Ordina - Inventory & Order Management System
-- Schema + seed. Mounted into MySQL's /docker-entrypoint-initdb.d so it runs
-- automatically the first time the `mysql` service starts on an empty volume
-- (DB-01: "dapat membuat database dari kondisi kosong").
--
-- NOTE (template stage): the seed below only has a handful of rows per table
-- so the schema can be sanity-checked early. Before final submission this
-- must be expanded to the §7.1 minimums: >=30 products (varied reorder
-- points, some below it), >=2 warehouses, >=25 PO+SO combined with varied
-- statuses (including PendingApproval and Cancelled examples), >=2 Sales and
-- >=2 Warehouse Staff accounts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Sales', 'WarehouseStaff') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- warehouses
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- products
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    unit VARCHAR(30) NOT NULL,
    buy_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
    sell_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
    reorder_point INT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_sku (sku),
    KEY idx_products_name (name),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT chk_products_prices CHECK (buy_price >= 0 AND sell_price >= 0 AND reorder_point >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- product_stocks (one row per product+warehouse, WH-01)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_stocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_stocks_product_warehouse (product_id, warehouse_id),
    CONSTRAINT fk_product_stocks_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_product_stocks_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id),
    CONSTRAINT chk_product_stocks_quantity CHECK (quantity >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- suppliers / customers
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- purchase_orders + items (PO-01)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    status ENUM('Draft', 'Ordered', 'PartiallyReceived', 'Received', 'Cancelled') NOT NULL DEFAULT 'Draft',
    order_date DATE NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_purchase_orders_status (status),
    CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id),
    CONSTRAINT fk_po_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id),
    CONSTRAINT fk_po_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty_ordered INT UNSIGNED NOT NULL,
    qty_received INT UNSIGNED NOT NULL DEFAULT 0,
    buy_price DECIMAL(14, 2) NOT NULL,
    CONSTRAINT fk_poi_purchase_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT chk_poi_quantities CHECK (qty_ordered > 0 AND qty_received >= 0 AND qty_received <= qty_ordered)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- sales_orders + items (SO-01)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    status ENUM('Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled') NOT NULL DEFAULT 'Draft',
    created_by INT UNSIGNED NOT NULL,
    approved_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sales_orders_status (status),
    KEY idx_sales_orders_created_by (created_by),
    CONSTRAINT fk_so_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_so_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id),
    CONSTRAINT fk_so_created_by FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_so_approved_by FOREIGN KEY (approved_by) REFERENCES users (id),
    -- Segregation of duties (SO-01): enforced again in the Service layer, but
    -- mirrored here as a last-resort guard against a buggy approve call.
    CONSTRAINT chk_so_approver_not_creator CHECK (approved_by IS NULL OR approved_by <> created_by)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty INT UNSIGNED NOT NULL,
    sell_price DECIMAL(14, 2) NOT NULL,
    CONSTRAINT fk_soi_sales_order FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_soi_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT chk_soi_qty CHECK (qty > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- stock_ledger - append-only movement log (§1.3, ARCH-02)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_ledger (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    movement_type ENUM('Receipt', 'Issue', 'Adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('PO', 'SO', 'Adjustment') NOT NULL,
    reference_id INT UNSIGNED NULL,
    performed_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_stock_ledger_product_warehouse (product_id, warehouse_id),
    KEY idx_stock_ledger_reference (reference_type, reference_id),
    CONSTRAINT fk_ledger_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_ledger_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id),
    CONSTRAINT fk_ledger_performed_by FOREIGN KEY (performed_by) REFERENCES users (id),
    CONSTRAINT chk_ledger_quantity CHECK (quantity <> 0)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================================================
-- Seed data (starter set - expand before final submission, see note above)
-- ===========================================================================

INSERT INTO categories (name, description) VALUES
    ('Electronics', 'Consumer electronics and accessories'),
    ('Stationery', 'Office and stationery supplies');

INSERT INTO warehouses (name, location, is_active) VALUES
    ('Warehouse Jakarta', 'Jakarta', 1),
    ('Warehouse Surabaya', 'Surabaya', 1);

-- Password hashes below are placeholders. Generate real ones with:
--   docker compose exec web php scripts/hash-password.php "YourPassword123"
-- and replace the values before relying on these accounts.
INSERT INTO users (name, email, password_hash, role, is_active) VALUES
    ('Admin Utama', 'admin@ordina.test', '$2y$10$tgokP.e/vj6yblLgybc9H.kyLbBCx8CB.5.54z7D.Aaz1.62P3woG', 'Admin', 1),
    ('Sales Satu', 'sales1@ordina.test', '$2y$10$tgokP.e/vj6yblLgybc9H.kyLbBCx8CB.5.54z7D.Aaz1.62P3woG', 'Sales', 1),
    ('Sales Dua', 'sales2@ordina.test', '$2y$10$tgokP.e/vj6yblLgybc9H.kyLbBCx8CB.5.54z7D.Aaz1.62P3woG', 'Sales', 1),
    ('Gudang Satu', 'wh1@ordina.test', '$2y$10$tgokP.e/vj6yblLgybc9H.kyLbBCx8CB.5.54z7D.Aaz1.62P3woG', 'WarehouseStaff', 1),
    ('Gudang Dua', 'wh2@ordina.test', '$2y$10$tgokP.e/vj6yblLgybc9H.kyLbBCx8CB.5.54z7D.Aaz1.62P3woG', 'WarehouseStaff', 1);

INSERT INTO suppliers (name, contact, address, is_active) VALUES
    ('Supplier Elektronik Nusantara', 'purchasing@elektroniknusantara.test', 'Jakarta', 1),
    ('Supplier Alat Tulis Sejahtera', 'sales@atksejahtera.test', 'Surabaya', 1);

INSERT INTO customers (name, contact, address, is_active) VALUES
    ('Toko Maju Jaya', 'majujaya@example.test', 'Jakarta', 1),
    ('CV Berkah Abadi', 'berkahabadi@example.test', 'Surabaya', 1);

INSERT INTO products (sku, name, category_id, unit, buy_price, sell_price, reorder_point, is_active) VALUES
    ('SKU-0001', 'Wireless Mouse', 1, 'pcs', 45000, 75000, 20, 1),
    ('SKU-0002', 'Mechanical Keyboard', 1, 'pcs', 250000, 399000, 10, 1),
    ('SKU-0003', 'A4 Paper Ream', 2, 'ream', 38000, 52000, 50, 1),
    ('SKU-0004', 'Ballpoint Pen (Box)', 2, 'box', 15000, 25000, 30, 1);

INSERT INTO product_stocks (product_id, warehouse_id, quantity) VALUES
    (1, 1, 15), (1, 2, 40),
    (2, 1, 5),  (2, 2, 12),
    (3, 1, 80), (3, 2, 60),
    (4, 1, 10), (4, 2, 45);

-- TODO: add PurchaseOrder/SalesOrder + StockLedger seed rows once the
-- goods-receipt/goods-issue services are implemented, covering >=25 orders
-- combined with varied statuses per §7.1.
