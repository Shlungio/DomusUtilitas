-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2026 at 09:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `domusutilitas_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(4, 'Gadgets'),
(2, 'House Appliances'),
(3, 'Textile');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 4097.00, 'Cancelled', '2026-09-01 08:05:46', '2026-09-01 09:01:56'),
(2, 1, 6498.00, 'Pending', '2026-09-01 12:13:26', '2026-09-01 12:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 2, 'Rice Cooker', 1499.00, 1, 1499.00),
(2, 1, 3, 'Blender', 1299.00, 2, 2598.00),
(3, 2, 42, 'Head Phones', 3999.00, 1, 3999.00),
(4, 2, 45, 'Microphones', 2499.00, 1, 2499.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `subcategory_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `subcategory_id`, `name`, `price`, `stock`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 2, 'Rice Cooker', 3295.00, 19, 'images/House Appliances/Cooking/Rice Cooker.jpg', 1, '2026-08-31 19:10:22', '2026-09-01 13:41:10'),
(3, 2, 'Blender', 2295.00, 24, 'images/House Appliances/Cooking/Blender.png', 1, '2026-08-31 19:10:22', '2026-09-01 13:29:48'),
(4, 2, 'Air Fryer', 4995.00, 17, 'images/House Appliances/Cooking/Air Fryer.png', 1, '2026-08-31 22:39:50', '2026-09-01 13:29:40'),
(5, 2, 'Oven', 10595.00, 12, 'images/House Appliances/Cooking/Oven.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:43:03'),
(6, 2, 'Pressure Cooker', 4495.00, 21, 'images/House Appliances/Cooking/Pressure Cooker.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:30:05'),
(7, 3, 'Iron', 1495.00, 27, 'images/House Appliances/Garments/Iron.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:40:42'),
(8, 3, 'Carpet Washer', 12995.00, 9, 'images/House Appliances/Garments/Carpet Washer.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:19:09'),
(9, 3, 'Dryer', 32995.00, 11, 'images/House Appliances/Garments/Dryer.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:22:53'),
(10, 3, 'Washing Machine', 18495.00, 14, 'images/House Appliances/Garments/Washing Machine.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:42:17'),
(11, 3, 'Vacuum', 5995.00, 18, 'images/House Appliances/Garments/Vacuum.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:41:49'),
(12, 4, 'Aircon', 25995.00, 10, 'images/House Appliances/Climate Wellness/Airconditioner.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:26:22'),
(13, 4, 'Humidifier', 1995.00, 23, 'images/House Appliances/Climate Wellness/Air Humidifier.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:25:47'),
(14, 4, 'Air Purifier', 6995.00, 16, 'images/House Appliances/Climate Wellness/Air Purifier.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:23:38'),
(15, 4, 'Electricfan', 2495.00, 30, 'images/House Appliances/Climate Wellness/Electric Fan.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:26:32'),
(16, 4, 'Heater', 2495.00, 13, 'images/House Appliances/Climate Wellness/Heater.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:26:45'),
(17, 5, 'Linen Cover', 1299.00, 22, 'images/Textiles/Bedding/Linen Cover.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:17'),
(18, 5, 'Pocket Sheet', 699.00, 25, 'images/Textiles/Bedding/Pocket Sheet.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:31'),
(19, 5, 'Pillowcase', 299.00, 28, 'images/Textiles/Bedding/Pillowcase.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:24'),
(20, 5, 'Blanket', 799.00, 20, 'images/Textiles/Bedding/Blanket.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:03'),
(21, 5, 'Comforter', 1499.00, 15, 'images/Textiles/Bedding/Comforter.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:43:20'),
(22, 6, 'Towel', 349.00, 30, 'images/Textiles/Bath/Towel.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:34:32'),
(23, 6, 'Bathrobe', 999.00, 17, 'images/Textiles/Bath/Bathrobe.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:34:04'),
(24, 6, 'Bath Mat', 249.00, 26, 'images/Textiles/Bath/Bath Mat.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:33:51'),
(25, 6, 'Face Towel', 149.00, 29, 'images/Textiles/Bath/Face Towel.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:34:39'),
(26, 6, 'Turban Wraps', 249.00, 18, 'images/Textiles/Bath/Turban Wraps.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:34:45'),
(27, 7, 'Rug', 1499.00, 12, 'images/Textiles/Dining/Rug.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:37:08'),
(28, 7, 'Curtains', 999.00, 21, 'images/Textiles/Dining/Curtains.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:44'),
(29, 7, 'Tablecloth', 699.00, 24, 'images/Textiles/Dining/Table Cloth.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:37:14'),
(30, 7, 'Place Mats', 299.00, 27, 'images/Textiles/Dining/Place Mats.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:59'),
(31, 7, 'Napkin', 129.00, 30, 'images/Textiles/Dining/Napkins.png', 1, '2026-09-01 10:32:33', '2026-09-01 13:36:51'),
(32, 8, 'Desktop Tower', 34999.00, 8, 'images/Gadgets/PC/DesktopTower.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 11:07:06'),
(33, 8, 'Monitor', 8499.00, 14, 'images/Gadgets/PC/Monitor.jpeg', 1, '2026-09-01 10:32:33', '2026-09-01 11:10:14'),
(34, 8, 'Laptop', 34999.00, 9, 'images/Gadgets/PC/Laptop.jpeg', 1, '2026-09-01 10:32:33', '2026-09-01 11:09:50'),
(35, 8, 'Keyboard', 1299.00, 22, 'images/Gadgets/PC/Keyboard.png', 1, '2026-09-01 10:32:33', '2026-09-01 11:07:31'),
(36, 8, 'Mouse', 799.00, 28, 'images/Gadgets/PC/Mouse.webp', 1, '2026-09-01 10:32:33', '2026-09-01 11:10:37'),
(37, 9, 'Smart Phone', 14999.00, 13, 'images\\Gadgets\\Phone & Tablets\\Phone.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:45:52'),
(38, 9, 'Smart Watch', 4999.00, 19, 'images\\Gadgets\\Phone & Tablets\\Watch.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:46:18'),
(39, 9, 'Tablet/Ipad', 19999.00, 11, 'images\\Gadgets\\Phone & Tablets\\Ipad.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 13:48:08'),
(40, 9, 'Wireless Charging Pad', 1499.00, 25, 'images\\Gadgets\\Phone & Tablets\\ChargingPad.jpeg', 1, '2026-09-01 10:32:33', '2026-09-01 13:46:38'),
(41, 9, 'Power Bank', 1995.00, 30, 'images\\Gadgets\\Phone & Tablets\\PowerBank.jpeg', 1, '2026-09-01 10:32:33', '2026-09-01 13:47:19'),
(42, 10, 'Head Phones', 3999.00, 15, 'images/Gadgets/Audio/Headphones.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 12:13:26'),
(43, 10, 'Earbuds', 1495.00, 24, 'images/Gadgets/Audio/Earbuds.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 10:45:00'),
(44, 10, 'BT Speaker', 2899.00, 20, 'images/Gadgets/Audio/Speaker.jpeg', 1, '2026-09-01 10:32:33', '2026-09-01 10:40:12'),
(45, 10, 'Microphones', 2499.00, 17, 'images/Gadgets/Audio/Microphone.jpeg', 1, '2026-09-01 10:32:33', '2026-09-01 12:13:26'),
(46, 10, 'PC Soundbar', 3999.00, 15, 'images/Gadgets/Audio/Soundbar.jpg', 1, '2026-09-01 10:32:33', '2026-09-01 10:45:39');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`) VALUES
(4, 2, 'Climate Wellness'),
(2, 2, 'Cooking'),
(3, 2, 'Garments / Floor Care'),
(6, 3, 'Bath'),
(5, 3, 'Bedding'),
(7, 3, 'Dining'),
(10, 4, 'Audio'),
(8, 4, 'PC'),
(9, 4, 'Phone & Tablets');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'testuser', '$2y$10$fmbIf6sC1LGEA0k3UvanSOULuSc1fEj7Qn4IV.9M1yfFl6PqMmeWm', 'customer', '2026-08-31 18:56:59'),
(2, 'admin', '$2y$10$m8fiVeL51d4REYZ1bi/URO4DnThYRg/qZvkUUcSUIEXV2qEeLLL6G', 'admin', '2026-08-31 20:46:52'),
(3, 'seconduser', '$2y$10$6q3CvKKsGI0UNK7jbFsWmOngiQuhDWzkPjLTl9Axx0Ye2Wjh0IAfO', 'customer', '2026-09-01 08:18:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_item_order` (`order_id`),
  ADD KEY `fk_order_item_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_subcategory` (`subcategory_id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_id` (`category_id`,`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`);

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `fk_subcategory_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
