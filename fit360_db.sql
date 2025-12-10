-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2025 at 07:12 AM
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
-- Database: `fit360_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categoria_producto`
--

CREATE TABLE `categoria_producto` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `esta_activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categoria_producto`
--

INSERT INTO `categoria_producto` (`id_categoria`, `nombre`, `descripcion`, `esta_activo`) VALUES
(1, 'Proteínas', 'Suplementos de proteína para recuperación muscular', 1),
(2, 'Aminoácidos', 'BCAA y otros aminoácidos esenciales', 1),
(3, 'Pre-Workout', 'Suplementos para energía y rendimiento', 1),
(4, 'Vitaminas', 'Multivitamínicos y suplementos nutricionales', 1),
(5, 'Equipamiento', 'Equipos y accesorios de entrenamiento', 1);

-- --------------------------------------------------------

--
-- Table structure for table `imagen_servicio`
--

CREATE TABLE `imagen_servicio` (
  `id_imagen` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `url_imagen` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `imagen_servicio`
--

INSERT INTO `imagen_servicio` (`id_imagen`, `id_servicio`, `url_imagen`, `fecha_subida`) VALUES
(4, 1, 'assets/services/68624fe7992d2_gallery_0.jpg', '2025-06-30 08:50:47'),
(5, 1, 'assets/services/68624fe799cd6_gallery_1.jpg', '2025-06-30 08:50:47'),
(7, 2, 'assets/services/686251c893c5e_gallery_0.jpg', '2025-06-30 08:58:48'),
(8, 2, 'assets/services/686251c89433f_gallery_1.jpg', '2025-06-30 08:58:48'),
(9, 2, 'assets/services/686251c894e28_gallery_2.jpg', '2025-06-30 08:58:48'),
(10, 3, 'assets/services/686251deb8bd3_gallery_0.jpg', '2025-06-30 08:59:10'),
(11, 3, 'assets/services/686251deb93bd_gallery_1.jpg', '2025-06-30 08:59:10'),
(12, 3, 'assets/services/686251deb989f_gallery_2.jpg', '2025-06-30 08:59:10'),
(13, 4, 'assets/services/686251ef7ef16_gallery_0.jpg', '2025-06-30 08:59:27'),
(14, 4, 'assets/services/686251ef7f842_gallery_1.jpg', '2025-06-30 08:59:27'),
(15, 4, 'assets/services/686251ef8015e_gallery_2.jpg', '2025-06-30 08:59:27'),
(16, 5, 'assets/services/68625203ec0b5_gallery_0.jpg', '2025-06-30 08:59:47'),
(17, 5, 'assets/services/68625203ec540_gallery_1.jpg', '2025-06-30 08:59:47'),
(18, 5, 'assets/services/68625203ed32f_gallery_2.jpg', '2025-06-30 08:59:47'),
(19, 5, 'assets/services/68625203edc61_gallery_3.jpg', '2025-06-30 08:59:47'),
(20, 5, 'assets/services/68625203ee1a1_gallery_4.jpg', '2025-06-30 08:59:47'),
(21, 6, 'assets/services/6862521754be1_gallery_0.jpg', '2025-06-30 09:00:07'),
(22, 6, 'assets/services/6862521755544_gallery_1.jpg', '2025-06-30 09:00:07'),
(23, 6, 'assets/services/6862521755ce3_gallery_2.jpg', '2025-06-30 09:00:07'),
(24, 6, 'assets/services/68625217564ac_gallery_3.jpg', '2025-06-30 09:00:07'),
(25, 6, 'assets/services/68625217571e7_gallery_4.jpg', '2025-06-30 09:00:07'),
(26, 6, 'assets/services/686252175776a_gallery_5.jpg', '2025-06-30 09:00:07'),
(27, 6, 'assets/services/6862521757be0_gallery_6.jpg', '2025-06-30 09:00:07'),
(28, 6, 'assets/services/6862521758586_gallery_7.jpg', '2025-06-30 09:00:07');

-- --------------------------------------------------------

--
-- Table structure for table `item_pedido`
--

CREATE TABLE `item_pedido` (
  `id_item_pedido` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_pedido`
--

INSERT INTO `item_pedido` (`id_item_pedido`, `id_pedido`, `id_producto`, `id_servicio`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, NULL, 1, 1.00),
(2, 2, 1, NULL, 1, 1.00),
(3, 3, 1, NULL, 1, 1.00),
(4, 3, 2, NULL, 1, 45.99),
(5, 4, 2, NULL, 2, 45.99),
(6, 5, 2, NULL, 2, 45.99),
(7, 6, 3, NULL, 1, 65.99),
(8, 7, 3, NULL, 3, 65.99),
(9, 8, 3, NULL, 3, 65.99),
(10, 9, 4, NULL, 1, 75.99),
(11, 10, 4, NULL, 1, 75.99),
(12, 11, 5, NULL, 2, 55.99),
(13, 12, 6, NULL, 10, 35.99),
(14, 13, 4, NULL, 2, 75.99),
(15, 14, 5, NULL, 1, 55.99),
(16, 15, 1, NULL, 1, 1.00),
(17, 15, 2, NULL, 1, 45.99),
(18, 15, 3, NULL, 1, 65.99),
(19, 15, 6, NULL, 1, 35.99),
(20, 15, 5, NULL, 1, 55.99),
(21, 15, 4, NULL, 1, 75.99),
(22, 16, 1, NULL, 1, 1.00),
(23, 17, 1, NULL, 2, 1.00),
(24, 17, 2, NULL, 2, 45.99),
(25, 17, 3, NULL, 2, 65.99),
(26, 17, 6, NULL, 1, 35.99),
(27, 17, 5, NULL, 1, 55.99),
(28, 17, 4, NULL, 1, 75.99),
(29, 18, 3, NULL, 1, 65.99),
(30, 18, 2, NULL, 1, 45.99),
(31, 19, 1, NULL, 1, 1.00),
(32, 19, 2, NULL, 1, 45.99),
(33, 20, 1, NULL, 1, 1.00),
(34, 20, 2, NULL, 1, 45.99),
(35, 21, 4, NULL, 1, 75.99),
(36, 22, 4, NULL, 1, 75.99),
(37, 23, 1, NULL, 1, 1.00),
(38, 24, 1, NULL, 1, 1.00),
(39, 25, 2, NULL, 1, 45.99),
(40, 26, 2, NULL, 1, 45.99),
(41, 27, 1, NULL, 1, 1.00),
(42, 28, 1, NULL, 1, 1.00),
(43, 29, 1, NULL, 1, 1.00),
(44, 30, 1, NULL, 1, 1.00),
(45, 31, 1, NULL, 1, 1.00),
(46, 32, 1, NULL, 1, 1.00),
(47, 33, 2, NULL, 1, 45.99),
(48, 34, 1, NULL, 1, 1.00),
(49, 35, 1, NULL, 1, 1.00),
(50, 36, 2, NULL, 1, 45.99),
(51, 37, 2, NULL, 1, 45.99),
(52, 38, 1, NULL, 1, 1.00),
(53, 39, 3, NULL, 1, 65.99),
(54, 40, 1, NULL, 1, 1.00),
(55, 41, 1, NULL, 1, 1.00),
(56, 42, 1, NULL, 1, 1.00),
(57, 43, 1, NULL, 1, 1.00),
(58, 44, 1, NULL, 1, 1.00),
(59, 45, 2, NULL, 1, 45.99),
(60, 46, 1, NULL, 1, 1.00),
(61, 47, 2, NULL, 1, 45.99),
(62, 47, 1, NULL, 1, 2.00),
(63, 48, 2, NULL, 1, 45.99),
(64, 49, 7, NULL, 1, 200.00),
(65, 50, 1, NULL, 1, 2.00),
(66, 51, 7, NULL, 1, 200.00),
(67, 52, 7, NULL, 2, 200.00),
(68, 53, 7, NULL, 1, 200.00),
(69, 54, 7, NULL, 3, 200.00),
(70, 55, 7, NULL, 3, 200.00),
(71, 56, 7, NULL, 1, 200.00),
(72, 57, 7, NULL, 1, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `metodo_pago_usuario`
--

CREATE TABLE `metodo_pago_usuario` (
  `id_metodo_pago` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_tarjeta` varchar(20) NOT NULL,
  `numero_tarjeta` varchar(255) NOT NULL,
  `fecha_vencimiento` varchar(7) NOT NULL,
  `cvv` varchar(255) NOT NULL,
  `nombre_titular` varchar(100) NOT NULL,
  `es_predeterminado` tinyint(1) DEFAULT 0,
  `esta_activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `metodo_pago_usuario`
--

INSERT INTO `metodo_pago_usuario` (`id_metodo_pago`, `id_usuario`, `tipo_tarjeta`, `numero_tarjeta`, `fecha_vencimiento`, `cvv`, `nombre_titular`, `es_predeterminado`, `esta_activo`, `fecha_creacion`) VALUES
(7, 15, 'Visa', '4815 1631 2032 2675', '03/28', '123', 'edgar', 0, 1, '2025-06-30 03:21:26'),
(8, 15, 'Visa', '4198 2200 0948 3544', '07/28', '123', 'edgar', 1, 1, '2025-06-30 03:24:42'),
(9, 6, 'Visa', '4815 1631 2032 2675', '03/28', '123', 'are', 1, 1, '2025-06-30 07:17:27'),
(11, 2, 'Visa', '4027 6657 4321 4232', '06/26', '123', 'EGDAR', 1, 0, '2025-07-02 06:51:14'),
(12, 2, 'Visa', '4815 1631 2032 2675', '03/28', '123', 'edgar', 1, 1, '2025-07-02 06:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `pago`
--

CREATE TABLE `pago` (
  `id_pago` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_suscripcion` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `id_transaccion` varchar(100) DEFAULT NULL,
  `estado` enum('pendiente','completado','fallido','reembolsado') DEFAULT 'pendiente',
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pago`
--

INSERT INTO `pago` (`id_pago`, `id_pedido`, `id_suscripcion`, `monto`, `metodo_pago`, `id_transaccion`, `estado`, `fecha_pago`) VALUES
(1, NULL, 1, 300.00, 'tarjeta', NULL, 'completado', '2025-06-23 03:43:57'),
(2, NULL, 2, 150.00, 'tarjeta', NULL, 'completado', '2025-06-23 03:59:32'),
(3, NULL, 3, 150.00, 'método existente', NULL, 'completado', '2025-06-23 06:31:04'),
(4, NULL, 4, 300.00, 'método existente', NULL, 'completado', '2025-06-23 06:40:43'),
(5, NULL, 5, 150.00, 'método existente', NULL, 'completado', '2025-06-23 08:09:26'),
(6, 1, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-23 08:24:30'),
(7, 2, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-23 08:24:30'),
(8, 3, NULL, 47.99, 'método existente', NULL, 'completado', '2025-06-23 08:35:03'),
(9, 4, NULL, 92.98, 'nueva tarjeta', NULL, 'completado', '2025-06-23 08:35:35'),
(10, 5, NULL, 92.98, 'método existente', NULL, 'completado', '2025-06-23 08:35:35'),
(11, 6, NULL, 66.99, 'método existente', NULL, 'completado', '2025-06-23 08:46:35'),
(12, 7, NULL, 198.97, 'nueva tarjeta', NULL, 'completado', '2025-06-23 08:46:59'),
(13, 8, NULL, 198.97, 'método existente', NULL, 'completado', '2025-06-23 08:46:59'),
(14, 9, NULL, 76.99, 'nueva tarjeta', NULL, 'completado', '2025-06-23 08:50:39'),
(15, 10, NULL, 76.99, 'método existente', NULL, 'completado', '2025-06-23 08:50:39'),
(16, 11, NULL, 112.98, 'método existente', NULL, 'completado', '2025-06-23 08:51:38'),
(17, 12, NULL, 360.90, 'método existente', NULL, 'completado', '2025-06-23 08:53:23'),
(18, 13, NULL, 152.98, 'método existente', NULL, 'completado', '2025-06-23 08:57:06'),
(19, 14, NULL, 56.99, 'método existente', NULL, 'completado', '2025-06-23 08:57:28'),
(20, 15, NULL, 281.95, 'nueva tarjeta', NULL, 'completado', '2025-05-08 10:01:00'),
(21, NULL, 6, 150.00, 'método existente', NULL, 'completado', '2025-06-26 08:57:41'),
(22, 16, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-26 09:34:00'),
(23, NULL, 7, 300.00, 'método existente', NULL, 'completado', '2025-06-26 09:36:58'),
(24, 17, NULL, 394.93, 'método existente', NULL, 'completado', '2025-06-26 10:29:29'),
(25, NULL, 8, 150.00, 'método existente', NULL, 'completado', '2025-06-26 13:28:07'),
(26, NULL, 9, 300.00, 'método existente', NULL, 'completado', '2025-06-26 22:59:15'),
(27, NULL, 10, 300.00, 'método existente', NULL, 'completado', '2025-06-30 04:11:51'),
(28, 18, NULL, 112.98, 'nueva tarjeta', NULL, 'completado', '2025-06-30 04:51:35'),
(29, 19, NULL, 47.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 06:32:21'),
(30, NULL, 11, 300.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 06:35:31'),
(31, 20, NULL, 47.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 06:39:43'),
(32, 21, NULL, 76.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 06:59:41'),
(33, 22, NULL, 76.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 06:59:41'),
(34, 23, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:02:27'),
(35, 24, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:02:27'),
(36, 25, NULL, 46.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:06:04'),
(37, 26, NULL, 46.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:06:04'),
(38, 27, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:09:58'),
(39, 28, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:09:58'),
(40, 29, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:12:13'),
(41, 30, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:12:13'),
(42, 31, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:14:50'),
(43, 32, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:14:50'),
(44, 33, NULL, 46.99, 'método existente', NULL, 'completado', '2025-06-30 07:18:22'),
(45, 34, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:23:02'),
(46, 35, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:23:02'),
(47, 36, NULL, 46.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:30:15'),
(48, 37, NULL, 46.99, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:30:15'),
(49, 38, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-30 07:33:42'),
(50, 39, NULL, 66.99, 'método existente', NULL, 'completado', '2025-06-30 07:35:17'),
(51, 40, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-30 07:37:15'),
(52, 41, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-30 07:38:39'),
(53, 42, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:45:17'),
(54, 43, NULL, 2.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 07:45:17'),
(55, 44, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-30 08:04:44'),
(56, 45, NULL, 46.99, 'método existente', NULL, 'completado', '2025-06-30 08:06:11'),
(57, 46, NULL, 2.00, 'método existente', NULL, 'completado', '2025-06-30 08:06:49'),
(58, NULL, 12, 400.00, 'nueva tarjeta', NULL, 'completado', '2025-06-30 09:46:16'),
(59, 47, NULL, 48.99, 'método existente', NULL, 'completado', '2025-07-02 07:03:23'),
(60, 48, NULL, 46.99, 'método existente', NULL, 'completado', '2025-07-02 07:18:54'),
(61, NULL, 13, 150.00, 'método existente', NULL, 'completado', '2025-07-02 07:40:00'),
(62, NULL, 14, 300.00, 'método existente', NULL, 'completado', '2025-07-02 07:49:14'),
(63, 49, NULL, 201.00, 'método existente', NULL, 'completado', '2025-07-02 07:51:52'),
(64, 50, NULL, 3.00, 'método existente', NULL, 'completado', '2025-07-02 08:17:18'),
(65, 51, NULL, 201.00, 'método existente', NULL, 'completado', '2025-07-02 08:20:42'),
(66, 52, NULL, 401.00, 'método existente', NULL, 'completado', '2025-07-02 08:38:24'),
(67, 53, NULL, 201.00, 'método existente', NULL, 'completado', '2025-07-02 08:38:54'),
(68, NULL, 15, 150.00, 'método existente', NULL, 'completado', '2025-07-02 08:44:23'),
(69, 54, NULL, 601.00, 'método existente', NULL, 'completado', '2025-07-02 09:00:49'),
(70, 55, NULL, 601.00, 'método existente', NULL, 'completado', '2025-07-02 09:03:05'),
(71, 56, NULL, 201.00, 'método existente', NULL, 'completado', '2025-07-02 09:03:47'),
(72, 57, NULL, 201.00, 'método existente', NULL, 'completado', '2025-07-02 09:04:23'),
(73, NULL, 16, 150.00, 'método existente', NULL, 'completado', '2025-07-02 16:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','enviado','entregado','cancelado') DEFAULT 'pendiente',
  `direccion_envio` text DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `id_usuario`, `monto_total`, `estado`, `direccion_envio`, `metodo_pago`, `fecha_creacion`) VALUES
(1, 2, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-01-07 08:24:30'),
(2, 2, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:24:30'),
(3, 2, 47.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-01-17 08:35:03'),
(4, 2, 92.98, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-23 08:35:35'),
(5, 2, 92.98, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:35:35'),
(6, 2, 66.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:46:35'),
(7, 2, 198.97, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-02-12 08:46:59'),
(8, 2, 198.97, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:46:59'),
(9, 2, 76.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-23 08:50:39'),
(10, 2, 76.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:50:39'),
(11, 2, 112.98, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:51:38'),
(12, 2, 360.90, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-04-09 08:53:23'),
(13, 2, 152.98, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-23 08:57:06'),
(14, 2, 56.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-05-06 08:57:28'),
(15, 6, 281.95, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-05-08 10:00:59'),
(16, 2, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-26 09:34:00'),
(17, 2, 394.93, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-26 10:29:29'),
(18, 2, 112.98, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'nueva tarjeta', '2025-06-30 04:51:35'),
(19, 6, 47.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 06:32:21'),
(20, 6, 47.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 06:39:43'),
(21, 6, 76.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 06:59:41'),
(22, 6, 76.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 06:59:41'),
(23, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:02:27'),
(24, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:02:27'),
(25, 6, 46.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:06:04'),
(26, 6, 46.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:06:04'),
(27, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:09:58'),
(28, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:09:58'),
(29, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:12:13'),
(30, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:12:13'),
(31, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:14:50'),
(32, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:14:50'),
(33, 6, 46.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 07:18:22'),
(34, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:23:02'),
(35, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:23:02'),
(36, 6, 46.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:30:15'),
(37, 6, 46.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:30:15'),
(38, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 07:33:42'),
(39, 6, 66.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 07:35:17'),
(40, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 07:37:15'),
(41, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 07:38:39'),
(42, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:45:17'),
(43, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'nueva tarjeta', '2025-06-30 07:45:17'),
(44, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 08:04:44'),
(45, 6, 46.99, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 08:06:11'),
(46, 6, 2.00, 'pagado', 'La Mancha III, Naucalpan de Juarez', 'método existente', '2025-06-30 08:06:49'),
(47, 2, 48.99, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 07:03:23'),
(48, 2, 46.99, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 07:18:54'),
(49, 2, 201.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 07:51:52'),
(50, 2, 3.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 08:17:18'),
(51, 2, 201.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 08:20:42'),
(52, 2, 401.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 08:38:24'),
(53, 2, 201.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 08:38:54'),
(54, 2, 601.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 09:00:49'),
(55, 2, 601.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 09:03:05'),
(56, 2, 201.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 09:03:47'),
(57, 2, 201.00, 'pagado', 'Calle 123, Colonia Centro, Ciudad de México', 'método existente', '2025-07-02 09:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `plan_suscripcion`
--

CREATE TABLE `plan_suscripcion` (
  `id_plan` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `duracion_dias` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `beneficios` text NOT NULL,
  `esta_activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan_suscripcion`
--

INSERT INTO `plan_suscripcion` (`id_plan`, `nombre`, `precio`, `duracion_dias`, `descripcion`, `beneficios`, `esta_activo`) VALUES
(1, 'BÁSICO', 100.00, 30, 'Plan básico para principiantes', 'Acceso a equipos básicos, Clases grupales limitadas, Locker room', 1),
(2, 'PRO', 150.00, 30, 'Plan profesional con más beneficios', 'Acceso ilimitado a todas las clases, Entrenador personal dedicado, Plan de nutrición personalizado, Acceso a la app premium', 1),
(3, 'PREMIUM', 300.00, 30, 'Plan premium con todos los beneficios', 'Todo del plan PRO, Clases en vivo exclusivas, Evaluación física mensual, Soporte 24/7, Acceso a la comunidad VIP', 1),
(5, 'proplus', 400.00, 30, 'mejor de todos', 'lo mejor de lo mejor\r\nobvio nice', 0);

-- --------------------------------------------------------

--
-- Table structure for table `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `cantidad_stock` int(11) DEFAULT 0,
  `id_categoria` int(11) DEFAULT NULL,
  `url_imagen` varchar(255) DEFAULT NULL,
  `calificacion` decimal(3,2) DEFAULT 0.00,
  `es_destacado` tinyint(1) DEFAULT 0,
  `esta_activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre`, `descripcion`, `precio`, `precio_anterior`, `cantidad_stock`, `id_categoria`, `url_imagen`, `calificacion`, `es_destacado`, `esta_activo`, `fecha_creacion`) VALUES
(1, 'Proteína Whey Gold Standard', 'Proteína de suero de leche premium para recuperación muscular', 2.00, 10.00, 22, 1, 'assets/products/686256756c9f2.webp', 4.20, 1, 1, '2025-06-21 13:16:52'),
(2, 'Creatina Monohidratada', 'Aumenta la fuerza y potencia muscular', 45.99, 59.99, 11, 1, 'assets/products/6862569465809.jpg', 3.00, 1, 1, '2025-06-21 13:16:52'),
(3, 'BCAA Aminoácidos', 'Previene la pérdida muscular y mejora la recuperación', 65.99, 79.99, 13, 2, 'assets/products/6862569da46cd.jpg', 4.00, 0, 1, '2025-06-21 13:16:52'),
(4, 'Pre-Workout Explosive', 'Energía máxima y enfoque para entrenamientos intensos', 75.99, 89.99, 12, 3, 'assets/products/686256acd18c2.webp', 3.00, 0, 1, '2025-06-21 13:16:52'),
(5, 'Omega 3 Premium', 'Ácidos grasos esenciales para la salud cardiovascular', 55.99, 69.99, 35, 4, 'assets/products/686256b5909bc.webp', 5.00, 0, 1, '2025-06-21 13:16:52'),
(6, 'Multivitamínico Complete', 'Vitaminas y minerales esenciales para el bienestar general', 35.99, 49.99, 48, 4, 'assets/products/686256c255e3c.webp', 5.00, 0, 1, '2025-06-21 13:16:52'),
(7, 'Mancuerna', 'Mancuerna de 10 kilos', 200.00, 250.00, 0, 5, 'assets/products/686257996704b.jpg', 0.00, 1, 1, '2025-06-30 09:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `reseña_producto`
--

CREATE TABLE `reseña_producto` (
  `id_reseña` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `calificacion` int(11) NOT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  `comentario` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reseña_producto`
--

INSERT INTO `reseña_producto` (`id_reseña`, `id_producto`, `id_usuario`, `calificacion`, `comentario`, `fecha_creacion`) VALUES
(1, 3, 2, 4, 'asdasdasdasdasdasdasd', '2025-06-21 14:05:59'),
(4, 2, 2, 3, 'me gusto al pero no tanto', '2025-06-21 14:08:41'),
(5, 1, 2, 5, 'asddddddddddddddddddddddddddaaaaaaaaaaa', '2025-06-23 04:17:59'),
(6, 1, 6, 4, 'dos tres', '2025-06-23 09:57:54'),
(7, 1, 2, 5, 'nice', '2025-06-26 09:49:09'),
(8, 5, 2, 5, 'bueno', '2025-06-26 09:55:32'),
(9, 6, 2, 5, 'nice', '2025-06-26 09:55:39'),
(10, 4, 2, 3, 'nice', '2025-06-26 09:55:51'),
(11, 1, 15, 2, 'otro comentario', '2025-06-30 04:15:51'),
(12, 1, 17, 5, 'se ve bien', '2025-06-30 09:04:05');

-- --------------------------------------------------------

--
-- Table structure for table `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `beneficios` text NOT NULL,
  `duracion_minutos` int(11) NOT NULL,
  `url_imagen` varchar(255) NOT NULL,
  `url_video` varchar(255) NOT NULL,
  `esta_activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servicio`
--

INSERT INTO `servicio` (`id_servicio`, `nombre`, `descripcion`, `beneficios`, `duracion_minutos`, `url_imagen`, `url_video`, `esta_activo`, `fecha_creacion`) VALUES
(1, 'Entrenamiento', 'Programas de entrenamiento 100% personalizados diseñados por expertos.', 'Entrenamiento personalizado\r\nSeguimiento profesional\r\nResultados garantizados\r\nHorarios flexibles\r\nOtra prueba', 60, 'assets/services/68625174db9ea.jpg', 'assets/videos/68625174dbc36.mp4', 1, '2025-06-21 13:16:52'),
(2, 'Clase de Spinning', 'Entrenamiento cardiovascular intenso en bicicleta estática.', 'Quema de calorías\r\nMejora cardiovascular\r\nBajo impacto articular', 45, 'assets/services/686251c892ec8.jpg', 'assets/videos/686251c893188.mp4', 1, '2025-06-21 13:16:52'),
(3, 'Yoga para Principiantes', 'Clase de yoga suave para mejorar flexibilidad y relajación.', 'Reduce el estrés\r\nMejora la flexibilidad\r\nAumenta la fuerza', 60, 'assets/services/686251deb7f1b.jpg', 'assets/videos/686251deb823a.mp4', 1, '2025-06-21 13:16:52'),
(4, 'Pilates Mat', 'Fortalecimiento del core y mejora de la postura.', 'Fortalece el abdomen\r\nMejora la postura\r\nTonifica el cuerpo', 50, 'assets/services/686251ef7e0d6.jpg', 'assets/videos/686251ef7e462.mp4', 1, '2025-06-21 13:16:52'),
(5, 'CrossFit', 'Entrenamiento funcional de alta intensidad.', 'Aumento de fuerza\r\nResistencia cardiovascular\r\nComunidad motivadora', 60, 'assets/services/68625203eb6af.jpg', 'assets/videos/68625203eb946.mp4', 1, '2025-06-21 13:16:52'),
(6, 'Zumba', 'Baile fitness para quemar calorías de forma divertida.', 'Quema calorías\r\nDivertido y social\r\nMejora la coordinación', 45, 'assets/services/6862521753e39.jpg', 'assets/videos/68625217541f6.mp4', 1, '2025-06-21 13:16:52'),
(7, 'prueba', '', '', 50, 'assets/services/68625a318b623.jpg', 'assets/videos/68625a318b7c6.mp4', 1, '2025-06-30 09:34:41');

-- --------------------------------------------------------

--
-- Table structure for table `suscripcion_usuario`
--

CREATE TABLE `suscripcion_usuario` (
  `id_suscripcion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_plan` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activa','expirada','cancelada') DEFAULT 'activa',
  `renovacion_automatica` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suscripcion_usuario`
--

INSERT INTO `suscripcion_usuario` (`id_suscripcion`, `id_usuario`, `id_plan`, `fecha_inicio`, `fecha_fin`, `estado`, `renovacion_automatica`, `fecha_creacion`) VALUES
(1, 2, 3, '2025-06-23', '2025-07-23', 'cancelada', 1, '2025-03-05 03:43:57'),
(2, 4, 2, '2025-06-23', '2025-07-23', 'activa', 1, '2025-03-05 03:59:32'),
(3, 2, 2, '2025-06-23', '2025-07-23', 'cancelada', 1, '2025-05-06 06:31:04'),
(4, 2, 3, '2025-06-23', '2025-07-23', 'cancelada', 1, '2025-06-23 06:40:43'),
(5, 2, 2, '2025-06-23', '2025-07-23', 'cancelada', 1, '2025-05-07 08:09:26'),
(6, 2, 2, '2025-06-26', '2025-07-26', 'cancelada', 1, '2025-06-26 08:57:41'),
(7, 2, 3, '2025-05-28', '2025-06-28', 'cancelada', 1, '2025-06-26 09:36:58'),
(8, 2, 2, '2025-06-26', '2025-07-26', 'cancelada', 1, '2025-06-26 13:28:07'),
(9, 2, 3, '2025-06-27', '2025-07-27', 'cancelada', 1, '2025-06-26 22:59:14'),
(10, 15, 3, '2025-06-30', '2025-07-30', 'activa', 1, '2025-06-30 04:11:51'),
(11, 6, 3, '2025-06-30', '2025-07-30', 'activa', 1, '2025-06-30 06:35:31'),
(12, 17, 5, '2025-06-30', '2025-07-30', 'cancelada', 1, '2025-06-30 09:46:16'),
(13, 2, 2, '2025-07-02', '2025-08-01', 'cancelada', 1, '2025-07-02 07:40:00'),
(14, 2, 3, '2025-07-02', '2025-08-01', 'cancelada', 1, '2025-07-02 07:49:14'),
(15, 2, 2, '2025-07-02', '2025-08-01', 'cancelada', 1, '2025-07-02 08:44:23'),
(16, 2, 2, '2025-07-02', '2025-08-01', 'activa', 1, '2025-07-02 16:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `hash_contraseña` varchar(255) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(10) NOT NULL,
  `direccion` text NOT NULL,
  `rol` enum('usuario','administrador') DEFAULT 'usuario',
  `esta_activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre_usuario`, `correo`, `hash_contraseña`, `nombre`, `apellido`, `telefono`, `direccion`, `rol`, `esta_activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Admin', 'admin@fit360.com', '12345678', 'Administrador', 'Sistema', '', '', 'administrador', 1, '2025-04-01 13:16:52', '2025-06-30 08:49:14'),
(2, 'edgarsc', 'itsfreestyle043@gmail.com', '12345678', 'Edgar', 'Solis hdez', '5568632197', 'Calle 123, Colonia Centro, Ciudad de México', 'usuario', 1, '2025-05-06 13:29:35', '2025-07-02 14:47:42'),
(4, 'otro', 'otro@gmai.com', '12345678', 'otro', 'otro', '', '', 'usuario', 1, '2025-06-23 03:59:03', '2025-06-23 05:58:31'),
(6, 'areliban', 'arelybandy81@gmail.com', '12345678', 'areli', 'bandi', '5568632197', 'La Mancha III, Naucalpan de Juarez', 'usuario', 1, '2025-06-23 09:57:15', '2025-06-30 06:26:03'),
(15, 'edgar14sc', 'edgardye14@gmail.com', '12345678', 'Edgar', 'Ramirez', '1232312313', 'calle 12, La Mancha III, Naucalpan de Juarez', 'usuario', 1, '2025-06-30 03:09:32', '2025-07-02 08:28:09'),
(17, 'pandaoscar', 'oscardejcynmb@gmail.com', '12345678', 'oscar', 'gatito', '5512312311', 'La Mancha III, Naucalpan de Juarez', 'usuario', 1, '2025-06-30 08:48:47', '2025-06-30 08:48:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categoria_producto`
--
ALTER TABLE `categoria_producto`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indexes for table `imagen_servicio`
--
ALTER TABLE `imagen_servicio`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indexes for table `item_pedido`
--
ALTER TABLE `item_pedido`
  ADD PRIMARY KEY (`id_item_pedido`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indexes for table `metodo_pago_usuario`
--
ALTER TABLE `metodo_pago_usuario`
  ADD PRIMARY KEY (`id_metodo_pago`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_suscripcion` (`id_suscripcion`),
  ADD KEY `idx_pago_pedido` (`id_pedido`);

--
-- Indexes for table `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `idx_pedido_usuario` (`id_usuario`);

--
-- Indexes for table `plan_suscripcion`
--
ALTER TABLE `plan_suscripcion`
  ADD PRIMARY KEY (`id_plan`);

--
-- Indexes for table `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `idx_producto_categoria` (`id_categoria`);

--
-- Indexes for table `reseña_producto`
--
ALTER TABLE `reseña_producto`
  ADD PRIMARY KEY (`id_reseña`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_servicio`);

--
-- Indexes for table `suscripcion_usuario`
--
ALTER TABLE `suscripcion_usuario`
  ADD PRIMARY KEY (`id_suscripcion`),
  ADD KEY `id_plan` (`id_plan`),
  ADD KEY `idx_suscripcion_usuario` (`id_usuario`);

--
-- Indexes for table `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `idx_usuario_correo` (`correo`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categoria_producto`
--
ALTER TABLE `categoria_producto`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `imagen_servicio`
--
ALTER TABLE `imagen_servicio`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `item_pedido`
--
ALTER TABLE `item_pedido`
  MODIFY `id_item_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `metodo_pago_usuario`
--
ALTER TABLE `metodo_pago_usuario`
  MODIFY `id_metodo_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `plan_suscripcion`
--
ALTER TABLE `plan_suscripcion`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reseña_producto`
--
ALTER TABLE `reseña_producto`
  MODIFY `id_reseña` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `servicio`
--
ALTER TABLE `servicio`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suscripcion_usuario`
--
ALTER TABLE `suscripcion_usuario`
  MODIFY `id_suscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `imagen_servicio`
--
ALTER TABLE `imagen_servicio`
  ADD CONSTRAINT `imagen_servicio_ibfk_1` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE;

--
-- Constraints for table `item_pedido`
--
ALTER TABLE `item_pedido`
  ADD CONSTRAINT `item_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`),
  ADD CONSTRAINT `item_pedido_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  ADD CONSTRAINT `item_pedido_ibfk_3` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);

--
-- Constraints for table `metodo_pago_usuario`
--
ALTER TABLE `metodo_pago_usuario`
  ADD CONSTRAINT `metodo_pago_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Constraints for table `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`),
  ADD CONSTRAINT `pago_ibfk_2` FOREIGN KEY (`id_suscripcion`) REFERENCES `suscripcion_usuario` (`id_suscripcion`);

--
-- Constraints for table `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Constraints for table `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_producto` (`id_categoria`);

--
-- Constraints for table `reseña_producto`
--
ALTER TABLE `reseña_producto`
  ADD CONSTRAINT `reseña_producto_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  ADD CONSTRAINT `reseña_producto_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Constraints for table `suscripcion_usuario`
--
ALTER TABLE `suscripcion_usuario`
  ADD CONSTRAINT `suscripcion_usuario_ibfk_1` FOREIGN KEY (`id_plan`) REFERENCES `plan_suscripcion` (`id_plan`),
  ADD CONSTRAINT `suscripcion_usuario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
