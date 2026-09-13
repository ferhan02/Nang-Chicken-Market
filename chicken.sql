-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2026 at 01:38 AM
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
-- Database: `chicken`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `customer_id`, `stock_id`, `quantity`) VALUES
(1, 10, 2, 1),
(2, 10, 1, 4),
(3, 10, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL COMMENT 'customer_id',
  `customer_name` varchar(255) NOT NULL COMMENT 'customer_name',
  `customer_phone` varchar(20) NOT NULL COMMENT 'customer_phone',
  `customer_address` varchar(255) NOT NULL COMMENT 'customer_address',
  `customer_email` varchar(255) NOT NULL COMMENT 'customer_email',
  `customer_password` varchar(255) NOT NULL COMMENT 'customer_password'
  `customer_image` varchar(255) DEFAULT NULL COMMENT 'customer_image'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_phone`, `customer_address`, `customer_email`, `customer_password`) VALUES
(1, 'Ahmad Zaki', '0123456789', 'Kuala Lumpur', 'ahmad@example.com', '123'),
(2, 'Nur Aisyah', '0134567890', 'Shah Alam', 'aisyah@example.com', '123'),
(3, 'Muhammad Amir', '0145678901', 'Petaling Jaya', 'amir@example.com', '123'),
(4, 'Siti Khadijah', '0156789012', 'Subang Jaya', 'khadijah@example.com', '123'),
(5, 'Daniel Hakim', '0167890123', 'Puchong', 'daniel@example.com', '123'),
(6, 'Aina Sofea', '0178901234', 'Bangi', 'aina@example.com', '123'),
(7, 'Adam Firdaus', '0189012345', 'Kajang', 'adam@example.com', '123'),
(8, 'Nabila Farhana', '0190123456', 'Ampang', 'nabila@example.com', '123'),
(9, 'Irfan Syafiq', '0112233445', 'Cheras', 'irfan@example.com', '123'),
(10, 'Ferhan', '0109988776', 'Setapak', 'fmuriddan@gmail.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orders_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `sale_quantitysold` int(11) NOT NULL,
  `sale_total` decimal(20,2) NOT NULL,
  `order_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '(0 = Pending, 1 = Completed)',
  `receipt_no` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orders_id`, `customer_id`, `staff_id`, `stock_id`, `sale_date`, `sale_quantitysold`, `sale_total`, `order_status`, `receipt_no`) VALUES
(1, 1, 1, 1, '2026-01-05', 5, 45.00, 1, 'NC-A91F3C2B'),
(2, 2, 2, 2, '2026-01-06', 3, 30.00, 1, 'NC-7B2E9A41'),
(3, 3, 1, 3, '2026-01-06', 4, 54.00, 1, 'NC-3C8F12AD'),
(4, 4, 3, 4, '2026-01-07', 2, 22.00, 1, 'NC-9D4A6F21'),
(5, 5, 2, 1, '2026-01-08', 6, 54.00, 1, 'NC-2E7B91CD'),
(6, 6, 1, 2, '2026-01-08', 7, 70.00, 1, 'NC-FA12B7C9'),
(7, 7, 3, 3, '2026-01-09', 5, 67.50, 1, 'NC-8C21DAF3'),
(8, 8, 2, 4, '2026-01-09', 4, 44.00, 1, 'NC-71D9EAC4'),
(9, 9, 1, 1, '2026-01-10', 8, 72.00, 1, 'NC-B4F91A3E'),
(10, 10, 2, 2, '2026-01-10', 5, 50.00, 1, 'NC-0A9C7D2B'),
(11, 1, 3, 3, '2026-01-11', 6, 81.00, 1, 'NC-4E91BC2A'),
(12, 2, 1, 4, '2026-01-11', 3, 33.00, 1, 'NC-9A71D4E2'),
(13, 3, 2, 2, '2026-01-12', 9, 90.00, 1, 'NC-6F2A9B8C'),
(14, 4, 3, 1, '2026-01-12', 4, 36.00, 1, 'NC-AD9C21F4'),
(15, 5, 1, 3, '2026-01-13', 7, 94.50, 1, 'NC-2F8C91DA');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `orders_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(19,2) NOT NULL,
  `payment_method` tinyint(1) NOT NULL COMMENT '0 = COD | 1 = Card'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `orders_id`, `customer_id`, `payment_date`, `payment_amount`, `payment_method`) VALUES
(1, 1, 1, '2026-01-05', 45.00, 0),
(2, 2, 2, '2026-01-06', 30.00, 1),
(3, 3, 3, '2026-01-06', 54.00, 0),
(4, 4, 4, '2026-01-07', 22.00, 1),
(5, 5, 5, '2026-01-08', 54.00, 0),
(6, 6, 6, '2026-01-08', 70.00, 1),
(7, 7, 7, '2026-01-09', 67.50, 0),
(8, 8, 8, '2026-01-09', 44.00, 1),
(9, 9, 9, '2026-01-10', 72.00, 0),
(10, 10, 10, '2026-01-10', 50.00, 1),
(11, 11, 1, '2026-01-11', 81.00, 0),
(12, 12, 2, '2026-01-11', 33.00, 1),
(13, 13, 3, '2026-01-12', 90.00, 0),
(14, 14, 4, '2026-01-12', 36.00, 1),
(15, 15, 5, '2026-01-13', 94.50, 0);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL COMMENT 'staff_id',
  `staff_name` varchar(255) NOT NULL COMMENT 'staff_name',
  `staff_phone` varchar(20) NOT NULL COMMENT 'staff_phone',
  `staff_salary` decimal(10,2) NOT NULL COMMENT 'staff_salary',
  `staff_dob` date NOT NULL COMMENT 'staff_dob',
  `staff_username` varchar(32) NOT NULL COMMENT 'staff_username',
  `staff_password` varchar(255) NOT NULL COMMENT 'staff_password'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `staff_name`, `staff_phone`, `staff_salary`, `staff_dob`, `staff_username`, `staff_password`) VALUES
(1, 'To be selected', '0000000000', 0.00, '0001-01-01', 'Reserved', '123'),
(2, 'Ichigo', '0125463879', 1700.00, '1998-03-21', 'ichigostrawberry', 'strawberryflavoredcurrypuff'),
(3, 'Beyond', '0126230955', 1700.00, '2002-02-12', 'b_yond', 'beyondtheboundary'),
(4, 'Kuro', '0120948578', 1700.00, '2001-08-03', 'kurosaki', 'kurokami'),
(5, 'ferhan', '123', 1000.00, '2026-01-14', 'ferhan', '123');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `stock_id` int(11) NOT NULL COMMENT 'stock_id',
  `stock_name` varchar(255) NOT NULL COMMENT 'stock_name',
  `stock_price` decimal(19,2) NOT NULL COMMENT 'stock_price',
  `stock_quantity` int(11) NOT NULL COMMENT 'stock_quantity'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`stock_id`, `stock_name`, `stock_price`, `stock_quantity`) VALUES
(1, 'THIGHS', 9.00, 221),
(2, 'DRUMSTICKS', 10.00, 124),
(3, 'WING', 13.50, 148),
(4, 'BREAST', 11.00, 178);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `stock_id` (`stock_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orders_id`),
  ADD KEY `staff_id` (`staff_id`,`stock_id`),
  ADD KEY `fk_stock_id` (`stock_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_receipt_no` (`receipt_no`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `orders_id_2` (`orders_id`),
  ADD KEY `orders_id` (`orders_id`,`customer_id`),
  ADD KEY `fk_customer_id` (`customer_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`stock_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'customer_id', AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `orders_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'staff_id', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'stock_id', AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `stock` (`stock_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_staff_id` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_id` FOREIGN KEY (`stock_id`) REFERENCES `stock` (`stock_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_id` FOREIGN KEY (`orders_id`) REFERENCES `orders` (`orders_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
