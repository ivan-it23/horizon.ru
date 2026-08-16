-- Интеграция с приложением «Визуализация данных».
--
-- Выполняется один раз перед первым использованием загрузки из приложения.
--
-- Таблицу api_tokens код создаёт и сам при первом обращении — она пустая и
-- лёгкая. А вот колонку sha256 код намеренно не добавляет: при ALTER TABLE
-- MySQL пересоздаёт таблицу целиком, а в uploaded_files лежат файлы приборов,
-- и на время операции нужно вдвое больше места на диске. Внутри обычного
-- запроса такое выполнять нельзя, поэтому колонка добавляется отсюда — в
-- спокойный момент и с контролем свободного места.

-- Токены доступа. Хранится только SHA-256 токена: исходное значение
-- показывается пользователю один раз при создании и в базу не попадает.
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `token_prefix` varchar(16) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `usage_window_start` datetime DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Контрольная сумма файла — защита от повторной загрузки того же содержимого.
-- Заполняется обоими путями: веб-формой и API приложения.
--
-- Колонка и индекс добавляются ОДНИМ запросом намеренно: каждый ALTER TABLE
-- пересоздаёт таблицу целиком, а в uploaded_files лежат файлы приборов.
-- Два отдельных запроса означали бы две полные перестройки вместо одной.
--
-- Перед запуском стоит убедиться, что колонки ещё нет:
--   SHOW COLUMNS FROM `uploaded_files` LIKE 'sha256';
-- Если строка вернулась — этот запрос уже выполнялся, повторять не нужно.
ALTER TABLE `uploaded_files`
  ADD COLUMN `sha256` char(64) DEFAULT NULL,
  ADD INDEX `idx_sha256` (`sha256`);

-- Уже загруженные файлы контрольной суммы не имеют. Проставить её можно
-- только чтением содержимого, поэтому существующие строки остаются с NULL:
-- повторная загрузка такого файла будет распознана после первой загрузки
-- его новой копии через приложение.
