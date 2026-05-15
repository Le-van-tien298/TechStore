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
-- Cấu trúc bảng cho bảng `p_product`
--

CREATE TABLE `p_product` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `flash_sale_price` int(11) DEFAULT NULL,
  `flash_sale_active` tinyint(1) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `type` varchar(100) NOT NULL,
  `image` varchar(100) NOT NULL,
  `id_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `p_product`
--

INSERT INTO `p_product` (`id`, `name`, `price`, `description`, `type`, `image`, `id_type`) VALUES
(1, 'iPhone 17 Pro Max 1TB | Chính hãng', 49990000, 'iPhone 17 Pro Max 1TB sở hữu bộ nhớ lưu trữ lớn, cho phép người dùng thoải mái quay video, lưu trữ kho phim ảnh và dữ liệu lớn. Máy được trang bị chip A19 Pro với CPU và GPU nâng cấp, mang đến hiệu năng xử lý mạnh mẽ và mượt mà. Màn hình OLED Super Retina XDR kích thước 6.9 inch cùng với độ sáng tối đa 3000 nits (ngoài trời) mang lại trải nghiệm hiển thị sống động.', 'apple', 'ip17pm.jpg', 'apple_01'),
(2, 'iPhone 15 128GB | Chính hãng VN/A', 17590000, 'iPhone 15 được trang bị màn hình Dynamic Island 6.1 inch với công nghệ Super Retina XDR mang đến trải nghiệm hiển thị sống động vượt trội. iPhone 15 128GB sở hữu camera chính 48MP cùng khả năng zoom 2x giúp ghi lại mọi khoảnh khắc với chất lượng rõ nét. Cổng Type-C của ip15 thường giúp cải thiện kết nối, đồng thời đem lại tiện ích khi dùng.', 'apple', 'ip15.png', 'apple_01'),
(3, 'Samsung Galaxy S26 Ultra 12GB 256GB', 32990000, 'Samsung Galaxy S26 Ultra mang đến trải nghiệm flagship đỉnh cao nhờ sự kết hợp giữa màn hình 6.9 inch Dynamic AMOLED 2X 120Hz với độ sáng kỷ lục 2600 nits. Sức mạnh phần cứng được bảo chứng bởi vi xử lý Snapdragon 8 Elite Gen 5 cùng RAM 12GB, đảm bảo mọi tác vụ đa nhiệm và chơi game đồ họa nặng luôn vận hành mượt mà. Không chỉ dừng lại ở hiệu năng, thiết bị còn là công cụ sáng tạo nội dung chuyên nghiệp với khả năng quay video 8K@30fps sắc nét, mang lại chất lượng hình ảnh chuẩn điện ảnh', 'samsung', 'sss26ul.png', 'samsung_01'),
(4, 'MacBook Air M4 13 inch 2025 10CPU 10GPU 16GB 512GB | Chính hãng Apple Việt Nam', 29890000, 'MacBook Air M4 13 inch 2025 10CPU 10GPU 16GB 512GB được đánh giá là một tuyệt tác công nghệ đến từ Apple Việt Nam. Điểm nhấn cốt lõi của chiếc Macbook M4 này nằm ở cấu hình đột phá với con chip Apple M4 thế hệ mới nhất.\r\n\r\nCụ thể, chiếc MacBook Air M4 này sở hữu 10 nhân CPU và 10 nhân GPU, M4 mang đến sức mạnh xử lý đáng kinh ngạc, dễ dàng chinh phục các tác vụ từ cơ bản đến chuyên sâu như chỉnh sửa video độ phân giải cao, thiết kế đồ họa, lập trình hay thậm chí là chơi game đòi hỏi cấu hình mạnh. Khả năng xử lý đồ họa được nâng tầm, cho phép hiển thị hình ảnh mượt mà và chi tiết hơn bao giờ hết.\r\n\r\nĐể tối ưu hóa sức mạnh của chip M4, Apple đã trang bị cho chiếc MacBook Air 16GB RAM, đảm bảo khả năng đa nhiệm vượt trội. Bạn có thể mở hàng loạt ứng dụng, duyệt web nhiều tab hay chuyển đổi giữa các tác vụ nặng mà không gặp phải hiện tượng giật lag. Ổ cứng SSD dung lượng 512GB không chỉ cung cấp không gian lưu trữ thoải mái cho công việc và giải trí mà còn mang lại tốc độ khởi động máy, mở ứng dụng và truy xuất dữ liệu siêu nhanh.', 'apple', 'macairm4.png', 'apple_02'),
(5, 'iPhone 16 Pro Max 512GB | Chính hãng VN/A', 26590000, 'iPhone 16 Pro Max phiên bản bộ nhớ trong 512GB có màn hình lớn 6.9 inches chuẩn Super Retina XDR OLED hiển thị vượt trội cùng độ sáng tối đa đến 2000 nit. Máy với thiết kế mới với nút điều khiển camera, nút action cùng với màu titan sa mạc ấn tượng. Phần cứng điện thoại cũng được nâng cấp với chip A18 Pro cũng như camera hỗ trợ chụp zoom quang học 5x.', 'apple', 'ip16pro.png', 'apple_01'),
(6, 'Xiaomi 17', 27990000, 'Xiaomi 17 tiếp tục khẳng định vị thế flagship nhỏ gọn khi sở hữu sức mạnh đột phá từ chip Snapdragon 8 Elite Gen 5, màn hình LTPO AMOLED 6.3 inch sắc nét. Điểm nhấn ấn tượng nhất chính là viên pin kỷ lục 7000mAh cho phép bạn sử dụng bền bỉ lên đến 2 ngày, đi kèm sạc nhanh 100W, nạp đầy pin trong khoảng 40 phút. Hệ thống camera Leica Summilux 50MP cùng công nghệ AI tiên tiến đảm bảo mọi bức ảnh chụp đêm đều giữ được độ chi tiết chuyên nghiệp và sống động. Bên cạnh đó, độ bền của máy cũng được nâng cấp với chuẩn kháng nước IP66/IP68/IP69, cho phép máy hoạt động trong nhiều điều kiện khắc nghiệt.', 'xiaomi', 'xiaomi17.png', 'xiaomi_01'),
(7, 'Redmi k90', 9950000, 'Cùng với bản Pro, Xiaomi REDMI K90 chính thức được công ty Trung Quốc phát hành vào hôm nay (23/10/2024) với cấu hình rất mạnh mẽ và nhiều tính năng cao cấp.\r\n\r\nNhư truyền thống của dòng K, Xiaomi REDMI K90 sở hữu chip của mẫu Pro đời trước đó là Snapdragon 8 Elite cùng với viên pin khủng 7100mAh và sạc nhanh 100W.\r\n\r\nBên cạnh đó, sản phẩm còn cung cấp trải nghiệm hình ảnh xuất sắc với màn hình OLED 68 tỷ màu độ phân giải 1.5K, hệ thống camera nâng cấp vượt trội so với bản tiền nhiệm và còn nhiều trang bị cao cấp khác.', 'redmi', 'redmik90.png', 'xiaomi_02'),
(8, 'iPhone 15 Pro Max 2TB | Chính hãng VN/A', 28990000, 'iPhone 15 Pro Max là chiếc iPhone cao cấp nhất với màn hình lớn nhất, thời lượng pin tốt nhất, cấu hình mạnh nhất và thiết kế khung Titan chuẩn hàng không vũ trụ siêu bền, siêu nhẹ. iPhone 15 Pro Max sở hữu những điểm vượt trội nhất nhà Apple. Theo đó, người dùng sẽ trải nghiệm chiếc iPhone cao cấp với hiệu năng “khủng” chip A17 Pro, khung titan, khả năng zoom nâng cấp, nút tác vụ mới,…', 'iphone', 'ip15pm.png', 'apple_01');

UPDATE p_product SET flash_sale_price = 14990000, flash_sale_active = 1 WHERE id = 2;
UPDATE p_product SET flash_sale_price = 21990000, flash_sale_active = 1 WHERE id = 5;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `p_product`
--
ALTER TABLE `p_product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sp_type` (`id_type`);

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `p_product`
--
ALTER TABLE `p_product`
  ADD CONSTRAINT `fk_sp_type` FOREIGN KEY (`id_type`) REFERENCES `p_type` (`type`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
