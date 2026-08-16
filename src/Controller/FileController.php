<?php
namespace App\Controller;

use App\Controller\BaseController;

class FileController extends BaseController
{
    /**
     * Upload file
     */
    public function upload(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для загрузки файлов (не более 20 файлов в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'file_upload', 20, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много загрузок файлов. Попробуйте через час.'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса.'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        if (!isset($_POST['serial_number'], $_POST['case_number'], $_POST['device_type'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Не все параметры были предоставлены.'], 400);
        }
        
        $serialNumber = $this->sanitizeString($_POST['serial_number']);
        $caseNumber   = $this->sanitizeString($_POST['case_number']);
        $deviceType   = $this->sanitizeString($_POST['device_type']);

        if (empty($serialNumber) || empty($caseNumber) || empty($deviceType)) {
            $this->jsonResponse(['success' => false, 'message' => 'Параметры не могут быть пустыми.'], 400);
        }

        // Валидация поля загрузки
        $allowedFields = ['thermoprofile', 'metrology', 'calibration', 'kis'];
        $uploadedField = null;
        foreach ($allowedFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $uploadedField = $field;
                break;
            }
        }
        
        if (!$uploadedField) {
            $this->jsonResponse(['success' => false, 'message' => 'Файл не был выбран или произошла ошибка при загрузке.'], 400);
        }

        // Безопасная валидация файла
        $file = $_FILES[$uploadedField];
        $fileName = $this->sanitizeString(basename($file['name']));
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Проверка размера файла
        if ($fileSize > MAX_FILE_SIZE) {
            $this->jsonResponse(['success' => false, 'message' => 'Размер файла превышает допустимый лимит (' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB).'], 400);
        }
        
        if (!file_exists($fileTmpPath)) {
            $this->jsonResponse(['success' => false, 'message' => 'Временный файл не найден.'], 400);
        }

        // Валидация типа файла по расширению - расширенный список для геофизических приборов
        // Основан на реальных данных из базы uploaded_files
        $allowedExtensions = [
            // Офисные документы
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'rtf',

            // Архивы (разрешаем загрузку архивов с данными/отчетами)
            'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz',
            
            // Изображения
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif',
            
            // Основные форматы геофизических данных (из реальной базы)
            'bin', 'dev', 'dat', 'raw', 'hex', 'cal', 'cfg', 'ini',
            'BIN', 'DEV', 'DAT', 'RAW', 'HEX', 'CAL', 'CFG', 'INI', // Заглавные варианты
            
            // Каротажные данные
            'las', 'lis', 'dlis', 'segy', 'seg', 'sgd', 'wits',
            'LAS', 'LIS', 'DLIS', 'SEGY', 'SEG', 'SGD', 'WITS',
            
            // Текстовые форматы данных
            'csv', 'log', 'ascii', 'asc', 'prn', 'out', 'res', 'rpt',
            'CSV', 'LOG', 'ASCII', 'ASC', 'PRN', 'OUT', 'RES', 'RPT',
            
            // Конфигурационные и системные файлы приборов
            'xml', 'json', 'yaml', 'yml', 'conf', 'config', 'settings',
            'XML', 'JSON', 'YAML', 'YML', 'CONF', 'CONFIG', 'SETTINGS',
            
            // Специализированные форматы приборов
            'ram', 'rom', 'eep', 'flash', 'mem', 'dump', 'trace',
            'RAM', 'ROM', 'EEP', 'FLASH', 'MEM', 'DUMP', 'TRACE',
            
            // Дополнительные форматы для геофизических исследований
            'mwd', 'lwd', 'mud', 'well', 'curve', 'depth', 'temp', 'press',
            'MWD', 'LWD', 'MUD', 'WELL', 'CURVE', 'DEPTH', 'TEMP', 'PRESS',
            
            // Форматы данных с приборов
            'metro', 'thermo', 'calib', 'kis', 'air', 'okt',
            'METRO', 'THERMO', 'CALIB', 'KIS', 'AIR', 'OKT'
        ];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Приводим все расширения к нижнему регистру для сравнения
        $allowedExtensionsLower = array_map('strtolower', $allowedExtensions);
        
        if (!in_array($fileExtension, $allowedExtensionsLower)) {
            $this->jsonResponse(['success' => false, 'message' => 'Недопустимый тип файла. Разрешены: ' . implode(', ', array_slice($allowedExtensionsLower, 0, 20)) . '...'], 400);
        }

        // Проверка MIME-типа для дополнительной безопасности
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            // Офисные документы
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/rtf',

            // Архивы
            'application/zip',
            'application/x-zip-compressed',
            'multipart/x-zip',
            'application/vnd.rar',
            'application/x-rar',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/x-gtar',
            'application/gzip',
            'application/x-gzip',
            'application/x-bzip2',
            'application/x-xz',
            
            // Текстовые файлы
            'text/plain',
            'text/csv',
            'text/xml',
            'application/xml',
            'application/json',
            'text/yaml',
            'application/yaml',
            
            // Изображения
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/tiff',
            
            // Бинарные данные приборов (часто определяются как application/octet-stream)
            'application/octet-stream',
            
            // Специализированные MIME-типы для геофизических данных
            'application/x-binary',
            'application/binary',
            'application/x-raw',
            'application/x-hex',
            'application/x-config',
            'application/x-ini',
            
            // Дополнительные типы для совместимости
            'application/force-download',
            'application/unknown',
            'binary/octet-stream'
        ];
        
        // Для геофизических файлов часто MIME-тип определяется как application/octet-stream
        // Поэтому делаем более гибкую проверку для всех специализированных форматов
        $isGeophysicalFile = in_array($fileExtension, [
            // Основные геофизические форматы
            'bin', 'dev', 'dat', 'raw', 'hex', 'cal', 'cfg', 'ini', 
            // Каротажные данные
            'las', 'lis', 'dlis', 'segy', 'seg', 'sgd', 'wits',
            // Специализированные форматы приборов
            'ram', 'rom', 'eep', 'flash', 'mem', 'dump', 'trace',
            // Дополнительные форматы для геофизических исследований
            'mwd', 'lwd', 'mud', 'well', 'curve', 'depth', 'temp', 'press',
            // Форматы данных с приборов (из реальной базы)
            'metro', 'thermo', 'calib', 'kis', 'air', 'okt'
        ]);
        
        if (!in_array($mimeType, $allowedMimeTypes) && !$isGeophysicalFile) {
            // Логируем для отладки
            $this->logSecurityEvent('file_mime_type_check', [
                'user_id' => $_SESSION['user']['id'],
                'file_name' => $fileName,
                'file_extension' => $fileExtension,
                'detected_mime_type' => $mimeType,
                'is_geophysical' => $isGeophysicalFile
            ]);
            
            $this->jsonResponse(['success' => false, 'message' => 'Недопустимый MIME-тип файла: ' . $mimeType], 400);
        }

        $fileData = file_get_contents($fileTmpPath);
        if ($fileData === false) {
            $this->jsonResponse(['success' => false, 'message' => 'Не удалось прочитать файл.'], 500);
        }

        // Ensure the table exists
        $tableName   = 'uploaded_files';
        $tableResult = $conn->query("SHOW TABLES LIKE '$tableName'");
        if (!$tableResult || $tableResult->num_rows === 0) {
            $createTableSQL = "CREATE TABLE `uploaded_files` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `field_name` varchar(255) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_data` longblob NOT NULL,
                `serial_number` varchar(255) NOT NULL,
                `case_number` varchar(255) NOT NULL,
                `device_type` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;";
            if (!$conn->query($createTableSQL)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Не удалось создать таблицу файлов.']);
                return;
            }
        }

        // Контрольная сумма нужна для защиты от повторной загрузки того же файла.
        // Её заполняют оба пути — веб-форма и API приложения, иначе проверка
        // дубля не увидела бы файлы, загруженные через сайт.
        //
        // Колонка не создаётся здесь: ALTER TABLE пересоздаёт таблицу целиком,
        // а в ней лежат файлы приборов. Структура готовится один раз скриптом
        // sql/2026-08-15-device-registry-api.sql; пока его не выполнили, загрузка
        // через сайт продолжает работать без контрольной суммы.
        $columnResult = $conn->query("SHOW COLUMNS FROM `uploaded_files` LIKE 'sha256'");
        $hasSha256Column = $columnResult && $columnResult->num_rows > 0;
        $fileSha256 = hash('sha256', $fileData);

        try {
            if ($hasSha256Column) {
                $stmt = $conn->prepare("INSERT INTO uploaded_files (field_name, file_name, file_data, serial_number, case_number, device_type, sha256, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('sssssss', $uploadedField, $fileName, $fileData, $serialNumber, $caseNumber, $deviceType, $fileSha256);
            } else {
                $stmt = $conn->prepare("INSERT INTO uploaded_files (field_name, file_name, file_data, serial_number, case_number, device_type, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('ssssss', $uploadedField, $fileName, $fileData, $serialNumber, $caseNumber, $deviceType);
            }

            $stmt->execute();
            
            // Логируем успешную загрузку файла
            $this->logSecurityEvent('file_uploaded', [
                'user_id' => $_SESSION['user']['id'],
                'field_name' => $uploadedField,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'serial_number' => $serialNumber,
                'case_number' => $caseNumber,
                'device_type' => $deviceType
            ]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Файл успешно загружен.', 'field' => $uploadedField]);
            $stmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('file_upload_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'file_name' => $fileName
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Ошибка при загрузке файла.'], 500);
        }
        $conn->close();
    }

    /**
     * List files (JSON)
     */
    public function list(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения списка файлов
        if (!$this->checkRateLimit($this->getClientIP(), 'file_list', 30, 60)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много запросов списка файлов'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса.'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $serialNumber = $this->sanitizeString($_POST['serial_number'] ?? '');
        $caseNumber   = $this->sanitizeString($_POST['case_number'] ?? '');
        $deviceType   = $this->sanitizeString($_POST['device_type'] ?? '');
        $fieldName    = $this->sanitizeString($_POST['field_name'] ?? '');
        
        if (empty($serialNumber) || empty($caseNumber) || empty($deviceType) || empty($fieldName)) {
            $this->jsonResponse(['success' => false, 'message' => 'Не все параметры были предоставлены.'], 400);
        }

        // Валидация поля по whitelist
        $allowedFields = ['thermoprofile', 'metrology', 'calibration', 'kis'];
        if (!in_array($fieldName, $allowedFields)) {
            $this->jsonResponse(['success' => false, 'message' => 'Недопустимое поле файла.'], 400);
        }

        // Ensure the table exists
        $tableName   = 'uploaded_files';
        $tableResult = $conn->query("SHOW TABLES LIKE '$tableName'");
        if (!$tableResult || $tableResult->num_rows === 0) {
            $createTableSQL = "CREATE TABLE `uploaded_files` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `field_name` varchar(255) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_data` longblob NOT NULL,
                `serial_number` varchar(255) NOT NULL,
                `case_number` varchar(255) NOT NULL,
                `device_type` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;";
            if (!$conn->query($createTableSQL)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Не удалось создать таблицу файлов.']);
                return;
            }
        }

        try {
            $stmt = $conn->prepare("SELECT id, file_name, created_at FROM uploaded_files
                WHERE serial_number = ? AND case_number = ? AND device_type = ? AND field_name = ?
                ORDER BY created_at DESC LIMIT 100");
            $stmt->bind_param('ssss', $serialNumber, $caseNumber, $deviceType, $fieldName);
            $stmt->execute();
            $result = $stmt->get_result();
            $files  = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
            
            // Логируем получение списка файлов
            $this->logSecurityEvent('file_list_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serialNumber,
                'case_number' => $caseNumber,
                'device_type' => $deviceType,
                'field_name' => $fieldName,
                'files_count' => count($files)
            ]);
            
            $this->jsonResponse(['success' => true, 'files' => $files, 'is_admin' => $isAdmin]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('file_list_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Ошибка при получении списка файлов.'], 500);
        }
        $conn->close();
    }

    /**
     * Download file
     */
    public function download(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для скачивания файлов
        if (!$this->checkRateLimit($this->getClientIP(), 'file_download', 50, 60)) {
            $this->handleError('Слишком много скачиваний файлов. Попробуйте через минуту.', [], 429);
        }

        require_once __DIR__ . '/../../db_connection.php';

        $fileId = $this->validateInt($_GET['id'] ?? 0, 1);
        if (!$fileId) {
            $this->handleError('Неверный запрос. ID файла не указан или некорректен.', [], 400);
        }

        try {
            $stmt = $conn->prepare("SELECT file_name, file_data, serial_number, case_number, device_type FROM uploaded_files WHERE id = ?");
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $fileName = $row['file_name'];
                $fileData = $row['file_data'];
                
                // Логируем скачивание файла
                $this->logSecurityEvent('file_downloaded', [
                    'user_id' => $_SESSION['user']['id'],
                    'file_id' => $fileId,
                    'file_name' => $fileName,
                    'serial_number' => $row['serial_number'],
                    'case_number' => $row['case_number'],
                    'device_type' => $row['device_type'],
                    'file_size' => strlen($fileData)
                ]);

                // Используем безопасный метод отправки файла
                $this->sendFileDownload($fileName, $fileData);
            } else {
                $this->handleError('Файл не найден.', ['file_id' => $fileId], 404);
            }
            $stmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('file_download_error', [
                'user_id' => $_SESSION['user']['id'],
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            $this->handleError('Ошибка при скачивании файла.', [], 500);
        }
        $conn->close();
    }

    /**
     * Delete file
     */
    public function delete(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для удаления файлов
        if (!$this->checkRateLimit($this->getClientIP(), 'file_delete', 20, 60)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много удалений файлов'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса.'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        $fileId = $this->validateInt($_POST['id'] ?? 0, 1);
        if (!$fileId) {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный ID файла.'], 400);
        }

        try {
            // Сначала получаем информацию о файле для логирования
            $selectStmt = $conn->prepare("SELECT file_name, serial_number, case_number, device_type FROM uploaded_files WHERE id = ?");
            $selectStmt->bind_param('i', $fileId);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            
            if ($result->num_rows === 0) {
                $selectStmt->close();
                $this->jsonResponse(['success' => false, 'message' => 'Файл с указанным ID не найден.'], 404);
            }
            
            $fileInfo = $result->fetch_assoc();
            $selectStmt->close();

            // Удаляем файл
            $deleteStmt = $conn->prepare("DELETE FROM uploaded_files WHERE id = ?");
            $deleteStmt->bind_param('i', $fileId);
            $deleteStmt->execute();

            if ($deleteStmt->affected_rows > 0) {
                // Логируем удаление файла
                $this->logSecurityEvent('file_deleted', [
                    'user_id' => $_SESSION['user']['id'],
                    'file_id' => $fileId,
                    'file_name' => $fileInfo['file_name'],
                    'serial_number' => $fileInfo['serial_number'],
                    'case_number' => $fileInfo['case_number'],
                    'device_type' => $fileInfo['device_type']
                ]);
                
                $this->jsonResponse(['success' => true, 'message' => 'Файл успешно удален.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Не удалось удалить файл.'], 500);
            }
            $deleteStmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('file_delete_error', [
                'user_id' => $_SESSION['user']['id'],
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Ошибка при удалении файла.'], 500);
        }
        $conn->close();
    }

    /**
     * Rename file
     */
    public function rename(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для переименования файлов
        if (!$this->checkRateLimit($this->getClientIP(), 'file_rename', 20, 60)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много переименований файлов'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Недопустимый метод запроса.'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $fileId       = $this->validateInt($_POST['id'] ?? 0, 1);
        $oldFileName  = $this->sanitizeString($_POST['old_file_name'] ?? '');
        $newFileName  = $this->sanitizeString($_POST['new_file_name'] ?? '');
        $createdAt    = $this->sanitizeString($_POST['created_at'] ?? '');
        $serialNumber = $this->sanitizeString($_POST['serial_number'] ?? '');
        $caseNumber   = $this->sanitizeString($_POST['case_number'] ?? '');
        $deviceType   = $this->sanitizeString($_POST['device_type'] ?? '');
        
        if (!$fileId || empty($oldFileName) || empty($newFileName) || empty($createdAt) || empty($serialNumber) || empty($caseNumber) || empty($deviceType)) {
            $this->jsonResponse(['success' => false, 'message' => 'Не все обязательные параметры указаны.'], 400);
        }

        // Дополнительная валидация имени файла
        $newFileName = basename($newFileName);
        if (strlen($newFileName) > 255) {
            $this->jsonResponse(['success' => false, 'message' => 'Имя файла слишком длинное.'], 400);
        }
        
        // Проверка на недопустимые символы (более строгая)
        if (!preg_match('/^[\p{L}\p{N}\s\.\-_()[\]]+$/u', $newFileName)) {
            $this->jsonResponse(['success' => false, 'message' => 'Новое имя файла содержит недопустимые символы.'], 400);
        }

        // Валидация расширения файла - используем тот же список что и для загрузки
        $allowedExtensions = [
            // Офисные документы
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'rtf',

            // Архивы
            'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz',
            
            // Изображения
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif',
            
            // Основные форматы геофизических данных (из реальной базы)
            'bin', 'dev', 'dat', 'raw', 'hex', 'cal', 'cfg', 'ini',
            
            // Каротажные данные
            'las', 'lis', 'dlis', 'segy', 'seg', 'sgd', 'wits',
            
            // Текстовые форматы данных
            'csv', 'log', 'ascii', 'asc', 'prn', 'out', 'res', 'rpt',
            
            // Конфигурационные и системные файлы приборов
            'xml', 'json', 'yaml', 'yml', 'conf', 'config', 'settings',
            
            // Специализированные форматы приборов
            'ram', 'rom', 'eep', 'flash', 'mem', 'dump', 'trace',
            
            // Дополнительные форматы для геофизических исследований
            'mwd', 'lwd', 'mud', 'well', 'curve', 'depth', 'temp', 'press',
            
            // Форматы данных с приборов
            'metro', 'thermo', 'calib', 'kis', 'air', 'okt'
        ];
        
        $fileExtension = strtolower(pathinfo($newFileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $this->jsonResponse(['success' => false, 'message' => 'Недопустимое расширение файла. Разрешены: ' . implode(', ', array_slice($allowedExtensions, 0, 20)) . '...'], 400);
        }

        try {
            $stmt = $conn->prepare("UPDATE uploaded_files
                SET file_name = ?
                WHERE id = ?
                  AND file_name = ?
                  AND created_at = ?
                  AND serial_number = ?
                  AND case_number = ?
                  AND device_type = ?");
            $stmt->bind_param('sisssss', $newFileName, $fileId, $oldFileName, $createdAt, $serialNumber, $caseNumber, $deviceType);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                // Логируем переименование файла
                $this->logSecurityEvent('file_renamed', [
                    'user_id' => $_SESSION['user']['id'],
                    'file_id' => $fileId,
                    'old_name' => $oldFileName,
                    'new_name' => $newFileName,
                    'serial_number' => $serialNumber,
                    'case_number' => $caseNumber,
                    'device_type' => $deviceType
                ]);
                
                $this->jsonResponse(['success' => true, 'message' => 'Файл успешно переименован.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Не удалось найти файл для переименования или новое имя совпадает со старым.'], 404);
            }
            $stmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('file_rename_error', [
                'user_id' => $_SESSION['user']['id'],
                'file_id' => $fileId,
                'old_name' => $oldFileName,
                'new_name' => $newFileName,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Ошибка при переименовании файла.'], 500);
        }
        $conn->close();
    }
} 