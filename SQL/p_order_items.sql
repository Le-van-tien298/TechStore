-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th3 26, 2026 lúc 03:20 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `products`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `p_order_items`
--

CREATE TABLE `p_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `p_order_items`
--
ALTER TABLE `p_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_item_order` (`order_id`),
  ADD KEY `fk_order_item_product` (`product_id`);

--
-- AUTO_INCREMENT cho bảng `p_order_items`
--
ALTER TABLE `p_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `p_order_items`
--
ALTER TABLE `p_order_items`
  ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `p_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `p_product` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;