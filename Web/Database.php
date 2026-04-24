<?php

class Database {
    private static $instance = null;
    private $conn;

    private $host     = '127.0.0.1';
    private $dbname   = 'products';
    private $username = 'root';
    private $password = '';
    private $charset  = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Kết nối thất bại: " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getConnection(): PDO { return $this->conn; }

    // ------------------------------------------------------------------ //
    //  p_users
    // ------------------------------------------------------------------ //
    public function login(string $username, string $password): array|false {
        $stmt = $this->conn->prepare("SELECT * FROM p_users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        if (!$user) return false;
        if (password_verify($password, $user['password']) || $password === $user['password']) return $user;
        return false;
    }
    public function getUserByUsername(string $username): array|false {
        $stmt = $this->conn->prepare("SELECT * FROM p_users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
    public function usernameExists(string $username): bool {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM p_users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return (int)$stmt->fetchColumn() > 0;
    }
    public function emailExists(string $email): bool {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM p_users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }
    public function register(string $username, string $password, string $name, string $email, string $role = 'User'): bool {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("INSERT INTO p_users (username, password, name, email, role) VALUES (:username, :password, :name, :email, :role)");
        return $stmt->execute([':username'=>$username,':password'=>$hashed,':name'=>$name,':email'=>$email,':role'=>$role]);
    }
    public function updateUser(string $username, string $name, string $email): bool {
        $stmt = $this->conn->prepare("UPDATE p_users SET name=:name, email=:email WHERE username=:username");
        return $stmt->execute([':username'=>$username,':name'=>$name,':email'=>$email]);
    }
    public function changePassword(string $username, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("UPDATE p_users SET password=:password WHERE username=:username");
        return $stmt->execute([':username'=>$username,':password'=>$hashed]);
    }

    // ------------------------------------------------------------------ //
    //  p_type
    // ------------------------------------------------------------------ //
    public function getAllTypes(): array {
        return $this->conn->query("SELECT * FROM p_type ORDER BY name ASC")->fetchAll();
    }
    public function getTypeByCode(string $typeCode): array|false {
        $stmt = $this->conn->prepare("SELECT * FROM p_type WHERE type = :type");
        $stmt->execute([':type' => $typeCode]);
        return $stmt->fetch();
    }
    public function insertType(string $type, string $name): bool {
        $stmt = $this->conn->prepare("INSERT INTO p_type (type, name) VALUES (:type, :name)");
        return $stmt->execute([':type'=>$type,':name'=>$name]);
    }
    public function updateType(string $type, string $name): bool {
        $stmt = $this->conn->prepare("UPDATE p_type SET name=:name WHERE type=:type");
        return $stmt->execute([':type'=>$type,':name'=>$name]);
    }
    public function deleteType(string $type): bool {
        $stmt = $this->conn->prepare("DELETE FROM p_type WHERE type=:type");
        return $stmt->execute([':type'=>$type]);
    }

    // ------------------------------------------------------------------ //
    //  p_product
    // ------------------------------------------------------------------ //
    public function getAllProducts(): array {
        $stmt = $this->conn->query("SELECT p.*, t.name AS type_name FROM p_product p LEFT JOIN p_type t ON p.id_type = t.type ORDER BY p.id ASC");
        return $stmt->fetchAll();
    }
    public function getProductById(int $id): array|false {
        $stmt = $this->conn->prepare("SELECT p.*, t.name AS type_name FROM p_product p LEFT JOIN p_type t ON p.id_type = t.type WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    public function insertProduct(string $name, float $price, string $description, string $id_type, string $image): int {
        $stmt = $this->conn->prepare("INSERT INTO p_product (name, price, description, id_type, image) VALUES (:name, :price, :description, :id_type, :image)");
        $stmt->execute([':name'=>$name,':price'=>$price,':description'=>$description,':id_type'=>$id_type,':image'=>$image]);
        return (int)$this->conn->lastInsertId();
    }
    public function updateProduct(int $id, string $name, float $price, string $description, string $id_type, string $image): bool {
        $stmt = $this->conn->prepare("UPDATE p_product SET name=:name, price=:price, description=:description, id_type=:id_type, image=:image WHERE id=:id");
        return $stmt->execute([':id'=>$id,':name'=>$name,':price'=>$price,':description'=>$description,':id_type'=>$id_type,':image'=>$image]);
    }
    public function deleteProduct(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM p_product WHERE id=:id");
        return $stmt->execute([':id'=>$id]);
    }

    // ------------------------------------------------------------------ //
    //  p_cart
    // ------------------------------------------------------------------ //

    /** Lấy toàn bộ giỏ hàng của user (kèm thông tin sản phẩm) */
    public function getCart(string $username): array {
        $stmt = $this->conn->prepare(
            "SELECT c.id, c.quantity, p.id AS product_id, p.name, p.price, p.image, p.description, t.name AS type_name
             FROM p_cart c
             JOIN p_product p ON c.product_id = p.id
             LEFT JOIN p_type t ON p.id_type = t.type
             WHERE c.username = :username
             ORDER BY c.id DESC"
        );
        $stmt->execute([':username' => $username]);
        return $stmt->fetchAll();
    }

    /** Đếm tổng số item trong giỏ */
    public function getCartCount(string $username): int {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM p_cart WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return (int)$stmt->fetchColumn();
    }

    /** Thêm vào giỏ (nếu đã có thì tăng quantity) */
    public function addToCart(string $username, int $productId, int $qty = 1): bool {
        // Kiểm tra đã có chưa
        $stmt = $this->conn->prepare("SELECT id, quantity FROM p_cart WHERE username=:u AND product_id=:p");
        $stmt->execute([':u'=>$username, ':p'=>$productId]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt2 = $this->conn->prepare("UPDATE p_cart SET quantity = quantity + :qty WHERE id = :id");
            return $stmt2->execute([':qty'=>$qty, ':id'=>$row['id']]);
        } else {
            $stmt2 = $this->conn->prepare("INSERT INTO p_cart (username, product_id, quantity) VALUES (:u, :p, :qty)");
            return $stmt2->execute([':u'=>$username, ':p'=>$productId, ':qty'=>$qty]);
        }
    }

    /** Cập nhật số lượng */
    public function updateCartQty(int $cartId, string $username, int $qty): bool {
        if ($qty <= 0) return $this->removeFromCart($cartId, $username);
        $stmt = $this->conn->prepare("UPDATE p_cart SET quantity=:qty WHERE id=:id AND username=:u");
        return $stmt->execute([':qty'=>$qty, ':id'=>$cartId, ':u'=>$username]);
    }

    /** Xóa 1 item khỏi giỏ */
    public function removeFromCart(int $cartId, string $username): bool {
        $stmt = $this->conn->prepare("DELETE FROM p_cart WHERE id=:id AND username=:u");
        return $stmt->execute([':id'=>$cartId, ':u'=>$username]);
    }

    /** Xóa nhiều item */
    public function removeCartItems(array $cartIds, string $username): bool {
        if (empty($cartIds)) return true;
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $stmt = $this->conn->prepare("DELETE FROM p_cart WHERE id IN ($placeholders) AND username = ?");
        return $stmt->execute([...$cartIds, $username]);
    }

    // ------------------------------------------------------------------ //
    //  p_wishlist
    // ------------------------------------------------------------------ //

    /** Lấy danh sách yêu thích */
    public function getWishlist(string $username): array {
        $stmt = $this->conn->prepare(
            "SELECT p.id AS product_id, p.name, p.price, p.image, t.name AS type_name
             FROM p_wishlist w
             JOIN p_product p ON w.product_id = p.id
             LEFT JOIN p_type t ON p.id_type = t.type
             WHERE w.username = :username
             ORDER BY p.name ASC"
        );
        $stmt->execute([':username' => $username]);
        return $stmt->fetchAll();
    }

    /** Đếm wishlist */
    public function getWishlistCount(string $username): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM p_wishlist WHERE username=:u");
        $stmt->execute([':u' => $username]);
        return (int)$stmt->fetchColumn();
    }

    /** Kiểm tra sản phẩm có trong wishlist không */
    public function inWishlist(string $username, int $productId): bool {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM p_wishlist WHERE username=:u AND product_id=:p");
        $stmt->execute([':u'=>$username, ':p'=>$productId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Toggle wishlist (thêm/xóa) */
    public function toggleWishlist(string $username, int $productId): string {
        if ($this->inWishlist($username, $productId)) {
            $stmt = $this->conn->prepare("DELETE FROM p_wishlist WHERE username=:u AND product_id=:p");
            $stmt->execute([':u'=>$username, ':p'=>$productId]);
            return 'removed';
        } else {
            $stmt = $this->conn->prepare("INSERT INTO p_wishlist (username, product_id) VALUES (:u, :p)");
            $stmt->execute([':u'=>$username, ':p'=>$productId]);
            return 'added';
        }
    }
}