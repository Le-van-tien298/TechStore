<?php
session_start();

// Hàm thông báo + redirect
function redirectWithMessage($message, $url) {
    echo "<script>
        alert('$message');
        window.location.href = '$url';
    </script>";
    exit();
}

// Chưa đăng nhập
if (!isset($_SESSION['user'])) {
    redirectWithMessage('Bạn chưa đăng nhập! Không có quyền truy cập.', '/login_form.php');
}

// Không phải admin
if ($_SESSION['role'] !== 'admin') {
    redirectWithMessage('Bạn không có quyền truy cập trang admin!', '/index.php');
}