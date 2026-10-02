-- Ordina - Inventory & Order Management System
-- Schema + seed. Mounted into MySQL's /docker-entrypoint-initdb.d so it runs
-- automatically the first time the `mysql` service starts on an empty volume
-- (DB-01: "dapat membuat database dari kondisi kosong").
--
-- Seed meets the 7.1 / FIND-01 minimums: 1 Admin, 2 Sales, 2 Warehouse Staff,
-- 2 warehouses, 30 products (6 below reorder point), 26 orders (13 PO + 13 SO)
-- covering every status, dated across Aug-Sep 2026 so REPORT-01 can be shown
-- with two different date ranges.
--
-- Stock integrity: product_stocks is NOT typed in by hand. Every movement is a
-- stock_ledger row (opening balance = Adjustment, received PO = Receipt,
-- fulfilled SO = Issue), and product_stocks is then computed FROM the ledger
-- at the end of this file - so the two tables reconcile by construction.

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
-- stock_ledger - append-only movement log (1.3, ARCH-02)
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
-- Seed data (see note at the top of this file)
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
    ('Admin Utama', 'admin@ordina.test', '$2y$10$nmGUHX3c7FAprybZDpmGFOJn98K0F4Ii118qz41xcA9fkfv9vxrI2', 'Admin', 1),
    ('Sales Satu', 'sales1@ordina.test', '$2y$10$nmGUHX3c7FAprybZDpmGFOJn98K0F4Ii118qz41xcA9fkfv9vxrI2', 'Sales', 1),
    ('Sales Dua', 'sales2@ordina.test', '$2y$10$nmGUHX3c7FAprybZDpmGFOJn98K0F4Ii118qz41xcA9fkfv9vxrI2', 'Sales', 1),
    ('Gudang Satu', 'wh1@ordina.test', '$2y$10$nmGUHX3c7FAprybZDpmGFOJn98K0F4Ii118qz41xcA9fkfv9vxrI2', 'WarehouseStaff', 1),
    ('Gudang Dua', 'wh2@ordina.test', '$2y$10$nmGUHX3c7FAprybZDpmGFOJn98K0F4Ii118qz41xcA9fkfv9vxrI2', 'WarehouseStaff', 1);

INSERT INTO suppliers (name, contact, address, is_active) VALUES
    ('Supplier Elektronik Nusantara', 'purchasing@elektroniknusantara.test', 'Jakarta', 1),
    ('Supplier Alat Tulis Sejahtera', 'sales@atksejahtera.test', 'Surabaya', 1),
    ('PT Kertas Prima', 'order@kertasprima.test', 'Bandung', 1),
    ('CV Gadget Mandiri', 'cs@gadgetmandiri.test', 'Semarang', 1);

INSERT INTO customers (name, contact, address, is_active) VALUES
    ('Toko Maju Jaya', 'majujaya@example.test', 'Jakarta', 1),
    ('CV Berkah Abadi', 'berkahabadi@example.test', 'Surabaya', 1),
    ('PT Sinar Kantor', 'procurement@sinarkantor.test', 'Bekasi', 1),
    ('Koperasi Karyawan Sejahtera', 'kopkar@example.test', 'Sidoarjo', 1);

-- 30 products, ids 1-30 in insert order. Reorder points vary from 5 to 60.
-- After the movements below, 6 active products end up below reorder point:
-- 6 Webcam, 8 Mouse Pad, 14 Monitor, 21 Correction Tape, 28 Kalkulator, and
-- 30 Stempel (no stock row at all). 29 Whiteboard is inactive (discontinued).
INSERT INTO products (sku, name, category_id, unit, buy_price, sell_price, reorder_point, is_active) VALUES
    ('SKU-0001', 'Wireless Mouse', 1, 'pcs', 45000, 75000, 20, 1),
    ('SKU-0002', 'Mechanical Keyboard', 1, 'pcs', 250000, 399000, 10, 1),
    ('SKU-0003', 'A4 Paper Ream', 2, 'ream', 38000, 52000, 50, 1),
    ('SKU-0004', 'Ballpoint Pen (Box)', 2, 'box', 15000, 25000, 30, 1),
    ('SKU-0005', 'USB-C Hub 4-Port', 1, 'pcs', 85000, 135000, 15, 1),
    ('SKU-0006', 'Webcam 1080p', 1, 'pcs', 150000, 245000, 10, 1),
    ('SKU-0007', 'Laptop Stand Aluminium', 1, 'pcs', 95000, 159000, 12, 1),
    ('SKU-0008', 'Mouse Pad', 1, 'pcs', 20000, 35000, 25, 1),
    ('SKU-0009', 'Kabel HDMI 2m', 1, 'pcs', 25000, 45000, 30, 1),
    ('SKU-0010', 'Power Bank 10000mAh', 1, 'pcs', 110000, 179000, 15, 1),
    ('SKU-0011', 'Speaker Bluetooth Mini', 1, 'pcs', 130000, 219000, 10, 1),
    ('SKU-0012', 'SSD Eksternal 512GB', 1, 'pcs', 650000, 899000, 8, 1),
    ('SKU-0013', 'Flashdisk 32GB', 1, 'pcs', 45000, 75000, 40, 1),
    ('SKU-0014', 'Monitor LED 24 Inch', 1, 'pcs', 1450000, 1899000, 5, 1),
    ('SKU-0015', 'Keyboard Wireless Compact', 1, 'pcs', 175000, 279000, 12, 1),
    ('SKU-0016', 'Mouse Pad Gaming XL', 1, 'pcs', 55000, 89000, 20, 1),
    ('SKU-0017', 'Docking Station USB-C', 1, 'pcs', 420000, 599000, 8, 1),
    ('SKU-0018', 'Sticky Notes', 2, 'pack', 8000, 15000, 60, 1),
    ('SKU-0019', 'Binder Clip (Box)', 2, 'box', 12000, 20000, 40, 1),
    ('SKU-0020', 'Spidol Whiteboard (Set)', 2, 'set', 25000, 42000, 30, 1),
    ('SKU-0021', 'Correction Tape', 2, 'pcs', 6000, 12000, 50, 1),
    ('SKU-0022', 'Stapler Heavy Duty', 2, 'pcs', 35000, 59000, 20, 1),
    ('SKU-0023', 'Paper Clip (Box)', 2, 'box', 5000, 10000, 60, 1),
    ('SKU-0024', 'Notebook A5 Hardcover', 2, 'pcs', 18000, 32000, 40, 1),
    ('SKU-0025', 'Highlighter 6 Warna', 2, 'set', 22000, 38000, 35, 1),
    ('SKU-0026', 'Amplop Coklat A4 (Pack)', 2, 'pack', 30000, 50000, 25, 1),
    ('SKU-0027', 'Map Plastik', 2, 'pcs', 7000, 13000, 50, 1),
    ('SKU-0028', 'Kalkulator Scientific', 2, 'pcs', 65000, 105000, 15, 1),
    ('SKU-0029', 'Whiteboard Kecil 60x45', 2, 'pcs', 120000, 189000, 10, 0),
    ('SKU-0030', 'Stempel Custom', 2, 'pcs', 45000, 79000, 15, 1);

-- ---------------------------------------------------------------------------
-- Purchase Orders (13): Draft x3, Ordered x3, PartiallyReceived x2,
-- Received x3, Cancelled x2. Created by Admin (1) or Warehouse Staff (4, 5).
-- ---------------------------------------------------------------------------
INSERT INTO purchase_orders (supplier_id, warehouse_id, status, order_date, created_by) VALUES
    (1, 1, 'Draft',             '2026-08-03', 1),
    (2, 2, 'Ordered',           '2026-08-05', 1),
    (1, 1, 'Received',          '2026-08-07', 4),
    (3, 2, 'Received',          '2026-08-10', 5),
    (1, 2, 'PartiallyReceived', '2026-08-12', 5),
    (2, 1, 'Cancelled',         '2026-08-14', 1),
    (4, 1, 'Ordered',           '2026-08-18', 4),
    (3, 1, 'Received',          '2026-08-20', 4),
    (1, 2, 'Draft',             '2026-08-25', 5),
    (2, 2, 'PartiallyReceived', '2026-09-01', 1),
    (4, 1, 'Cancelled',         '2026-09-04', 4),
    (3, 2, 'Ordered',           '2026-09-10', 5),
    (1, 1, 'Draft',             '2026-09-15', 1);

-- qty_received must match the Receipt ledger rows further down.
INSERT INTO purchase_order_items (purchase_order_id, product_id, qty_ordered, qty_received, buy_price) VALUES
    (1, 2, 20, 0, 250000),
    (2, 4, 50, 0, 15000),
    (3, 5, 10, 10, 85000),
    (3, 9, 20, 20, 25000),
    (4, 18, 30, 30, 8000),
    (5, 1, 25, 10, 45000),
    (6, 3, 40, 0, 38000),
    (7, 14, 5, 0, 1450000),
    (8, 24, 20, 20, 18000),
    (9, 6, 10, 0, 150000),
    (10, 20, 20, 8, 25000),
    (10, 22, 10, 10, 35000),
    (11, 12, 4, 0, 650000),
    (12, 21, 60, 0, 6000),
    (13, 28, 20, 0, 65000);

-- ---------------------------------------------------------------------------
-- Sales Orders (13): Draft x3, PendingApproval x3, Approved x2, Fulfilled x3,
-- Cancelled x2. Created by Sales (2, 3); every approved_by is Admin (1), never
-- the creator (chk_so_approver_not_creator).
-- ---------------------------------------------------------------------------
INSERT INTO sales_orders (customer_id, warehouse_id, status, created_by, approved_by, created_at) VALUES
    (1, 1, 'Draft',           2, NULL, '2026-08-04 09:00:00'),
    (2, 2, 'PendingApproval', 3, NULL, '2026-08-06 10:15:00'),
    (1, 1, 'Fulfilled',       2, 1,    '2026-08-08 11:00:00'),
    (3, 2, 'Fulfilled',       3, 1,    '2026-08-11 13:30:00'),
    (2, 1, 'Approved',        2, 1,    '2026-08-16 14:00:00'),
    (4, 2, 'Cancelled',       3, NULL, '2026-08-19 08:45:00'),
    (1, 2, 'PendingApproval', 2, NULL, '2026-08-23 15:20:00'),
    (3, 1, 'Fulfilled',       3, 1,    '2026-08-27 09:40:00'),
    (2, 1, 'Draft',           3, NULL, '2026-09-02 10:10:00'),
    (4, 2, 'Approved',        2, 1,    '2026-09-05 11:25:00'),
    (1, 1, 'Cancelled',       2, NULL, '2026-09-08 16:05:00'),
    (3, 2, 'PendingApproval', 3, NULL, '2026-09-12 13:00:00'),
    (4, 1, 'Draft',           2, NULL, '2026-09-16 16:30:00');

-- sell_price is never below the product's own price (SalesOrderService rule);
-- SO 13 shows a raised price (52,000 vs 50,000).
INSERT INTO sales_order_items (sales_order_id, product_id, qty, sell_price) VALUES
    (1, 1, 5, 75000),
    (2, 3, 10, 52000),
    (3, 9, 10, 45000),
    (3, 13, 5, 75000),
    (4, 18, 20, 15000),
    (5, 7, 3, 159000),
    (6, 10, 5, 179000),
    (7, 16, 4, 89000),
    (8, 24, 15, 32000),
    (8, 23, 10, 10000),
    (9, 11, 2, 219000),
    (10, 25, 6, 38000),
    (11, 15, 2, 279000),
    (12, 19, 12, 20000),
    (13, 26, 5, 52000);

-- ---------------------------------------------------------------------------
-- Stock ledger: quantity is always positive, movement_type sets direction.
-- ---------------------------------------------------------------------------

-- Opening balance per product+warehouse (stock taken over on go-live).
-- Product 30 deliberately has none.
INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by, created_at) VALUES
    (1, 1, 'Adjustment', 15, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (1, 2, 'Adjustment', 40, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (2, 1, 'Adjustment', 5, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),  (2, 2, 'Adjustment', 12, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (3, 1, 'Adjustment', 80, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (3, 2, 'Adjustment', 60, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (4, 1, 'Adjustment', 10, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (4, 2, 'Adjustment', 45, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (5, 1, 'Adjustment', 20, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (5, 2, 'Adjustment', 25, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (6, 1, 'Adjustment', 4, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),  (6, 2, 'Adjustment', 3, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (7, 1, 'Adjustment', 15, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (7, 2, 'Adjustment', 14, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (8, 1, 'Adjustment', 10, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (8, 2, 'Adjustment', 8, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (9, 1, 'Adjustment', 40, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (9, 2, 'Adjustment', 35, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (10, 1, 'Adjustment', 18, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (10, 2, 'Adjustment', 20, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (11, 1, 'Adjustment', 12, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (11, 2, 'Adjustment', 9, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (12, 1, 'Adjustment', 10, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (12, 2, 'Adjustment', 6, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (13, 1, 'Adjustment', 50, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (13, 2, 'Adjustment', 45, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (14, 1, 'Adjustment', 2, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (14, 2, 'Adjustment', 1, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (15, 1, 'Adjustment', 14, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (15, 2, 'Adjustment', 13, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (16, 1, 'Adjustment', 22, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (16, 2, 'Adjustment', 18, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (17, 1, 'Adjustment', 9, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (17, 2, 'Adjustment', 7, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (18, 1, 'Adjustment', 70, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (18, 2, 'Adjustment', 60, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (19, 1, 'Adjustment', 45, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (19, 2, 'Adjustment', 40, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (20, 1, 'Adjustment', 32, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (20, 2, 'Adjustment', 30, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (21, 1, 'Adjustment', 20, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (21, 2, 'Adjustment', 15, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (22, 1, 'Adjustment', 22, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (22, 2, 'Adjustment', 20, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (23, 1, 'Adjustment', 65, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (23, 2, 'Adjustment', 60, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (24, 1, 'Adjustment', 45, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (24, 2, 'Adjustment', 42, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (25, 1, 'Adjustment', 38, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (25, 2, 'Adjustment', 35, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (26, 1, 'Adjustment', 28, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (26, 2, 'Adjustment', 25, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (27, 1, 'Adjustment', 55, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (27, 2, 'Adjustment', 50, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (28, 1, 'Adjustment', 5, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (28, 2, 'Adjustment', 4, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'),
    (29, 1, 'Adjustment', 12, 'Adjustment', NULL, 1, '2026-08-01 08:00:00'), (29, 2, 'Adjustment', 10, 'Adjustment', NULL, 1, '2026-08-01 08:00:00');

-- Goods receipts - one row per received PO line, qty = qty_received above.
INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by, created_at) VALUES
    (5, 1, 'Receipt', 10, 'PO', 3, 4, '2026-08-09 10:00:00'),
    (9, 1, 'Receipt', 20, 'PO', 3, 4, '2026-08-09 10:00:00'),
    (18, 2, 'Receipt', 30, 'PO', 4, 5, '2026-08-12 09:30:00'),
    (1, 2, 'Receipt', 10, 'PO', 5, 5, '2026-08-15 14:00:00'),
    (24, 1, 'Receipt', 20, 'PO', 8, 4, '2026-08-22 11:15:00'),
    (20, 2, 'Receipt', 8, 'PO', 10, 5, '2026-09-03 10:45:00'),
    (22, 2, 'Receipt', 10, 'PO', 10, 5, '2026-09-03 10:45:00');

-- Goods issues - one row per line of each Fulfilled SO, from its own warehouse.
INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by, created_at) VALUES
    (9, 1, 'Issue', 10, 'SO', 3, 4, '2026-08-09 15:00:00'),
    (13, 1, 'Issue', 5, 'SO', 3, 4, '2026-08-09 15:00:00'),
    (18, 2, 'Issue', 20, 'SO', 4, 5, '2026-08-13 10:20:00'),
    (24, 1, 'Issue', 15, 'SO', 8, 4, '2026-08-29 09:10:00'),
    (23, 1, 'Issue', 10, 'SO', 8, 4, '2026-08-29 09:10:00');

-- product_stocks derived from the ledger, never typed separately - so
-- SUM(ledger) == product_stocks.quantity for every product+warehouse.
INSERT INTO product_stocks (product_id, warehouse_id, quantity)
SELECT product_id,
       warehouse_id,
       SUM(CASE WHEN movement_type = 'Issue' THEN -quantity ELSE quantity END)
FROM stock_ledger
GROUP BY product_id, warehouse_id;
