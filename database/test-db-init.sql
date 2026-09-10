-- Provisions the dedicated integration-test database (TEST-02).
-- Runs automatically on a FRESH mysql volume, alongside schema-and-seed.sql
-- (docker-entrypoint-initdb.d runs *.sql files in name order; "schema-and-
-- seed.sql" sorts before "test-db-init.sql" so `ordina` exists first, though
-- the two are otherwise independent).
--
-- Already have a running mysql container from before this file existed? The
-- entrypoint only runs init scripts once, against an empty data directory,
-- so apply this by hand instead of recreating the volume:
--   docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < database/test-db-init.sql
--
-- NOTE: table structure here is intentionally a copy of schema-and-seed.sql
-- (no seed rows - each integration test sets up its own fixtures, per
-- FIRST's "Independent"). Keep both files in sync by hand for now; see
-- docs/quality/tech-debt.md for the tracked follow-up.

CREATE DATABASE IF NOT EXISTS ordina_test CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS 'tester'@'%' IDENTIFIED BY 'testing123';
-- Force the password even if 'tester'@'%' already existed from an earlier,
-- half-applied run - CREATE USER IF NOT EXISTS alone would silently skip
-- re-setting it in that case.
ALTER USER 'tester'@'%' IDENTIFIED BY 'testing123';
GRANT ALL PRIVILEGES ON ordina_test.* TO 'tester'@'%';
FLUSH PRIVILEGES;

USE ordina_test;

SET FOREIGN_KEY_CHECKS = 0;

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

CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

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
