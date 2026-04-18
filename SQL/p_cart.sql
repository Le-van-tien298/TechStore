CREATE TABLE p_cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    FOREIGN KEY (username)   REFERENCES p_users(username),
    FOREIGN KEY (product_id) REFERENCES p_product(id)
);