<?php
session_start();
require_once __DIR__ . '/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'msg' => 'Chưa đăng nhập']);
    exit;
}

$db        = Database::getInstance();
$username  = $_SESSION['user'];
$action    = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);

switch ($action) {
    case 'toggle':
        if (!$productId) { echo json_encode(['ok'=>false]); exit; }
        $result = $db->toggleWishlist($username, $productId);
        $count  = $db->getWishlistCount($username);
        echo json_encode(['ok'=>true, 'result'=>$result, 'count'=>$count]);
        break;

    case 'count':
        echo json_encode(['ok'=>true, 'count'=>$db->getWishlistCount($username)]);
        break;

    default:
        echo json_encode(['ok'=>false, 'msg'=>'Action không hợp lệ']);
}