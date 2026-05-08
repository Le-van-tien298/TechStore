<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['last_order'])) {
    header('Location: index.php');
    exit;
}

$order = $_SESSION['last_order'];
unset($_SESSION['last_order']); // Xóa sau khi hiển thị

function formatPrice(float $p): string
{
    return number_format($p, 0, ',', '.') . '₫';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công – TechStore</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            max-width: 600px;
            width: 100%;
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--green);
            margin-bottom: 24px;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 16px;
        }

        .success-message {
            font-size: 1.1rem;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .order-details {
            background: var(--bg);
            border-radius: var(--r-sm);
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .order-id {
            font-weight: 600;
            color: var(--blue);
        }

        .order-time {
            font-size: .9rem;
            color: var(--muted);
        }

        .order-item {
            display: flex;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 60px;
            height: 60px;
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

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
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
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="success-title">Đặt hàng thành công!</h1>
        <p class="success-message">
            Cảm ơn bạn đã mua hàng tại TechStore. Đơn hàng của bạn đã được xử lý thành công và sẽ được giao trong vòng
            3-5 ngày làm việc.
        </p>

        <div class="order-details">
            <div class="order-header">
                <div class="order-id">Đơn hàng #<?= strtoupper(substr(md5($order['order_time']), 0, 8)) ?></div>
                <div class="order-time"><?= date('d/m/Y H:i', strtotime($order['order_time'])) ?></div>
            </div>

            <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
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
                Tổng cộng: <?= formatPrice($order['total']) ?>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
            </a>
            <a href="cart.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i> Xem giỏ hàng
            </a>
        </div>
    </div>
</body>

</html>