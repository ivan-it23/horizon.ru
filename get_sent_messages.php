<?php
// get_sent_messages.php
declare(strict_types=1);
ob_start(); // Включаем буферизацию вывода
session_start();
header('Content-Type: application/json; charset=utf-8'); // Устанавливаем заголовок JSON

include 'db_connection.php'; // Подключаем соединение с БД

// Проверяем авторизацию пользователя
if (empty($_SESSION['user']['id'])) {
    ob_clean(); // Очищаем буфер вывода
    echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user']['id']; // ID текущего пользователя (отправителя)
$response = ['success' => false, 'data' => [], 'error' => null]; // Структура ответа по умолчанию

try {
    // SQL-запрос для получения отправленных сообщений
    $sql = "
        SELECT
            m.id,
            m.message,
            DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS sent_at,
            u.lastname AS recipient_lastname,
            u.firstname AS recipient_firstname,
            u.middlename AS recipient_middlename
        FROM messages m
        JOIN users u ON m.receiver_id = u.id -- Присоединяем таблицу users по ID получателя
        WHERE m.sender_id = ?               -- Фильтруем по ID отправителя (текущего пользователя)
        ORDER BY m.created_at DESC          -- Сортируем по дате отправки (сначала новые)
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Ошибка подготовки запроса: " . $conn->error);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $sent_messages = [];
    while ($row = $result->fetch_assoc()) {
        // Формируем полное имя получателя
        $row['recipient_fullname'] = trim(
            ($row['recipient_lastname'] ?? '') . ' ' .
            ($row['recipient_firstname'] ?? '') . ' ' .
            ($row['recipient_middlename'] ?? '')
        );
        // Удаляем отдельные поля имен, если они больше не нужны в ответе
        unset($row['recipient_lastname'], $row['recipient_firstname'], $row['recipient_middlename']);
        $sent_messages[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $sent_messages;

    $stmt->close(); // Закрываем statement

} catch (Exception $e) {
    // Ловим любые исключения во время выполнения
    $response['error'] = "Ошибка получения отправленных сообщений: " . $e->getMessage();
    // Можно добавить логирование ошибки $e->getMessage()
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close(); // Всегда закрываем соединение
    }
}

ob_clean(); // Очищаем буфер перед выводом JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE); // Выводим результат в формате JSON
exit;
