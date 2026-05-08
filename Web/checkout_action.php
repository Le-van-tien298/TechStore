<?php
session_start();
require_once __DIR__ . '/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$db = Database::getInstance();
$username = $_SESSION['user'];
$cart = $db->getCart($username);

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Validate payment data (giả lập)
$cardNumber = trim($_POST['card_number'] ?? '');
$expiry = trim($_POST['expiry'] ?? '');
$cvv = trim($_POST['cvv'] ?? '');
$cardName = trim($_POST['card_name'] ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');

$errors = [];
if (empty($cardNumber) || !preg_match('/^\d{4} \d{4} \d{4} \d{4}$/', $cardNumber)) {
    $errors[] = 'Số thẻ không hợp lệ';
}
if (empty($expiry) || !preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
    $errors[] = 'Ngày hết hạn không hợp lệ';
}
if (empty($cvv) || !preg_match('/^\d{3}$/', $cvv)) {
    $errors[] = 'CVV không hợp lệ';
}
if (empty($cardName)) {
    $errors[] = 'Tên trên thẻ không được để trống';
}
if (empty($shippingAddress)) {
    $errors[] = 'Địa chỉ giao hàng không được để trống';
}

if (!empty($errors)) {
    // Trong thực tế, nên lưu errors vào session và hiển thị
    header('Location: checkout.php?error=1');
    exit;
}

// Giả lập thanh toán thành công (luôn thành công cho demo)
$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));

// Tạo đơn hàng mới
try {
    $orderId = $db->createOrder($username, $cart, $total, $shippingAddress, 'credit_card');
} catch (Exception $e) {
    // Nếu tạo order thất bại, quay lại checkout với lỗi
    header('Location: checkout.php?error=1');
    exit;
}

// Xóa giỏ hàng sau khi thanh toán thành công
$db->clearCart($username);

// Chuyển đến trang trạng thái đơn hàng
header('Location: order_status.php?id=' . $orderId);
exit;
?>