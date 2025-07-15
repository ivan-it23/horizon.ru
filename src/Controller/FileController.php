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
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Неверный метод запроса.']);
            return;
        }

        if (!isset($_POST['serial_number'], $_POST['case_number'], $_POST['device_type'])) {
            echo json_encode(['success' => false, 'message' => 'Не все параметры были предоставлены.']);
            return;
        }
        $serialNumber = trim($_POST['serial_number']);
        $caseNumber   = trim($_POST['case_number']);
        $deviceType   = trim($_POST['device_type']);

        $fields = ['thermoprofile', 'metrology', 'calibration', 'kis'];
        $uploadedField = null;
        foreach ($fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $uploadedField = $field;
                break;
            }
        }
        if (!$uploadedField) {
            echo json_encode(['success' => false, 'message' => 'Файл не был выбран или произошла ошибка при загрузке.']);
            return;
        }

        $fileName    = basename($_FILES[$uploadedField]['name']);
        $fileTmpPath = $_FILES[$uploadedField]['tmp_name'];
        if (!file_exists($fileTmpPath)) {
            echo json_encode(['success' => false, 'message' => 'Временный файл не найден.']);
            return;
        }
        $fileData = file_get_contents($fileTmpPath);
        if ($fileData === false) {
            echo json_encode(['success' => false, 'message' => 'Не удалось прочитать файл.']);
            return;
        }
        if ($_FILES[$uploadedField]['size'] > MAX_FILE_SIZE) {
            echo json_encode(['success' => false, 'message' => 'Размер файла превышает допустимый лимит.']);
            return;
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
            $stmt = $conn->prepare("INSERT INTO uploaded_files (field_name, file_name, file_data, serial_number, case_number, device_type, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param('ssssss', $uploadedField, $fileName, $fileData, $serialNumber, $caseNumber, $deviceType);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Файл успешно загружен.', 'field' => $uploadedField]);
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке файла.']);
        }
        $conn->close();
    }

    /**
     * List files (JSON)
     */
    public function list(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Неверный метод запроса.']);
            return;
        }
        $serialNumber = $_POST['serial_number'] ?? '';
        $caseNumber   = $_POST['case_number'] ?? '';
        $deviceType   = $_POST['device_type'] ?? '';
        $fieldName    = $_POST['field_name'] ?? '';
        if (empty($serialNumber) || empty($caseNumber) || empty($deviceType) || empty($fieldName)) {
            echo json_encode(['success' => false, 'message' => 'Не все параметры были предоставлены.']);
            return;
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
                ORDER BY created_at DESC");
            $stmt->bind_param('ssss', $serialNumber, $caseNumber, $deviceType, $fieldName);
            $stmt->execute();
            $result = $stmt->get_result();
            $files  = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
            echo json_encode(['success' => true, 'files' => $files, 'is_admin' => $isAdmin]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при получении списка файлов.']);
        }
        $conn->close();
    }

    /**
     * Download file
     */
    public function download(): void
    {
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        if (!isset($_SESSION['user'])) {
            echo "Доступ запрещен. Пожалуйста, авторизуйтесь.";
            return;
        }
        $fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$fileId) {
            echo "Неверный запрос. ID файла не указан или некорректен.";
            return;
        }

        try {
            $stmt = $conn->prepare("SELECT file_name, file_data FROM uploaded_files WHERE id = ?");
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($fileName, $fileData);
                $stmt->fetch();

                ob_clean();
                header("Content-Type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\"");
                header("Content-Length: " . strlen($fileData));

                echo $fileData;
                exit;
            } else {
                echo "Файл не найден.";
            }
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Ошибка: " . $e->getMessage();
        }
        $conn->close();
    }

    /**
     * Delete file
     */
    public function delete(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Неверный метод запроса.']);
            return;
        }
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Доступ запрещен. Пользователь не авторизован.']);
            return;
        }
        $fileId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$fileId) {
            echo json_encode(['success' => false, 'message' => 'Неверный ID файла.']);
            return;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM uploaded_files WHERE id = ?");
            $stmt->bind_param('i', $fileId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Файл успешно удален.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Файл с указанным ID не найден.']);
            }
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при удалении файла.']);
        }
        $conn->close();
    }

    /**
     * Rename file
     */
    public function rename(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Недопустимый метод запроса.']);
            return;
        }
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Вы не авторизованы.']);
            return;
        }
        $fileId       = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $oldFileName  = $_POST['old_file_name'] ?? '';
        $newFileName  = $_POST['new_file_name'] ?? '';
        $createdAt    = $_POST['created_at'] ?? '';
        $serialNumber = $_POST['serial_number'] ?? '';
        $caseNumber   = $_POST['case_number'] ?? '';
        $deviceType   = $_POST['device_type'] ?? '';
        if (!$fileId || empty($oldFileName) || empty(trim($newFileName)) || empty($createdAt) || empty($serialNumber) || empty($caseNumber) || empty($deviceType)) {
            echo json_encode(['success' => false, 'message' => 'Не все обязательные параметры указаны.']);
            return;
        }
        $newFileName = basename($newFileName);
        if (preg_match('/[^\p{L}\p{N}\s\.\-_()\[\]]/u', $newFileName)) {
            echo json_encode(['success' => false, 'message' => 'Новое имя файла содержит недопустимые символы.']);
            return;
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
                echo json_encode(['success' => true, 'message' => 'Файл успешно переименован.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Не удалось найти файл для переименования или новое имя совпадает со старым.']);
            }
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при переименовании файла.']);
        }
        $conn->close();
    }
} 