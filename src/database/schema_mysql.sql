-- =====================================================================
-- Online Local Mart System (OLMS) - MySQL schema
-- Tables follow the class diagram (Assignment Part 1, Section 3):
--   users (User / Customer / Administrator), categories, products,
--   shopping_carts, cart_items, orders, order_items, payments
-- Naming:  tables and columns use snake_case (Assignment Part 1, Section 6)
-- Usage:   mysql -u root -p < src/database/schema_mysql.sql
--          (or import this file in phpMyAdmin)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS olms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE olms;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS shopping_carts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Customer and Administrator both inherit from User, so they share one
-- table and are told apart by "role". Customer-only fields may be empty
-- for administrators.
CREATE TABLE users (
    user_id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name             VARCHAR(100)  NOT NULL,
    email            VARCHAR(150)  NOT NULL,
    password_hash    VARCHAR(255)  NOT NULL,
    role             VARCHAR(20)   NOT NULL DEFAULT 'customer',
    shipping_address VARCHAR(255)  NOT NULL DEFAULT '',
    phone_number     VARCHAR(20)   NOT NULL DEFAULT '',
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'administrator'))
) ENGINE = InnoDB;

CREATE TABLE categories (
    category_id   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    -- Readable code made from the name (Fresh Produce -> FRP); category_id stays the key
    category_code VARCHAR(10)   NOT NULL,
    category_name VARCHAR(50)   NOT NULL,
    description   VARCHAR(255)  NOT NULL DEFAULT '',
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_category_code (category_code),
    UNIQUE KEY uq_categories_category_name (category_name)
) ENGINE = InnoDB;

CREATE TABLE products (
    product_id     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    category_id    INT UNSIGNED   NOT NULL,
    -- Readable code: category code + running number (FRP-001); product_id stays the key
    product_code   VARCHAR(20)    NOT NULL,
    product_name   VARCHAR(100)   NOT NULL,
    description    TEXT           NOT NULL,
    price          DECIMAL(10, 2) NOT NULL,
    stock_quantity INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Soft delete: a deleted product keeps its row (and ID) and can be restored from Delete History
    deleted_at     DATETIME       NULL DEFAULT NULL,
    PRIMARY KEY (product_id),
    UNIQUE KEY uq_products_product_code (product_code),
    KEY idx_products_product_name (product_name),
    CONSTRAINT fk_products_category_id FOREIGN KEY (category_id)
        REFERENCES categories (category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

-- One active cart per customer (class diagram: Customer 1 - 1 ShoppingCart)
CREATE TABLE shopping_carts (
    cart_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cart_id),
    UNIQUE KEY uq_shopping_carts_user_id (user_id),
    CONSTRAINT fk_shopping_carts_user_id FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE cart_items (
    cart_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id      INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    PRIMARY KEY (cart_item_id),
    UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id),
    CONSTRAINT fk_cart_items_cart_id FOREIGN KEY (cart_id)
        REFERENCES shopping_carts (cart_id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product_id FOREIGN KEY (product_id)
        REFERENCES products (product_id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE orders (
    order_id         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED   NOT NULL,
    order_date       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount     DECIMAL(10, 2) NOT NULL,
    order_status     VARCHAR(20)    NOT NULL DEFAULT 'Pending Payment',
    shipping_address VARCHAR(255)   NOT NULL,
    updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (order_id),
    KEY idx_orders_order_status (order_status),
    CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE RESTRICT
) ENGINE = InnoDB;

-- product_name and price are copied into the order so old orders stay
-- correct even after a product is renamed, repriced or deleted.
CREATE TABLE order_items (
    order_item_id INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED   NOT NULL,
    product_id    INT UNSIGNED   NULL,
    product_name  VARCHAR(100)   NOT NULL,
    quantity      INT UNSIGNED   NOT NULL,
    price         DECIMAL(10, 2) NOT NULL,
    subtotal      DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (order_item_id),
    CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id)
        REFERENCES orders (order_id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product_id FOREIGN KEY (product_id)
        REFERENCES products (product_id) ON DELETE SET NULL
) ENGINE = InnoDB;

-- An order has zero or one payment (class diagram: Order 1 - 0..1 Payment)
CREATE TABLE payments (
    payment_id     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    order_id       INT UNSIGNED   NOT NULL,
    payment_method VARCHAR(30)    NOT NULL,
    payment_status VARCHAR(20)    NOT NULL,
    amount         DECIMAL(10, 2) NOT NULL,
    payment_date   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (payment_id),
    UNIQUE KEY uq_payments_order_id (order_id),
    CONSTRAINT fk_payments_order_id FOREIGN KEY (order_id)
        REFERENCES orders (order_id) ON DELETE CASCADE
) ENGINE = InnoDB;

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

INSERT INTO categories (category_code, category_name, description) VALUES
    ('FRP', 'Fresh Produce', 'Vegetables, fruits and eggs from local farms'),
    ('BAK', 'Bakery',        'Bread, buns and kuih baked daily'),
    ('BEV', 'Beverages',     'Coffee, tea, juices and drinking water'),
    ('PAN', 'Pantry',        'Rice, cooking oil, sauces and dry goods'),
    ('HOU', 'Household',     'Cleaning and everyday household items');

INSERT INTO products (category_id, product_code, product_name, description, price, stock_quantity) VALUES
    (1, 'FRP-001', 'Kampung Eggs (Tray of 10)',   'Free-range kampung chicken eggs from a farm in Kuala Selangor.', 7.90, 40),
    (1, 'FRP-002', 'Fresh Kangkung 250g',         'Water spinach harvested the same morning. Best for belacan stir-fry.', 2.50, 25),
    (1, 'FRP-003', 'Cameron Highlands Tomatoes 500g', 'Firm red tomatoes grown in Cameron Highlands.', 4.20, 3),
    (2, 'BAK-001', 'Wholemeal Bread Loaf',        'Soft wholemeal sandwich loaf, 400g, no preservatives.', 4.80, 18),
    (2, 'BAK-002', 'Kaya Puff (Pack of 6)',       'Flaky pastry filled with homemade pandan kaya.', 6.00, 12),
    (3, 'BEV-001', 'Kopi O Powder 500g',          'Traditional roasted coffee powder from a family roaster in Ipoh.', 12.90, 30),
    (3, 'BEV-002', 'Mineral Water 1.5L',          'Natural mineral water, single bottle.', 1.60, 120),
    (4, 'PAN-001', 'Local White Rice 5kg',        'Premium local white rice from Sekinchan paddy fields.', 26.50, 15),
    (4, 'PAN-002', 'Homemade Sambal Paste 250g',  'Spicy chilli sambal made with dried shrimp. Keep refrigerated.', 9.50, 0),
    (5, 'HOU-001', 'Dishwashing Liquid 900ml',    'Lime-scented dishwashing liquid, gentle on hands.', 5.30, 22);

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
