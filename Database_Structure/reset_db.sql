/*
 * Olive Tree Soap Co. development data reset
 *
 * WARNING: This permanently deletes existing rows.
 * Use only on the local development database.
 */

SET FOREIGN_KEY_CHECKS = 0;

/* Remove experimental transactional data first. */
TRUNCATE TABLE orders;

/* Empty product-related tables. */
TRUNCATE TABLE product_images;
TRUNCATE TABLE product_options;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;

/*
 * Uncomment these if you also want to remove all accounts.
 *
 * TRUNCATE TABLE users;
 * TRUNCATE TABLE testusers;
 */

SET FOREIGN_KEY_CHECKS = 1;


/* ========================================
   Restore categories
   ======================================== */

INSERT INTO categories
    (category_id, category_name)
VALUES
    (1, 'Soap'),
    (2, 'Lip Balm'),
    (3, 'Hajj and Umrah Kits');


/* ========================================
   Restore products
   ======================================== */

INSERT INTO products
    (
        product_id,
        category_id,
        product_name,
        description,
        base_price,
        stock,
        is_active
    )
VALUES
    (
        1,
        1,
        'Lavender Soap',
        '',
        8.00,
        25,
        1
    ),
    (
        2,
        1,
        'Dragonfire Soap',
        '',
        8.00,
        20,
        1
    ),
    (
        3,
        1,
        'Black Seed Soap',
        '',
        9.00,
        15,
        1
    ),
    (
        4,
        2,
        'Mint Lip Balm',
        '',
        5.00,
        30,
        1
    ),
    (
        5,
        2,
        'Vanilla Lip Balm',
        '',
        5.00,
        30,
        1
    ),
    (
        6,
        3,
        'Regular Hajj Kit',
        '',
        35.00,
        10,
        1
    ),
    (
        7,
        3,
        'Advanced Hajj Kit',
        '',
        55.00,
        10,
        1
    );


/* ========================================
   Restore Lavender Soap options
   ======================================== */

INSERT INTO product_options
    (
        option_id,
        product_id,
        option_name,
        option_value,
        price_adjustment,
        sort_order
    )
VALUES
    (1, 1, 'Size', 'Regular', 0.00, 1),
    (2, 1, 'Size', 'Large', 3.00, 2),
    (3, 1, 'Pack Size', 'Single Bar', 0.00, 1),
    (4, 1, 'Pack Size', 'Pack of 3', 14.00, 2);


/* ========================================
   Set the next automatic ID values
   ======================================== */

ALTER TABLE categories AUTO_INCREMENT = 4;
ALTER TABLE products AUTO_INCREMENT = 8;
ALTER TABLE product_options AUTO_INCREMENT = 5;
ALTER TABLE product_images AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;