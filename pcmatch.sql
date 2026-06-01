-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-05-2026 a las 20:04:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proveedor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bodegas`
--

INSERT INTO `bodegas` (`id`, `nombre`, `telefono`, `correo`, `password`, `activa`, `created_at`, `proveedor_id`) VALUES
(1, 'TecnoStores', '+57 123 456 9871', 'tecnostore@gmail.com', '$2y$10$T.zIISmAidQnT3y0om5/LOg.pLsufltFeb6MNgLQQfSrbvH3Z785K', 1, '2026-03-02 04:10:33', 2),
(3, '.', '12345678902345', 'hmaya@gmail.com', '$2y$10$JjDqWye24Sj4U8oVhnEgLuXDXANv0QGmSJ8xuIcoTrMcKQwZkmFI2', 1, '2026-03-10 21:47:14', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 1, 2, '7 núcleos', 'alta', 100000, 6, '2026-03-02 04:11:29'),
(3, 1, 17, 'NVIDIA RTX 4080 Founders Edition 16GB GDDR6X', 'alta', 1200, 2, '2026-04-13 09:51:19'),
(4, 1, 31, 'Samsung 970 EVO Plus 1TB PCIe NVMe M.2', 'alta', 90, 5, '2026-04-13 09:51:23');

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

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id`, `usuario_id`, `perfil`, `total`, `created_at`) VALUES
(1, 1, '', 100000, '2026-03-02 17:32:53'),
(2, 1, '', 100000, '2026-03-10 21:54:30'),
(3, 1, 'gaming', 1380, '2026-04-13 09:54:37'),
(4, 10, 'gaming', 101290, '2026-04-29 02:10:31');

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

--
-- Volcado de datos para la tabla `cotizacion_items`
--

INSERT INTO `cotizacion_items` (`id`, `cotizacion_id`, `componente_id`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 1, 100000),
(2, 2, 1, 1, 100000),
(3, 3, 1, 1, 1200),
(5, 4, 1, 1, 100000),
(6, 4, 3, 1, 1200),
(7, 4, 4, 1, 90);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_acciones`
--

CREATE TABLE `historial_acciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_nombre` varchar(255) DEFAULT NULL,
  `rol_usuario` varchar(255) DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `modulo` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_acciones`
--

INSERT INTO `historial_acciones` (`id`, `usuario_id`, `usuario_nombre`, `rol_usuario`, `accion`, `modulo`, `created_at`, `updated_at`) VALUES
(1, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-28 23:43:52', '2026-04-28 23:43:52'),
(2, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-28 23:44:08', '2026-04-28 23:44:08'),
(3, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-28 23:44:16', '2026-04-28 23:44:16'),
(4, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-28 23:44:19', '2026-04-28 23:44:19'),
(5, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-28 23:44:20', '2026-04-28 23:44:20'),
(6, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-28 23:44:22', '2026-04-28 23:44:22'),
(7, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-28 23:44:23', '2026-04-28 23:44:23'),
(8, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-28 23:44:25', '2026-04-28 23:44:25'),
(9, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-28 23:44:27', '2026-04-28 23:44:27'),
(10, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-28 23:44:44', '2026-04-28 23:44:44'),
(11, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-28 23:44:45', '2026-04-28 23:44:45'),
(12, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-28 23:44:46', '2026-04-28 23:44:46'),
(13, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: estado_aprobacion: \'aprobado\' -> \'rechazado\'', 'Proveedores', '2026-04-28 23:44:56', '2026-04-28 23:44:56'),
(14, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: estado_aprobacion: \'rechazado\' -> \'aprobado\'', 'Proveedores', '2026-04-28 23:44:58', '2026-04-28 23:44:58'),
(15, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: Intel S.A.. Cambios: estado_aprobacion: \'rechazado\' -> \'aprobado\'', 'Proveedores', '2026-04-28 23:44:59', '2026-04-28 23:44:59'),
(16, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: Intel S.A.. Cambios: estado_aprobacion: \'aprobado\' -> \'rechazado\'', 'Proveedores', '2026-04-28 23:45:00', '2026-04-28 23:45:00'),
(17, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: Intel S.A.. Cambios: estado_aprobacion: \'rechazado\' -> \'aprobado\'', 'Proveedores', '2026-04-28 23:45:05', '2026-04-28 23:45:05'),
(18, 2, 'Admin PCMATCH', 'admin', 'Modificó la bodega: .. Cambios: activa: \'1\' -> \'0\'', 'Bodegas', '2026-04-28 23:47:33', '2026-04-28 23:47:33'),
(19, 2, 'Admin PCMATCH', 'admin', 'Modificó la bodega: .. Cambios: activa: \'0\' -> \'1\'', 'Bodegas', '2026-04-28 23:47:35', '2026-04-28 23:47:35'),
(20, 2, 'Admin PCMATCH', 'admin', 'Eliminó el usuario con correo: gabriel.nuevo@pcmatch.test', 'Usuarios', '2026-04-28 23:47:54', '2026-04-28 23:47:54'),
(21, 2, 'Admin PCMATCH', 'admin', 'Creó el usuario con correo: sdjasjdajsdasd@gmail.com', 'Usuarios', '2026-04-29 01:05:02', '2026-04-29 01:05:02'),
(22, 6, 'Super Admin', 'superadmin', 'Modificó el usuario con correo: sdjasjdajsdasd@gmail.com. Cambios: apellido: \'Catqaño\' -> \'\', telefono: \'300 1231929\' -> \'\', activo: \'1\' -> \'0\'', 'Usuarios', '2026-04-29 01:05:19', '2026-04-29 01:05:19'),
(23, 6, 'Super Admin', 'superadmin', 'Modificó el usuario con correo: sdjasjdajsdasd@gmail.com. Cambios: activo: \'0\' -> \'1\'', 'Usuarios', '2026-04-29 01:05:22', '2026-04-29 01:05:22'),
(24, 2, 'Admin PCMATCH', 'admin', 'Modificó la bodega: TecnoStore. Cambios: activa: \'0\' -> \'1\'', 'Bodegas', '2026-04-29 01:06:43', '2026-04-29 01:06:43'),
(25, 2, 'Admin PCMATCH', 'admin', 'Modificó el usuario con correo: sdjasjdajsdasd@gmail.com. Cambios: apellido: \'\' -> \'\', telefono: \'\' -> \'\', activo: \'1\' -> \'0\'', 'Usuarios', '2026-04-29 01:24:32', '2026-04-29 01:24:32'),
(26, 2, 'Admin PCMATCH', 'admin', 'Modificó el usuario con correo: sdjasjdajsdasd@gmail.com. Cambios: activo: \'0\' -> \'1\'', 'Usuarios', '2026-04-29 01:24:37', '2026-04-29 01:24:37'),
(27, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: razon_social: \'Example\' -> \'Examples\'', 'Proveedores', '2026-04-29 01:26:16', '2026-04-29 01:26:16'),
(28, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: nombre: \'TecnoStore\' -> \'TecnoStores\'', 'Bodegas', '2026-04-29 01:26:39', '2026-04-29 01:26:39'),
(29, 6, 'Super Admin', 'superadmin', 'Creó la bodega: play store', 'Bodegas', '2026-04-29 01:27:37', '2026-04-29 01:27:37'),
(30, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: play store. Cambios: activa: \'1\' -> \'0\'', 'Bodegas', '2026-04-29 01:27:41', '2026-04-29 01:27:41'),
(31, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: play store. Cambios: activa: \'0\' -> \'1\'', 'Bodegas', '2026-04-29 01:27:46', '2026-04-29 01:27:46'),
(32, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-29 01:29:28', '2026-04-29 01:29:28'),
(33, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitoperez. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-29 01:29:30', '2026-04-29 01:29:30'),
(34, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: .. Cambios: activa: \'1\' -> \'0\'', 'Bodegas', '2026-04-29 01:29:33', '2026-04-29 01:29:33'),
(35, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: .. Cambios: activa: \'0\' -> \'1\'', 'Bodegas', '2026-04-29 01:29:35', '2026-04-29 01:29:35'),
(36, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'\' -> \'2\'', 'Bodegas', '2026-04-29 01:30:55', '2026-04-29 01:30:55'),
(37, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: Sin cambios aparentes', 'Bodegas', '2026-04-29 01:31:08', '2026-04-29 01:31:08'),
(38, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'2\' -> \'1\'', 'Bodegas', '2026-04-29 01:32:49', '2026-04-29 01:32:49'),
(39, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: play store. Cambios: proveedor_id: \'\' -> \'2\'', 'Bodegas', '2026-04-29 01:33:54', '2026-04-29 01:33:54'),
(40, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: play store. Cambios: activa: \'1\' -> \'0\'', 'Bodegas', '2026-04-29 01:36:26', '2026-04-29 01:36:26'),
(41, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: play store. Cambios: activa: \'0\' -> \'1\'', 'Bodegas', '2026-04-29 01:36:29', '2026-04-29 01:36:29'),
(42, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: play store. Cambios: proveedor_id: \'2\' -> \'\'', 'Bodegas', '2026-04-29 01:36:33', '2026-04-29 01:36:33'),
(43, 6, 'Super Admin', 'superadmin', 'Eliminó la bodega: play store', 'Bodegas', '2026-04-29 01:36:38', '2026-04-29 01:36:38'),
(44, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitopere. Cambios: nombre: \'juanitoperez\' -> \'juanitopere\'', 'Proveedores', '2026-04-29 01:37:06', '2026-04-29 01:37:06'),
(45, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitopere. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-29 01:37:17', '2026-04-29 01:37:17'),
(46, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitopere. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-29 01:37:18', '2026-04-29 01:37:18'),
(47, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitopere. Cambios: activo: \'1\' -> \'0\'', 'Proveedores', '2026-04-29 01:37:20', '2026-04-29 01:37:20'),
(48, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'1\' -> \'2\'', 'Bodegas', '2026-04-29 01:37:29', '2026-04-29 01:37:29'),
(49, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'2\' -> \'1\'', 'Bodegas', '2026-04-29 01:37:56', '2026-04-29 01:37:56'),
(50, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'1\' -> \'2\'', 'Bodegas', '2026-04-29 01:38:18', '2026-04-29 01:38:18'),
(51, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'2\' -> \'1\'', 'Bodegas', '2026-04-29 01:40:48', '2026-04-29 01:40:48'),
(52, 6, 'Super Admin', 'superadmin', 'Modificó el proveedor: juanitopere. Cambios: activo: \'0\' -> \'1\'', 'Proveedores', '2026-04-29 01:40:58', '2026-04-29 01:40:58'),
(53, 6, 'Super Admin', 'superadmin', 'Modificó la bodega: TecnoStores. Cambios: proveedor_id: \'1\' -> \'2\'', 'Bodegas', '2026-04-29 01:41:05', '2026-04-29 01:41:05'),
(54, 6, 'Super Admin', 'superadmin', 'Editó el proveedor «juanitopere» — Estado: Activo → Inactivo', 'Proveedores', '2026-04-29 01:46:07', '2026-04-29 01:46:07'),
(55, 6, 'Super Admin', 'superadmin', 'Editó el proveedor «juanitopere» — Estado: Inactivo → Activo', 'Proveedores', '2026-04-29 01:46:09', '2026-04-29 01:46:09'),
(56, 6, 'Super Admin', 'superadmin', 'Editó el componente «AMD Ryzen 5 5600» — Stock: 3 → 5', 'Componentes', '2026-04-29 01:46:26', '2026-04-29 01:46:26'),
(57, 1, 'TecnoStores', 'bodega', 'Editó el componente «AMD Ryzen 5 5600» — Stock: 5 → 1', 'Componentes', '2026-04-29 01:46:59', '2026-04-29 01:46:59'),
(58, 1, 'TecnoStores', 'bodega', 'Editó el componente «AMD Ryzen 5 5600» — Stock: 1 → 6', 'Componentes', '2026-04-29 01:47:12', '2026-04-29 01:47:12'),
(59, 1, 'TecnoStores', 'bodega', 'Eliminó el componente «NVIDIA RTX 4080»', 'Componentes', '2026-04-29 01:53:50', '2026-04-29 01:53:50'),
(60, 1, 'TecnoStores', 'bodega', 'Agregó el componente «AMD Ryzen 3 4100» (Gama: alta) a la bodega «TecnoStores»', 'Componentes', '2026-04-29 01:54:20', '2026-04-29 01:54:20'),
(61, 1, 'TecnoStores', 'bodega', 'Eliminó el componente «AMD Ryzen 3 4100»', 'Componentes', '2026-04-29 01:54:26', '2026-04-29 01:54:26'),
(62, 6, 'Super Admin', 'superadmin', 'Registró el proveedor «Angel» (migui s.a)', 'Proveedores', '2026-04-29 01:56:56', '2026-04-29 01:56:56'),
(63, 6, 'Super Admin', 'superadmin', 'Editó el proveedor «Angel» — Aprobación: Pendiente → Aprobado', 'Proveedores', '2026-04-29 01:57:02', '2026-04-29 01:57:02'),
(64, 3, 'Angel', 'proveedor', 'Creó la bodega «gab»', 'Bodegas', '2026-04-29 01:58:10', '2026-04-29 01:58:10'),
(65, 3, 'Angel', 'proveedor', 'Editó la bodega «gab» — Estado: Activo → Inactivo', 'Bodegas', '2026-04-29 01:58:14', '2026-04-29 01:58:14'),
(66, 3, 'Angel', 'proveedor', 'Editó la bodega «gab» — Estado: Inactivo → Activo', 'Bodegas', '2026-04-29 01:58:16', '2026-04-29 01:58:16'),
(67, 3, 'Angel', 'proveedor', 'Editó la bodega «gabi» — Nombre: gab → gabi', 'Bodegas', '2026-04-29 01:58:21', '2026-04-29 01:58:21'),
(68, 3, 'Angel', 'proveedor', 'Eliminó la bodega «gabi»', 'Bodegas', '2026-04-29 02:07:31', '2026-04-29 02:07:31'),
(69, 6, 'Super Admin', 'superadmin', 'Editó el usuario «m » — Teléfono: 316987656598 →  · Estado: Activo → Inactivo', 'Usuarios', '2026-04-29 02:08:32', '2026-04-29 02:08:32'),
(70, 6, 'Super Admin', 'superadmin', 'Editó el usuario «m » — Estado: Inactivo → Activo', 'Usuarios', '2026-04-29 02:08:34', '2026-04-29 02:08:34'),
(71, 10, 'Yisus Jesus', 'cliente', 'Se registró como nuevo usuario', 'Usuarios', '2026-04-29 02:10:15', '2026-04-29 02:10:15'),
(72, 10, 'Yisus Jesus', 'cliente', 'Creó una nueva cotización (ID: 4) por 101290', 'Cotizaciones', '2026-04-29 02:10:31', '2026-04-29 02:10:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_04_13_021132_create_personal_access_tokens_table', 1),
(5, '2026_04_13_225620_add_fields_to_proveedores_table', 2),
(6, '2026_04_13_233258_add_activo_to_usuarios_table', 3),
(7, '2026_04_14_215613_create_historial_accions_table', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '59caa3fa502149d614af5d2527a2c4a4a9021360b608c4b88d305519cf4e52d4', '[\"*\"]', '2026-04-13 09:03:20', NULL, '2026-04-13 08:32:22', '2026-04-13 09:03:20'),
(2, 'App\\Models\\Usuario', 7, 'auth_token_usuario', 'a44e8c601dd3dca4763338592bff232977ea27849742fcc2f28426394450026a', '[\"*\"]', NULL, NULL, '2026-04-13 08:38:57', '2026-04-13 08:38:57'),
(3, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'b164a659577599561e397bfdad6fc86607c06784e7dae148458401599dd5e286', '[\"*\"]', '2026-04-13 09:51:23', NULL, '2026-04-13 09:03:45', '2026-04-13 09:51:23'),
(4, 'App\\Models\\Usuario', 1, 'auth_token_usuario', 'c05d822bf44e0c96f54a06d0cb5f954e6c7a9bdb824e319a856741c831c32c9c', '[\"*\"]', '2026-04-13 09:54:37', NULL, '2026-04-13 09:32:29', '2026-04-13 09:54:37'),
(5, 'App\\Models\\Usuario', 2, 'auth_token_usuario', 'ebc1200402109006279b45bf326e367832c22d9b9990c21775c1158ce3d82b7b', '[\"*\"]', '2026-04-13 19:21:08', NULL, '2026-04-13 19:21:06', '2026-04-13 19:21:08'),
(6, 'App\\Models\\Usuario', 2, 'auth_token_usuario', 'b1332869c0f777333a9b235b2fba530f65903fe581f87ef471dd515d6d6dc3ae', '[\"*\"]', '2026-04-13 19:23:43', NULL, '2026-04-13 19:23:42', '2026-04-13 19:23:43'),
(7, 'App\\Models\\Usuario', 2, 'auth_token_usuario', 'fcb88ab3fb2bae651b622af9e6bf80087c45f42ad195569fc12e9399b1a39a8f', '[\"*\"]', '2026-04-13 19:23:59', NULL, '2026-04-13 19:23:58', '2026-04-13 19:23:59'),
(8, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'd9a97226d9dcc78da539069bd3907c64f61db546a6083b3d05a02d808e2c6cf6', '[\"*\"]', '2026-04-14 04:13:32', NULL, '2026-04-14 04:05:54', '2026-04-14 04:13:32'),
(9, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '4cff4c771bf961a6897a1e18405190ba5b91a609c9c7aea002fe55753f7a8c69', '[\"*\"]', '2026-04-14 04:19:41', NULL, '2026-04-14 04:14:12', '2026-04-14 04:19:41'),
(10, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '25efc496ed98c3a021f17d0d4ca01399af41f49c3a6395c2e43b870a71aa6764', '[\"*\"]', '2026-04-14 04:38:35', NULL, '2026-04-14 04:20:02', '2026-04-14 04:38:35'),
(11, 'App\\Models\\Usuario', 2, 'auth_token_usuario', 'a0f04bfa247f6a5ac8f40febaefeeb6d66283abfcce9f0106ace20c2dac0ea11', '[\"*\"]', '2026-04-14 04:43:45', NULL, '2026-04-14 04:43:44', '2026-04-14 04:43:45'),
(12, 'App\\Models\\Proveedor', 2, 'auth_token_proveedor', '0263e3e35b44e281b702b3ca5f5776009e1ed1661feef73daecad18a4effaebd', '[\"*\"]', '2026-04-14 04:44:18', NULL, '2026-04-14 04:44:18', '2026-04-14 04:44:18'),
(13, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '53e2bde9822093cada511f2b9cd1868578af18ae8b3938e896c5e33d7df89037', '[\"*\"]', '2026-04-14 04:45:41', NULL, '2026-04-14 04:45:40', '2026-04-14 04:45:41'),
(14, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '833621475a89aac81a14b7f0145ad1fad4cedfee26978779f839b7c234ffd1a7', '[\"*\"]', NULL, NULL, '2026-04-28 22:40:32', '2026-04-28 22:40:32'),
(15, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'be47f5858368b9af1f6e4a2bf1c88f31af3bb8c0cda4a0f32b9a4ea647278829', '[\"*\"]', NULL, NULL, '2026-04-28 22:40:35', '2026-04-28 22:40:35'),
(16, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '827f91130ee70528ac3666d8f5bf3d0a690e0a95d689bc89e543f8fca7d1bd62', '[\"*\"]', NULL, NULL, '2026-04-28 22:40:48', '2026-04-28 22:40:48'),
(17, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'e9085b67308b6e60d6c510f7ccdab03f345670aa1006a019f047e8cf24039c06', '[\"*\"]', NULL, NULL, '2026-04-28 22:44:33', '2026-04-28 22:44:33'),
(18, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'b7846f547596b486b1fddffdaa1a6df3a0bc0e88b3fbb8292a537f82df5fb25e', '[\"*\"]', NULL, NULL, '2026-04-28 22:44:51', '2026-04-28 22:44:51'),
(19, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '55b6fc833e30aef95ffb76fd842b7da0cc1cab00e2f94a5906fe50a544a482d8', '[\"*\"]', NULL, NULL, '2026-04-28 22:45:29', '2026-04-28 22:45:29'),
(20, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '94164cbe4e7cfe64e8915b9e4bf8d67f054fb6131760fc5e186c1b8d37517036', '[\"*\"]', '2026-04-28 22:45:42', NULL, '2026-04-28 22:45:39', '2026-04-28 22:45:42'),
(21, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'bd3c5be89d93347a076a601cbf7632c8c58a29819e99321178371ecf669ef695', '[\"*\"]', NULL, NULL, '2026-04-28 22:46:01', '2026-04-28 22:46:01'),
(22, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '036b09d0494f4c2b165d6ec2b061b5e98701ccf29fd6cc10680d590852397cc3', '[\"*\"]', '2026-04-28 22:48:27', NULL, '2026-04-28 22:47:13', '2026-04-28 22:48:27'),
(23, 'App\\Models\\Usuario', 2, 'auth_token_usuario', 'b2161210d69f32eba1ec853bbffa838bb8dd8fb160448ed4239d8a37b30df3c0', '[\"*\"]', '2026-04-28 22:51:56', NULL, '2026-04-28 22:48:20', '2026-04-28 22:51:56'),
(24, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'b40145f5fec6d6065b41dbf91fcc2c10b3e6b89ddaa87cb8639d730539d228be', '[\"*\"]', '2026-04-28 23:02:36', NULL, '2026-04-28 22:53:08', '2026-04-28 23:02:36'),
(25, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '9908da0e2ae71fa2f4399ee6d11e072d356404d21ebd304f64c45a3886c979cb', '[\"*\"]', '2026-04-28 23:09:02', NULL, '2026-04-28 23:03:37', '2026-04-28 23:09:02'),
(26, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '29d489dffcddc3fb05d52b32318870ba2a394cde3bd2fccd26f7426e9afb3b7c', '[\"*\"]', '2026-04-28 23:32:47', NULL, '2026-04-28 23:22:56', '2026-04-28 23:32:47'),
(27, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '3ae9b2b1581bcb1dbe8d01385bef2ea688daa256a1b72bbf48ded8f4fee4b32f', '[\"*\"]', '2026-04-28 23:45:06', NULL, '2026-04-28 23:43:47', '2026-04-28 23:45:06'),
(28, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '61cd4a8e9536f97a3204e3052feb9adabe909e7d80a86d941030be6a0626dcef', '[\"*\"]', '2026-04-28 23:47:55', NULL, '2026-04-28 23:47:25', '2026-04-28 23:47:55'),
(29, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '83521b1a7c726a91b036d23100bc5865c087d67f3de4e8381e697428aa51ef9c', '[\"*\"]', '2026-04-29 00:57:26', NULL, '2026-04-29 00:57:20', '2026-04-29 00:57:26'),
(30, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '166be4270364c33497c0b51a43ffc67a8be3fdfe082b1dd84b693350f28ab3bd', '[\"*\"]', '2026-04-29 00:59:54', NULL, '2026-04-29 00:57:42', '2026-04-29 00:59:54'),
(31, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '0e7896115fbc78774cc39401e70a5d5414ba4ab8105ebc3282dc0c4dbb8b412d', '[\"*\"]', '2026-04-29 01:05:03', NULL, '2026-04-29 01:00:20', '2026-04-29 01:05:03'),
(32, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'ef9ee46d3a5bc6eaedb74566ec27685476d3bf48aa79557d00fa00b32aba749a', '[\"*\"]', '2026-04-29 01:05:23', NULL, '2026-04-29 01:05:12', '2026-04-29 01:05:23'),
(33, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '4f3ccefe9d09070b8a3f058cd299643ab10999a2ff3abcf42636452345874dcb', '[\"*\"]', '2026-04-29 01:06:43', NULL, '2026-04-29 01:05:30', '2026-04-29 01:06:43'),
(34, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '74ac0e5fccaa982fe066d1bab9c74d7b9684d229f8be1dce633bbdc55c2c3509', '[\"*\"]', '2026-04-29 01:24:37', NULL, '2026-04-29 01:06:48', '2026-04-29 01:24:37'),
(35, 'App\\Models\\Usuario', 2, 'auth_token_usuario', '2e721d33c62e414d3b3c44d43de35b77c71289079b6af352c43d31b851587977', '[\"*\"]', '2026-04-29 01:25:39', NULL, '2026-04-29 01:25:36', '2026-04-29 01:25:39'),
(36, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '664fa23765271d0f5bad9f07f77842250c4fa678460bd7fd5e8e460ab717faff', '[\"*\"]', '2026-04-29 01:46:26', NULL, '2026-04-29 01:26:03', '2026-04-29 01:46:26'),
(37, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'c12256b7f230f7cfacf042ef57118f3c18c868a9cf2495b4fb4ea557c65ba936', '[\"*\"]', '2026-04-29 01:29:35', NULL, '2026-04-29 01:29:10', '2026-04-29 01:29:35'),
(38, 'App\\Models\\Bodega', 1, 'auth_token_bodega', '9d56b896a12f4c9653e79d16193224acb795f8803f86f49bab1ec3cc22185f3c', '[\"*\"]', '2026-04-29 01:49:19', NULL, '2026-04-29 01:46:48', '2026-04-29 01:49:19'),
(39, 'App\\Models\\Bodega', 1, 'auth_token_bodega', '8c304c261343c04dd7052cb62b8b2d7aec00c5f69f52f82d7b37a82af744e694', '[\"*\"]', '2026-04-29 01:54:27', NULL, '2026-04-29 01:52:35', '2026-04-29 01:54:27'),
(40, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'c42a46305621fb422ee9c9d90894a08f9dc16fa2c71f2d9c6c1bbbc0853e8e56', '[\"*\"]', '2026-04-29 01:57:03', NULL, '2026-04-29 01:55:51', '2026-04-29 01:57:03'),
(41, 'App\\Models\\Proveedor', 3, 'auth_token_proveedor', '629566151b8ea2da8f628be0b739cc09692d0c21514a8b9d5e43b869eb826685', '[\"*\"]', '2026-04-29 02:07:32', NULL, '2026-04-29 01:57:18', '2026-04-29 02:07:32'),
(42, 'App\\Models\\Usuario', 6, 'auth_token_usuario', 'ee921e2ab05b5eae600e2df877487d80e9f1e509d4b0f70d92ed8152efc3f692', '[\"*\"]', '2026-04-29 02:08:34', NULL, '2026-04-29 02:08:03', '2026-04-29 02:08:34'),
(43, 'App\\Models\\Usuario', 10, 'auth_token_usuario', '0756b3a561ab16433035d6ba8485a628b86a1dfac8c1a0d0f1b8af4434c29426', '[\"*\"]', '2026-04-29 02:10:31', NULL, '2026-04-29 02:10:15', '2026-04-29 02:10:31'),
(44, 'App\\Models\\Usuario', 6, 'auth_token_usuario', '2615d475c4c41d3e3e003e49da7722d80f5afaf45224fb6062ea8b3cd2bd538b', '[\"*\"]', '2026-04-29 02:10:43', NULL, '2026-04-29 02:10:39', '2026-04-29 02:10:43');

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
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `identificacion_legal` varchar(255) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `estado_aprobacion` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `documento_soporte` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `correo`, `password`, `activo`, `created_at`, `identificacion_legal`, `razon_social`, `estado_aprobacion`, `documento_soporte`) VALUES
(1, 'Intel S.A.', 'intel@proveedor.test', '$2y$10$HmF8pFcWzUU8shDCojAsK.1NWdY3FTpuJpvKwXGzioRGWaH2vDp06', 1, '2026-04-13 04:10:03', NULL, NULL, 'aprobado', NULL),
(2, 'juanitopere', 'example@pcmatch.com', '$2y$10$mlPvwZuYOVMtcmWBe45Csu2WwUNsSnZIP.c2Uwcos02jARnmjRUUq', 1, '2026-04-13 23:14:54', '123456789', 'Examples', 'aprobado', NULL),
(3, 'Angel', 'eje@gmail.com', '$2y$10$JbZ4bMJefre94S.ltSadHedhOA6tyG0R59/k7I/ZxOL37NDf.ZN76', 1, '2026-04-28 20:56:56', '12345678-0', 'migui s.a', 'aprobado', NULL);

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
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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
  `rol` enum('superadmin','admin','cliente') NOT NULL DEFAULT 'cliente',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `correo`, `telefono`, `password`, `rol`, `activo`, `created_at`) VALUES
(1, 'Juan', 'Pérez', 'Juanperezoso@gmail.com', '+57 123 456 7891', '$2y$10$WJT/tkqo.QBvn77lFtXLKeAv0l3wxhkKZUukddlx1IVn7RFYhMiHS', 'cliente', 1, '2026-02-28 19:25:52'),
(2, 'Admin', 'PCMATCH', 'admin@pcmatch.com', NULL, '$2y$10$Q9gxEykl6iq4tCysFfgeZ.7cWMFhmt9CMP.Xt.jCw5K3di3S/HIuG', 'admin', 1, '2026-02-28 19:47:57'),
(5, 'm', '', 'hmayaa@gmail.com', '', '$2y$10$7CrqNG5i/hl2Tk79saxiSORpx7Vefziy/YQD8Wv6dlGom88S1zaUO', 'cliente', 1, '2026-03-10 21:42:10'),
(6, 'Super', 'Admin', 'superadmin@pcmatch.com', NULL, '$2y$10$zBVIvKEqbAS10kJyn7jAx.HGCw9IHjUXo4rIH4CAIRL13/scc0jpO', 'superadmin', 1, '2026-04-06 17:33:51'),
(9, 'Jeffrito', NULL, 'sdjasjdajsdasd@gmail.com', NULL, '$2y$10$QNadTsdypWv0GC/eyPSTjuGHRTfdCTrs3zYDg6z8k9Mm2Racu3wqW', 'cliente', 1, '2026-04-28 20:05:02'),
(10, 'Yisus', 'Jesus', 'yisus@gmail.com', '+573016585873', '$2y$10$wIY1be9SqR4TpSGcp3yp7.x3yJPgZ.cRTZwzwY3vhSqnRN2M7zgoq', 'cliente', 1, '2026-04-28 21:10:15');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

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
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `historial_acciones`
--
ALTER TABLE `historial_acciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indices de la tabla `productos_catalogo`
--
ALTER TABLE `productos_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `proveedores_identificacion_legal_unique` (`identificacion_legal`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `componentes`
--
ALTER TABLE `componentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cotizacion_items`
--
ALTER TABLE `cotizacion_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_acciones`
--
ALTER TABLE `historial_acciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `productos_catalogo`
--
ALTER TABLE `productos_catalogo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD CONSTRAINT `bodegas_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

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
