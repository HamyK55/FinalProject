-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 30, 2026 at 03:22 PM
-- Server version: 10.4.34-MariaDB-log
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `khan2w1_final_project_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Soap'),
(2, 'Lip Balm'),
(3, 'Hajj and Umrah Kits'),
(4, 'Deodorant');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `shipping_address` text NOT NULL,
  `subtotal_cents` int(10) UNSIGNED NOT NULL,
  `total_cents` int(10) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_number`, `full_name`, `email`, `shipping_address`, `subtotal_cents`, `total_cents`, `status`, `created_at`) VALUES
(12, 6, 'ORD-20260729211236-9914', 'test user', 'test@user.ca', '123 street', 5700, 5700, 'Fulfilled', '2026-07-30 01:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `unit_price_cents` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `line_total_cents` int(10) UNSIGNED NOT NULL,
  `options_json` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `user_id`, `order_number`, `product_id`, `product_name`, `category_name`, `unit_price_cents`, `quantity`, `line_total_cents`, `options_json`, `created_at`) VALUES
(12, 12, 6, 'ORD-20260729211236-9914', 8, 'Cherry Pie Lip Balm', 'Lip Balm', 1900, 3, 5700, '[{\"option_name\":\"Pack Size\",\"option_value\":\"Pack of 3\"}]', '2026-07-30 01:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `base_price`, `stock`, `is_active`) VALUES
(1, 1, 'White Tea Ginger Soap', '', 10.00, 20, 1),
(2, 4, 'Unscented Deodorant', '', 10.00, 20, 1),
(3, 1, 'Satsuma Jasmine Mandarin Soap', '', 12.00, 20, 1),
(4, 1, 'Peppermint Candy Soap', '', 10.00, 20, 1),
(5, 2, 'Spiced Chai Lip Balm', '', 7.00, 20, 1),
(6, 2, 'Mango Pomegranate Lip Balm', '', 7.00, 20, 1),
(7, 2, 'Juicy Mango Lip Balm', '', 7.00, 20, 1),
(8, 2, 'Cherry Pie Lip Balm', '', 7.00, 17, 1),
(9, 1, 'Lemongrass Sage Soap', '', 10.00, 20, 1),
(10, 4, 'Lavender Tea Tree Deodorant', '', 10.00, 20, 1),
(11, 1, 'Lavender Oatmeal Soap', '', 10.00, 20, 1),
(12, 1, 'Herbal Clean Soap', '', 10.00, 20, 1),
(13, 1, 'Hajj and Umrah Soap', '', 7.00, 20, 1),
(14, 3, 'Hajj and Umrah Kit', '', 35.00, 20, 1),
(15, 1, 'Dragon\'s Blood Soap', '', 10.00, 20, 1),
(16, 1, 'Warm Apple Cider Soap', '', 10.00, 20, 1),
(17, 1, 'Pumpkin Spice Soap', '', 10.00, 20, 1),
(18, 1, 'Citrus Pomegranate Soap', '', 10.00, 20, 1),
(19, 1, 'Honey & Peonies Soap', '', 10.00, 20, 1),
(20, 1, 'Island Plumeria Soap', '', 10.00, 20, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1, 1, 'images/product_images/White Tea Ginger.jpg', 'White Tea Ginger Soap', 1, 0),
(2, 1, 'images/product_images/White Tea Ginger (2).jpg', 'White Tea Ginger Soap second view', 0, 1),
(3, 2, 'images/product_images/Unscented Deodorant.jpg', 'Unscented Deodorant', 1, 0),
(4, 3, 'images/product_images/Satsuma Jasmine Mandarin.jpg', 'Satsuma Jasmine Mandarin Soap', 1, 0),
(5, 3, 'images/product_images/Satsuma Jasmine Mandarin (2).jpg', 'Satsuma Jasmine Mandarin Soap second view', 0, 1),
(6, 4, 'images/product_images/Peppermint Candy.jpg', 'Peppermint Candy Soap', 1, 0),
(7, 5, 'images/product_images/LipBalm Spiced Chai 1.jpg', 'Spiced Chai Lip Balm', 1, 0),
(8, 6, 'images/product_images/LipBalm Mango Pomagranate 1.jpg', 'Mango Pomegranate Lip Balm', 1, 0),
(9, 7, 'images/product_images/LipBalm Juicy Mango 1.jpg', 'Juicy Mango Lip Balm', 1, 0),
(10, 8, 'images/product_images/LipBalm Cherry Pie 1.jpg', 'Cherry Pie Lip Balm', 1, 0),
(11, 9, 'images/product_images/Lemongrass Sage.jpg', 'Lemongrass Sage Soap', 1, 0),
(12, 10, 'images/product_images/Lavendar Tea Tree Deodorant.jpg', 'Lavender Tea Tree Deodorant', 1, 0),
(13, 11, 'images/product_images/Lavendar Oatmeal.jpg', 'Lavender Oatmeal Soap', 1, 0),
(14, 12, 'images/product_images/Herbal Clean.jpg', 'Herbal Clean Soap', 1, 0),
(15, 12, 'images/product_images/Herbal Clean(2).jpg', 'Herbal Clean Soap second view', 0, 1),
(16, 13, 'images/product_images/Hajj and Umrah Soap.jpg', 'Hajj and Umrah Soap', 1, 0),
(17, 14, 'images/product_images/Hajj and Umrah Kit.jpg', 'Hajj and Umrah Kit', 1, 0),
(18, 15, 'images/product_images/Dragon\'s Blood.jpg', 'Dragon\'s Blood Soap', 1, 0),
(19, 16, 'images/product_images/A Warm Apple Cider 1.jpg', 'Warm Apple Cider Soap', 1, 0),
(20, 17, 'images/product_images/A Pumpkin Spice 1.jpg', 'Pumpkin Spice Soap', 1, 0),
(21, 18, 'images/product_images/Citrus Pomigranite.jpg', 'Citrus Pomegranate Soap', 1, 0),
(22, 19, 'images/product_images/Honey & Peonies.jpg', 'Honey and Peonies Soap', 1, 0),
(23, 20, 'images/product_images/Island Plumaria.jpg', 'Island Plumeria Soap', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_options`
--

CREATE TABLE `product_options` (
  `option_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `option_name` varchar(50) NOT NULL,
  `option_value` varchar(100) NOT NULL,
  `price_adjustment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_options`
--

INSERT INTO `product_options` (`option_id`, `product_id`, `option_name`, `option_value`, `price_adjustment`, `sort_order`) VALUES
(1, 1, 'Size', 'Regular', 0.00, 1),
(2, 1, 'Size', 'Large', 3.00, 2),
(3, 1, 'Pack Size', 'Single Bar', 0.00, 1),
(4, 1, 'Pack Size', 'Pack of 3', 14.00, 2),
(5, 3, 'Size', 'Regular', 0.00, 1),
(6, 3, 'Size', 'Large', 3.00, 2),
(7, 3, 'Pack Size', 'Single Bar', 0.00, 1),
(8, 3, 'Pack Size', 'Pack of 3', 14.00, 2),
(9, 4, 'Size', 'Regular', 0.00, 1),
(10, 4, 'Size', 'Large', 3.00, 2),
(11, 4, 'Pack Size', 'Single Bar', 0.00, 1),
(12, 4, 'Pack Size', 'Pack of 3', 14.00, 2),
(13, 5, 'Pack Size', 'Single', 0.00, 1),
(14, 5, 'Pack Size', 'Pack of 3', 12.00, 2),
(15, 6, 'Pack Size', 'Single', 0.00, 1),
(16, 6, 'Pack Size', 'Pack of 3', 12.00, 2),
(17, 7, 'Pack Size', 'Single', 0.00, 1),
(18, 7, 'Pack Size', 'Pack of 3', 12.00, 2),
(19, 8, 'Pack Size', 'Single', 0.00, 1),
(20, 8, 'Pack Size', 'Pack of 3', 12.00, 2),
(21, 9, 'Size', 'Regular', 0.00, 1),
(22, 9, 'Size', 'Large', 3.00, 2),
(23, 9, 'Pack Size', 'Single Bar', 0.00, 1),
(24, 9, 'Pack Size', 'Pack of 3', 14.00, 2),
(25, 11, 'Size', 'Regular', 0.00, 1),
(26, 11, 'Size', 'Large', 3.00, 2),
(27, 11, 'Pack Size', 'Single Bar', 0.00, 1),
(28, 11, 'Pack Size', 'Pack of 3', 14.00, 2),
(29, 12, 'Size', 'Regular', 0.00, 1),
(30, 12, 'Size', 'Large', 3.00, 2),
(31, 12, 'Pack Size', 'Single Bar', 0.00, 1),
(32, 12, 'Pack Size', 'Pack of 3', 14.00, 2),
(33, 13, 'Size', 'Regular', 0.00, 1),
(34, 13, 'Size', 'Large', 3.00, 2),
(35, 13, 'Pack Size', 'Single Bar', 0.00, 1),
(36, 13, 'Pack Size', 'Pack of 3', 14.00, 2),
(37, 14, 'Kit Type', 'Regular', 0.00, 1),
(38, 14, 'Kit Type', 'Advanced', 20.00, 2),
(39, 15, 'Size', 'Regular', 0.00, 1),
(40, 15, 'Size', 'Large', 3.00, 2),
(41, 15, 'Pack Size', 'Single Bar', 0.00, 1),
(42, 15, 'Pack Size', 'Pack of 3', 14.00, 2),
(43, 16, 'Size', 'Regular', 0.00, 1),
(44, 16, 'Size', 'Large', 3.00, 2),
(45, 16, 'Pack Size', 'Single Bar', 0.00, 1),
(46, 16, 'Pack Size', 'Pack of 3', 14.00, 2),
(47, 17, 'Size', 'Regular', 0.00, 1),
(48, 17, 'Size', 'Large', 3.00, 2),
(49, 17, 'Pack Size', 'Single Bar', 0.00, 1),
(50, 17, 'Pack Size', 'Pack of 3', 14.00, 2),
(51, 18, 'Size', 'Regular', 0.00, 1),
(52, 18, 'Size', 'Large', 3.00, 2),
(53, 18, 'Pack Size', 'Single Bar', 0.00, 1),
(54, 18, 'Pack Size', 'Pack of 3', 14.00, 2),
(55, 19, 'Size', 'Regular', 0.00, 1),
(56, 19, 'Size', 'Large', 3.00, 2),
(57, 19, 'Pack Size', 'Single Bar', 0.00, 1),
(58, 19, 'Pack Size', 'Pack of 3', 14.00, 2),
(59, 20, 'Size', 'Regular', 0.00, 1),
(60, 20, 'Size', 'Large', 3.00, 2),
(61, 20, 'Pack Size', 'Single Bar', 0.00, 1),
(62, 20, 'Pack Size', 'Pack of 3', 14.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(75) NOT NULL,
  `last_name` varchar(75) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(4, 'admin', 'db', 'myadmin@hotmail.com', '$2y$10$Y5Ux/8nV9VMNrMtZ/mJ.rujQ3uwEgfFU4nxPO5ZjFYrCV6RDfPGS.', 'admin', 1, '2026-07-23 00:27:14'),
(5, 'Humza', 'Khan', 'cust1@gmail.ca', '$2y$10$.UJ4E6q.6/TBkfJzGq3ww.FvPpvFmXzeiOLuXj0usCdRLJxuycteW', 'customer', 1, '2026-07-26 18:43:01'),
(6, 'Test', 'User', 'test@user.ca', '$2y$10$Q0UMSs8hV9E99xW3KDFIqed/ZCNuvlaN1LoK0eKq5wAsFdhGGfMRi', 'customer', 1, '2026-07-30 01:11:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `uq_orders_order_number` (`order_number`),
  ADD KEY `idx_orders_user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_user_id` (`user_id`),
  ADD KEY `idx_order_items_order_number` (`order_number`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_options`
--
ALTER TABLE `product_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_options`
--
ALTER TABLE `product_options`
  MODIFY `option_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
