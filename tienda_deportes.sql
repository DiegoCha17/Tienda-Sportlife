-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-08-2025 a las 07:39:56
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tienda_deportes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carritos`
--

CREATE TABLE `carritos` (
  `id` int(20) UNSIGNED NOT NULL,
  `id_usuario` int(20) UNSIGNED NOT NULL,
  `id_producto` int(10) UNSIGNED NOT NULL,
  `estado` enum('activo','pendiente','cancelado') NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `cantidad` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carritos`
--

INSERT INTO `carritos` (`id`, `id_usuario`, `id_producto`, `estado`, `fecha_creacion`, `cantidad`) VALUES
(23, 10, 87, 'activo', '2025-08-28 05:54:42', 1),
(24, 4, 88, 'activo', '2025-08-28 06:07:26', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Ropa'),
(2, 'Tenis'),
(3, 'Gym'),
(4, 'Suplementos'),
(5, 'Accesorios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id` int(20) UNSIGNED NOT NULL,
  `id_usuario` int(20) UNSIGNED NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `pais` varchar(50) NOT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`id`, `id_usuario`, `ciudad`, `provincia`, `pais`, `codigo_postal`) VALUES
(1, 9, 'Ciudad Quesada', 'Alajuela', 'Costa Rica', '10102'),
(3, 10, 'La Uruca', 'San José', 'Costa Rica', '10104'),
(4, 11, 'Ciudad Quesada', 'Alajuela', 'Costa Rica', '21001'),
(5, 12, 'Ciudad Quesada', 'Alajuela', 'Costa Rica', '21001'),
(6, 12, 'San martin', 'Alajuela', 'Costa Rica', '21001'),
(7, 11, 'San martin', 'Alajuela', 'Costa Rica', '21001'),
(8, 13, 'Monterrey', 'Alajuela', 'Costa Rica', '10105'),
(9, 13, 'Fortuna', 'Alajuela', 'Costa Rica', '10106');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id` int(11) NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio`) VALUES
(3, 34, 103, 1, 19900.00),
(4, 35, 98, 1, 9700.00),
(5, 35, 100, 1, 36900.00),
(6, 36, 104, 1, 13100.00),
(7, 37, 104, 1, 13100.00),
(8, 38, 104, 1, 13100.00),
(9, 39, 104, 1, 13100.00),
(10, 40, 103, 1, 19900.00),
(11, 41, 103, 1, 19900.00),
(12, 42, 100, 1, 36900.00),
(13, 43, 84, 1, 78900.00),
(14, 44, 81, 1, 41900.00),
(15, 45, 91, 1, 24900.00),
(16, 46, 99, 1, 18700.00),
(17, 47, 84, 1, 78900.00),
(18, 48, 84, 1, 78900.00),
(19, 49, 102, 1, 43500.00),
(20, 49, 95, 1, 18500.00),
(21, 50, 82, 1, 62700.00),
(23, 52, 104, 1, 13100.00),
(25, 54, 87, 1, 26200.00),
(26, 54, 104, 1, 13100.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id`, `nombre`) VALUES
(2, 'Adidas'),
(6, 'Everlast'),
(7, 'New balance'),
(1, 'Nike'),
(3, 'Puma'),
(5, 'Reebok'),
(4, 'Under Armour');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(20) NOT NULL,
  `id_pedido` int(20) UNSIGNED NOT NULL,
  `metodo` enum('credito','debito','paypal') NOT NULL,
  `numero_transaccion` varchar(32) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `estado` enum('pendiente','aprobado','fallido') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `id_pedido`, `metodo`, `numero_transaccion`, `monto`, `estado`, `fecha_creacion`) VALUES
(1, 1, '', NULL, 15053.78, '', '2025-08-28 01:07:31'),
(7, 9, 'credito', 'TXN20250828064733540', 14957.73, 'aprobado', '2025-08-28 04:47:33'),
(8, 10, 'paypal', 'TXN20250828074658848', 36.64, 'aprobado', '2025-08-28 05:46:58'),
(9, 11, 'debito', 'TXN20250828075014909', 101.62, 'aprobado', '2025-08-28 05:50:14'),
(10, 13, 'paypal', 'TXN20250829035147413', 59217.00, 'aprobado', '2025-08-29 01:51:47'),
(11, 20, 'paypal', 'TXN20250829085018188', 29611.00, 'aprobado', '2025-08-29 06:50:18'),
(12, 21, 'debito', 'TXN20250829085553845', 22492.00, 'aprobado', '2025-08-29 06:55:53'),
(13, 28, 'paypal', 'TXN20250829092146882', 73342.00, 'aprobado', '2025-08-29 07:21:46'),
(14, 29, 'paypal', 'TXN20250829092403407', 10966.00, 'aprobado', '2025-08-29 07:24:03'),
(15, 30, 'paypal', 'TXN20250829092457171', 41702.00, 'aprobado', '2025-08-29 07:24:57'),
(16, 31, 'paypal', 'TXN20250829092622552', 49160.00, 'aprobado', '2025-08-29 07:26:22'),
(17, 32, 'paypal', 'TXN20250829092655905', 29611.00, 'aprobado', '2025-08-29 07:26:55'),
(18, 33, 'paypal', 'TXN20250829093002697', 14808.00, 'aprobado', '2025-08-29 07:30:02'),
(19, 34, 'paypal', 'TXN20250829095225982', 22492.00, 'aprobado', '2025-08-29 07:52:25'),
(20, 35, 'paypal', 'TXN20250829095346479', 52663.00, 'aprobado', '2025-08-29 07:53:46'),
(21, 36, 'paypal', 'TXN20250829095559469', 14808.00, 'aprobado', '2025-08-29 07:55:59'),
(22, 39, 'paypal', 'TXN20250829103115601', 14808.00, 'aprobado', '2025-08-29 08:31:15'),
(23, 38, 'paypal', 'TXN20250829103145697', 14808.00, 'aprobado', '2025-08-29 08:31:45'),
(24, 40, 'paypal', 'TXN20250829104131330', 22492.00, 'aprobado', '2025-08-29 08:41:31'),
(25, 42, 'credito', 'TXN20250830032431200', 41702.00, 'aprobado', '2025-08-30 01:24:31'),
(26, 43, 'credito', 'TXN20250830032828214', 89162.00, 'aprobado', '2025-08-30 01:28:28'),
(27, 44, 'paypal', 'TXN20250830043245460', 47352.00, 'aprobado', '2025-08-30 02:32:45'),
(28, 45, 'paypal', 'TXN20250830050419512', 28142.00, 'aprobado', '2025-08-30 03:04:19'),
(29, 46, 'paypal', 'TXN20250830052104750', 21136.00, 'aprobado', '2025-08-30 03:21:04'),
(30, 48, 'paypal', 'TXN20250830052321731', 89162.00, 'aprobado', '2025-08-30 03:23:21'),
(31, 49, 'paypal', 'TXN20250830052800197', 70065.00, 'aprobado', '2025-08-30 03:28:00'),
(32, 50, 'paypal', 'TXN20250830053710987', 70856.00, 'aprobado', '2025-08-30 03:37:10'),
(33, 51, 'debito', 'TXN20250830071137411', 16955.00, 'aprobado', '2025-08-30 05:11:37'),
(34, 54, 'paypal', 'TXN20250830072004761', 44414.00, 'aprobado', '2025-08-30 05:20:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(20) UNSIGNED NOT NULL,
  `id_direccion` int(20) UNSIGNED DEFAULT NULL,
  `estado` enum('pendiente','pagado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `subtotal` decimal(12,2) NOT NULL,
  `total_impuesto` decimal(12,2) NOT NULL,
  `total_envio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_general` decimal(12,2) NOT NULL,
  `numero_seguimiento` varchar(80) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `id_usuario`, `id_direccion`, `estado`, `subtotal`, `total_impuesto`, `total_envio`, `total_general`, `numero_seguimiento`, `fecha_creacion`) VALUES
(1, 9, 1, 'pagado', 13317.50, 1731.28, 5.00, 15053.78, 'TD202508282630', '2025-08-28 00:33:02'),
(9, 10, 3, 'pagado', 13232.50, 1720.23, 5.00, 14957.73, 'TD202508281863', '2025-08-28 04:47:02'),
(10, 10, 3, 'pagado', 28.00, 3.64, 5.00, 36.64, 'TD202508282050', '2025-08-28 05:42:39'),
(11, 10, 3, 'pagado', 85.50, 11.12, 5.00, 101.62, 'TD202508283796', '2025-08-28 05:49:55'),
(12, 9, 1, 'pendiente', 13242.50, 1721.53, 5.00, 14969.03, 'TD202508283398', '2025-08-28 06:11:56'),
(13, 11, 4, 'pagado', 52400.00, 6812.00, 5.00, 59217.00, 'TD202508297773', '2025-08-29 01:51:38'),
(15, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508299481', '2025-08-29 06:26:25'),
(16, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508292937', '2025-08-29 06:27:07'),
(17, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508291239', '2025-08-29 06:30:25'),
(18, 12, 5, 'pendiente', 26200.00, 3406.00, 5.00, 29611.00, 'TD202508293330', '2025-08-29 06:43:55'),
(20, 12, 6, 'pagado', 26200.00, 3406.00, 5.00, 29611.00, 'TD202508296469', '2025-08-29 06:50:15'),
(21, 12, 6, 'pagado', 19900.00, 2587.00, 5.00, 22492.00, 'TD202508294952', '2025-08-29 06:55:25'),
(22, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508295932', '2025-08-29 07:05:53'),
(23, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508299288', '2025-08-29 07:06:03'),
(24, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508292419', '2025-08-29 07:07:36'),
(25, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508295943', '2025-08-29 07:08:37'),
(26, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508292915', '2025-08-29 07:10:41'),
(27, 11, 4, 'pendiente', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508292752', '2025-08-29 07:12:35'),
(28, 11, 4, 'pagado', 64900.00, 8437.00, 5.00, 73342.00, 'TD202508299347', '2025-08-29 07:21:42'),
(29, 11, 4, 'pagado', 9700.00, 1261.00, 5.00, 10966.00, 'TD202508291394', '2025-08-29 07:24:01'),
(30, 11, 4, 'pagado', 36900.00, 4797.00, 5.00, 41702.00, 'TD202508296769', '2025-08-29 07:24:55'),
(31, 11, 4, 'pagado', 43500.00, 5655.00, 5.00, 49160.00, 'TD202508296400', '2025-08-29 07:26:19'),
(32, 11, 4, 'pagado', 26200.00, 3406.00, 5.00, 29611.00, 'TD202508291543', '2025-08-29 07:26:52'),
(33, 11, 4, 'pagado', 13100.00, 1703.00, 5.00, 14808.00, 'TD202508296332', '2025-08-29 07:29:58'),
(34, 12, 5, 'pagado', 19900.00, 2587.00, 5.00, 22492.00, 'TD202508295191', '2025-08-29 07:52:19'),
(35, 12, 5, 'pagado', 46600.00, 6058.00, 5.00, 52663.00, 'TD202508299597', '2025-08-29 07:53:44'),
(36, 12, 6, 'pagado', 13100.00, 1703.00, 5.00, 14808.00, 'TD202508296939', '2025-08-29 07:55:46'),
(37, 11, 4, 'pendiente', 13100.00, 1703.00, 5.00, 14808.00, 'TD202508292543', '2025-08-29 08:24:42'),
(38, 11, 4, 'pagado', 13100.00, 1703.00, 5.00, 14808.00, 'TD202508296754', '2025-08-29 08:25:07'),
(39, 11, 4, 'pagado', 13100.00, 1703.00, 5.00, 14808.00, 'TD202508297077', '2025-08-29 08:31:11'),
(40, 11, 4, 'pagado', 19900.00, 2587.00, 5.00, 22492.00, 'TD202508292441', '2025-08-29 08:41:29'),
(41, 11, 7, 'pendiente', 19900.00, 2587.00, 5.00, 22492.00, 'TD202508298492', '2025-08-29 08:44:55'),
(42, 9, 1, 'pagado', 36900.00, 4797.00, 5.00, 41702.00, 'TD202508302208', '2025-08-30 01:22:19'),
(43, 9, 1, 'pagado', 78900.00, 10257.00, 5.00, 89162.00, 'TD202508303423', '2025-08-30 01:28:04'),
(44, 9, 1, 'pagado', 41900.00, 5447.00, 5.00, 47352.00, 'TD202508308032', '2025-08-30 02:32:39'),
(45, 9, 1, 'pagado', 24900.00, 3237.00, 5.00, 28142.00, 'TD202508301036', '2025-08-30 03:04:13'),
(46, 9, 1, 'pagado', 18700.00, 2431.00, 5.00, 21136.00, 'TD202508303933', '2025-08-30 03:20:58'),
(47, 9, 1, 'pendiente', 78900.00, 10257.00, 5.00, 89162.00, 'TD202508305240', '2025-08-30 03:21:34'),
(48, 9, 1, 'pagado', 78900.00, 10257.00, 5.00, 89162.00, 'TD202508307683', '2025-08-30 03:23:14'),
(49, 9, 1, 'pagado', 62000.00, 8060.00, 5.00, 70065.00, 'TD202508303424', '2025-08-30 03:27:45'),
(50, 11, 7, 'pagado', 62700.00, 8151.00, 5.00, 70856.00, 'TD202508309354', '2025-08-30 03:37:05'),
(51, 13, 8, 'pagado', 15000.00, 1950.00, 5.00, 16955.00, 'TD202508309375', '2025-08-30 05:09:53'),
(52, 13, 8, 'pendiente', 13100.00, 1703.00, 5.00, 14808.00, 'TD202508305992', '2025-08-30 05:16:28'),
(54, 13, 9, 'pagado', 39300.00, 5109.00, 5.00, 44414.00, 'TD202508305690', '2025-08-30 05:19:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(20) UNSIGNED NOT NULL,
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `id_marca` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(180) NOT NULL,
  `cantidad` int(20) DEFAULT NULL,
  `imagen` varchar(1000) NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `id_categoria`, `id_marca`, `nombre`, `cantidad`, `imagen`, `precio`, `activo`) VALUES
(81, 2, 7, 'Tenis new balance 460 v4', 10, '\\Calzado deportivo\\tenis new balance 460 v4.jpg', 41900.00, 1),
(82, 2, 1, 'Tenis nike sabrina', 10, '\\Calzado deportivo\\tenis nike sabrina.jpg', 62700.00, 1),
(83, 2, 3, 'Tenis puma club II era', 10, '\\Calzado deportivo\\tenis puma club II era.jpg', 37200.00, 1),
(84, 2, 5, 'Tenis reebok nanoflex adventure tr 2', 10, '\\Calzado deportivo\\tenis reebok nanoflex adventure tr 2.jpg', 78900.00, 1),
(85, 2, 4, 'Tenis under armour w charged rogue-4', 10, '\\Calzado deportivo\\tenis under armour w charged rogue-4.jpg', 49900.00, 1),
(87, 3, 6, 'Bola everlast heavy duty gym ball', 20, '\\Equipos y máquinas\\bola everlast heavy duty gym ball.jpg', 26200.00, 1),
(88, 3, 1, 'Hand grip everlast ajustable', 30, '\\Equipos y máquinas\\hand grip everlast ajustable.jpg', 15600.00, 1),
(89, 3, 1, 'Mancuernas everlast vinyl coated dumbbel', 15, '\\Equipos y máquinas\\mancuernas everlast vinyl coated dumbbel.jpg', 19400.00, 1),
(90, 3, 1, 'Suiza everlast delux speed rope', 25, '\\Equipos y máquinas\\suiza everlast delux speed rope.jpg', 8400.00, 1),
(91, 1, 3, 'Abrigo puma', 8, '\\Ropa deportiva\\abrigo puma.jpg', 24900.00, 1),
(92, 1, 1, 'Camiseta nike camo', 15, '\\Ropa deportiva\\camiseta nike camo.jpg', 31100.00, 1),
(93, 1, 6, 'Lycra everlast ruffle', 12, '\\Ropa deportiva\\lycra everlast ruffle.jpg', 35100.00, 1),
(94, 1, 6, 'Pantalon everlast warp 3pocket', 10, '\\Ropa deportiva\\pantalon everlast warp 3pocket.jpg', 29200.00, 1),
(95, 1, 7, 'Short new balance accel 5in', 14, '\\Ropa deportiva\\short new balance accel 5in.jpg', 18500.00, 1),
(96, 3, 6, 'Bandas everlast power band', 22, '\\Accesorios\\bandas everlast power band.jpg', 38700.00, 1),
(98, 5, 1, 'Espinilleras nike jguard', 15, '\\Accesorios\\espinilleras nike jguard.jpg', 9700.00, 1),
(99, 5, 7, 'Gorra new balance curved brim hat', 20, '\\Accesorios\\gorra new balance curved brim hat.jpg', 18700.00, 1),
(100, 5, 4, 'Maletin under armour undeniable 50', 7, '\\Accesorios\\maletin under armour undeniable 50.jpg', 36900.00, 1),
(102, 4, 5, 'Gold Standard', 10, '68a3b7ee4bf06_1755559918.jpg', 43500.00, 1),
(103, 4, 5, 'Superior 14 Creatina', 1, '68a3bbe2bec6c_1755560930.jpg', 19900.00, 1),
(104, 5, 3, 'Botella puma tr fruit infuser bottle', 12, '68a6ee02bd53b_1755770370.jpg', 13100.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'admin'),
(2, 'cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(20) UNSIGNED NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `correo` varchar(190) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `cedula` int(20) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `id_rol`, `correo`, `clave`, `cedula`, `nombre`, `Apellido`, `telefono`) VALUES
(4, 1, 'admin@gmail.com', '$2y$10$H1GKbOU5zdCuuiLYwzm5ku9lJNGQlEzIid11rJ8xuTwhGtqAHuY4S', 0, 'Admin', 'Ramírez', '88889999'),
(9, 2, 'eithel.herrera03@gmail.com', '$2y$10$Ys3BHeTc/0ktgUxaV20nO.7/WEAADKEDKOpZvjsv/JKzhxcuY9saC', 0, 'Eithel', 'Herrera', '84599184'),
(10, 2, 'harold.saborio05@gmail.com', '$2y$10$n6BG4lkuqwSxda0.EkFvL.Nacn49IzQ7vdW3/HMMRouL537kTuhLy', 0, 'Harold', 'Saborío', '56639874'),
(11, 2, 'chavalaluis30@gmail.com', '$2y$10$5DzbJHf05EA/HPiMHgWgie1Qf7n3SVgzXF6coqYPwyDdZSTy9nHMS', 208550619, 'Luis Diego', 'Chavala', '70939586'),
(12, 2, 'chava@gmail.com', '$2y$10$JLDVmL/vxjk9CGqHa//QA.3qFbL/AdQDvYJ2TuSDqEgXsFwiE0NAi', 208550649, 'chaka', 'gonzalez', '78965412'),
(13, 2, 'johana.solano06@gmail.com', '$2y$10$bUJ9F6xr0TOxKxsne82CC.34h4MTkmI44ymshof1ggDOH1mUNe8xa', 148596589, 'Johana', 'Solano', '45698752');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_carrito_usuario_estado_producto` (`id_usuario`,`estado`,`id_producto`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dir_usuario` (`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_detalles_ibfk_1` (`pedido_id`),
  ADD KEY `pedido_detalles_ibfk_2` (`producto_id`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pagos_pedido_estado` (`id_pedido`,`estado`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedidos_direccion` (`id_direccion`),
  ADD KEY `idx_pedidos_usuario_fecha` (`id_usuario`,`fecha_creacion`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_productos_marca` (`id_marca`),
  ADD KEY `idx_prod_nombre` (`nombre`),
  ADD KEY `idx_prod_categoria` (`id_categoria`,`activo`),
  ADD KEY `idx_prod_marca` (`id_marca`,`activo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuarios_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carritos`
--
ALTER TABLE `carritos`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD CONSTRAINT `fk_carritos_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_carritos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `fk_productos_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `factura_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pedidos` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_ip_producto` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direcciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categorias` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_productos_marca` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
