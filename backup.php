<?php
session_start();

// Подключаем сервис безопасности
require_once __DIR__ . '/src/Service/SecurityService.php';
use App\Service\SecurityService;

// Проверка прав администратора
SecurityService::requireAdmin();

// Установка безопасных заголовков
SecurityService::setSecurityHeaders();

// Rate limiting для бэкапов (не чаще 1 раза в 5 минут)
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!SecurityService::checkRateLimit($clientIP, 'backup', 1, 300)) {
    die('Слишком частые запросы на создание бэкапа. Попробуйте через 5 минут.');
}

require_once 'config.php';
require_once 'db_connection.php';

// Логируем попытку создания бэкапа
SecurityService::logSecurityEvent('backup_attempt', [
    'user_id' => $_SESSION['user']['id'],
    'username' => $_SESSION['user']['lastname']
]);

try {
    // Безопасное создание бэкапа через PHP (без system())
    $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Создаем бэкап через PHP-код вместо system()
    if (createBackupWithPHP($conn, $backupFile)) {
        // Проверяем, что файл создался и не пустой
        if (file_exists($backupFile) && filesize($backupFile) > 0) {
            // Логируем успешное создание бэкапа
            SecurityService::logSecurityEvent('backup_success', [
                'file' => $backupFile,
                'size' => filesize($backupFile)
            ]);
            
            // Отправка заголовков для скачивания файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
            header('Content-Length: ' . filesize($backupFile));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            // Очищаем буфер вывода
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Читаем и отправляем файл
            readfile($backupFile);
            
            // Удаляем файл после скачивания
            unlink($backupFile);
            exit;
        } else {
            throw new Exception('Файл бэкапа не создан или пуст');
        }
    } else {
        throw new Exception('Ошибка создания бэкапа');
    }
} catch (Exception $e) {
    // Логируем ошибку
    SecurityService::logSecurityEvent('backup_error', [
        'error' => $e->getMessage()
    ]);
    
    // Удаляем файл если он был создан
    if (isset($backupFile) && file_exists($backupFile)) {
        unlink($backupFile);
    }
    
    die('Ошибка создания резервной копии: ' . htmlspecialchars($e->getMessage()));
}

/**
 * Безопасное создание бэкапа через PHP без использования system()
 */
function createBackupWithPHP($conn, $backupFile) {
    try {
        // Получаем список всех таблиц
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        if (!$result) {
            throw new Exception('Не удалось получить список таблиц: ' . $conn->error);
        }
        
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        // Начинаем формировать бэкап
        $backup = "-- MySQL Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Host: " . DB_HOST . "\n";
        $backup .= "-- Database: " . DB_NAME . "\n\n";
        $backup .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $backup .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $backup .= "SET AUTOCOMMIT = 0;\n";
        $backup .= "START TRANSACTION;\n\n";
        
        foreach ($tables as $table) {
            // Экранируем имя таблицы
            $safeTable = $conn->real_escape_string($table);
            
            // Получаем структуру таблицы
            $result = $conn->query("SHOW CREATE TABLE `$safeTable`");
            if (!$result) {
                continue; // Пропускаем таблицу при ошибке
            }
            
            $row = $result->fetch_row();
            $backup .= "\n-- Structure for table `$safeTable`\n";
            $backup .= "DROP TABLE IF EXISTS `$safeTable`;\n";
            $backup .= $row[1] . ";\n\n";
            
            // Получаем данные таблицы
            $result = $conn->query("SELECT * FROM `$safeTable`");
            if ($result && $result->num_rows > 0) {
                $backup .= "-- Data for table `$safeTable`\n";
                
                // Получаем информацию о столбцах
                $fields = $result->fetch_fields();
                $fieldNames = [];
                foreach ($fields as $field) {
                    $fieldNames[] = "`{$field->name}`";
                }
                
                // Сбрасываем указатель результата
                $result->data_seek(0);
                
                while ($row = $result->fetch_assoc()) {
                    $backup .= "INSERT INTO `$safeTable` (" . implode(', ', $fieldNames) . ") VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $backup .= implode(', ', $values) . ");\n";
                }
                $backup .= "\n";
            }
        }
        
        $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $backup .= "COMMIT;\n";
        
        // Записываем бэкап в файл
        return file_put_contents($backupFile, $backup) !== false;
        
    } catch (Exception $e) {
        error_log('Backup error: ' . $e->getMessage());
        return false;
    }
}
?>
