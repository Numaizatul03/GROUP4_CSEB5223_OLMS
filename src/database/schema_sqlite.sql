-- =====================================================================
-- Online Local Mart System (OLMS) - SQLite schema
-- Same tables and sample data as schema_mysql.sql. Used by the automated
-- tests in tests/ and as an optional no-MySQL fallback for development.
-- =====================================================================

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS shopping_carts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id          INTEGER      PRIMARY KEY AUTOINCREMENT,
    name             VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE COLLATE NOCASE,
    password_hash    VARCHAR(255) NOT NULL,
    role             VARCHAR(20)  NOT NULL DEFAULT 'customer' CHECK (role IN ('customer', 'administrator')),
    shipping_address VARCHAR(255) NOT NULL DEFAULT '',
    phone_number     VARCHAR(20)  NOT NULL DEFAULT '',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    category_id   INTEGER      PRIMARY KEY AUTOINCREMENT,
    category_name VARCHAR(50)  NOT NULL UNIQUE COLLATE NOCASE,
    description   VARCHAR(255) NOT NULL DEFAULT ''
);

CREATE TABLE products (
    product_id     INTEGER        PRIMARY KEY AUTOINCREMENT,
    category_id    INTEGER        NOT NULL REFERENCES categories (category_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    product_name   VARCHAR(100)   NOT NULL,
    description    TEXT           NOT NULL,
    price          DECIMAL(10, 2) NOT NULL,
    stock_quantity INTEGER        NOT NULL DEFAULT 0,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at     DATETIME       NULL DEFAULT NULL
);
CREATE INDEX idx_products_product_name ON products (product_name);

CREATE TABLE shopping_carts (
    cart_id    INTEGER  PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER  NOT NULL UNIQUE REFERENCES users (user_id) ON DELETE CASCADE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cart_items (
    cart_item_id INTEGER PRIMARY KEY AUTOINCREMENT,
    cart_id      INTEGER NOT NULL REFERENCES shopping_carts (cart_id) ON DELETE CASCADE,
    product_id   INTEGER NOT NULL REFERENCES products (product_id) ON DELETE CASCADE,
    quantity     INTEGER NOT NULL,
    UNIQUE (cart_id, product_id)
);

CREATE TABLE orders (
    order_id         INTEGER        PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER        NOT NULL REFERENCES users (user_id) ON DELETE RESTRICT,
    order_date       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount     DECIMAL(10, 2) NOT NULL,
    order_status     VARCHAR(20)    NOT NULL DEFAULT 'Pending Payment',
    shipping_address VARCHAR(255)   NOT NULL,
    updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_orders_order_status ON orders (order_status);

CREATE TABLE order_items (
    order_item_id INTEGER        PRIMARY KEY AUTOINCREMENT,
    order_id      INTEGER        NOT NULL REFERENCES orders (order_id) ON DELETE CASCADE,
    product_id    INTEGER        NULL REFERENCES products (product_id) ON DELETE SET NULL,
    product_name  VARCHAR(100)   NOT NULL,
    quantity      INTEGER        NOT NULL,
    price         DECIMAL(10, 2) NOT NULL,
    subtotal      DECIMAL(10, 2) NOT NULL
);

CREATE TABLE payments (
    payment_id     INTEGER        PRIMARY KEY AUTOINCREMENT,
    order_id       INTEGER        NOT NULL UNIQUE REFERENCES orders (order_id) ON DELETE CASCADE,
    payment_method VARCHAR(30)    NOT NULL,
    payment_status VARCHAR(20)    NOT NULL,
    amount         DECIMAL(10, 2) NOT NULL,
    payment_date   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- Sample data
-- Test logins (development only):
--   Administrator: admin@olms.test  / Admin@123
--   Customers:     siti@olms.test, daniel@olms.test, kavitha@olms.test / Customer@123
-- ---------------------------------------------------------------------
INSERT INTO users (name, email, password_hash, role, shipping_address, phone_number) VALUES
    ('OLMS Administrator', 'admin@olms.test',   '$2y$10$qd6PPATYqmX5GD43MOdP/uQsQ/BDNr3XtSAvcWVlui0/2dQ5c2LiW', 'administrator', '', ''),
    ('Siti Nurhaliza Rahman', 'siti@olms.test', '$2y$10$Q72vK.GS//t9Vt7GhiXP1uRzvis5amMD254oGp.CqITB18gC/TPXK', 'customer', '12, Jalan Mawar 3, Taman Bukit Indah, 43000 Kajang, Selangor', '012-3456789'),
    ('Daniel Lim Wei Jie', 'daniel@olms.test',  '$2y$10$Q72vK.GS//t9Vt7GhiXP1uRzvis5amMD254oGp.CqITB18gC/TPXK', 'customer', '8-2, Residensi Seri Putra, Jalan Putra 5, 43300 Seri Kembangan, Selangor', '017-2223344'),
    ('Kavitha a/p Raj', 'kavitha@olms.test',    '$2y$10$Q72vK.GS//t9Vt7GhiXP1uRzvis5amMD254oGp.CqITB18gC/TPXK', 'customer', '45, Lorong Cempaka 2, 41200 Klang, Selangor', '019-8765432');

INSERT INTO categories (category_name, description) VALUES
    ('Fresh Produce', 'Vegetables, fruits and eggs from local farms'),
    ('Bakery',        'Bread, buns and kuih baked daily'),
    ('Beverages',     'Coffee, tea, juices and drinking water'),
    ('Pantry',        'Rice, cooking oil, sauces and dry goods'),
    ('Household',     'Cleaning and everyday household items');

INSERT INTO products (category_id, product_name, description, price, stock_quantity) VALUES
    (1, 'Kampung Eggs (Tray of 10)',   'Free-range kampung chicken eggs from a farm in Kuala Selangor.', 7.90, 40),
    (1, 'Fresh Kangkung 250g',         'Water spinach harvested the same morning. Best for belacan stir-fry.', 2.50, 25),
    (1, 'Cameron Highlands Tomatoes 500g', 'Firm red tomatoes grown in Cameron Highlands.', 4.20, 3),
    (2, 'Wholemeal Bread Loaf',        'Soft wholemeal sandwich loaf, 400g, no preservatives.', 4.80, 18),
    (2, 'Kaya Puff (Pack of 6)',       'Flaky pastry filled with homemade pandan kaya.', 6.00, 12),
    (3, 'Kopi O Powder 500g',          'Traditional roasted coffee powder from a family roaster in Ipoh.', 12.90, 30),
    (3, 'Mineral Water 1.5L',          'Natural mineral water, single bottle.', 1.60, 120),
    (4, 'Local White Rice 5kg',        'Premium local white rice from Sekinchan paddy fields.', 26.50, 15),
    (4, 'Homemade Sambal Paste 250g',  'Spicy chilli sambal made with dried shrimp. Keep refrigerated.', 9.50, 0),
    (5, 'Dishwashing Liquid 900ml',    'Lime-scented dishwashing liquid, gentle on hands.', 5.30, 22);

INSERT INTO orders (user_id, order_date, total_amount, order_status, shipping_address, updated_at) VALUES
    (2, '2026-10-20 10:15:00', 28.70, 'Delivered', '12, Jalan Mawar 3, Taman Bukit Indah, 43000 Kajang, Selangor', '2026-10-22 16:40:00'),
    (3, '2026-10-25 20:05:00', 33.80, 'Processing', '8-2, Residensi Seri Putra, Jalan Putra 5, 43300 Seri Kembangan, Selangor', '2026-10-25 20:06:00'),
    (4, '2026-10-26 09:30:00', 9.60, 'Pending Payment', '45, Lorong Cempaka 2, 41200 Klang, Selangor', '2026-10-26 09:30:00');

INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES
    (1, 1, 'Kampung Eggs (Tray of 10)', 2, 7.90, 15.80),
    (1, 6, 'Kopi O Powder 500g',        1, 12.90, 12.90),
    (2, 8, 'Local White Rice 5kg',      1, 26.50, 26.50),
    (2, 7, 'Mineral Water 1.5L',        3, 1.60, 4.80),
    (2, 2, 'Fresh Kangkung 250g',       1, 2.50, 2.50),
    (3, 4, 'Wholemeal Bread Loaf',      2, 4.80, 9.60);

INSERT INTO payments (order_id, payment_method, payment_status, amount, payment_date) VALUES
    (1, 'Online Banking', 'Completed', 28.70, '2026-10-20 10:16:00'),
    (2, 'Cash on Delivery', 'Pending', 33.80, '2026-10-25 20:06:00');
