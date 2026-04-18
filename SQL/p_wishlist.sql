CREATE TABLE p_wishlist (
    username    VARCHAR(50) NOT NULL,
    product_id  INT NOT NULL,
    PRIMARY KEY (username, product_id),
    FOREIGN KEY (username)   REFERENCES p_users(username),
    FOREIGN KEY (product_id) REFERENCES p_product(id)
);