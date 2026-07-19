-- Olive Tree Soap Co. sample data

START TRANSACTION;



INSERT INTO categories (category_name)
VALUES
    ('Soap'),
    ('Lip Balm'),
    ('Hajj and Umrah Kits');


INSERT INTO products (category_id, product_name, base_price, stock)
VALUES
    (
        (SELECT category_id FROM categories WHERE category_name = 'Soap'),
        'Lavender Soap',
        8.00,
        25
    ),
    (
        (SELECT category_id FROM categories WHERE category_name = 'Soap'),
        'Dragonfire Soap',
        8.00,
        20
    ),
    (
        (SELECT category_id FROM categories WHERE category_name = 'Soap'),
        'Black Seed Soap',
        9.00,
        15
    ),
    (
        (SELECT category_id FROM categories WHERE category_name = 'Lip Balm'),
        'Mint Lip Balm',
        5.00,
        30
    ),
    (
        (SELECT category_id FROM categories WHERE category_name = 'Lip Balm'),
        'Vanilla Lip Balm',
        5.00,
        30
    ),
    (
        (SELECT category_id FROM categories
         WHERE category_name = 'Hajj and Umrah Kits'),
        'Regular Hajj Kit',
        35.00,
        10
    ),
    (
        (SELECT category_id FROM categories
         WHERE category_name = 'Hajj and Umrah Kits'),
        'Advanced Hajj Kit',
        55.00,
        10
    );



INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Size',
    'Regular',
    0.00
FROM products
WHERE product_name IN (
    'Lavender Soap',
    'Dragonfire Soap',
    'Black Seed Soap'
);

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Size',
    'Large',
    3.00
FROM products
WHERE product_name IN (
    'Lavender Soap',
    'Dragonfire Soap',
    'Black Seed Soap'
);

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Pack Size',
    'Single Bar',
    0.00
FROM products
WHERE product_name IN (
    'Lavender Soap',
    'Dragonfire Soap',
    'Black Seed Soap'
);

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Pack Size',
    'Pack of 3',
    14.00
FROM products
WHERE product_name IN (
    'Lavender Soap',
    'Dragonfire Soap',
    'Black Seed Soap'
);



INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Pack Size',
    'Single',
    0.00
FROM products
WHERE product_name IN ('Mint Lip Balm', 'Vanilla Lip Balm');

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Pack Size',
    'Pack of 3',
    9.00
FROM products
WHERE product_name IN ('Mint Lip Balm', 'Vanilla Lip Balm');

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Packaging',
    'Tube',
    0.00
FROM products
WHERE product_name IN ('Mint Lip Balm', 'Vanilla Lip Balm');

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Packaging',
    'Tin',
    1.00
FROM products
WHERE product_name IN ('Mint Lip Balm', 'Vanilla Lip Balm');


INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Bag Colour',
    'Black',
    0.00
FROM products
WHERE product_name IN ('Regular Hajj Kit', 'Advanced Hajj Kit');

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Bag Colour',
    'Blue',
    0.00
FROM products
WHERE product_name IN ('Regular Hajj Kit', 'Advanced Hajj Kit');

INSERT INTO product_options
    (product_id, option_name, option_value, price_adjustment)
SELECT
    product_id,
    'Bag Colour',
    'Green',
    0.00
FROM products
WHERE product_name IN ('Regular Hajj Kit', 'Advanced Hajj Kit');

COMMIT;

