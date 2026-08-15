-- Интеграция с приложением «Визуализация данных».
--
-- Скрипт необязателен: обе структуры создаются кодом при первом обращении
-- (ApiTokenService::ensureTable и проверка колонки sha256 при загрузке файла).
-- Он нужен, если структуру предпочтительнее подготовить заранее, до первого
-- запроса приложения.

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
ALTER TABLE `uploaded_files` ADD COLUMN `sha256` char(64) DEFAULT NULL;
ALTER TABLE `uploaded_files` ADD INDEX `idx_sha256` (`sha256`);

-- Уже загруженные файлы контрольной суммы не имеют. Проставить её можно
-- только чтением содержимого, поэтому существующие строки остаются с NULL:
-- повторная загрузка такого файла будет распознана после первой загрузки
-- его новой копии через приложение.
