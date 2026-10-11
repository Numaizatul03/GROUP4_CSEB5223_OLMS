-- =====================================================================
-- Online Local Mart System (OLMS) - MySQL schema
-- Module:  Product (Lab 3 & 4) + Category (needed by Product)
-- Naming:  tables and columns use snake_case (Assignment Part 1, Section 6)
-- Usage:   mysql -u root -p < src/database/schema_mysql.sql
--          (or import this file in phpMyAdmin)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS olms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE olms;

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    category_id   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(50)   NOT NULL,
    description   VARCHAR(255)  NOT NULL DEFAULT '',
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_category_name (category_name)
) ENGINE = InnoDB;

CREATE TABLE products (
    product_id     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    category_id    INT UNSIGNED   NOT NULL,
    product_name   VARCHAR(100)   NOT NULL,
    description    TEXT           NOT NULL,
    price          DECIMAL(10, 2) NOT NULL,
    stock_quantity INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id),
    KEY idx_products_product_name (product_name),
    CONSTRAINT fk_products_category_id FOREIGN KEY (category_id)
        REFERENCES categories (category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Sample data
-- ---------------------------------------------------------------------
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
