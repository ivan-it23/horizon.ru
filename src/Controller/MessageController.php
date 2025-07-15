<?php
namespace App\Controller;

use App\Controller\BaseController;

class MessageController extends BaseController
{
    /**
     * Send a message
     */
    public function send(): void
    {
        session_start();
        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Метод не разрешен. Используйте POST.'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (empty($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $fromId = (int)$_SESSION['user']['id'];
        $toId   = isset($_POST['to_id']) ? (int)$_POST['to_id'] : 0;
        $msg    = isset($_POST['message']) ? trim($_POST['message']) : '';
        if ($toId <= 0 || $msg === '') {
            echo json_encode(['success' => false, 'error' => 'Некорректные данные'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $stmt->bind_param('iis', $fromId, $toId, $msg);
        if ($stmt->execute()) {
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    /**
     * Get sent messages (JSON)
     */
    public function sent(): void
    {
        session_start();
        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $userId   = (int)$_SESSION['user']['id'];
        $response = ['success' => false, 'data' => [], 'error' => null];
        try {
            $sql = "SELECT m.id, m.message, DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS sent_at, u.lastname AS recipient_lastname, u.firstname AS recipient_firstname, u.middlename AS recipient_middlename FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = ? ORDER BY m.created_at DESC";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $sentMessages = [];
            while ($row = $result->fetch_assoc()) {
                $row['recipient_fullname'] = trim(($row['recipient_lastname'] ?? '') . ' ' . ($row['recipient_firstname'] ?? '') . ' ' . ($row['recipient_middlename'] ?? ''));
                unset($row['recipient_lastname'], $row['recipient_firstname'], $row['recipient_middlename']);
                $sentMessages[] = $row;
            }
            $response['success'] = true;
            $response['data']    = $sentMessages;
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            $response['error'] = 'Ошибка получения отправленных сообщений: ' . $e->getMessage();
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Mark messages read (JSON)
     */
    public function markRead(): void
    {
        session_start();
        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Метод не разрешен. Используйте POST.'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (empty($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $userId   = (int)$_SESSION['user']['id'];
        $response = ['success' => false, 'error' => null];
        try {
            $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0');
            if (!$stmt) throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            $stmt->bind_param('i', $userId);
            if ($stmt->execute()) {
                $response['success'] = true;
            } else {
                throw new \Exception('Ошибка выполнения запроса: ' . $stmt->error);
            }
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            $response['error'] = 'Ошибка сервера при пометке сообщений как прочитанных: ' . $e->getMessage();
            error_log('Ошибка в MessageController::markRead: ' . $e->getMessage());
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Get received messages (JSON)
     */
    public function received(): void
    {
        session_start();
        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $userId   = (int)$_SESSION['user']['id'];
        $response = ['success' => false, 'data' => [], 'error' => null];
        try {
            $sql = "SELECT m.id, m.message, DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS received_at, u.lastname AS sender_lastname, u.firstname AS sender_firstname, u.middlename AS sender_middlename, m.is_read FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $receivedMessages = [];
            while ($row = $result->fetch_assoc()) {
                $row['sender_fullname'] = trim(($row['sender_lastname'] ?? '') . ' ' . ($row['sender_firstname'] ?? '') . ' ' . ($row['sender_middlename'] ?? ''));
                unset($row['sender_lastname'], $row['sender_firstname'], $row['sender_middlename']);
                $receivedMessages[] = $row;
            }
            $response['success'] = true;
            $response['data']    = $receivedMessages;
            $stmt->close();
        } catch (\Exception $e) {
            http_response_code(500);
            $response['error'] = 'Ошибка получения входящих сообщений: ' . $e->getMessage();
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    /**
     * Get list of other users for messaging (migrated from get_users.php)
     */
    public function users(): void
    {
        session_start();
        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user']['id'])) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $currentId = (int)$_SESSION['user']['id'];
        $stmt = $conn->prepare('SELECT id, firstname, lastname, middlename FROM users WHERE id <> ?');
        $stmt->bind_param('i', $currentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users, JSON_UNESCAPED_UNICODE);
        exit();
    }
    /**
     * Fetch current user's admin status (migrated from fetch_user_role.php)
     */
    public function role(): void
    {
        session_start();
        header('Content-Type: application/json; charset=utf-8');
        if (isset($_SESSION['user']['is_admin'])) {
            echo json_encode(['is_admin' => (bool)$_SESSION['user']['is_admin']]);
        } else {
            echo json_encode(['is_admin' => false]);
        }
        exit();
    }
} 