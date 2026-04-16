<?php
// Определяем корневую директорию проекта для стабильных путей в include
define('ROOT_PATH', __DIR__);

// Временно логируем доменное имя для диагностики
error_log('SERVER_NAME: ' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'undefined'));
// Автоматическое определение продакшн-режима для всех боевых доменов
$is_production = strpos($_SERVER['SERVER_NAME'], 'free.nf') !== false || 
                 strpos($_SERVER['SERVER_NAME'], 'infinityfree') !== false ||
                 $_SERVER['SERVER_NAME'] === 'accounting-of-devices.free.nf' ||
                 strpos($_SERVER['SERVER_NAME'], 'beget.tech') !== false ||
                 strpos($_SERVER['SERVER_NAME'], 'amk-horizon.ru') !== false ||
                 strpos($_SERVER['SERVER_NAME'], 'amk-horizon.store') !== false;

// Конфигурационный файл для настроек базы данных
// В производственной среде эти значения должны браться из переменных окружения

// Настройки для локальной разработки (OSPanel) или продакшена
if (!$is_production) {
    // Локальные настройки для OSPanel
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'amk_gorizont1');
    
    // Локальные настройки отладки
    define('DEBUG_MODE', true);
} else {
    // Продакшн-сервер Beget
    define('DB_HOST', 'localhost'); // Для сайта на Beget
    define('DB_USERNAME', 'ivanbohg_account');
    define('DB_PASSWORD', 'btpvJ1F1aDJ*');
    define('DB_NAME', 'ivanbohg_account');
    define('DEBUG_MODE', false);
    // Параметры для удалённого доступа к БД Beget
    define('DB_REMOTE_HOST', 'ivanbohg.beget.tech');
    define('DB_REMOTE_USER', 'ivanbohg');
    define('DB_REMOTE_PASS', 'u52rfhPlUXzb');
    // Для удалённого подключения используйте DB_REMOTE_* в своих скриптах
}

// Настройки сессии
define('SESSION_TIMEOUT', 3600); // 1 час

// Настройки загрузки файлов
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// API key для polza.ai AI-агента
define('POLZA_AI_API_KEY', 'pza_gPz7Y5ou1MB_6c25f-_pd5-_Fvo_cAoK');

// Функция для логирования ошибок
function logError($message, $file = '', $line = '') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] Error: $message";
    if ($file) $logMessage .= " in $file";
    if ($line) $logMessage .= " on line $line";
    $logMessage .= PHP_EOL;

    // Убедимся, что папка logs существует
    if (!is_dir(__DIR__ . '/logs')) {
        @mkdir(__DIR__ . '/logs', 0755, true);
    }

    error_log($logMessage, 3, __DIR__ . '/logs/error.log');
}

// Создаем директорию для логов если её нет
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0755, true);
}
?>
