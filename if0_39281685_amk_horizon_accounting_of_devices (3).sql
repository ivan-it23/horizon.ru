-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Хост: sql105.infinityfree.com
-- Время создания: Июл 03 2025 г., 15:51
-- Версия сервера: 11.4.7-MariaDB
-- Версия PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `if0_39281685_amk_horizon_accounting_of_devices`
--

-- --------------------------------------------------------

--
-- Структура таблицы `additional_data`
--

CREATE TABLE `additional_data` (
  `id` int(11) NOT NULL,
  `additional_date` date DEFAULT NULL,
  `additional_content` text DEFAULT NULL,
  `creator_surname` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `additional_data`
--

INSERT INTO `additional_data` (`id`, `additional_date`, `additional_content`, `creator_surname`, `serial_number`, `case_number`, `created_at`) VALUES
(1, '2025-06-23', 'Прибор работает корректно. Сотрудники довольны', 'Админ   ', '105', '002-2023', '2025-06-23 10:54:10'),
(2, '2025-07-10', 'арьоаь', 'Админ   ', '45', '45', '2025-07-02 20:36:22'),
(3, '2025-07-10', 'liyuib', 'Админ   ', '105', '002-2023', '2025-07-03 17:30:01'),
(4, '2025-07-22', 'всцу', 'Админ   ', '105', '002-2023', '2025-07-03 19:51:20');

-- --------------------------------------------------------

--
-- Структура таблицы `device_composition`
--

CREATE TABLE `device_composition` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `board_name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL COMMENT 'ID пользователя-создателя',
  `creator_name` varchar(255) DEFAULT NULL COMMENT 'ФИО создателя записи',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `device_composition`
--

INSERT INTO `device_composition` (`id`, `device_id`, `board_name`, `type`, `version`, `serial_number`, `creator_id`, `creator_name`, `created_at`) VALUES
(5, 1, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS', '2', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(6, 1, 'Транзитная', 'укороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(7, 1, 'AL_V3', 'Сбора и хранения данных (Олег)', '3', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(8, 1, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(9, 1, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника', '2', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(10, 1, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(11, 2, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (неуменьшенная)', '2', '-', 7, 'Администратор', '2025-06-02 22:33:52'),
(12, 2, 'Транзитная', 'неукороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 22:33:52'),
(13, 2, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 7, 'Администратор', '2025-06-02 22:33:52'),
(14, 2, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Администратор', '2025-06-02 22:33:52'),
(15, 2, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника (неуменьшенная)', '1', '-', 7, 'Администратор', '2025-06-02 22:33:52'),
(16, 2, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Администратор', '2025-06-02 22:33:52'),
(17, 3, 'Транзитная', 'неукороченная несимметричная, с стабилизатором 6В', '2', '-', 26, 'Литвинов Владислав Глебович', '2025-06-05 15:41:41'),
(18, 3, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (неуменьшенная)', '1', '-', 26, 'Литвинов Владислав Глебович', '2025-06-05 15:41:41'),
(19, 3, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 26, 'Литвинов Владислав Глебович', '2025-06-05 15:41:41'),
(20, 3, '1DDS', 'АЦП, DDS (перенесена от середины прибора)', '', '-', 26, 'Литвинов Владислав Глебович', '2025-06-05 15:41:41'),
(21, 3, 'Передатчик', 'неуменьшенная плата (источник 6В на транзитке)', '', '-', 26, 'Литвинов Владислав Глебович', '2025-06-05 15:41:41'),
(22, 4, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (уменьшенная)', '', '', 26, 'Литвинов Владислав Глебович', '2025-06-05 16:46:06'),
(23, 4, 'Транзитная', 'неукороченная несимметричная', '', '', 26, 'Литвинов Владислав Глебович', '2025-06-05 16:46:06'),
(24, 4, 'AL_V1.0', 'плата старой версии', '', '', 26, 'Литвинов Владислав Глебович', '2025-06-05 16:46:06'),
(25, 4, '1DDS', 'АЦП, DDS', '3', '-', 26, 'Литвинов Владислав Глебович', '2025-06-05 16:46:06'),
(26, 4, 'ПРМ-ЛВД-1DDS', 'уменьшенная, УВЧ', '', '', 26, 'Литвинов Владислав Глебович', '2025-06-05 16:46:06'),
(27, 4, 'Передатчик', 'Т3, Т1, Т2 неукороченая без источника 6В. Т4 укороченная, с источником 6В', '', '', 26, 'Литвинов Владислав Глебович', '2025-06-05 16:46:06'),
(46, 8, 'Инклинометр', 'geo-5V6', '2', '123', NULL, NULL, '2025-06-29 14:59:32'),
(47, 8, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (уменьшенная)', '', '', NULL, NULL, '2025-06-29 14:59:32'),
(48, 8, 'Транзитная', 'неукороченная несимметричная', '', '', NULL, NULL, '2025-06-29 14:59:32'),
(49, 8, 'AL_V1.0', 'плата старой версии', '', '', NULL, NULL, '2025-06-29 14:59:32'),
(50, 8, '1DDS', 'АЦП, DDS', '3', '-', NULL, NULL, '2025-06-29 14:59:32'),
(51, 8, 'ПРМ-ЛВД-1DDS', 'уменьшенная, УВЧ', '', '', NULL, NULL, '2025-06-29 14:59:32'),
(52, 8, 'Передатчик', 'Т3, Т1, Т2 неукороченая без источника 6В. Т4 укороченная, с источником 6В', '', '', NULL, NULL, '2025-06-29 14:59:32'),
(66, 7, 'Транзитная', 'неукороченная несимметричная', '2', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(67, 7, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(68, 7, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(69, 7, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника (неуменьшенная)', '22222', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(70, 7, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(71, 7, '333', '3333', '33333', '33333', 7, 'Админ', '2025-07-03 17:15:51');

-- --------------------------------------------------------

--
-- Структура таблицы `device_composition_history`
--

CREATE TABLE `device_composition_history` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `board_name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `version` varchar(100) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `creator_id` int(11) DEFAULT NULL COMMENT 'ID пользователя-создателя',
  `creator_name` varchar(255) DEFAULT NULL COMMENT 'ФИО создателя записи',
  `changed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `device_composition_history`
--

INSERT INTO `device_composition_history` (`id`, `device_id`, `board_name`, `type`, `version`, `serial_number`, `creator_id`, `creator_name`, `changed_at`) VALUES
(1, 1, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS', '2', '-', 7, 'Администратор', '2025-06-02 22:04:34'),
(2, 1, 'Транзитная', 'укороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 22:04:34'),
(3, 1, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS', '2', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(4, 1, 'Транзитная', 'укороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 22:08:57'),
(5, 7, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (неуменьшенная)', '2', '-', 7, 'Админ', '2025-07-03 17:15:05'),
(6, 7, 'Транзитная', 'неукороченная несимметричная', '2', '-', 7, 'Админ', '2025-07-03 17:15:05'),
(7, 7, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 7, 'Админ', '2025-07-03 17:15:05'),
(8, 7, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Админ', '2025-07-03 17:15:05'),
(9, 7, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника (неуменьшенная)', '1', '-', 7, 'Админ', '2025-07-03 17:15:05'),
(10, 7, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Админ', '2025-07-03 17:15:05'),
(11, 7, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (неуменьшенная)', '2', '-', 7, 'Админ', '2025-07-03 17:15:31'),
(12, 7, 'Транзитная', 'неукороченная несимметричная', '2', '-', 7, 'Админ', '2025-07-03 17:15:31'),
(13, 7, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 7, 'Админ', '2025-07-03 17:15:31'),
(14, 7, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Админ', '2025-07-03 17:15:31'),
(15, 7, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника (неуменьшенная)', '22222', '-', 7, 'Админ', '2025-07-03 17:15:31'),
(16, 7, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Админ', '2025-07-03 17:15:31'),
(17, 7, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (неуменьшенная)', '2', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(18, 7, 'Транзитная', 'неукороченная несимметричная', '2', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(19, 7, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(20, 7, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(21, 7, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника (неуменьшенная)', '22222', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(22, 7, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Админ', '2025-07-03 17:15:51'),
(23, 7, '333', '3333', '33333', '33333', 7, 'Админ', '2025-07-03 17:15:51');

-- --------------------------------------------------------

--
-- Структура таблицы `device_history`
--

CREATE TABLE `device_history` (
  `history_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `nominal_diameter` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `length_mm` varchar(255) DEFAULT NULL,
  `diameter_mm` varchar(255) DEFAULT NULL,
  `upper_thread` varchar(255) DEFAULT NULL,
  `lower_thread` varchar(255) DEFAULT NULL,
  `frequency1_khz` varchar(255) DEFAULT NULL,
  `frequency2_khz` varchar(255) DEFAULT NULL,
  `receiver_base1_mm` varchar(255) DEFAULT NULL,
  `receiver_base2_mm` varchar(255) DEFAULT NULL,
  `receiver_base3_mm` varchar(255) DEFAULT NULL,
  `record_date` varchar(255) DEFAULT NULL,
  `creator_lastname` varchar(255) DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `transmitter_base1_mm` varchar(255) DEFAULT NULL,
  `transmitter_base2_mm` varchar(255) DEFAULT NULL,
  `transmitter_base3_mm` varchar(255) DEFAULT NULL,
  `transmitter_base4_mm` varchar(255) DEFAULT NULL,
  `transmitter_base5_mm` varchar(255) DEFAULT NULL,
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `device_history`
--

INSERT INTO `device_history` (`history_id`, `device_id`, `device_type`, `nominal_diameter`, `serial_number`, `case_number`, `length_mm`, `diameter_mm`, `upper_thread`, `lower_thread`, `frequency1_khz`, `frequency2_khz`, `receiver_base1_mm`, `receiver_base2_mm`, `receiver_base3_mm`, `record_date`, `creator_lastname`, `changed_at`, `transmitter_base1_mm`, `transmitter_base2_mm`, `transmitter_base3_mm`, `transmitter_base4_mm`, `transmitter_base5_mm`, `additional_info`) VALUES
(1, 1, 'LWD', '120', '109', '008-2024', '4200.00', '122.00', 'mk-90', 'mk-90', '399.00', '1960.00', '1', NULL, NULL, '2025-06-02', 'Администратор', '2025-06-02 22:04:34', '1', '1', '1', '1', NULL, NULL),
(2, 1, 'LWD', '120', '109', '008-2024', '4200.00', '122.00', 'mk-90', 'mk-90', '399.00', '1960.00', '254', NULL, NULL, '2025-06-02', 'Администратор', '2025-06-02 22:08:57', '471', '848', '1228', '1600', NULL, NULL),
(3, 2, 'LWD', '120', '108', '005-2024', '1.00', '1.00', '1', '1', '1.00', '1.00', '1', NULL, NULL, '2025-06-02', 'Администратор', '2025-06-02 22:33:52', '1', '1', '1', '1', NULL, NULL),
(4, 3, 'LWD', '120', '106', '003-2023', '3903.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '254', NULL, NULL, '2025-06-05', 'Литвинов Владислав Глебович', '2025-06-05 15:41:41', '472', '847', '1228', '1604', NULL, NULL),
(5, 4, 'LWD', '120', '105', '002-2023', '4140.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '254', NULL, NULL, '0000-00-00', 'Литвинов Владислав Глебович', '2025-06-05 16:46:06', '470', '846', '1230', '1605', NULL, NULL),
(6, 4, 'LWD', '120', '105', '002-2023', '4140.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '254', NULL, NULL, '0000-00-00', 'Админ', '2025-06-23 10:49:21', '470', '846', '1230', '1605', NULL, NULL),
(7, 7, 'Автономный', '90', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '399.00', '1960.00', '34', NULL, NULL, '2025-06-22', 'Админ', '2025-06-29 06:47:30', '32', '31', '12', NULL, NULL, 'Тестовый ответ'),
(8, 7, 'Автономный', '90', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '399.00', '1960.00', '34', NULL, NULL, '2025-06-22', 'Админ', '2025-06-29 06:48:11', '222', '31', '12', NULL, NULL, 'Тестовый ответ'),
(9, 7, 'Автономный', '90', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '399.00', '1960.00', '34', NULL, NULL, '2025-06-22', 'Админ', '2025-06-29 08:20:18', 'пикеиеу', '31', '12', NULL, NULL, 'Тестовый ответ'),
(10, 8, 'Картограф', '90', '45', '45', '4140.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '43', '22', '242', '2025-06-29', 'Админ', '2025-06-29 15:02:30', '44-wer', '242-er', '2244-geg', NULL, NULL, 'Тестовый ответ'),
(11, 8, 'Картограф', '90', '45', '45', '4140.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '43', '11', '242', '2025-06-29', 'Админ', '2025-07-02 20:34:31', '44-wer', '242-er', '2244-geg', NULL, NULL, 'Тестовый ответ'),
(12, 7, 'Автономный', '90', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '399.00', 'eeee', '34', NULL, NULL, '2025-06-22', 'Админ', '2025-07-03 17:15:05', 'пикеиеу', '31', '12', NULL, NULL, 'Тестовый ответ'),
(13, 7, 'Автономный', '90', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '1111', 'eeee', '34', NULL, NULL, '2025-06-22', 'Админ', '2025-07-03 17:15:31', 'пикеиеу', '31', '12', NULL, NULL, 'Тестовый ответ2222222'),
(14, 7, 'Автономный', '90', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '1111', 'eeee', '34', NULL, NULL, '2025-06-22', 'Админ', '2025-07-03 17:15:51', 'пикеиеу', '31', '12', NULL, NULL, 'Тестовый ответ2222222');

-- --------------------------------------------------------

--
-- Структура таблицы `device_identification`
--

CREATE TABLE `device_identification` (
  `id` int(11) NOT NULL,
  `device_type` varchar(255) NOT NULL,
  `nominal_diameter` varchar(255) NOT NULL,
  `transmitter_count` varchar(255) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `length_mm` varchar(255) DEFAULT NULL,
  `diameter_mm` varchar(255) DEFAULT NULL,
  `upper_thread` varchar(255) DEFAULT NULL,
  `lower_thread` varchar(255) DEFAULT NULL,
  `frequency1_khz` varchar(255) DEFAULT NULL,
  `frequency2_khz` varchar(255) DEFAULT NULL,
  `record_date` date DEFAULT NULL,
  `creator_lastname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `receiver_base1_mm` varchar(255) DEFAULT NULL,
  `receiver_base2_mm` varchar(255) DEFAULT NULL,
  `receiver_base3_mm` varchar(255) DEFAULT NULL,
  `changed_at` datetime DEFAULT NULL,
  `transmitter_base1_mm` varchar(255) DEFAULT NULL,
  `transmitter_base2_mm` varchar(255) DEFAULT NULL,
  `transmitter_base3_mm` varchar(255) DEFAULT NULL,
  `transmitter_base4_mm` varchar(255) DEFAULT NULL,
  `transmitter_base5_mm` varchar(255) DEFAULT NULL,
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `device_identification`
--

INSERT INTO `device_identification` (`id`, `device_type`, `nominal_diameter`, `transmitter_count`, `serial_number`, `case_number`, `length_mm`, `diameter_mm`, `upper_thread`, `lower_thread`, `frequency1_khz`, `frequency2_khz`, `record_date`, `creator_lastname`, `created_at`, `receiver_base1_mm`, `receiver_base2_mm`, `receiver_base3_mm`, `changed_at`, `transmitter_base1_mm`, `transmitter_base2_mm`, `transmitter_base3_mm`, `transmitter_base4_mm`, `transmitter_base5_mm`, `additional_info`) VALUES
(1, 'LWD', '120', '4', '109', '008-2024', '4200.00', '122.00', 'mk-90', 'mk-90', '399.00', '1960.00', '2025-06-02', 'Администратор', '2025-06-02 22:03:22', '254', NULL, NULL, '2025-06-02 11:08:57', '471', '848', '1228', '1600', NULL, NULL),
(2, 'LWD', '120', '4', '108', '005-2024', '3920.00', '120.00', 'mk-90', 'mk-90', '399.00', '1960.00', '2025-06-02', 'Администратор', '2025-06-02 22:23:33', '254', NULL, NULL, '2025-06-02 11:33:51', '446', '824', '1202', '1580', NULL, NULL),
(3, 'LWD', '120', '4', '106', '003-2023', '3903.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '2025-06-05', 'Литвинов Владислав Глебович', '2025-06-05 15:27:38', '254', NULL, NULL, '2025-06-05 01:41:41', '472', '847', '1228', '1604', NULL, NULL),
(4, 'LWD', '120', '4', '105', '002-2023', '4140.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '0000-00-00', 'Админ', '2025-06-05 16:38:34', '254', NULL, NULL, '2025-06-23 03:49:21', '470', '846', '1230', '1605', NULL, 'Прибор работает на скважине №4'),
(7, 'Автономный', '90', '3', '56', '56-ty', '3920.00', '120.00', 'mk-90', 'mk-90', '1111', 'eeee', '2025-06-22', 'Админ', '2025-06-22 16:47:36', '34', NULL, NULL, '2025-07-03 10:15:51', 'пикеиеу', '31', '12', NULL, NULL, 'Тестовый ответ2222222'),
(8, 'Картограф', '90', '3', '45', '45', '4140.00', '120.00', 'МК-90', 'МК-90', '399.00', '1960.00', '2025-06-29', 'Админ', '2025-06-29 14:59:32', '43', '11', '111', '2025-07-02 13:34:31', '44-wer', '242-er', '2244-geg', NULL, NULL, 'Тестовый ответ');

-- --------------------------------------------------------

--
-- Структура таблицы `inspection_data`
--

CREATE TABLE `inspection_data` (
  `id` int(11) NOT NULL,
  `inspection_date` date DEFAULT NULL,
  `inspection_result` text DEFAULT NULL,
  `creator_surname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `inspection_data`
--

INSERT INTO `inspection_data` (`id`, `inspection_date`, `inspection_result`, `creator_surname`, `created_at`, `serial_number`, `case_number`) VALUES
(1, '2025-06-23', 'Прибор годен к эксплуатации, работает корректно', 'Админ   ', '2025-06-23 10:53:32', '105', '002-2023'),
(2, '2025-07-18', 'лббгшдгбг', 'Админ   ', '2025-07-02 14:17:11', '45', '45'),
(3, '2025-07-24', 'ииии', 'Админ   ', '2025-07-02 20:35:59', '45', '45'),
(4, '2025-07-19', '55gg54g', 'Админ   ', '2025-07-03 17:19:27', '105', '002-2023'),
(5, '2025-07-18', 'ohliub', 'Админ   ', '2025-07-03 17:29:28', '105', '002-2023');

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = не прочитано, 1 = прочитано',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 7, 29, 'тестовое сообщение', 0, '2025-06-23 14:39:33'),
(2, 7, 29, 'тестовое сообщение', 0, '2025-06-28 17:03:56');

-- --------------------------------------------------------

--
-- Структура таблицы `production_discontinuation`
--

CREATE TABLE `production_discontinuation` (
  `id` int(11) NOT NULL,
  `discontinuation_date` date DEFAULT NULL,
  `creator_surname` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `production_discontinuation`
--

INSERT INTO `production_discontinuation` (`id`, `discontinuation_date`, `creator_surname`, `serial_number`, `case_number`, `created_at`) VALUES
(1, '2025-04-05', ' Администратор ', '109', '008-2024', '2025-06-02 22:10:55'),
(2, '2024-01-01', ' Администратор ', '108', '005-2024', '2025-06-02 22:34:57'),
(3, '2025-06-24', 'Админ   ', '105', '002-2023', '2025-06-23 10:49:52'),
(4, '2025-07-15', 'Админ   ', '45', '45', '2025-07-02 08:08:43'),
(5, '2025-07-22', 'Админ   ', '45', '45', '2025-07-02 08:12:13'),
(6, '2025-07-11', 'Админ   ', '45', '45', '2025-07-02 20:34:44'),
(7, '2025-07-11', 'Админ   ', '56', '56-ty', '2025-07-03 17:17:17'),
(8, '2025-07-25', 'Админ   ', '105', '002-2023', '2025-07-03 17:29:18');

-- --------------------------------------------------------

--
-- Структура таблицы `repair_data`
--

CREATE TABLE `repair_data` (
  `id` int(11) NOT NULL,
  `repair_reason` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `actions_description` text DEFAULT NULL,
  `repair_creator` varchar(255) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `repair_data`
--

INSERT INTO `repair_data` (`id`, `repair_reason`, `failure_reason`, `admission_date`, `release_date`, `actions_description`, `repair_creator`, `creator_id`, `created_at`, `serial_number`, `case_number`) VALUES
(1, 'В ходе испытаний мокрого индуктора произошёл выход из строя 4-ого передатчика', 'Сгорел выходной мост индуктора', '2025-04-15', '2025-06-02', 'Замена платы 4-ого передатчика. Настройка метрологии. Поверка на кольцах, прогрев. ', ' Администратор ', 7, '2025-06-02 22:14:25', '109', '008-2024'),
(2, 'Отказал на скважине при попытке считать данные. Были сохранены данные предыдущей скважины.', 'Короткое замыкание по цепи батареи.', '2025-05-10', '2025-06-02', 'Ремонт транзитной платы №2, замена верхних экранов передающих индукторов, прогрев кольца.', ' Администратор ', 7, '2025-06-02 22:40:39', '108', '005-2024'),
(3, 'не могли вычитать данные на скважине. ', 'На транзитной плате вышел из строя керамический конденсатор на линии питания', '2025-05-12', '2025-06-05', 'произведён ремонт транзитной платы', 'Литвинов Владислав Глебович', 26, '2025-06-05 15:46:20', '108', '005-2024'),
(4, 'В скважине Т3_400 фаза намного смещена на 5-6 градусов относительно ожидаемой величины.\r\nТ4_2000 нарушен порядок. Кольца проходит успешно.', '', '2025-06-05', '0000-00-00', 'В работе.', 'Литвинов Владислав Глебович', 26, '2025-06-05 16:25:55', '106', '003-2023'),
(10, 'Перепрошивка на новые частоты (399, 1960) калибровка.', 'Обнаружена неисправность аварийного разъема.', '2025-06-05', '2025-06-06', '', 'Литвинов Владислав Глебович', 26, '2025-06-09 07:34:48', '105', '002-2023'),
(11, 'пробное оформление', 'не было', '2025-06-12', '2025-06-25', 'В работе.', 'Литвинов Владислав Глебович', 26, '2025-06-09 07:35:43', '106', '003-2023'),
(13, 'кккк', 'ииииии', NULL, NULL, 'ккккк', 'Админ   ', 7, '2025-07-02 20:35:29', '45', '45'),
(14, 'rrrr', 'rrr', '2025-07-24', NULL, '', 'Админ   ', 7, '2025-07-03 17:18:58', '105', '002-2023'),
(15, '', '', NULL, NULL, '', 'Админ   ', 7, '2025-07-03 17:18:58', '105', '002-2023');

-- --------------------------------------------------------

--
-- Структура таблицы `repair_data_history`
--

CREATE TABLE `repair_data_history` (
  `history_id` int(11) NOT NULL,
  `repair_data_id` int(11) NOT NULL,
  `repair_reason` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `actions_description` text DEFAULT NULL,
  `changed_by_user_id` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `repair_data_history`
--

INSERT INTO `repair_data_history` (`history_id`, `repair_data_id`, `repair_reason`, `failure_reason`, `admission_date`, `release_date`, `actions_description`, `changed_by_user_id`, `changed_at`) VALUES
(3, 10, 'Перепрошивка на новые частоты (399, 1960) калибровка. Обнаружена неисправность аварийного разъема.', '', '2025-06-05', '0000-00-00', 'проведен ремонт передатчика', 7, '2025-06-09 16:30:26'),
(4, 10, 'Перепрошивка на новые частоты (399, 1960) калибровка.', 'Обнаружена неисправность аварийного разъема.', '2025-06-05', NULL, '', 26, '2025-06-10 07:35:36'),
(5, 10, 'Перепрошивка на новые частоты (399, 1960) калибровка.', 'Обнаружена неисправность аварийного разъема.', '2025-06-05', NULL, '', 26, '2025-06-10 07:47:24'),
(6, 13, 'кккк', '', NULL, NULL, '', 7, '2025-07-02 20:35:36'),
(7, 13, 'кккк', '', NULL, NULL, 'ккккк', 7, '2025-07-02 20:35:46'),
(8, 14, '', '', NULL, NULL, '', 7, '2025-07-03 17:19:10'),
(9, 14, 'rrrr', 'rrr', NULL, NULL, '', 7, '2025-07-03 17:19:18');

-- --------------------------------------------------------

--
-- Структура таблицы `termination_data`
--

CREATE TABLE `termination_data` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `creator_surname` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `termination_data`
--

INSERT INTO `termination_data` (`id`, `serial_number`, `case_number`, `termination_date`, `termination_reason`, `creator_surname`, `created_at`) VALUES
(1, '105', '002-2023', '2025-07-16', 'ывсц', 'Админ   ', '2025-07-03 19:51:12'),
(2, '105', '002-2023', '2025-07-15', 'усцмц', 'Админ   ', '2025-07-03 19:51:28');

-- --------------------------------------------------------

--
-- Структура таблицы `testing_data`
--

CREATE TABLE `testing_data` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `creator` varchar(255) NOT NULL,
  `temperature_test` text DEFAULT NULL,
  `vibration_test` text DEFAULT NULL,
  `noise_sko_x1_400` float DEFAULT NULL,
  `noise_sko_y1_400` float DEFAULT NULL,
  `noise_sko_z1_400` float DEFAULT NULL,
  `noise_sko_x2_400` float DEFAULT NULL,
  `noise_sko_y2_400` float DEFAULT NULL,
  `noise_sko_z2_400` float DEFAULT NULL,
  `noise_sko_x1_2000` float DEFAULT NULL,
  `noise_sko_y1_2000` float DEFAULT NULL,
  `noise_sko_z1_2000` float DEFAULT NULL,
  `noise_sko_x2_2000` float DEFAULT NULL,
  `noise_sko_y2_2000` float DEFAULT NULL,
  `noise_sko_z2_2000` float DEFAULT NULL,
  `mds_transmitter_1_400` float DEFAULT NULL,
  `mds_transmitter_2_400` float DEFAULT NULL,
  `mds_transmitter_3_400` float DEFAULT NULL,
  `mds_transmitter_4_400` float DEFAULT NULL,
  `mds_transmitter_5_400` float DEFAULT NULL,
  `mds_transmitter_1_2000` float DEFAULT NULL,
  `mds_transmitter_2_2000` float DEFAULT NULL,
  `mds_transmitter_3_2000` float DEFAULT NULL,
  `mds_transmitter_4_2000` float DEFAULT NULL,
  `mds_transmitter_5_2000` float DEFAULT NULL,
  `second_harmonic_1_400` float DEFAULT NULL,
  `second_harmonic_2_400` float DEFAULT NULL,
  `second_harmonic_3_400` float DEFAULT NULL,
  `second_harmonic_4_400` float DEFAULT NULL,
  `second_harmonic_5_400` float DEFAULT NULL,
  `second_harmonic_1_2000` float DEFAULT NULL,
  `second_harmonic_2_2000` float DEFAULT NULL,
  `second_harmonic_3_2000` float DEFAULT NULL,
  `second_harmonic_4_2000` float DEFAULT NULL,
  `second_harmonic_5_2000` float DEFAULT NULL,
  `third_harmonic_1_400` float DEFAULT NULL,
  `third_harmonic_2_400` float DEFAULT NULL,
  `third_harmonic_3_400` float DEFAULT NULL,
  `third_harmonic_4_400` float DEFAULT NULL,
  `third_harmonic_5_400` float DEFAULT NULL,
  `third_harmonic_1_2000` float DEFAULT NULL,
  `third_harmonic_2_2000` float DEFAULT NULL,
  `third_harmonic_3_2000` float DEFAULT NULL,
  `third_harmonic_4_2000` float DEFAULT NULL,
  `third_harmonic_5_2000` float DEFAULT NULL,
  `non_harmonic_components_1_400` float DEFAULT NULL,
  `non_harmonic_components_2_400` float DEFAULT NULL,
  `non_harmonic_components_3_400` float DEFAULT NULL,
  `non_harmonic_components_4_400` float DEFAULT NULL,
  `non_harmonic_components_5_400` float DEFAULT NULL,
  `non_harmonic_components_1_2000` float DEFAULT NULL,
  `non_harmonic_components_2_2000` float DEFAULT NULL,
  `non_harmonic_components_3_2000` float DEFAULT NULL,
  `non_harmonic_components_4_2000` float DEFAULT NULL,
  `non_harmonic_components_5_2000` float DEFAULT NULL,
  `phase_difference_drift_1_400` float DEFAULT NULL,
  `phase_difference_drift_2_400` float DEFAULT NULL,
  `phase_difference_drift_3_400` float DEFAULT NULL,
  `phase_difference_drift_4_400` float DEFAULT NULL,
  `phase_difference_drift_5_400` float DEFAULT NULL,
  `phase_difference_drift_1_2000` float DEFAULT NULL,
  `phase_difference_drift_2_2000` float DEFAULT NULL,
  `phase_difference_drift_3_2000` float DEFAULT NULL,
  `phase_difference_drift_4_2000` float DEFAULT NULL,
  `phase_difference_drift_5_2000` float DEFAULT NULL,
  `test_result` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `testing_data`
--

INSERT INTO `testing_data` (`id`, `serial_number`, `case_number`, `created_at`, `creator`, `temperature_test`, `vibration_test`, `noise_sko_x1_400`, `noise_sko_y1_400`, `noise_sko_z1_400`, `noise_sko_x2_400`, `noise_sko_y2_400`, `noise_sko_z2_400`, `noise_sko_x1_2000`, `noise_sko_y1_2000`, `noise_sko_z1_2000`, `noise_sko_x2_2000`, `noise_sko_y2_2000`, `noise_sko_z2_2000`, `mds_transmitter_1_400`, `mds_transmitter_2_400`, `mds_transmitter_3_400`, `mds_transmitter_4_400`, `mds_transmitter_5_400`, `mds_transmitter_1_2000`, `mds_transmitter_2_2000`, `mds_transmitter_3_2000`, `mds_transmitter_4_2000`, `mds_transmitter_5_2000`, `second_harmonic_1_400`, `second_harmonic_2_400`, `second_harmonic_3_400`, `second_harmonic_4_400`, `second_harmonic_5_400`, `second_harmonic_1_2000`, `second_harmonic_2_2000`, `second_harmonic_3_2000`, `second_harmonic_4_2000`, `second_harmonic_5_2000`, `third_harmonic_1_400`, `third_harmonic_2_400`, `third_harmonic_3_400`, `third_harmonic_4_400`, `third_harmonic_5_400`, `third_harmonic_1_2000`, `third_harmonic_2_2000`, `third_harmonic_3_2000`, `third_harmonic_4_2000`, `third_harmonic_5_2000`, `non_harmonic_components_1_400`, `non_harmonic_components_2_400`, `non_harmonic_components_3_400`, `non_harmonic_components_4_400`, `non_harmonic_components_5_400`, `non_harmonic_components_1_2000`, `non_harmonic_components_2_2000`, `non_harmonic_components_3_2000`, `non_harmonic_components_4_2000`, `non_harmonic_components_5_2000`, `phase_difference_drift_1_400`, `phase_difference_drift_2_400`, `phase_difference_drift_3_400`, `phase_difference_drift_4_400`, `phase_difference_drift_5_400`, `phase_difference_drift_1_2000`, `phase_difference_drift_2_2000`, `phase_difference_drift_3_2000`, `phase_difference_drift_4_2000`, `phase_difference_drift_5_2000`, `test_result`) VALUES
(1, '105', '002-2023', '2025-06-23 03:52:59', 'Админ   ', 'испытания прошли успешно', 'испытания прошли в целом хорошо', 1, 1, 1, 11, 1, 1, 1, 11, 11, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 11, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'Испытания пройдены');

-- --------------------------------------------------------

--
-- Структура таблицы `tolerances_autonomous`
--

CREATE TABLE `tolerances_autonomous` (
  `id` int(11) NOT NULL,
  `parameter` varchar(255) NOT NULL,
  `frequency` int(11) NOT NULL,
  `column_name` varchar(100) NOT NULL,
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tolerances_cartographer`
--

CREATE TABLE `tolerances_cartographer` (
  `id` int(11) NOT NULL,
  `parameter` varchar(255) NOT NULL,
  `frequency` int(11) NOT NULL,
  `column_name` varchar(100) NOT NULL,
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tolerances_lwd`
--

CREATE TABLE `tolerances_lwd` (
  `id` int(11) NOT NULL,
  `parameter` varchar(255) NOT NULL,
  `frequency` int(11) NOT NULL,
  `column_name` varchar(100) NOT NULL,
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `tolerances_lwd`
--

INSERT INTO `tolerances_lwd` (`id`, `parameter`, `frequency`, `column_name`, `min_value`, `max_value`, `created_at`, `updated_at`) VALUES
(1, 'СКО шума приёмника', 400, 'X1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(2, 'СКО шума приёмника', 400, 'Y1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(3, 'СКО шума приёмника', 400, 'Z1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(4, 'СКО шума приёмника', 400, 'X2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(5, 'СКО шума приёмника', 400, 'Y2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(6, 'СКО шума приёмника', 400, 'Z2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(7, 'СКО шума приёмника', 2000, 'X1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(8, 'СКО шума приёмника', 2000, 'Y1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(9, 'СКО шума приёмника', 2000, 'Z1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(10, 'СКО шума приёмника', 2000, 'X2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(11, 'СКО шума приёмника', 2000, 'Y2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(12, 'СКО шума приёмника', 2000, 'Z2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(13, 'МДС передатчика', 400, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(14, 'МДС передатчика', 400, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(15, 'МДС передатчика', 400, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(16, 'МДС передатчика', 400, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(17, 'МДС передатчика', 400, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(18, 'МДС передатчика', 2000, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(19, 'МДС передатчика', 2000, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(20, 'МДС передатчика', 2000, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(21, 'МДС передатчика', 2000, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(22, 'МДС передатчика', 2000, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(23, 'Вторая гармоника', 400, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(24, 'Вторая гармоника', 400, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(25, 'Вторая гармоника', 400, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(26, 'Вторая гармоника', 400, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(27, 'Вторая гармоника', 400, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(28, 'Вторая гармоника', 2000, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(29, 'Вторая гармоника', 2000, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(30, 'Вторая гармоника', 2000, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(31, 'Вторая гармоника', 2000, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(32, 'Вторая гармоника', 2000, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(33, 'Третья гармоника', 400, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(34, 'Третья гармоника', 400, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(35, 'Третья гармоника', 400, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(36, 'Третья гармоника', 400, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(37, 'Третья гармоника', 400, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(38, 'Третья гармоника', 2000, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(39, 'Третья гармоника', 2000, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(40, 'Третья гармоника', 2000, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(41, 'Третья гармоника', 2000, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(42, 'Третья гармоника', 2000, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(43, 'Уровень негармонических компонент', 400, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(44, 'Уровень негармонических компонент', 400, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(45, 'Уровень негармонических компонент', 400, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(46, 'Уровень негармонических компонент', 400, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(47, 'Уровень негармонических компонент', 400, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(48, 'Уровень негармонических компонент', 2000, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(49, 'Уровень негармонических компонент', 2000, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(50, 'Уровень негармонических компонент', 2000, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(51, 'Уровень негармонических компонент', 2000, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(52, 'Уровень негармонических компонент', 2000, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(53, 'Дрейф разности фаз', 400, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(54, 'Дрейф разности фаз', 400, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(55, 'Дрейф разности фаз', 400, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(56, 'Дрейф разности фаз', 400, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(57, 'Дрейф разности фаз', 400, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(58, 'Дрейф разности фаз', 2000, '1', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(59, 'Дрейф разности фаз', 2000, '2', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(60, 'Дрейф разности фаз', 2000, '3', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(61, 'Дрейф разности фаз', 2000, '4', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53'),
(62, 'Дрейф разности фаз', 2000, '5', '1.00', '11.00', '2025-06-23 10:51:53', '2025-06-23 10:51:53');

-- --------------------------------------------------------

--
-- Структура таблицы `uploaded_files`
--

CREATE TABLE `uploaded_files` (
  `id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_data` longblob NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `case_number` varchar(255) NOT NULL,
  `device_type` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `firstname`, `lastname`, `middlename`, `created_at`, `is_admin`) VALUES
(7, 'admin', '$2y$10$ll.3LQnGG34bUiU2PUCN6euaBhYNbYZjaYzTTzAIdJ0prLCOBHvX.', ' ', 'Админ', '', '2025-02-15 18:06:51', 1),
(23, 'Roman_Sh', '$2y$10$X4xXA4DUhvBRMNAgK37CzOV9Y6Z7mmyf3ZmBbxOC/8cNEXtuZldDu', 'Роман', 'Шарипов', 'Ильсурович', '2025-04-11 06:03:25', 0),
(24, 'Ilnar_N', '$2y$10$i.wGv7sTTMn/bkwpgUdPWOPjdTSvLoYreaoO6UM.OWHtQa0rAjS/u', 'Ильнар', 'Набиуллин', 'Габдуллович', '2025-04-11 06:07:57', 0),
(25, 'Peter_M', '$2y$10$LSFUXYNj27nvqXs3JlGzPex2L7mOZoOrnVDl2VntgIfgW3Wj74gBK', 'Пётр', 'Милых', 'Сергеевич', '2025-04-11 08:09:28', 0),
(26, 'Vladislav_L', '$2y$10$BbUKJURfoMGurVCCgw35SuDj52Dt9AQ9rQLO0g9qZF3fJjIcyv42S', 'Владислав', 'Литвинов', 'Глебович', '2025-04-11 08:12:59', 1),
(29, 'Andrey_B', '$2y$10$NIHuY50U3m6EdXrGVik/u.dBNCevLxdWum4dS4oZtTsz3TQWSv2fq', 'Андрей', 'Боев', 'Игоревич', '2025-04-13 08:03:51', 1),
(31, 'Sonilev', '$2y$10$42lYUD9HMuuZRd3amJ.gkeFTScBl0ppiSHphLuYJ7h7xa/fKhRgU2', 'Семен', 'Смирнов', 'Георгиевич', '2025-06-10 07:50:24', 0),
(32, 'Smirnov_SF', '$2y$10$E5uHsYFtxC3GJYWklqnAhe6VZeLiXky8b2/wQLAUqYxyP/SeY0nFS', 'Сергей', 'Смирнов', 'Федорович', '2025-06-10 11:43:17', 0),
(33, 'Saptarov_A', '$2y$10$s9FbDXK1j3CEGjBs2FaemetwigrtxD0Ke8Mf0ShT9wTyJfy7yvgfW', 'Артур', 'Саптаров', 'Ринатович', '2025-06-10 11:51:59', 0),
(34, 'Ziyazetdinov_A', '$2y$10$9wVjODQZjmmyuxVkyFP7.uNLpssnS.pW2XLenxPzBp4lpC0Ng0ckC', 'Альберт', 'Зиязетдинов', 'Халимович', '2025-06-10 19:10:26', 0),
(35, 'Sultanov_A', '$2y$10$xowxBCoelwEbhXnMXsKfw.fZCTGNjTnP14U7vpH4Kk4fGSjab1V1W', 'Алмаз', 'Султанов', 'Музавирович', '2025-06-17 06:21:51', 0),
(36, 'Legotin_L', '1c5c9eaca5c184d6e075cc3be3a91fb2', 'Леонид', 'Леготин', 'Георгиевич', '2025-06-17 06:24:20', 0),
(37, 'Sharipov_R', '$2y$10$e3ts.N7vHD7SS9daPYW8EuJMiabjc8HvJZBkfgIp6qRSme9zOxodC', 'Роман', 'Шарипов', 'Ильсурович', '2025-06-17 06:40:07', 0),
(38, 'Maganavetdinov_D', '$2y$10$PXrDRwGlfwciHF2fsKISoOTencbjlA5VpJD1OCeQ6B7KUthgEfLnK', 'Денис', 'Маганаветдинов', 'Равилевич', '2025-06-17 06:45:59', 0),
(39, 'Kryukov_D', '$2y$10$RiSe/3iCAXZzfNe7C.jPruwaVH03pZlKVlup7NnbHpiSU1IKYaFLS', 'Дмитрий', 'Крюков', 'Валерьевич', '2025-06-17 10:59:12', 0);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `additional_data`
--
ALTER TABLE `additional_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `device_composition`
--
ALTER TABLE `device_composition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Индексы таблицы `device_composition_history`
--
ALTER TABLE `device_composition_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Индексы таблицы `device_history`
--
ALTER TABLE `device_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Индексы таблицы `device_identification`
--
ALTER TABLE `device_identification`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `inspection_data`
--
ALTER TABLE `inspection_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_receiver_read` (`receiver_id`,`is_read`);

--
-- Индексы таблицы `production_discontinuation`
--
ALTER TABLE `production_discontinuation`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `repair_data`
--
ALTER TABLE `repair_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_repair_data_creator_idx` (`creator_id`);

--
-- Индексы таблицы `repair_data_history`
--
ALTER TABLE `repair_data_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `repair_data_id` (`repair_data_id`),
  ADD KEY `changed_by_user_id` (`changed_by_user_id`);

--
-- Индексы таблицы `termination_data`
--
ALTER TABLE `termination_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `serial_case_idx` (`serial_number`,`case_number`);

--
-- Индексы таблицы `testing_data`
--
ALTER TABLE `testing_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tolerances_autonomous`
--
ALTER TABLE `tolerances_autonomous`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parameter_frequency` (`parameter`,`frequency`);

--
-- Индексы таблицы `tolerances_cartographer`
--
ALTER TABLE `tolerances_cartographer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parameter_frequency` (`parameter`,`frequency`);

--
-- Индексы таблицы `tolerances_lwd`
--
ALTER TABLE `tolerances_lwd`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parameter_frequency` (`parameter`,`frequency`);

--
-- Индексы таблицы `uploaded_files`
--
ALTER TABLE `uploaded_files`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `additional_data`
--
ALTER TABLE `additional_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `device_composition`
--
ALTER TABLE `device_composition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT для таблицы `device_composition_history`
--
ALTER TABLE `device_composition_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `device_history`
--
ALTER TABLE `device_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `device_identification`
--
ALTER TABLE `device_identification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `inspection_data`
--
ALTER TABLE `inspection_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `production_discontinuation`
--
ALTER TABLE `production_discontinuation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `repair_data`
--
ALTER TABLE `repair_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `repair_data_history`
--
ALTER TABLE `repair_data_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `termination_data`
--
ALTER TABLE `termination_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `testing_data`
--
ALTER TABLE `testing_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `tolerances_autonomous`
--
ALTER TABLE `tolerances_autonomous`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tolerances_cartographer`
--
ALTER TABLE `tolerances_cartographer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tolerances_lwd`
--
ALTER TABLE `tolerances_lwd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT для таблицы `uploaded_files`
--
ALTER TABLE `uploaded_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `device_composition`
--
ALTER TABLE `device_composition`
  ADD CONSTRAINT `device_composition_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `device_identification` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `repair_data`
--
ALTER TABLE `repair_data`
  ADD CONSTRAINT `fk_repair_data_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `repair_data_history`
--
ALTER TABLE `repair_data_history`
  ADD CONSTRAINT `fk_repair_data_history_to_repair_data` FOREIGN KEY (`repair_data_id`) REFERENCES `repair_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_repair_history_to_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
