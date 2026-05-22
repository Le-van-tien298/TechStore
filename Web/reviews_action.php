<?php
session_start();
require_once __DIR__ . '/Database.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Bạn cần đăng nhập để đánh giá.']);
    exit;
}

$action = trim($_REQUEST['action'] ?? '');
if ($action === 'list') {
    $productId = (int) ($_REQUEST['product_id'] ?? 0);
    if ($productId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'ID sản phẩm không hợp lệ.']);
        exit;
    }

    $db = Database::getInstance();
    $reviews = $db->getProductReviews($productId);
    echo json_encode(['ok' => true, 'reviews' => $reviews]);
    exit;
}

if ($action !== 'submit') {
    echo json_encode(['ok' => false, 'message' => 'Hành động không hợp lệ.']);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($productId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['ok' => false, 'message' => 'Dữ liệu đánh giá không hợp lệ.']);
    exit;
}

$db = Database::getInstance();
try {
    $ok = $db->addProductReview($_SESSION['user'], $productId, $rating, $comment);
    if (!$ok) {
        echo json_encode(['ok' => false, 'message' => 'Không lưu được đánh giá.']);
        exit;
    }
    $summary = $db->getProductRatingSummary($productId);
    echo json_encode([
        'ok' => true,
        'avg_rating' => isset($summary['avg_rating']) ? (float) $summary['avg_rating'] : 0,
        'review_count' => isset($summary['review_count']) ? (int) $summary['review_count'] : 0,
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
