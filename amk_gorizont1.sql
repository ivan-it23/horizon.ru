-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 05 2025 г., 12:38
-- Версия сервера: 8.0.12
-- Версия PHP: 7.2.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `amk_gorizont1`
--

-- --------------------------------------------------------

--
-- Структура таблицы `additional_data`
--

CREATE TABLE `additional_data` (
  `id` int(11) NOT NULL,
  `additional_date` date DEFAULT NULL,
  `additional_content` text,
  `creator_surname` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `device_composition`
--

INSERT INTO `device_composition` (`id`, `device_id`, `board_name`, `type`, `version`, `serial_number`, `creator_id`, `creator_name`, `created_at`) VALUES
(5, 1, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS', '2', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(6, 1, 'Транзитная', 'укороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(7, 1, 'AL_V3', 'Сбора и хранения данных (Олег)', '3', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(8, 1, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(9, 1, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника', '2', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(10, 1, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(11, 2, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS (неуменьшенная)', '2', '-', 7, 'Администратор', '2025-06-02 08:33:52'),
(12, 2, 'Транзитная', 'неукороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 08:33:52'),
(13, 2, 'AL_V1.0', 'Сбора и хранения данных (старая плата, неуменьшенная))', '1', '-', 7, 'Администратор', '2025-06-02 08:33:52'),
(14, 2, '1DDS', 'АЦП, DDS', '3', '-', 7, 'Администратор', '2025-06-02 08:33:52'),
(15, 2, 'ПРМ-ЛВД-1DDS', 'УВЧ приёмника (неуменьшенная)', '1', '-', 7, 'Администратор', '2025-06-02 08:33:52'),
(16, 2, 'Передатчик', 'уменьшенная плата (источник 6В на плате)', '-', '-', 7, 'Администратор', '2025-06-02 08:33:52'),
(17, 3, 'Контроллер', 'rvr-(#*) ed', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', NULL, NULL, '2025-06-02 16:15:25'),
(18, 5, 'ydjydytjt', 'vgrrvgrvgr;c,r-24532098', 'crr0rm9v09m3--34535940', 'c4093-4-44', 7, 'Администратор', '2025-06-02 17:00:01'),
(37, 6, 'Контроллер', 'gggg', '3453ecvhhyhtet', 'авпыекипипи', 7, 'Администратор', '2025-06-05 09:35:33'),
(38, 6, 'акип', 'апип', '123', 'кпик', 7, 'Администратор', '2025-06-05 09:35:33');

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
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `device_composition_history`
--

INSERT INTO `device_composition_history` (`id`, `device_id`, `board_name`, `type`, `version`, `serial_number`, `creator_id`, `creator_name`, `changed_at`) VALUES
(1, 1, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS', '2', '-', 7, 'Администратор', '2025-06-02 08:04:34'),
(2, 1, 'Транзитная', 'укороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 08:04:34'),
(3, 1, 'УВЧ приёмника', 'ПРН-ЛВД-1DDS', '2', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(4, 1, 'Транзитная', 'укороченная несимметричная', '2', '-', 7, 'Администратор', '2025-06-02 08:08:57'),
(5, 6, 'Контроллер', 'vv', '55t5v5vv56u65v', 'vt53tv5vv5', 7, 'Администратор', '2025-06-03 19:15:58'),
(6, 6, '', '', '', '', 7, 'Администратор', '2025-06-03 19:15:58'),
(7, 6, 'Контроллер', 'vv', '55t5v5vv56u65v', 'vt53tv5vv5', 7, 'Администратор', '2025-06-04 16:28:04'),
(8, 6, 'Контроллер', 'vv', '55t5v5vv56u65v', 'vt53tv5vv5', 7, 'Администратор', '2025-06-04 19:14:35'),
(9, 6, 'Контроллер', 'yyyyy', '222222', 'vt53tv5vv5', 7, 'Администратор', '2025-06-04 19:14:51'),
(10, 6, 'Контроллер', 'yyyyy', '222222', 'vt53tv5vv5', 7, 'Администратор', '2025-06-04 19:15:27'),
(11, 6, 'gkfg', '1', '1', '1', 7, 'Администратор', '2025-06-04 19:15:27'),
(12, 6, 'Контроллер', 'yyyyy', '222222', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 06:52:16'),
(13, 6, 'Контроллер', 'yyyyy', '222222', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 08:43:08'),
(14, 6, 'Контроллер', '4', '222222', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 08:43:21'),
(15, 6, 'Контроллер', '4', '566666', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 08:54:09'),
(16, 6, 'Контроллер', '4', '566666', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 09:15:27'),
(17, 6, 'вереер', 'укуруре', 'укруф', 'укрук', 7, 'Администратор', '2025-06-05 09:15:27'),
(18, 6, 'Контроллер', '4', '566666', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 09:23:09'),
(19, 6, 'вереер', '789', 'укруф', 'укрук', 7, 'Администратор', '2025-06-05 09:23:09'),
(20, 6, 'Контроллер', '4', '566666', 'vt53tv5vv5', 7, 'Администратор', '2025-06-05 09:34:12'),
(21, 6, 'Контроллер', 'gggg', '3453ecvhhyhtet', 'авпыекипипи', 7, 'Администратор', '2025-06-05 09:35:33'),
(22, 6, 'акип', 'апип', 'пипи', 'кпик', 7, 'Администратор', '2025-06-05 09:35:33');

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
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `transmitter_base1_mm` varchar(255) DEFAULT NULL,
  `transmitter_base2_mm` varchar(255) DEFAULT NULL,
  `transmitter_base3_mm` varchar(255) DEFAULT NULL,
  `transmitter_base4_mm` varchar(255) DEFAULT NULL,
  `transmitter_base5_mm` varchar(255) DEFAULT NULL,
  `additional_info` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `device_history`
--

INSERT INTO `device_history` (`history_id`, `device_id`, `device_type`, `nominal_diameter`, `serial_number`, `case_number`, `length_mm`, `diameter_mm`, `upper_thread`, `lower_thread`, `frequency1_khz`, `frequency2_khz`, `receiver_base1_mm`, `receiver_base2_mm`, `receiver_base3_mm`, `record_date`, `creator_lastname`, `changed_at`, `transmitter_base1_mm`, `transmitter_base2_mm`, `transmitter_base3_mm`, `transmitter_base4_mm`, `transmitter_base5_mm`, `additional_info`) VALUES
(1, 1, 'LWD', NULL, '109', '008-2024', '4200.00', '122.00', 'mk-90', 'mk-90', '399.00', '1960.00', '1', NULL, NULL, '2025-06-02', 'Администратор', '2025-06-02 08:04:34', '1', '1', '1', '1', NULL, NULL),
(2, 1, 'LWD', NULL, '109', '008-2024', '4200.00', '122.00', 'mk-90', 'mk-90', '399.00', '1960.00', '254', NULL, NULL, '2025-06-02', 'Администратор', '2025-06-02 08:08:57', '471', '848', '1228', '1600', NULL, NULL),
(3, 2, 'LWD', NULL, '108', '005-2024', '1.00', '1.00', '1', '1', '1.00', '1.00', '1', NULL, NULL, '2025-06-02', 'Администратор', '2025-06-02 08:33:52', '1', '1', '1', '1', NULL, NULL),
(4, 3, 'Автономный', NULL, 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', '', NULL, NULL, '2025-06-03', 'Администратор', '2025-06-02 16:19:51', '0', '0', '0', NULL, NULL, NULL),
(5, 3, 'Автономный', '90', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', NULL, NULL, 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', NULL, NULL, NULL, NULL, NULL, '2025-06-03', 'Администратор', '2025-06-02 16:52:23', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 5, 'Автономный', '90', '1', '1', '1', '1', '1', '1', '1', '1', '1', NULL, NULL, '2025-06-03', 'Администратор', '2025-06-02 16:53:56', '1', '1', '1', NULL, NULL, NULL),
(7, 5, 'Автономный', '90', '1ее', '1еее', '1ай44', '1ймц4е', '1ймцей', '1мй', '1мй', '1мфкепм', '1кумпку', NULL, NULL, '2025-06-03', 'Администратор', '2025-06-02 16:59:19', '1мцу', '1мцук', '1мукумк', NULL, NULL, NULL),
(8, 5, 'Автономный', '90', '1ее', '1еее', '1ай44', '1ймц4е', '1ймцей', 'fgtrttg-6565', '-5666u6', '1мфкепм', '1кумпку', NULL, NULL, '2025-06-03', 'Администратор', '2025-06-02 17:00:01', '1мцу', '1мцук', '1мукумк', NULL, NULL, NULL),
(9, 6, 'Картограф', '90', 'укку', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-03 19:15:58', 'hthh', 'reere', 'wgarw', NULL, NULL, NULL),
(10, 6, 'Картограф', '90', 'укку', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-03 19:21:54', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, NULL),
(11, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:11:41', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(12, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:12:11', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(13, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:31:17', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(14, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:31:23', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(15, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', 'укrgt', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:31:32', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(16, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', '12345', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:41:17', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(17, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', '12345', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:44:59', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, 'gioktjg[wigji945i234-04cmk0c=k=403t14icc[4'),
(18, 6, 'Картограф', '90', 'кеек', 'укекек', 'куке', '12345', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 08:53:37', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, '1234'),
(19, 6, 'Картограф', '90', 'кеек', '123', 'куке', '12345', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 16:27:53', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, '1234'),
(20, 6, 'Картограф', '90', 'кеек', '123', 'куке', '12345', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 16:28:04', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, '567'),
(21, 6, 'Картограф', '90', 'кеек', '123', 'куке', '12345', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 19:14:19', '56щщшшд-fsdytj', 'reere', 'wgarw', NULL, NULL, '1234555'),
(22, 6, 'Картограф', '90', 'кеек', '123', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 19:14:35', 'yyyyy', 'reere', '55555', NULL, NULL, 'ftrhehthe'),
(23, 6, 'Картограф', '90', 'кеек', '123', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 19:14:51', 'yyyyy', 'reere', '55555', NULL, NULL, 'ftrhehthe'),
(24, 6, 'Картограф', '90', 'кеек', '123', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 19:15:27', 'yyyyy', 'reere', '55555', NULL, NULL, 'ftrhehthe'),
(25, 6, 'Картограф', '90', 'кеек', '123', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-04 19:19:41', 'yyyyy', 'reere', '55555', NULL, NULL, 'ftrhehthe'),
(26, 6, 'Картограф', '90', 'кеек', '123', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 06:50:42', 'yyyyy', 'reere', '55555', NULL, NULL, '2344'),
(27, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 06:52:16', 'yyyyy', 'reere', '55555', NULL, NULL, '2344'),
(28, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 08:43:08', 'yyyyy', 'reere', '55555', NULL, NULL, 'цуцеккуек'),
(29, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 08:43:21', 'yyyyy', 'reere', '55555', NULL, NULL, 'цуцеккуек'),
(30, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', 'rehthr', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 08:44:30', 'yyyyy', 'reere', '55555', NULL, NULL, 'цуцеккуек'),
(31, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', '123', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 08:54:09', 'yyyyy', 'reere', '55555', NULL, NULL, 'кпцкшопцкшпщкпжпшкпкшпо\nкпакужщпцкпок\nпйктпжкпщто'),
(32, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', '123', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 09:15:27', 'yyyyy', 'reere', '55555', NULL, NULL, 'кпцкшопцкшпщкпжпшкпкшпо\nкпакужщпцкпок\nпйктпжкпщто'),
(33, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', '123', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 09:16:28', 'yyyyy', 'reere', '55555', NULL, NULL, 'кпцкшопцкшпщкпжпшкпкшпо\nкпакужщпцкпок\nпйктпжкпщто'),
(34, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', '123', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 09:23:09', '567-rtiotreo', 'reere', '55555', NULL, NULL, 'кпцкшопцкшпщкпжпшкпкшпо\nкпакужщпцкпок\nпйктпжкпщто'),
(35, 6, 'Картограф', '90', '77774каа', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', '123', 'rehsrth', '2025-06-02', 'Администратор', '2025-06-05 09:34:12', '567-rtiotreo', 'reere', '55555', NULL, NULL, 'кпцкшопцкшпщкпжпшкпкшпо\nкпакужщпцкпок\nпйктпжкпщто'),
(36, 6, 'Картограф', '90', '25sdtttt', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', 'erhet', '123', 'tht', '2025-06-02', 'Администратор', '2025-06-05 09:35:33', '567-rtiotreo', 'reere', '25', NULL, NULL, 'рejofiwefewqfw;e;fedk;w92[930029343825459902nc9mpfc;lksdfjfc;cfmsdfj;cmlkssfj,lep,4-04');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `receiver_base1_mm` varchar(255) DEFAULT NULL,
  `receiver_base2_mm` varchar(255) DEFAULT NULL,
  `receiver_base3_mm` varchar(255) DEFAULT NULL,
  `changed_at` datetime DEFAULT NULL,
  `transmitter_base1_mm` varchar(255) DEFAULT NULL,
  `transmitter_base2_mm` varchar(255) DEFAULT NULL,
  `transmitter_base3_mm` varchar(255) DEFAULT NULL,
  `transmitter_base4_mm` varchar(255) DEFAULT NULL,
  `transmitter_base5_mm` varchar(255) DEFAULT NULL,
  `additional_info` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `device_identification`
--

INSERT INTO `device_identification` (`id`, `device_type`, `nominal_diameter`, `transmitter_count`, `serial_number`, `case_number`, `length_mm`, `diameter_mm`, `upper_thread`, `lower_thread`, `frequency1_khz`, `frequency2_khz`, `record_date`, `creator_lastname`, `created_at`, `receiver_base1_mm`, `receiver_base2_mm`, `receiver_base3_mm`, `changed_at`, `transmitter_base1_mm`, `transmitter_base2_mm`, `transmitter_base3_mm`, `transmitter_base4_mm`, `transmitter_base5_mm`, `additional_info`) VALUES
(1, 'LWD', '120', '4', '109', '008-2024', '4200.00', '122.00', 'mk-90', 'mk-90', '399.00', '1960.00', '2025-06-02', 'Администратор', '2025-06-02 08:03:22', '254', NULL, NULL, '2025-06-02 11:08:57', '471', '848', '1228', '1600', NULL, NULL),
(2, 'LWD', '120', '4', '108', '005-2024', '3920.00', '120.00', 'mk-90', 'mk-90', '399.00', '1960.00', '2025-06-02', 'Администратор', '2025-06-02 08:23:33', '254', NULL, NULL, '2025-06-02 11:33:51', '446', '824', '1202', '1580', NULL, NULL),
(3, 'Автономный', '90', '3', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'ук', 'цуцу', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'тест-45ghl#$(*&0834.13/^#^@ВУАШГОВЦWERUKJd', 'цу', 'уц', '2025-06-03', 'Администратор', '2025-06-02 16:15:25', NULL, NULL, NULL, '2025-06-02 19:52:23', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Автономный', '90', '3', '1ее', '1еее', '1ай44', '1ймц4е', '1ймцей', 'fgtrttg-6565', '-5666u6', '1мфкепм', '2025-06-03', 'Администратор', '2025-06-02 16:53:24', '1кумпку', NULL, NULL, '2025-06-02 20:00:01', '1мцу', '1мцук', '1мукумк', NULL, NULL, NULL),
(6, 'Картограф', '90', '3', '25sdtttt', 'йцуукк', 'куке', 'ццццц', 'ergtte', 'erstht', 'rhstwe', 'erhwt', '2025-06-02', 'Администратор', '2025-06-02 17:48:33', '4566', '123', 'tht', '2025-06-05 12:35:33', '567-rtiotreo', 'reere', '25', NULL, NULL, 'рejofiwefewqfw;e;fedk;w92[930029343825459902nc9mpfc;lksdfjfc;cfmsdfj;cmlkssfj,lep,4-04');

-- --------------------------------------------------------

--
-- Структура таблицы `inspection_data`
--

CREATE TABLE `inspection_data` (
  `id` int(11) NOT NULL,
  `inspection_date` date DEFAULT NULL,
  `inspection_result` text,
  `creator_surname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = не прочитано, 1 = прочитано',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `production_discontinuation`
--

INSERT INTO `production_discontinuation` (`id`, `discontinuation_date`, `creator_surname`, `serial_number`, `case_number`, `created_at`) VALUES
(1, '2025-04-05', ' Администратор ', '109', '008-2024', '2025-06-02 08:10:55'),
(2, '2024-01-01', ' Администратор ', '108', '005-2024', '2025-06-02 08:34:57');

-- --------------------------------------------------------

--
-- Структура таблицы `repair_data`
--

CREATE TABLE `repair_data` (
  `id` int(11) NOT NULL,
  `repair_type` varchar(255) DEFAULT NULL,
  `repair_reason` text,
  `failure_reason` text,
  `admission_date` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `actions_description` text,
  `repair_creator` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `repair_data`
--

INSERT INTO `repair_data` (`id`, `repair_type`, `repair_reason`, `failure_reason`, `admission_date`, `release_date`, `actions_description`, `repair_creator`, `created_at`, `serial_number`, `case_number`) VALUES
(1, 'мелкий', 'В ходе испытаний мокрого индуктора произошёл выход из строя 4-ого передатчика', 'Сгорел выходной мост индуктора', '2025-04-15', '2025-06-02', 'Замена платы 4-ого передатчика. Настройка метрологии. Поверка на кольцах, прогрев. ', ' Администратор ', '2025-06-02 08:14:25', '109', '008-2024'),
(2, 'мелкий', 'Отказал на скважине при попытке считать данные. Были сохранены данные предыдущей скважины.', 'Короткое замыкание по цепи батареи.', '2025-05-10', '2025-06-02', 'Ремонт транзитной платы №2, замена верхних экранов передающих индукторов, прогрев кольца.', ' Администратор ', '2025-06-02 08:40:39', '108', '005-2024');

-- --------------------------------------------------------

--
-- Структура таблицы `termination_data`
--

CREATE TABLE `termination_data` (
  `id` int(11) NOT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` text,
  `creator_surname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `serial_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `testing_data`
--

CREATE TABLE `testing_data` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `creator` varchar(255) NOT NULL,
  `temperature_test` text,
  `vibration_test` text,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `uploaded_files`
--

INSERT INTO `uploaded_files` (`id`, `field_name`, `file_name`, `file_data`, `serial_number`, `case_number`, `device_type`, `created_at`) VALUES
(1, 'metrology', 'metro__lwd_009_(0082024).bin', 0x71ad9374684001005801d4024c04c7050000e400000000005402d0034805c3060000e400b03ab3008f01a807accbe40060ec8f004a01e0028403bc028a02bc022201c8008200320000000000b03ab30068cce40090dccd00b9b01e7700000000888435745f000100040f090000000080fbf06fe4bcec8f00bcec8f00788435747f843574e583d196e46adb02ccec8f006c7ee30258b8b1004300000098843574a47b357488843574a47b357459d1ceb0603e337494ed8f00ccec8f00accbe400b03ab3000100000000800000a20a0700fcffffff90dccd00000000000000000050ec8f00a4ee8f0088ea4b74ffffffff, '109', '008-2024', 'LWD', '2025-06-02 11:15:52'),
(2, 'metrology', 'lwd_109_raport.rtf', 0x7b5c727466315c616e73695c616e7369637067313235315c64656666305c6465666c616e67313034397b5c666f6e7474626c7b5c66305c666e696c5c6663686172736574323034204d6963726f736f66742053616e732053657269663b7d7b5c66315c666e696c5c666368617273657430204d6963726f736f66742053616e732053657269663b7d7d0d0a7b5c636f6c6f7274626c203b5c7265643235355c677265656e305c626c7565303b5c726564305c677265656e305c626c7565303b5c726564305c677265656e3132385c626c7565303b5c7265643235355c677265656e3235355c626c7565303b5c7265643133395c677265656e305c626c7565303b7d0d0a5c766965776b696e64345c7563315c706172645c6366315c625c66305c667334305c2763665c2766305c2765655c2766325c2765655c2765615c2765655c276562205c2765665c2766305c2765655c2765325c2765355c2766305c2765615c276538205c2765665c2766305c2765385c2765315c2765655c2766305c276530205c2764645c2763635c2763612d3930205c66315c75383437303f2038323032345c7061720d0a5c6366325c62305c66305c667332305c7061720d0a205c7061720d0a205c2764305c2765305c2766315c2766375c2765355c276632205c2763645c2763355c2766315c2765385c2765635c2765635c2765355c2766325c2766305c2765385c2765375c2765655c2765325c2765305c2765645c2765645c2766625c276635205c2766345c2765305c276537205c276538205c2764335c2764645c276431205c2765645c276530205c2765615c2765655c2765635c2765665c2766635c2766655c2766325c2765355c2766305c276535205c7061720d0a5c6366305c625c667332345c7061720d0a20205c2763375c2763655c2763645c27633420202020202020205c2763665c2765655c2765335c2766305c2765355c2766385c2765645c2765655c2766315c2766325c2766632025205c7061720d0a5c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f2031202035205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663320545f312020203339396b477a20202020202020202020202020322c305c7061720d0a545f322020203339396b477a202020202020202020202020202d312c305c7061720d0a545f332020203339396b477a202020202020202020202020202d312c385c7061720d0a545f342020203339396b477a202020202020202020202020202d322c385c7061720d0a545f35202020313936306b477a202020202020202020202020202d332c305c7061720d0a545f36202020313936306b477a202020202020202020202020202d342c305c7061720d0a545f37202020313936306b477a202020202020202020202020202d342c305c7061720d0a5c63663420545f38202020313936306b477a202020202020202020202020202d31312c305c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f203220203230205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663320545f312020203339396b477a20202020202020202020202020372c325c7061720d0a545f322020203339396b477a20202020202020202020202020312c305c7061720d0a545f332020203339396b477a20202020202020202020202020302c345c7061720d0a545f342020203339396b477a202020202020202020202020202d302c345c7061720d0a545f35202020313936306b477a20202020202020202020202020332c305c7061720d0a545f36202020313936306b477a202020202020202020202020202d312c305c7061720d0a545f37202020313936306b477a202020202020202020202020202d312c305c7061720d0a545f38202020313936306b477a202020202020202020202020202d332c305c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f203320203530205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663420545f312020203339396b477a2020202020202020202020202031302c365c7061720d0a5c63663320545f322020203339396b477a20202020202020202020202020332c305c7061720d0a545f332020203339396b477a20202020202020202020202020322c345c7061720d0a545f342020203339396b477a20202020202020202020202020302c385c7061720d0a5c63663420545f35202020313936306b477a2020202020202020202020202031312c305c7061720d0a5c63663320545f36202020313936306b477a20202020202020202020202020302c305c7061720d0a545f37202020313936306b477a202020202020202020202020202d342c305c7061720d0a545f38202020313936306b477a202020202020202020202020202d352c325c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f20342020313030205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663420545f312020203339396b477a2020202020202020202020202031332c305c7061720d0a5c63663320545f322020203339396b477a20202020202020202020202020332c365c7061720d0a545f332020203339396b477a20202020202020202020202020332c365c7061720d0a545f342020203339396b477a20202020202020202020202020322c385c7061720d0a5c63663420545f35202020313936306b477a2020202020202020202020202031322c305c7061720d0a5c63663320545f36202020313936306b477a20202020202020202020202020342c305c7061720d0a545f37202020313936306b477a20202020202020202020202020322c305c7061720d0a545f38202020313936306b477a20202020202020202020202020322c325c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f20352020343330205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663320545f312020203339396b477a20202020202020202020202020392c345c7061720d0a545f322020203339396b477a20202020202020202020202020322c345c7061720d0a545f332020203339396b477a20202020202020202020202020362c385c7061720d0a545f342020203339396b477a20202020202020202020202020332c385c7061720d0a5c63663420545f35202020313936306b477a2020202020202020202020202031322c305c7061720d0a5c63663320545f36202020313936306b477a20202020202020202020202020342c365c7061720d0a545f37202020313936306b477a20202020202020202020202020332c325c7061720d0a545f38202020313936306b477a20202020202020202020202020332c385c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f20362020383230205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663420545f312020203339396b477a202020202020202020202020202d31312c305c7061720d0a5c63663320545f322020203339396b477a20202020202020202020202020332c325c7061720d0a5c63663120545f332020203339396b477a202020202020202020202020202d32312c305c7061720d0a5c63663320545f342020203339396b477a20202020202020202020202020352c305c7061720d0a5c63663420545f35202020313936306b477a2020202020202020202020202031322c385c7061720d0a5c63663320545f36202020313936306b477a20202020202020202020202020342c325c7061720d0a545f37202020313936306b477a20202020202020202020202020342c305c7061720d0a545f38202020313936306b477a20202020202020202020202020342c305c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f2037202031353030205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663120545f312020203339396b477a2020202020202020202020202032352c305c7061720d0a5c63663320545f322020203339396b477a20202020202020202020202020342c305c7061720d0a545f332020203339396b477a20202020202020202020202020332c365c7061720d0a545f342020203339396b477a202020202020202020202020202d352c385c7061720d0a5c63663420545f35202020313936306b477a2020202020202020202020202031342c305c7061720d0a5c63663320545f36202020313936306b477a20202020202020202020202020342c385c7061720d0a545f37202020313936306b477a20202020202020202020202020342c305c7061720d0a545f38202020313936306b477a20202020202020202020202020352c345c7061720d0a5c6366325c7061720d0a205c2763635c2765355c2766305c276530205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c276530205c66315c75383437303f20382020352c3035313039452d3339205c66305c2763655c27656320205c7061720d0a5c7061720d0a5c63663120545f312020203339396b477a202020202020202020202020202d32352c345c7061720d0a5c63663320545f322020203339396b477a20202020202020202020202020332c365c7061720d0a5c63663420545f332020203339396b477a202020202020202020202020202d31392c385c7061720d0a5c63663520545f342020203339396b477a202020202020202020202020202d33342c385c7061720d0a5c63663420545f35202020313936306b477a2020202020202020202020202031332c305c7061720d0a5c63663320545f36202020313936306b477a20202020202020202020202020342c305c7061720d0a545f37202020313936306b477a20202020202020202020202020332c385c7061720d0a5c63663420545f38202020313936306b477a2020202020202020202020202031302c365c7061720d0a5c6366325c7061720d0a5c7061720d0a5c7061720d0a5c6366305c2763375c2765305c2765615c2765625c2766655c2766375c2765355c2765645c2765385c276535205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5c7061720d0a205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5c7061720d0a205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5c7061720d0a205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5c7061720d0a5c276434205c276338205c2763652c205c2765345c2765655c2765625c2765365c2765645c2765655c2766315c2766325c2766632c205c2765665c2765655c2765345c2765665c2765385c2766315c2766635f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f3238205c2763635c2765305c2766662032303235205c2765332e203133203a33315c7061720d0a7d0d0a00, '109', '008-2024', 'LWD', '2025-06-02 11:16:15'),
(3, 'metrology', 'metro__lwd_008_(005_2024).bin', 0xd4ad038038cb00003e01ba023204b70500000f7689e5886e3c02b8033005b50600000e76565d11058f01a8078400000000000000f3017703ecff38ffa6ff88ff5500c4ffd8ff00000000000001000000000000000f4f0e761800000000000000160a0400010000000cec8f006603000098ec8f0090350f76a9e5886efeffffff88ec8f00afbd0e76207a63011c322077c7bd0e7670ec8f001800000000000000b03ab30001000000000000000000000000000000000000000800000066f00e76d0ec8f00118b83670c800000160a0400fcffffff0000000059d1ceb0f446337494ed8f001000000094ec8f0000000000, '108', '005-2024', 'LWD', '2025-06-02 11:42:04');
INSERT INTO `uploaded_files` (`id`, `field_name`, `file_name`, `file_data`, `serial_number`, `case_number`, `device_type`, `created_at`) VALUES
(5, 'calibration', 'lwd_108_raport.rtf', 0x7b5c727466315c616465666c616e67313032355c616e73695c616e7369637067313235315c7563315c616465666633313530375c64656666305c73747368666462636833313530355c73747368666c6f636833313530365c73747368666869636833313530365c7374736866626933313530375c6465666c616e67313034395c6465666c616e676665313034395c7468656d656c616e67313034395c7468656d656c616e676665305c7468656d656c616e676373307b5c666f6e7474626c7b5c66305c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d7b5c6633345c6662696469205c66726f6d616e5c6663686172736574315c66707271327b5c2a5c70616e6f73652030323034303530333035303430363033303230347d43616d62726961204d6174683b7d0d0a7b5c6633395c6662696469205c6673776973735c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323062303630343032303230323032303230347d4d6963726f736f66742053616e732053657269663b7d7b5c666c6f6d616a6f725c6633313530305c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d0d0a7b5c6664626d616a6f725c6633313530315c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d7b5c6668696d616a6f725c6633313530325c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323034303530333035303430363033303230347d43616d627269613b7d0d0a7b5c6662696d616a6f725c6633313530335c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d7b5c666c6f6d696e6f725c6633313530345c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d0d0a7b5c6664626d696e6f725c6633313530355c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d7b5c6668696d696e6f725c6633313530365c6662696469205c6673776973735c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323066303530323032303230343033303230347d43616c696272693b7d0d0a7b5c6662696d696e6f725c6633313530375c6662696469205c66726f6d616e5c66636861727365743230345c66707271327b5c2a5c70616e6f73652030323032303630333035303430353032303330347d54696d6573204e657720526f6d616e3b7d7b5c6634325c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d7b5c6634305c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d0d0a7b5c6634335c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d7b5c6634345c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d7b5c6634355c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d7b5c6634365c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d0d0a7b5c6634375c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d7b5c6634385c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d7b5c663433325c6662696469205c6673776973735c6663686172736574305c6670727132204d6963726f736f66742053616e732053657269663b7d7b5c663433305c6662696469205c6673776973735c66636861727365743233385c6670727132204d6963726f736f66742053616e732053657269662043453b7d0d0a7b5c663433335c6662696469205c6673776973735c66636861727365743136315c6670727132204d6963726f736f66742053616e7320536572696620477265656b3b7d7b5c663433345c6662696469205c6673776973735c66636861727365743136325c6670727132204d6963726f736f66742053616e73205365726966205475723b7d7b5c663433355c6662696469205c6673776973735c66636861727365743137375c6670727132204d6963726f736f66742053616e732053657269662028486562726577293b7d0d0a7b5c663433365c6662696469205c6673776973735c66636861727365743137385c6670727132204d6963726f736f66742053616e732053657269662028417261626963293b7d7b5c663433375c6662696469205c6673776973735c66636861727365743138365c6670727132204d6963726f736f66742053616e732053657269662042616c7469633b7d7b5c663433385c6662696469205c6673776973735c66636861727365743136335c6670727132204d6963726f736f66742053616e732053657269662028566965746e616d657365293b7d0d0a7b5c663433395c6662696469205c6673776973735c66636861727365743232325c6670727132204d6963726f736f66742053616e73205365726966202854686169293b7d7b5c666c6f6d616a6f725c6633313531305c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d7b5c666c6f6d616a6f725c6633313530385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d0d0a7b5c666c6f6d616a6f725c6633313531315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d7b5c666c6f6d616a6f725c6633313531325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d7b5c666c6f6d616a6f725c6633313531335c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d0d0a7b5c666c6f6d616a6f725c6633313531345c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d7b5c666c6f6d616a6f725c6633313531355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d7b5c666c6f6d616a6f725c6633313531365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d0d0a7b5c6664626d616a6f725c6633313532305c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d7b5c6664626d616a6f725c6633313531385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d7b5c6664626d616a6f725c6633313532315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d0d0a7b5c6664626d616a6f725c6633313532325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d7b5c6664626d616a6f725c6633313532335c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d7b5c6664626d616a6f725c6633313532345c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d0d0a7b5c6664626d616a6f725c6633313532355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d7b5c6664626d616a6f725c6633313532365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d7b5c6668696d616a6f725c6633313533305c6662696469205c66726f6d616e5c6663686172736574305c66707271322043616d627269613b7d0d0a7b5c6668696d616a6f725c6633313532385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322043616d627269612043453b7d7b5c6668696d616a6f725c6633313533315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322043616d6272696120477265656b3b7d7b5c6668696d616a6f725c6633313533325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322043616d62726961205475723b7d0d0a7b5c6668696d616a6f725c6633313533355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322043616d627269612042616c7469633b7d7b5c6668696d616a6f725c6633313533365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322043616d627269612028566965746e616d657365293b7d7b5c6662696d616a6f725c6633313534305c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d0d0a7b5c6662696d616a6f725c6633313533385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d7b5c6662696d616a6f725c6633313534315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d7b5c6662696d616a6f725c6633313534325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d0d0a7b5c6662696d616a6f725c6633313534335c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d7b5c6662696d616a6f725c6633313534345c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d7b5c6662696d616a6f725c6633313534355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d0d0a7b5c6662696d616a6f725c6633313534365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d7b5c666c6f6d696e6f725c6633313535305c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d7b5c666c6f6d696e6f725c6633313534385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d0d0a7b5c666c6f6d696e6f725c6633313535315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d7b5c666c6f6d696e6f725c6633313535325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d7b5c666c6f6d696e6f725c6633313535335c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d0d0a7b5c666c6f6d696e6f725c6633313535345c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d7b5c666c6f6d696e6f725c6633313535355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d7b5c666c6f6d696e6f725c6633313535365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d0d0a7b5c6664626d696e6f725c6633313536305c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d7b5c6664626d696e6f725c6633313535385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d7b5c6664626d696e6f725c6633313536315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d0d0a7b5c6664626d696e6f725c6633313536325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d7b5c6664626d696e6f725c6633313536335c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d7b5c6664626d696e6f725c6633313536345c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d0d0a7b5c6664626d696e6f725c6633313536355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d7b5c6664626d696e6f725c6633313536365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d7b5c6668696d696e6f725c6633313537305c6662696469205c6673776973735c6663686172736574305c66707271322043616c696272693b7d0d0a7b5c6668696d696e6f725c6633313536385c6662696469205c6673776973735c66636861727365743233385c66707271322043616c696272692043453b7d7b5c6668696d696e6f725c6633313537315c6662696469205c6673776973735c66636861727365743136315c66707271322043616c6962726920477265656b3b7d7b5c6668696d696e6f725c6633313537325c6662696469205c6673776973735c66636861727365743136325c66707271322043616c69627269205475723b7d0d0a7b5c6668696d696e6f725c6633313537335c6662696469205c6673776973735c66636861727365743137375c66707271322043616c696272692028486562726577293b7d7b5c6668696d696e6f725c6633313537345c6662696469205c6673776973735c66636861727365743137385c66707271322043616c696272692028417261626963293b7d7b5c6668696d696e6f725c6633313537355c6662696469205c6673776973735c66636861727365743138365c66707271322043616c696272692042616c7469633b7d0d0a7b5c6668696d696e6f725c6633313537365c6662696469205c6673776973735c66636861727365743136335c66707271322043616c696272692028566965746e616d657365293b7d7b5c6662696d696e6f725c6633313538305c6662696469205c66726f6d616e5c6663686172736574305c66707271322054696d6573204e657720526f6d616e3b7d7b5c6662696d696e6f725c6633313537385c6662696469205c66726f6d616e5c66636861727365743233385c66707271322054696d6573204e657720526f6d616e2043453b7d0d0a7b5c6662696d696e6f725c6633313538315c6662696469205c66726f6d616e5c66636861727365743136315c66707271322054696d6573204e657720526f6d616e20477265656b3b7d7b5c6662696d696e6f725c6633313538325c6662696469205c66726f6d616e5c66636861727365743136325c66707271322054696d6573204e657720526f6d616e205475723b7d7b5c6662696d696e6f725c6633313538335c6662696469205c66726f6d616e5c66636861727365743137375c66707271322054696d6573204e657720526f6d616e2028486562726577293b7d0d0a7b5c6662696d696e6f725c6633313538345c6662696469205c66726f6d616e5c66636861727365743137385c66707271322054696d6573204e657720526f6d616e2028417261626963293b7d7b5c6662696d696e6f725c6633313538355c6662696469205c66726f6d616e5c66636861727365743138365c66707271322054696d6573204e657720526f6d616e2042616c7469633b7d7b5c6662696d696e6f725c6633313538365c6662696469205c66726f6d616e5c66636861727365743136335c66707271322054696d6573204e657720526f6d616e2028566965746e616d657365293b7d7d0d0a7b5c636f6c6f7274626c3b5c726564305c677265656e305c626c7565303b5c726564305c677265656e305c626c75653235353b5c726564305c677265656e3235355c626c75653235353b5c726564305c677265656e3235355c626c7565303b5c7265643235355c677265656e305c626c75653235353b5c7265643235355c677265656e305c626c7565303b5c7265643235355c677265656e3235355c626c7565303b5c7265643235355c677265656e3235355c626c75653235353b5c726564305c677265656e305c626c75653132383b5c726564305c677265656e3132385c626c75653132383b5c726564305c677265656e3132385c626c7565303b0d0a5c7265643132385c677265656e305c626c75653132383b5c7265643132385c677265656e305c626c7565303b5c7265643132385c677265656e3132385c626c7565303b5c7265643132385c677265656e3132385c626c75653132383b5c7265643139325c677265656e3139325c626c75653139323b5c7265643133395c677265656e305c626c7565303b7d7b5c2a5c646566636870205c667332325c6c6f63685c616633313530365c686963685c616633313530365c646263685c61663331353035207d7b5c2a5c646566706170205c716c205c6c69305c7269305c73613230305c736c3237365c736c6d756c74310d0a5c77696463746c7061725c7772617064656661756c745c617370616c7068615c6173706e756d5c66616175746f5c61646a75737472696768745c72696e305c6c696e305c6974617030207d5c6e6f716670726f6d6f7465207b5c7374796c6573686565747b5c716c205c6c69305c7269305c73613230305c736c3237365c736c6d756c74315c77696463746c7061725c7772617064656661756c745c617370616c7068615c6173706e756d5c66616175746f5c61646a75737472696768745c72696e305c6c696e305c6974617030205c72746c63685c66637331205c616633313530375c61667332325c616c616e6731303235200d0a5c6c747263685c66637330205c667332325c6c616e67313034395c6c616e676665313034395c6c6f63685c6633313530365c686963685c616633313530365c646263685c616633313530355c63677269645c6c616e676e70313034395c6c616e6766656e7031303439205c736e65787430205c7371666f726d6174205c737072696f7269747930204e6f726d616c3b7d7b5c2a5c63733130205c6164646974697665205c7373656d6968696464656e205c73756e6869646575736564205c737072696f72697479312044656661756c742050617261677261706820466f6e743b7d7b5c2a0d0a5c747331315c7473726f77645c7472667473576964746842335c7472706164646c3130385c747270616464723130385c747270616464666c335c7472706164646674335c7472706164646662335c7472706164646672335c74726362706174315c74726366706174315c74626c696e64305c74626c696e6474797065335c747363656c6c7769647468667473305c747376657274616c745c747362726472745c7473627264726c5c747362726472625c747362726472725c74736272647264676c5c7473627264726467725c747362726472685c74736272647276205c716c205c6c69305c7269305c73613230305c736c3237365c736c6d756c74310d0a5c77696463746c7061725c7772617064656661756c745c617370616c7068615c6173706e756d5c66616175746f5c61646a75737472696768745c72696e305c6c696e305c6974617030205c72746c63685c66637331205c616633313530375c61667332325c616c616e6731303235205c6c747263685c66637330205c667332325c6c616e67313034395c6c616e676665313034395c6c6f63685c6633313530365c686963685c616633313530365c646263685c616633313530355c63677269645c6c616e676e70313034395c6c616e6766656e7031303439200d0a5c736e6578743131205c7373656d6968696464656e205c73756e6869646575736564205c7371666f726d6174204e6f726d616c205461626c653b7d7d7b5c2a5c7273696474626c205c72736964313634333930355c7273696431333139313635307d7b5c6d6d61746850725c6d6d617468466f6e7433345c6d62726b42696e305c6d62726b42696e537562305c6d736d616c6c46726163305c6d64697370446566315c6d6c4d617267696e305c6d724d617267696e305c6d6465664a63315c6d77726170496e64656e74313434305c6d696e744c696d305c6d6e6172794c696d317d7b5c696e666f0d0a7b5c6f70657261746f722067656c7473656e40676d61696c2e636f6d7d7b5c6372656174696d5c7972323032355c6d6f355c647932385c687231325c6d696e34347d7b5c72657674696d5c7972323032355c6d6f355c647932385c687231325c6d696e34357d7b5c76657273696f6e337d7b5c65646d696e73317d7b5c6e6f667061676573337d7b5c6e6f66776f7264733237327d7b5c6e6f666368617273323439367d7b5c6e6f6663686172737773323736337d7b5c7665726e33323835397d7d7b5c2a5c786d6c6e7374626c207b5c786d6c6e733120687474703a2f2f736368656d61732e6d6963726f736f66742e636f6d2f6f66666963652f776f720d0a642f323030332f776f72646d6c7d7d5c70617065727731323234305c70617065726831353834305c6d6172676c313730315c6d617267723835305c6d61726774313133345c6d61726762313133345c677574746572305c6c747273656374200d0a5c7769646f776374726c5c66746e626a5c61656e64646f635c747261636b6d6f766573315c747261636b666f726d617474696e67315c646f6e6f74656d626564737973666f6e74305c72656c796f6e766d6c305c646f6e6f74656d6265646c696e6764617461315c677266646f636576656e7473305c76616c6964617465786d6c305c73686f77706c616365686f6c6474657874305c69676e6f72656d69786564636f6e74656e74305c73617665696e76616c6964786d6c305c73686f77786d6c6572726f7273305c686f727a646f635c64676873706163653132305c64677673706163653132305c6467686f726967696e313730310d0a5c6467766f726967696e313938345c64676873686f77305c64677673686f77335c6a636f6d70726573735c766965776b696e64315c766965777363616c653137305c72736964726f6f7431363433393035205c666574307b5c2a5c77677266666d7466696c74657220323435307d5c696c666f6d61636174636c6e7570305c6c7472706172205c7365637464205c6c7472736563745c6c696e6578305c7365637464656661756c74636c5c7366746e626a207b5c2a5c706e7365636c766c315c706e7563726d5c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e74787461202e7d7d7b5c2a5c706e7365636c766c320d0a5c706e75636c74725c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e74787461202e7d7d7b5c2a5c706e7365636c766c335c706e6465635c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e74787461202e7d7d7b5c2a5c706e7365636c766c345c706e6c636c74725c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e7478746120297d7d7b5c2a5c706e7365636c766c355c706e6465635c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e7478746220287d7b5c706e7478746120297d7d7b5c2a5c706e7365636c766c360d0a5c706e6c636c74725c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e7478746220287d7b5c706e7478746120297d7d7b5c2a5c706e7365636c766c375c706e6c63726d5c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e7478746220287d7b5c706e7478746120297d7d7b5c2a5c706e7365636c766c385c706e6c636c74725c706e7374617274315c706e696e64656e743732305c706e68616e67207b5c706e7478746220287d7b5c706e7478746120297d7d7b5c2a5c706e7365636c766c395c706e6c63726d5c706e7374617274315c706e696e64656e743732305c706e68616e67200d0a7b5c706e7478746220287d7b5c706e7478746120297d7d5c706172645c706c61696e205c6c74727061725c716c205c6c69305c7269305c6e6f77696463746c7061725c7772617064656661756c745c66616175746f5c72696e305c6c696e305c6974617030205c72746c63685c66637331205c616633313530375c61667332325c616c616e6731303235205c6c747263685c66637330205c667332325c6c616e67313034395c6c616e676665313034395c6c6f63685c616633313530365c686963685c616633313530365c646263685c616633313530355c63677269645c6c616e676e70313034395c6c616e6766656e7031303439207b0d0a5c72746c63685c66637331205c61625c616633395c6166733430205c6c747263685c66637330205c625c6633395c667334305c6366365c696e73727369643133313931363530205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2763665c2766305c2765655c2766325c2765655c2765615c2765655c2765625c6c6f63685c663339205c686963685c66333920205c2765665c2766305c2765655c2765325c2765355c2766305c2765615c2765385c6c6f63685c663339205c686963685c66333920205c2765665c2766305c2765385c2765315c2765655c2766305c2765305c6c6f63685c663339200d0a5c686963685c66333920205c2764645c2763635c2763612d7d7b5c72746c63685c66637331205c61625c616633395c6166733430205c6c747263685c66637330205c625c6633395c667334305c6366365c696e7372736964313634333930355c63686172727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c663339203132307d7b5c72746c63685c66637331205c61625c616633395c6166733430205c6c747263685c66637330205c625c6633395c667334305c6366365c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c663339200d0a205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339202035323032340d0a5c706172207d7b5c72746c63685c66637331205c616633395c6166733230205c6c747263685c66637330205c6633395c667332305c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2764305c2765305c2766315c2766375c2765355c2766325c6c6f63685c663339205c686963685c66333920205c2763645c2763357d7b5c72746c63685c66637331205c616633395c6166733230205c6c747263685c66637330205c6633395c667332305c6366315c696e737273696431333139313635305c63686172727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920207d7b5c72746c63685c66637331205c616633395c6166733230205c6c747263685c66637330200d0a5c6633395c667332305c6366315c696e73727369643133313931363530205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2766315c2765385c2765635c2765635c2765355c2766325c2766305c2765385c2765375c2765655c2765325c2765305c2765645c2765645c2766625c2766355c6c6f63685c663339205c686963685c66333920205c2766345c2765305c2765375c6c6f63685c663339205c686963685c66333920205c2765385c6c6f63685c663339205c686963685c66333920205c2764335c2764645c2764315c6c6f63685c663339205c686963685c66333920205c2765645c2765300d0a5c6c6f63685c663339205c686963685c66333920205c2765615c2765655c2765635c2765665c2766635c2766655c2766325c2765355c2766305c2765355c6c6f63685c66333920200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c6633392020205c2763375c2763655c2763645c2763345c6c6f63685c663339205c686963685c6633392020202020202020205c2763665c2765655c2765335c2766305c2765355c2766385c2765645c2765655c2766315c2766325c2766635c6c6f63685c663339202025200d0a5c706172200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c663339202031202035205c2763655c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a20202020202020202020202020332c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a202020202020202020202020202d322c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a202020202020202020202020202d342c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a202020202020202020202020202d342c360d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a202020202020202020202020202d322c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a202020202020202020202020202d342c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a202020202020202020202020202d342c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a202020202020202020202020202d31312c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920203220203230205c2763655c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a20202020202020202020202020372c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a20202020202020202020202020302c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a5c686963685c616633395c646263685c616633313530355c6c6f63685c66333920202020202020202020202020202d312c360d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a202020202020202020202020202d322c380d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a20202020202020202020202020342c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a202020202020202020202020202d322c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a202020202020202020202020202d332c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a202020202020202020202020202d382c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920203320203530205c2763655c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a20202020202020202020202020392c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a20202020202020202020202020322c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a20202020202020202020202020302c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a202020202020202020202020202d302c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a2020202020202020202020202031302c380d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a202020202020202020202020202d312c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a202020202020202020202020202d372c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366365c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a202020202020202020202020202d31332c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c6633392020342020313030205c2763655c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b475c686963685c616633395c646263685c616633313530355c6c6f63685c663339207a20202020202020202020202020382c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a20202020202020202020202020322c320d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a202020202020202020202020202d302c320d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a202020202020202020202020202d312c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a2020202020202020202020202031312c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a20202020202020202020202020312c380d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a202020202020202020202020202d302c320d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a202020202020202020202020202d342c380d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c6633392020352020343330205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2763655c2765630d0a5c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a20202020202020202020202020372c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a202020202020202020202020202d302c320d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a20202020202020202020202020302c320d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a20202020202020202020202020322c380d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366365c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a2020202020202020202020202031322c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a20202020202020202020202020322c380d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a20202020202020202020202020302c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a202020202020202020202020202d332c320d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c6633392020362020383230205c2763650d0a5c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a20202020202020202020202020312c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a202020202020202020202020202d362c320d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a20202020202020202020202020392c360d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a202020202020202020202020202d392c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366365c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a2020202020202020202020202031322c320d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a20202020202020202020202020312c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a202020202020202020202020202d372c360d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a2020202020202020202020205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920202d372c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c663339202037202031353030205c2763655c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a202020202020202020202020202d392c360d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a20202020202020202020202020302c380d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a202020202020202020202020202d31302c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366365c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a2020202020202020202020202032372c320d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a2020202020202020202020202031382c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a20202020202020202020202020332c360d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a20202020202020202020202020312c300d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f382020205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920313936306b477a20202020202020202020202020312c380d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c66333920205c2763635c2765355c2766305c2765305c6c6f63685c663339205c686963685c66333920205c2765385c2765635c2765665c2765355c2765345c2765305c2765645c2766315c2765305c6c6f63685c66333920205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2762395c686963685c616633395c646263685c616633313530355c6c6f63685c663339205c686963685c663339202038202030205c2763655c2765635c6c6f63685c6633392020200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f312020203339396b477a2020202020202020202020202031352c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f322020203339396b477a202020202020202020202020202d372c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f332020203339396b477a202020202020202020202020202d31372c300d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f342020203339396b477a2020202020202020202020202034372c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366375c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f35202020313936306b477a2020202020202020202020202031382c340d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c636631315c696e73727369643133313931363530205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f36202020313936306b477a20202020202020202020202020362c340d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f37202020313936306b477a202020202020202020202020202d302c380d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920545f38202020313936306b477a202020202020202020202020202d392c360d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c6366315c696e73727369643133313931363530200d0a5c706172200d0a5c706172200d0a5c706172207d7b5c72746c63685c66637331205c61625c616633395c6166733234205c6c747263685c66637330205c625c6633395c667332345c696e73727369643133313931363530205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2763375c2765305c2765615c2765625c2766655c2766375c2765355c2765645c2765385c2765355c6c6f63685c66333920205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f0d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f0d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920205f5f5f5f5f5f5f5f5f5f5f5f5f5f5c686963685c616633395c646263685c616633313530355c6c6f63685c663339205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f0d0a5c706172205c686963685c616633395c646263685c616633313530355c6c6f63685c66333920205f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f0d0a5c706172205c6c6f63685c616633395c646263685c616633313530355c686963685c663339205c2764345c6c6f63685c663339205c686963685c66333920205c2763385c6c6f63685c663339205c686963685c66333920205c2763655c6c6f63685c663339205c686963685c663339202c205c2765345c2765655c2765625c2765365c2765645c2765655c2766315c2766325c2766635c6c6f63685c663339205c686963685c663339202c205c2765665c2765655c2765345c2765665c2765385c2766315c2766635c6c6f63685c663339205c686963685c663339200d0a5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f5f3238205c2763635c2765305c2766665c6c6f63685c663339205c686963685c663339202032303235205c2765335c6c6f63685c663339202e20315c686963685c616633395c646263685c616633313530355c6c6f63685c6633392032203a34320d0a5c706172207d7b5c2a5c7468656d65646174612035303462303330343134303030363030303830303030303032313030383238616263313366613030303030303163303230303030313330303030303035623433366636653734363536653734356635343739373036353733356432653738366436636163393163623661633333303130343566373835666538336430623664380d0a37326261323861356438636561323439373737643263643230663138653462313264366138663834333430396339646637376563623835306261303832643734323331303632636539393762353561653866653361303065313839336633353465393535356536383835363437646533613861626634666265653239626264370d0a32613331353030333833323761636634303939333565643764373537653565653134333032393939613635346539396533393363313839333663386632336134646330373234373936393764316338316535316133623133633037653430383765366236323865653863663563343438396366316334643037356639326130620d0a34346437613037613833633832663330386163376230613066306662663930633234383039383062353861626337333336313561613264323130633265303263623034343330303736613765653833336466623663653632653365643765313436393365383331376438636430343333626635633630663533666561326665370d0a30363562643830666163623634376539653235633766633432316664326464623532366232653933373366656434626239303265313832653937623762343631653662666164336630313030303066666666303330303530346230333034313430303036303030383030303030303231303061356436613765376330303030300d0a30303336303130303030306230303030303035663732363536633733326632653732363536633733383438666366366163333330306338376566383562643833643137643531643263333138323537363266613539303433326661333764303065313238376636383232316264623162656264623466633730363061626230380d0a38346134656666376139336466656165386266396531393465373230313639616161303663336532343333666362363865313736336462663766383263393835613461373235303835623738373038366133376264626235356662633530643161333363636433313162613534386236333039353132306638386439346662630d0a35326165343236346431633931306432346134356462333436323234376661373931373135666437316639383965313965303336346364336635313635326437333736306165386661386339666662336333333063633965346663313766616632636535343530343665333739343463363965343632613161383266653335330d0a62643930613836356161643431656430623562386639643666643031303030306666666630333030353034623033303431343030303630303038303030303030323130303662373939363136383330303030303038613030303030303163303030303030373436383635366436353266373436383635366436353266373436380d0a36353664363534643631366536313637363537323265373836643663306363633464306163333230313034306531376461313737393064393337363362623238343536326232636261656262663630303433396331613431633761306432396664626437653565333833333763656466313464353962346230643539326339630d0a30373064386136356364326538386237663037633263613731626138646134383163633532633663653163373135653665393738313863396234386431336466343963383733353137643233643539303835616462356464323064366235326264353231656632636464356562393234366133643862343735376538643366370d0a32396532343565623262323630613032333866643031303030306666666630333030353034623033303431343030303630303038303030303030323130306130626331386336623530363030303038333162303030303136303030303030373436383635366436353266373436383635366436353266373436383635366436350d0a33313265373836643663656335396366366531623435313862663233663130656133626462376231313333623864613333383535656364383064623436396133643832646561373162633165656634653333626262333961313932376635616434613866343832303434343131636138303435633338323032303532386262380d0a62346566653033653433613030383861643435376530396239393564376232376465393061343864613038326536313037623637376664666666336666336364373865356632646438386131356432323234653537316464326235663263373938386334336565666433333861383762333762626564306234623165393230610d0a63373764636337383463656164653838343865666632656162626566616365303635313539323838323061303866653533326165376261313532633966326463396366343631313963623862336332313331626331623730313136313035386632323938656230626263303737633233333633373566326132646365343539380d0a63363165386137313034366363376466386337663161336631393166613031623833303166353839623739616631366633313130313232626139313737633236336139613362633938386265376562363366336531383366316433663165316633636262303764663966633265376337383662366266353364363134373232340d0a39623463613035646363656131653838656566336264326562396162336363346230353466306132656539356363396633376237626133323837393735333232613638656131636464316235636435663461393731326634373765363864346331316634323634326362656434616564643266613834626630313330333538620d0a36626235356163643536373963326366303062306566383365353536393733636366346137626139646363383738653634306636656232636566363661393561616162386638316366663835313939643662386434366133356134623735623134633064633837656164636365303937346138623935623537393037366634300d0a31363566396463313537316136626364653661323833333732303862356639636331623732666435313632623265646538303432343665333964313962343065363862623964373239663430303639633664313463323937303062653534346165313533313436346333323464626234383830313866643536393733326663320d0a37376238363830333831323636343538643131386139353134323036643838373434366665326138323732386436303266313332633162393337373663393937333334623561333639326265613038396161376265663237313838613636636165666535393365663566336537393834306566373166316665656666376337380d0a66666665653166653866393639313433623538316533323034666635653264623466666537633738306666646631653861623137306633653262633663623363666564373166336566636535653961376335343032386137613933616366336633666638656466316331663332663365666166646262303730356630333538310d0a37623739373839373436343461326562363430663664663330383063333335653731333532373364373133363861366538383639396536323264306532343865623139363532633062666135343230373764376438343539316131643437386630363731336437383462343033623239303235653139646537313134656538340d0a36326138363838316534616236316534303033373339363730643265306162643730353563626361623962393362386338333632653136323938633736643633626335623234626238393633323762656164363130323764333534623462633766303636343831633335623731383865313530653438346331346432656666380d0a30653231303564366464613664346631656232366635303539373763613064303664386131613938313662616134346237623465333634643839333636383034373131393135643930636631373637636233373930623335333832626232376139646563626134386138306163633061393465663132653662386631306131650d0a32613163313562316563653238386535316437653064616262303438633963653438663837393563346232613838373434303138343761643365393162323838653638363030376237333431626638616131383331353836376439333864323231373239313464643239653237393064373339653437616566333964363638380d0a61336134303864626131373139386337626532373737323034353331646165326161303862656339646430616431636631303037316331663162656535623934333865313365623931626463613438316133643233343431663439626131643062313834643665643734653038386336376664373865313938353765366337330d0a65306663646133313334633065373566336532636338616333376235313161666331396535343534303931623437646165663731623861333464623763393435396662656639336437373164306665333264303236393365626266316263366462393666356261656637396636666239633764356633363931626564623462370d0a34326462643537333833316439326364633831633964376136323165353063363361366163346338333536393836363630396662343662663064386239613866333935303932633938393261303965313662646165373164356332306230613134313832616230666138306133623231346536306530326537623961343932300d0a35336436383134343039393737306630333363623835626333353165383637363635386638643535376461306230666434313632623563396662373637393431326636376537383630393162623366623034653662303961303935616430306334653262366365313532636131346363376531353631363561646434613961350d0a39353864366161366635333964323236323634333463363734643833633538393337363132303431333063363830393731376531343861663435633334313035333364326437376562373762373131363136313338356633306339313063373139666134333164323736636663366138366338323934653538616239333938300d0a64633239383839313365303439656530623539636234396136366662316164323465313361346263623863613331653262326538626434653934623230633965343634396437663139313732363437316265333835393863663665613565616433613566663539303866393362613337383033333265376338643132383862610d0a64343333323036363031646332356639346164386234336662313938346439353466613335396362306337333862613030636437313864366566333330363362376432303131353261643633313964616434333061666432313436306231393636346635396661663832356263666362303039626539616661306335633231320d0a32346333626661363035663864313064326431393063383861666632633163656164363864666439633762343935663261313232613231336636663735303866306463353336383666306562353430353762666135346332353538356539303866613031656564396234623763643262623733396137343539376266646433320d0a33386262386535393132653262346464656131326364326164396332346431643466373433303466333966356330623634326464386437313637333763353934666333393939393234666533666639393239376133663831396238333835626538653830306633376266303232333564616637353866306231353732653834320d0a34393438666462363830343163326634306563383136623861623835643739303534373066663663336530356439643539666236653632633066353364363730303035346462333434303832633237656134343234316338313662343235393337643237333032626137376239373635633935323436323661333732656163610d0a63346161646432336262383437353735306635636434376262623837343234383735643334646432333636303730343766336366376434653262613831376538323132373566366634653066393965636264623630366665653963396337313633333138653566363631333364303634666539666138353862306162356137610d0a34333965656462643739343366343862653939383535633961613032383465356236383235613561663661666138633231396237356164626231363632633965616636366361343131343637326438366335633934303934633066643066643266663630666661336332363766363937306362646131373666393336663435360d0a30343366343436383636393033363930643531376563653038313734383362343862336431383963656361323464323663646361626133363164396462346437623263646661396332376464383964633233636564363961396432366465363737346636363433383733633533396235373839656365346533646563663864610d0a61653164656236613838656364313132383561353431373662303331383133316266383265353766613565326264336231306538373562386566316633323235346433326331366634653032633365386439333137353030633536663235316164326435626630303030303066666666303330303530346230333034313430300d0a30363030303830303030303032313030306464313930396662363030303030303162303130303030323730303030303037343638363536643635326637343638363536643635326635663732363536633733326637343638363536643635346436313665363136373635373232653738366436633265373236353663373338340d0a38663464306163323330313438346637383237373038366636666433626131303931323664643838643061646434303338346534333530643336336632343531656365643064616532633038326538373631626539393639626239373964633931333633333264653331363861613161303833616539393537313961633136640d0a62386563386534303532313634653839643933623634623036303832386536663337656431353637393134623238346432363234353232383265333139383732306532373461393339636430386135346639383061653338613338663536653432326133613634316338626264303438663737353764613066313962303137630d0a63353234626436323130376264353030313939363530396166666233666433383161383936373266316631363564666535313431373364393835303532386132633663636530323339626161346330346361356262616261633464663030303030306666666630333030353034623031303232643030313430303036303030380d0a30303030303032313030383238616263313366613030303030303163303230303030313330303030303030303030303030303030303030303030303030303030303030303030356234333666366537343635366537343566353437393730363537333564326537383664366335303462303130323264303031343030303630300d0a30383030303030303231303061356436613765376330303030303030333630313030303030623030303030303030303030303030303030303030303030303030326230313030303035663732363536633733326632653732363536633733353034623031303232643030313430303036303030383030303030303231303036620d0a37393936313638333030303030303861303030303030316330303030303030303030303030303030303030303030303030303134303230303030373436383635366436353266373436383635366436353266373436383635366436353464363136653631363736353732326537383664366335303462303130323264303031340d0a30303036303030383030303030303231303061306263313863366235303630303030383331623030303031363030303030303030303030303030303030303030303030303030643130323030303037343638363536643635326637343638363536643635326637343638363536643635333132653738366436633530346230310d0a303232643030313430303036303030383030303030303231303030646431393039666236303030303030316230313030303032373030303030303030303030303030303030303030303030303030626130393030303037343638363536643635326637343638363536643635326635663732363536633733326637343638363536643635346436313665363136373635373232653738366436633265373236353663373335303462303530363030303030303030303530303035303035643031303030306235306130303030303030307d0d0a7b5c2a5c636f6c6f72736368656d656d617070696e672033633366373836643663323037363635373237333639366636653364323233313265333032323230363536653633366636343639366536373364323235353534343632643338323232303733373436313665363436313663366636653635336432323739363537333232336633653064306133633631336136333663373234640d0a36313730323037383664366336653733336136313364323236383734373437303361326632663733363336383635366436313733326536663730363536653738366436633636366637323664363137343733326536663732363732663634373236313737363936653637366436633266333233303330333632663664363136390d0a36653232323036323637333133643232366337343331323232303734373833313364323236343662333132323230363236373332336432323663373433323232323037343738333233643232363436623332323232303631363336333635366537343331336432323631363336333635366537343331323232303631363336330d0a363536653734333233643232363136333633363536653734333232323230363136333633363536653734333333643232363136333633363536653734333332323230363136333633363536653734333433643232363136333633363536653734333432323230363136333633363536653734333533643232363136333633363536653734333532323230363136333633363536653734333633643232363136333633363536653734333632323230363836633639366536623364323236383663363936653662323232303636366636633438366336393665366233643232363636663663343836633639366536623232326633657d0d0a7b5c2a5c6c6174656e747374796c65735c6c73647374696d61783236375c6c73646c6f636b6564646566305c6c736473656d6968696464656e646566315c6c7364756e6869646575736564646566315c6c736471666f726d6174646566305c6c73647072696f7269747964656639397b5c6c73646c6f636b6564657863657074205c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f7269747930205c6c73646c6f636b656430204e6f726d616c3b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720313b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720323b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720333b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720343b0d0a5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720353b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720363b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720373b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720383b5c6c736471666f726d617431205c6c73647072696f7269747939205c6c73646c6f636b6564302068656164696e6720393b0d0a5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320313b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320323b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320333b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320343b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320353b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320363b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320373b0d0a5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320383b5c6c73647072696f726974793339205c6c73646c6f636b65643020746f6320393b5c6c736471666f726d617431205c6c73647072696f726974793335205c6c73646c6f636b6564302063617074696f6e3b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793130205c6c73646c6f636b656430205469746c653b5c6c73647072696f7269747931205c6c73646c6f636b6564302044656661756c742050617261677261706820466f6e743b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793131205c6c73646c6f636b656430205375627469746c653b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793232205c6c73646c6f636b656430205374726f6e673b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793230205c6c73646c6f636b65643020456d7068617369733b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793539205c6c73646c6f636b656430205461626c6520477269643b5c6c7364756e686964657573656430205c6c73646c6f636b65643020506c616365686f6c64657220546578743b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f7269747931205c6c73646c6f636b656430204e6f2053706163696e673b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e673b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c6973743b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c6967687420477269643b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e6720313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e6720323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c69737420313b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c69737420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d204772696420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d204772696420323b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d204772696420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c6973743b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e673b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c6973743b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c20477269643b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e6720416363656e7420313b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c69737420416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c69676874204772696420416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e67203120416363656e7420313b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e67203220416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c697374203120416363656e7420313b5c6c7364756e686964657573656430205c6c73646c6f636b656430205265766973696f6e3b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793334205c6c73646c6f636b656430204c697374205061726167726170683b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793239205c6c73646c6f636b6564302051756f74653b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793330205c6c73646c6f636b65643020496e74656e73652051756f74653b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c697374203220416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d2047726964203120416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d2047726964203220416363656e7420313b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d2047726964203320416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c69737420416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e6720416363656e7420313b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c69737420416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c204772696420416363656e7420313b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e6720416363656e7420323b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c69737420416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c69676874204772696420416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e67203120416363656e7420323b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e67203220416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c697374203120416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c697374203220416363656e7420323b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d2047726964203120416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d2047726964203220416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d2047726964203320416363656e7420323b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c69737420416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e6720416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c69737420416363656e7420323b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c204772696420416363656e7420323b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e6720416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c69737420416363656e7420333b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c69676874204772696420416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e67203120416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e67203220416363656e7420333b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c697374203120416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c697374203220416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d2047726964203120416363656e7420333b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d2047726964203220416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d2047726964203320416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c69737420416363656e7420333b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e6720416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c69737420416363656e7420333b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c204772696420416363656e7420333b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e6720416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c69737420416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c69676874204772696420416363656e7420343b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e67203120416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e67203220416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c697374203120416363656e7420343b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c697374203220416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d2047726964203120416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d2047726964203220416363656e7420343b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d2047726964203320416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c69737420416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e6720416363656e7420343b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c69737420416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c204772696420416363656e7420343b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e6720416363656e7420353b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c69737420416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c69676874204772696420416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e67203120416363656e7420353b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e67203220416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c697374203120416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c697374203220416363656e7420353b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d2047726964203120416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d2047726964203220416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d2047726964203320416363656e7420353b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c69737420416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e6720416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c69737420416363656e7420353b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c204772696420416363656e7420353b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793630205c6c73646c6f636b656430204c696768742053686164696e6720416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793631205c6c73646c6f636b656430204c69676874204c69737420416363656e7420363b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793632205c6c73646c6f636b656430204c69676874204772696420416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793633205c6c73646c6f636b656430204d656469756d2053686164696e67203120416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793634205c6c73646c6f636b656430204d656469756d2053686164696e67203220416363656e7420363b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793635205c6c73646c6f636b656430204d656469756d204c697374203120416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793636205c6c73646c6f636b656430204d656469756d204c697374203220416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793637205c6c73646c6f636b656430204d656469756d2047726964203120416363656e7420363b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793638205c6c73646c6f636b656430204d656469756d2047726964203220416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793639205c6c73646c6f636b656430204d656469756d2047726964203320416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793730205c6c73646c6f636b656430204461726b204c69737420416363656e7420363b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793731205c6c73646c6f636b65643020436f6c6f7266756c2053686164696e6720416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793732205c6c73646c6f636b65643020436f6c6f7266756c204c69737420416363656e7420363b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c73647072696f726974793733205c6c73646c6f636b65643020436f6c6f7266756c204772696420416363656e7420363b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793139205c6c73646c6f636b65643020537562746c6520456d7068617369733b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793231205c6c73646c6f636b65643020496e74656e736520456d7068617369733b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793331205c6c73646c6f636b65643020537562746c65205265666572656e63653b5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793332205c6c73646c6f636b65643020496e74656e7365205265666572656e63653b0d0a5c6c736473656d6968696464656e30205c6c7364756e686964657573656430205c6c736471666f726d617431205c6c73647072696f726974793333205c6c73646c6f636b65643020426f6f6b205469746c653b5c6c73647072696f726974793337205c6c73646c6f636b656430204269626c696f6772617068793b5c6c736471666f726d617431205c6c73647072696f726974793339205c6c73646c6f636b65643020544f432048656164696e673b7d7d7b5c2a5c6461746173746f7265203031303530303030303230303030303031383030303030300d0a3464373337383664366333323265353334313538353834643463353236353631363436353732326533353265333030303030303030303030303030303030303030303036303030300d0a6430636631316530613162313161653130303030303030303030303030303030303030303030303030303030303030303365303030333030666566663039303030363030303030303030303030303030303030303030303030313030303030303031303030303030303030303030303030303130303030306665666666666666303030303030303066656666666666663030303030303030303030303030303066666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666646666666666666665666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666660d0a6666666666666666666666666666666666666666666666666666666666666666353230303666303036663030373430303230303034353030366530303734303037323030373930303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303031363030303530306666666666666666666666666666666666666666666666666563363964393838386238623364346338353965616636636431353862653066303030303030303030303030303030303030303030303030323037310d0a3839333262356366646230316665666666666666303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303066666666666666666666666666666666666666666666666630303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030300d0a3030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030666666666666666666666666666666666666666666666666303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030300d0a3030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030306666666666666666666666666666666666666666666666663030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030300d0a303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303030303130353030303030303030303030307d7d, '108', '005-2024', 'LWD', '2025-06-02 11:44:33');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `firstname`, `lastname`, `middlename`, `created_at`, `is_admin`) VALUES
(1, 'ivanov', '912e79cd13c64069d91da65d62fbb78c', 'Иван', 'Иванов', 'Сергеевич', '2025-03-04 19:48:22', 0),
(7, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', '', '', '2025-02-15 10:06:51', 1),
(8, 'petrov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Пётр', 'Петров', 'Петрович', '2025-02-15 10:11:03', 1);

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
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `termination_data`
--
ALTER TABLE `termination_data`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `device_composition`
--
ALTER TABLE `device_composition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `device_composition_history`
--
ALTER TABLE `device_composition_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `device_history`
--
ALTER TABLE `device_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT для таблицы `device_identification`
--
ALTER TABLE `device_identification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `inspection_data`
--
ALTER TABLE `inspection_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `production_discontinuation`
--
ALTER TABLE `production_discontinuation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `repair_data`
--
ALTER TABLE `repair_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `termination_data`
--
ALTER TABLE `termination_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `testing_data`
--
ALTER TABLE `testing_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `uploaded_files`
--
ALTER TABLE `uploaded_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
