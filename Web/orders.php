<?php
session_start();
require_once __DIR__ . '/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

$db = Database::getInstance();
$username = $_SESSION['user'];
$orders = $db->getUserOrders($username);

function formatPrice(float $p): string
{
    return number_format($p, 0, ',', '.') . '₫';
}

function getStatusText(string $status): string
{
    return match ($status) {
        'pending' => 'Chờ xử lý',
        'processing' => 'Đang xử lý',
        'shipped' => 'Đang giao hàng',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy',
        default => 'Không xác định'
    };
}

function getStatusColor(string $status): string
{
    return match ($status) {
        'pending' => '#f59e0b',
        'processing' => '#3b82f6',
        'shipped' => '#8b5cf6',
        'delivered' => '#10b981',
        'cancelled' => '#ef4444',
        default => '#6b7280'
    };
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi – TechStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --cyan: #06b6d4;
            --red: #ef4444;
            --green: #10b981;
            --navy: #0d1b4b;
            --navy-deep: #080f2d;
            --text: #111827;
            --muted: #6b7280;
            --bg: #f8fafc;
            --white: #fff;
            --border: #e5e7eb;
            --shadow: 0 4px 24px rgba(13, 27, 75, .10);
            --shadow-lg: 0 8px 40px rgba(13, 27, 75, .18);
            --r: 14px;
            --r-sm: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* NAV */
        nav {
            background: var(--white);
            border-bottom: 1.5px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        }

        .nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 14px 24px;
        }

        .logo {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--blue);
            letter-spacing: -.5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo i {
            color: var(--cyan);
        }

        .nav-back {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .88rem;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            margin-left: auto;
            transition: color .2s;
        }

        .nav-back:hover {
            color: var(--blue);
        }

        /* LAYOUT */
        .page {
            max-width: 1100px;
            margin: 32px auto 60px;
            padding: 0 24px;
        }

        /* HEADER */
        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 1rem;
        }

        /* ORDERS LIST */
        .orders-list {
            display: grid;
            gap: 16px;
        }

        .order-card {
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
            padding: 24px;
            transition: transform .2s, box-shadow .2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .order-id {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--blue);
        }

        .order-date {
            font-size: .9rem;
            color: var(--muted);
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: .85rem;
            font-weight: 600;
        }

        .status-icon {
            font-size: .9rem;
        }

        .order-summary {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
        }

        .order-items {
            font-size: .9rem;
            color: var(--muted);
        }

        .order-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--navy);
            text-align: right;
        }

        .order-actions {
            margin-top: 16px;
            text-align: right;
        }

        .btn-view {
            background: var(--blue);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: var(--r-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background .2s;
        }

        .btn-view:hover {
            background: var(--blue-dark);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .empty-text {
            color: var(--muted);
            margin-bottom: 24px;
        }

        .btn-shop {
            background: var(--blue);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            border-radius: var(--r-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background .2s;
        }

        .btn-shop:hover {
            background: var(--blue-dark);
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-inner">
            <a href="index.php" class="logo"><i class="fas fa-mobile-alt"></i> TechStore</a>
            <a href="index.php" class="nav-back"><i class="fas fa-arrow-left"></i> Về trang chủ</a>
        </div>
    </nav>

    <div class="page">
        <div class="page-header">
            <h1 class="page-title">Đơn hàng của tôi</h1>
            <p class="page-subtitle">Theo dõi và quản lý các đơn hàng đã đặt</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 class="empty-title">Chưa có đơn hàng nào</h2>
                <p class="empty-text">Bạn chưa đặt đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                <a href="index.php" class="btn-shop">
                    <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                </a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Đơn hàng #<?= $order['id'] ?></div>
                            <div class="order-date"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>

                        <div class="order-status"
                            style="background: <?= getStatusColor($order['status']) ?>20; color: <?= getStatusColor($order['status']) ?>;">
                            <i class="fas fa-circle status-icon"></i>
                            <?= getStatusText($order['status']) ?>
                        </div>

                        <div class="order-summary">
                            <div class="order-items">
                                Tổng tiền: <?= formatPrice($order['total_amount']) ?>
                            </div>
                            <div class="order-total">
                                <?= formatPrice($order['total_amount']) ?>
                            </div>
                        </div>

                        <div class="order-actions">
                            <a href="order_status.php?id=<?= $order['id'] ?>" class="btn-view">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>