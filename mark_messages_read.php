<?php
// mark_messages_read.php
declare(strict_types=1);
ob_start(); // Включаем буферизацию вывода
session_start();
header('Content-Type: application/json; charset=utf-8'); // Устанавливаем заголовок JSON

// Разрешаем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Метод не разрешен. Используйте POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

include 'db_connection.php'; // Подключаем соединение с БД

// Проверяем авторизацию пользователя
if (empty($_SESSION['user']['id'])) {
    ob_clean();
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user']['id']; // ID текущего пользователя (получателя)
$response = ['success' => false, 'error' => null]; // Структура ответа по умолчанию

try {
    // SQL-запрос для обновления статуса is_read
    $sql = "UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Ошибка подготовки запроса: " . $conn->error);
    }

    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        // Запрос выполнен успешно
        $response['success'] = true;
        // Можно добавить количество обновленных строк, если нужно
        // $response['affected_rows'] = $stmt->affected_rows;
    } else {
        // Ошибка выполнения запроса
        throw new Exception("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close(); // Закрываем statement

} catch (Exception $e) {
    // Ловим любые исключения во время выполнения
    http_response_code(500); // Internal Server Error
    $response['error'] = "Ошибка сервера при пометке сообщений как прочитанных: " . $e->getMessage();
    // Можно добавить логирование ошибки $e->getMessage()
    error_log("Ошибка в mark_messages_read.php для пользователя ID {$userId}: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close(); // Всегда закрываем соединение
    }
}

ob_clean(); // Очищаем буфер перед выводом JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE); // Выводим результат в формате JSON
exit;
