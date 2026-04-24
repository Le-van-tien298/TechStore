<?php
session_start();
require_once __DIR__ . '/Database.php';

header('Content-Type: application/json');

// Phải đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'msg' => 'Chưa đăng nhập']);
    exit;
}

$db       = Database::getInstance();
$username = $_SESSION['user'];
$action   = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // Thêm vào giỏ
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty       = max(1, (int)($_POST['qty'] ?? 1));
        if (!$productId) { echo json_encode(['ok'=>false,'msg'=>'Thiếu product_id']); exit; }
        $ok    = $db->addToCart($username, $productId, $qty);
        $count = $db->getCartCount($username);
        echo json_encode(['ok'=>$ok, 'count'=>$count]);
        break;

    // Cập nhật số lượng
    case 'update':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $qty    = (int)($_POST['qty'] ?? 1);
        $ok     = $db->updateCartQty($cartId, $username, $qty);
        $count  = $db->getCartCount($username);
        // Tính lại tổng giỏ
        $cart   = $db->getCart($username);
        $total  = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
        echo json_encode(['ok'=>$ok, 'count'=>$count, 'total'=>$total]);
        break;

    // Xóa nhiều item
    case 'remove':
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) $ids = [$ids];
        $ids = array_map('intval', $ids);
        $ok  = $db->removeCartItems($ids, $username);
        $count = $db->getCartCount($username);
        $cart  = $db->getCart($username);
        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
        echo json_encode(['ok'=>$ok, 'count'=>$count, 'total'=>$total]);
        break;

    // Lấy số lượng giỏ hàng
    case 'count':
        echo json_encode(['ok'=>true, 'count'=>$db->getCartCount($username)]);
        break;

    default:
        echo json_encode(['ok'=>false, 'msg'=>'Action không hợp lệ']);
}