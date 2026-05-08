<?php
session_start();
require_once __DIR__ . '/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

$db = Database::getInstance();
$username = $_SESSION['user'];

$orderId = (int) ($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: index.php');
    exit;
}

$order = $db->getOrderDetails($orderId);
if (!$order || $order['username'] !== $username) {
    header('Location: index.php');
    exit;
}

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

function getStatusIcon(string $status): string
{
    return match ($status) {
        'pending' => 'fas fa-clock',
        'processing' => 'fas fa-cog',
        'shipped' => 'fas fa-truck',
        'delivered' => 'fas fa-check-circle',
        'cancelled' => 'fas fa-times-circle',
        default => 'fas fa-question-circle'
    };
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng thái đơn hàng – TechStore</title>
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
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        /* STATUS CARD */
        .status-card {
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
            padding: 32px;
            margin-bottom: 24px;
        }

        .status-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: .9rem;
        }

        .status-icon {
            font-size: 1.1rem;
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 32px 0;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: 50%;
            width: calc(100% - 32px);
            height: 2px;
            background: var(--border);
            z-index: 1;
        }

        .timeline-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }

        .timeline-label {
            font-size: .8rem;
            color: var(--muted);
            text-align: center;
        }

        /* ORDER DETAILS */
        .order-details {
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
            padding: 24px;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .info-item {}

        .info-label {
            font-size: .9rem;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
        }

        .order-items {}

        .order-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--r-sm);
        }

        .order-item-info {
            flex: 1;
        }

        .order-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .order-item-price {
            font-size: .9rem;
            color: var(--muted);
        }

        .order-total {
            text-align: right;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--navy);
            margin-top: 16px;
        }

        /* ACTIONS */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 32px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: var(--r-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .2s;
        }

        .btn-primary {
            background: var(--blue);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--blue-dark);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg);
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
        <div class="status-card">
            <div class="status-header">
                <h1 class="status-title">Đơn hàng #<?= $order['id'] ?></h1>
                <div class="status-badge"
                    style="background: <?= getStatusColor($order['status']) ?>20; color: <?= getStatusColor($order['status']) ?>; border: 1px solid <?= getStatusColor($order['status']) ?>40;">
                    <i class="<?= getStatusIcon($order['status']) ?> status-icon"></i>
                    <?= getStatusText($order['status']) ?>
                </div>
            </div>

            <div class="status-timeline">
                <?php
                $steps = ['pending', 'processing', 'shipped', 'delivered'];
                $currentStep = array_search($order['status'], $steps);
                foreach ($steps as $index => $step) {
                    $isCompleted = $index <= $currentStep && $order['status'] !== 'cancelled';
                    $isCurrent = $index === $currentStep && $order['status'] !== 'cancelled';
                    $circleColor = $isCompleted ? getStatusColor($step) : ($isCurrent ? getStatusColor($step) : '#e5e7eb');
                    $textColor = $isCompleted || $isCurrent ? 'var(--text)' : 'var(--muted)';
                    ?>
                    <div class="timeline-step">
                        <div class="timeline-circle" style="background: <?= $circleColor ?>; color: white;">
                            <?php if ($isCompleted): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="<?= getStatusIcon($step) ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-label"
                            style="color: <?= $textColor ?>; font-weight: <?= $isCurrent ? '600' : '400' ?>;">
                            <?= getStatusText($step) ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="order-details">
            <div class="order-info">
                <div class="info-item">
                    <div class="info-label">Ngày đặt hàng</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phương thức thanh toán</div>
                    <div class="info-value">Thẻ tín dụng</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Địa chỉ giao hàng</div>
                    <div class="info-value" style="white-space: pre-line;">
                        <?= htmlspecialchars($order['shipping_address'] ?: 'Chưa cập nhật') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tổng tiền</div>
                    <div class="info-value"><?= formatPrice($order['total_amount']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Cập nhật lần cuối</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></div>
                </div>
            </div>

            <div class="order-items">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 16px; color: var(--navy);">Chi tiết sản
                    phẩm</h3>
                <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <img src="images/<?= htmlspecialchars($item['image']) ?>"
                            alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="order-item-info">
                            <div class="order-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="order-item-price">
                                <?= formatPrice($item['price']) ?> × <?= $item['quantity'] ?> =
                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="order-total">
                    Tổng cộng: <?= formatPrice($order['total_amount']) ?>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                </a>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Xem tất cả đơn hàng
                </a>
            </div>
        </div>
    </div>
</body>

</html>