<?php
session_start();
include 'db_connection.php'; // Убедитесь, что этот файл содержит подключение к вашей базе данных

header('Content-Type: application/json'); // Устанавливаем заголовок для JSON-ответа

$response = ['success' => false, 'message' => 'Неизвестная ошибка.'];

// Проверка авторизации (очень важно для безопасности!)
// В реальном приложении здесь должна быть более сложная проверка прав пользователя
if (!isset($_SESSION['user'])) {
    $response['message'] = 'Доступ запрещен. Пользователь не авторизован.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $fileId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($fileId)) {
        // Подготовленное выражение для удаления файла
        $stmt = $conn->prepare("DELETE FROM uploaded_files WHERE id = ?");
        $stmt->bind_param("i", $fileId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Файл успешно удален.';
            } else {
                $response['message'] = 'Файл с указанным ID не найден.';
            }
        } else {
            $response['message'] = 'Ошибка выполнения запроса к базе данных: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Неверный ID файла.';
    }
} else {
    $response['message'] = 'Неверный метод запроса или отсутствуют параметры.';
}

$conn->close();
echo json_encode($response);
?>
