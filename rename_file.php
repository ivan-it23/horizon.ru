<?php
session_start();
include 'db_connection.php'; // Убедитесь, что этот файл содержит подключение к вашей базе данных

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user'])) {
     $response['message'] = 'Вы не авторизованы.';
     echo json_encode($response);
     exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fileId = $_POST['id'] ?? null;
    $oldFileName = $_POST['old_file_name'] ?? null; // Добавлено для дополнительной проверки
    $newFileName = $_POST['new_file_name'] ?? null;
    $createdAt = $_POST['created_at'] ?? null; // Дата создания файла
    $serialNumber = $_POST['serial_number'] ?? null;
    $caseNumber = $_POST['case_number'] ?? null;
    $deviceType = $_POST['device_type'] ?? null;

    if (!$fileId || !$oldFileName || !$newFileName || !$createdAt || !$serialNumber || !$caseNumber || !$deviceType) {
        $response['message'] = 'Не все обязательные параметры для переименования файла указаны.';
        echo json_encode($response);
        exit();
    }

    // Проверка на корректность нового имени файла (например, отсутствие недопустимых символов)
    // Здесь можно добавить более строгие правила валидации имени файла
    if (empty(trim($newFileName))) {
        $response['message'] = 'Новое имя файла не может быть пустым.';
        echo json_encode($response);
        exit();
    }
    // Удаляем потенциально опасные символы или пути из имени файла
    $newFileName = basename($newFileName);
    // Дополнительная валидация на символы, если нужно
    if (preg_match('/[^\p{L}\p{N}\s\.\-_()\[\]]/u', $newFileName)) { // Разрешаем буквы, цифры, пробелы, точки, дефисы, подчеркивания, скобки
        $response['message'] = 'Новое имя файла содержит недопустимые символы.';
        echo json_encode($response);
        exit();
    }


    try {
        // Подготовка запроса для обновления имени файла.
        // Используем ID, старое имя файла, created_at и параметры прибора
        // для точной идентификации записи, чтобы избежать случайного переименования
        // файла с таким же именем, но для другого прибора или другой датой создания.
        $stmt = $conn->prepare("UPDATE uploaded_files SET file_name = ?
                               WHERE id = ?
                               AND file_name = ?
                               AND created_at = ?
                               AND serial_number = ?
                               AND case_number = ?
                               AND device_type = ?");

        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . $conn->error);
        }

        $stmt->bind_param("sisssss", $newFileName, $fileId, $oldFileName, $createdAt, $serialNumber, $caseNumber, $deviceType);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Файл успешно переименован.';
        } else if ($stmt->affected_rows === 0) {
            // Это может произойти, если файл уже был переименован, или параметры не совпали
            $response['message'] = 'Не удалось найти файл для переименования или новое имя совпадает со старым.';
        } else {
            throw new Exception("Ошибка выполнения запроса: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Ошибка базы данных: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Недопустимый метод запроса.';
}

echo json_encode($response);
?>
