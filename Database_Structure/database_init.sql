CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT,
    category_name VARCHAR(100),
    PRIMARY KEY (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT,
    category_id INT UNSIGNED ,
    product_name VARCHAR(150),
    description TEXT,
    base_price DECIMAL(10,2),
    stock INT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    PRIMARY KEY (product_id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_options (
    option_id INT UNSIGNED AUTO_INCREMENT,
    product_id INT UNSIGNED,
    option_name VARCHAR(50),
    option_value VARCHAR(100,
    price_adjustment DECIMAL(10,2) DEFAULT 0.00,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    PRIMARY KEY (option_id),

    CONSTRAINT uq_product_option
        UNIQUE (product_id, option_name, option_value),

    CONSTRAINT fk_options_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

