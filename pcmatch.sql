-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-03-2026 a las 16:15:15
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pcmatch`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bodegas`
--

CREATE TABLE `bodegas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bodegas`
--

INSERT INTO `bodegas` (`id`, `nombre`, `telefono`, `correo`, `password`, `activa`, `created_at`) VALUES
(1, 'TecnoStore', '+57 123 456 7891', 'tecnostore@gmail.com', '$2y$10$T.zIISmAidQnT3y0om5/LOg.pLsufltFeb6MNgLQQfSrbvH3Z785K', 1, '2026-03-02 04:10:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `componentes`
--

CREATE TABLE `componentes` (
  `id` int(11) NOT NULL,
  `bodega_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `especificacion` text DEFAULT NULL,
  `gama` enum('alta','media','baja') NOT NULL,
  `precio` decimal(10,0) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `componentes`
--

INSERT INTO `componentes` (`id`, `bodega_id`, `producto_id`, `especificacion`, `gama`, `precio`, `stock`, `created_at`) VALUES
(1, 1, 2, '6 núcleos', 'alta', 100000, 3, '2026-03-02 04:11:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `perfil` enum('gaming','oficina','diseño','estudio') NOT NULL,
  `total` decimal(10,0) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion_items`
--

CREATE TABLE `cotizacion_items` (
  `id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `componente_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 1,
  `precio_unitario` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_catalogo`
--

CREATE TABLE `productos_catalogo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` enum('CPU','GPU','RAM','Storage','PSU','Motherboard','Cooler','Case') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos_catalogo`
--

INSERT INTO `productos_catalogo` (`id`, `nombre`, `categoria`, `created_at`) VALUES
(1, 'AMD Ryzen 3 4100', 'CPU', '2026-02-28 19:07:10'),
(2, 'AMD Ryzen 5 5600', 'CPU', '2026-02-28 19:07:10'),
(3, 'AMD Ryzen 5 5600X', 'CPU', '2026-02-28 19:07:10'),
(4, 'AMD Ryzen 7 5700X', 'CPU', '2026-02-28 19:07:10'),
(5, 'AMD Ryzen 7 7700X', 'CPU', '2026-02-28 19:07:10'),
(6, 'AMD Ryzen 9 7900X', 'CPU', '2026-02-28 19:07:10'),
(7, 'Intel Core i3-12100F', 'CPU', '2026-02-28 19:07:10'),
(8, 'Intel Core i5-12400F', 'CPU', '2026-02-28 19:07:10'),
(9, 'Intel Core i5-13600K', 'CPU', '2026-02-28 19:07:10'),
(10, 'Intel Core i7-13700K', 'CPU', '2026-02-28 19:07:10'),
(11, 'Intel Core i9-13900K', 'CPU', '2026-02-28 19:07:10'),
(12, 'NVIDIA GTX 1650', 'GPU', '2026-02-28 19:07:10'),
(13, 'NVIDIA RTX 3050', 'GPU', '2026-02-28 19:07:10'),
(14, 'NVIDIA RTX 3060', 'GPU', '2026-02-28 19:07:10'),
(15, 'NVIDIA RTX 3070', 'GPU', '2026-02-28 19:07:10'),
(16, 'NVIDIA RTX 4070', 'GPU', '2026-02-28 19:07:10'),
(17, 'NVIDIA RTX 4080', 'GPU', '2026-02-28 19:07:10'),
(18, 'AMD RX 6500 XT', 'GPU', '2026-02-28 19:07:10'),
(19, 'AMD RX 6600', 'GPU', '2026-02-28 19:07:10'),
(20, 'AMD RX 6700 XT', 'GPU', '2026-02-28 19:07:10'),
(21, 'AMD RX 7600', 'GPU', '2026-02-28 19:07:10'),
(22, 'AMD RX 7800 XT', 'GPU', '2026-02-28 19:07:10'),
(23, 'Kingston Fury Beast 8GB DDR4', 'RAM', '2026-02-28 19:07:10'),
(24, 'Kingston Fury Beast 16GB DDR4', 'RAM', '2026-02-28 19:07:10'),
(25, 'Corsair Vengeance LPX 16GB DDR4', 'RAM', '2026-02-28 19:07:10'),
(26, 'Corsair Vengeance 32GB DDR4', 'RAM', '2026-02-28 19:07:10'),
(27, 'G.Skill Ripjaws V 16GB DDR4', 'RAM', '2026-02-28 19:07:10'),
(28, 'G.Skill Trident Z 32GB DDR5', 'RAM', '2026-02-28 19:07:10'),
(29, 'Kingston A400 480GB SSD', 'Storage', '2026-02-28 19:07:10'),
(30, 'Crucial BX500 1TB SSD', 'Storage', '2026-02-28 19:07:10'),
(31, 'Samsung 970 EVO Plus 1TB', 'Storage', '2026-02-28 19:07:10'),
(32, 'WD Black SN850X 1TB', 'Storage', '2026-02-28 19:07:10'),
(33, 'Kingston NV2 2TB NVMe', 'Storage', '2026-02-28 19:07:10'),
(34, 'Seagate Barracuda 2TB HDD', 'Storage', '2026-02-28 19:07:10'),
(35, 'ASUS PRIME B450M-A', 'Motherboard', '2026-02-28 19:07:10'),
(36, 'MSI B450 TOMAHAWK MAX', 'Motherboard', '2026-02-28 19:07:10'),
(37, 'ASUS TUF B550-PLUS', 'Motherboard', '2026-02-28 19:07:10'),
(38, 'Gigabyte B660M DS3H', 'Motherboard', '2026-02-28 19:07:10'),
(39, 'MSI MAG B660 TOMAHAWK', 'Motherboard', '2026-02-28 19:07:10'),
(40, 'ASUS PRIME Z790-P', 'Motherboard', '2026-02-28 19:07:10'),
(41, 'EVGA 500W 80+ Bronze', 'PSU', '2026-02-28 19:07:10'),
(42, 'Corsair CX650M', 'PSU', '2026-02-28 19:07:10'),
(43, 'Corsair RM750x', 'PSU', '2026-02-28 19:07:10'),
(44, 'Seasonic Focus GX-750', 'PSU', '2026-02-28 19:07:10'),
(45, 'Thermaltake Toughpower 850W', 'PSU', '2026-02-28 19:07:10'),
(46, 'NZXT C750', 'PSU', '2026-02-28 19:07:10'),
(47, 'Cooler Master Hyper 212', 'Cooler', '2026-02-28 19:07:10'),
(48, 'DeepCool AK400', 'Cooler', '2026-02-28 19:07:10'),
(49, 'DeepCool AK620', 'Cooler', '2026-02-28 19:07:10'),
(50, 'Noctua NH-D15', 'Cooler', '2026-02-28 19:07:10'),
(51, 'Corsair H100i Elite', 'Cooler', '2026-02-28 19:07:10'),
(52, 'NZXT Kraken X63', 'Cooler', '2026-02-28 19:07:10'),
(53, 'NZXT H510', 'Case', '2026-02-28 19:07:10'),
(54, 'Corsair 4000D Airflow', 'Case', '2026-02-28 19:07:10'),
(55, 'Cooler Master TD500 Mesh', 'Case', '2026-02-28 19:07:10'),
(56, 'Fractal Design Meshify C', 'Case', '2026-02-28 19:07:10'),
(57, 'Lian Li PC-O11 Dynamic', 'Case', '2026-02-28 19:07:10'),
(58, 'Phanteks Eclipse P400A', 'Case', '2026-02-28 19:07:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','cliente','bodega') NOT NULL DEFAULT 'cliente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `correo`, `telefono`, `password`, `rol`, `created_at`) VALUES
(1, 'Juan', 'Pérez', 'Juanperezoso@gmail.com', '+57 123 456 7891', '$2y$10$x/cuphUo3SFJF7fpUDGUq.yCcsjw/s.wWm/8CdrhsxvdnLUd3lWcK', 'cliente', '2026-02-28 19:25:52'),
(2, 'Admin', 'PCMATCH', 'admin@pcmatch.com', NULL, '$2y$10$Q9gxEykl6iq4tCysFfgeZ.7cWMFhmt9CMP.Xt.jCw5K3di3S/HIuG', 'admin', '2026-02-28 19:47:57');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `componentes`
--
ALTER TABLE `componentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bodega_id` (`bodega_id`),
  ADD KEY `fk_componente_producto` (`producto_id`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `cotizacion_items`
--
ALTER TABLE `cotizacion_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `componente_id` (`componente_id`);

--
-- Indices de la tabla `productos_catalogo`
--
ALTER TABLE `productos_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `componentes`
--
ALTER TABLE `componentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizacion_items`
--
ALTER TABLE `cotizacion_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos_catalogo`
--
ALTER TABLE `productos_catalogo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `componentes`
--
ALTER TABLE `componentes`
  ADD CONSTRAINT `componentes_ibfk_1` FOREIGN KEY (`bodega_id`) REFERENCES `bodegas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_componente_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos_catalogo` (`id`);

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cotizacion_items`
--
ALTER TABLE `cotizacion_items`
  ADD CONSTRAINT `cotizacion_items_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizacion_items_ibfk_2` FOREIGN KEY (`componente_id`) REFERENCES `componentes` (`id`);

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
