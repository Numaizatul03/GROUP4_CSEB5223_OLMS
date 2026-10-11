-- =====================================================================
-- Online Local Mart System (OLMS) - SQLite schema
-- Same tables as schema_mysql.sql. Used by the automated tests in
-- tests/ and as an optional no-MySQL fallback for local development.
-- =====================================================================

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    category_id   INTEGER      PRIMARY KEY AUTOINCREMENT,
    category_name VARCHAR(50)  NOT NULL UNIQUE,
    description   VARCHAR(255) NOT NULL DEFAULT ''
);

CREATE TABLE products (
    product_id     INTEGER        PRIMARY KEY AUTOINCREMENT,
    category_id    INTEGER        NOT NULL REFERENCES categories (category_id) ON DELETE RESTRICT,
    product_name   VARCHAR(100)   NOT NULL,
    description    TEXT           NOT NULL,
    price          DECIMAL(10, 2) NOT NULL,
    stock_quantity INTEGER        NOT NULL DEFAULT 0,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_products_product_name ON products (product_name);

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
