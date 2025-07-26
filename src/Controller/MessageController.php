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
        // Проверка авторизации
        $this->requireAuth();
        
        // Проверка метода запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Метод не разрешен. Используйте POST.'], 405);
        }
        
        // Rate limiting для отправки сообщений (не более 10 сообщений в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'message_send', 10, 60)) {
            $this->jsonResponse(['success' => false, 'error' => 'Слишком много сообщений. Попробуйте через минуту.'], 429);
        }

        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';

        $fromId = (int)$_SESSION['user']['id'];
        $toId   = $this->validateInt($_POST['to_id'] ?? 0, 1);
        $msg    = $this->sanitizeString($_POST['message'] ?? '', 1000); // Ограничиваем длину сообщения

        if (!$toId || empty($msg)) {
            $this->jsonResponse(['success' => false, 'error' => 'Некорректные данные']);
        }

        // Проверяем, что получатель существует
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE id = ?');
        $checkStmt->bind_param('i', $toId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            $this->jsonResponse(['success' => false, 'error' => 'Получатель не найден']);
        }
        $checkStmt->close();

        try {
            $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
            if (!$stmt) {
                throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            }
            
            $stmt->bind_param('iis', $fromId, $toId, $msg);
            
            if ($stmt->execute()) {
                // Логируем отправку сообщения
                $this->logSecurityEvent('message_sent', [
                    'from_user_id' => $fromId,
                    'to_user_id' => $toId,
                    'message_length' => strlen($msg)
                ]);
                
                $this->jsonResponse(['success' => true]);
            } else {
                throw new \Exception('Ошибка выполнения запроса: ' . $stmt->error);
            }
            $stmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('message_send_error', [
                'from_user_id' => $fromId,
                'to_user_id' => $toId,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'error' => 'Ошибка отправки сообщения'], 500);
        }
    }

    /**
     * Get sent messages (JSON)
     */
    public function sent(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения сообщений
        if (!$this->checkRateLimit($this->getClientIP(), 'message_list', 30, 60)) {
            $this->jsonResponse(['success' => false, 'error' => 'Слишком много запросов списка сообщений'], 429);
        }

        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';

        $userId = (int)$_SESSION['user']['id'];
        
        try {
            $sql = "SELECT m.id, m.message, DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS sent_at, u.lastname AS recipient_lastname, u.firstname AS recipient_firstname, u.middlename AS recipient_middlename FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = ? ORDER BY m.created_at DESC LIMIT 100";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            }
            
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $sentMessages = [];
            
            while ($row = $result->fetch_assoc()) {
                $row['recipient_fullname'] = trim(($row['recipient_lastname'] ?? '') . ' ' . ($row['recipient_firstname'] ?? '') . ' ' . ($row['recipient_middlename'] ?? ''));
                unset($row['recipient_lastname'], $row['recipient_firstname'], $row['recipient_middlename']);
                $sentMessages[] = $row;
            }
            
            $stmt->close();
            $this->jsonResponse(['success' => true, 'data' => $sentMessages]);
            
        } catch (\Exception $e) {
            $this->logSecurityEvent('message_sent_list_error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'error' => 'Ошибка получения отправленных сообщений'], 500);
        }
    }

    /**
     * Mark messages read (JSON)
     */
    public function markRead(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Проверка метода запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Метод не разрешен. Используйте POST.'], 405);
        }
        
        // Rate limiting для пометки сообщений как прочитанных
        if (!$this->checkRateLimit($this->getClientIP(), 'message_mark_read', 20, 60)) {
            $this->jsonResponse(['success' => false, 'error' => 'Слишком много запросов пометки сообщений'], 429);
        }

        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';

        $userId = (int)$_SESSION['user']['id'];
        
        try {
            $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0');
            if (!$stmt) {
                throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            }
            
            $stmt->bind_param('i', $userId);
            
            if ($stmt->execute()) {
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
                
                // Логируем пометку сообщений как прочитанных
                $this->logSecurityEvent('messages_marked_read', [
                    'user_id' => $userId,
                    'messages_count' => $affectedRows
                ]);
                
                $this->jsonResponse(['success' => true, 'marked_count' => $affectedRows]);
            } else {
                throw new \Exception('Ошибка выполнения запроса: ' . $stmt->error);
            }
        } catch (\Exception $e) {
            $this->logSecurityEvent('message_mark_read_error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'error' => 'Ошибка сервера при пометке сообщений как прочитанных'], 500);
        }
    }

    /**
     * Get received messages (JSON)
     */
    public function received(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения сообщений
        if (!$this->checkRateLimit($this->getClientIP(), 'message_list', 30, 60)) {
            $this->jsonResponse(['success' => false, 'error' => 'Слишком много запросов списка сообщений'], 429);
        }

        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';

        $userId = (int)$_SESSION['user']['id'];
        
        try {
            $sql = "SELECT m.id, m.message, DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS received_at, u.lastname AS sender_lastname, u.firstname AS sender_firstname, u.middlename AS sender_middlename, m.is_read FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC LIMIT 100";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception('Ошибка подготовки запроса: ' . $conn->error);
            }
            
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $receivedMessages = [];
            
            while ($row = $result->fetch_assoc()) {
                $row['sender_fullname'] = trim(($row['sender_lastname'] ?? '') . ' ' . ($row['sender_firstname'] ?? '') . ' ' . ($row['sender_middlename'] ?? ''));
                unset($row['sender_lastname'], $row['sender_firstname'], $row['sender_middlename']);
                $receivedMessages[] = $row;
            }
            
            $stmt->close();
            $this->jsonResponse(['success' => true, 'data' => $receivedMessages]);
            
        } catch (\Exception $e) {
            $this->logSecurityEvent('message_received_list_error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'error' => 'Ошибка получения входящих сообщений'], 500);
        }
    }
    /**
     * Get list of other users for messaging (migrated from get_users.php)
     */
    public function users(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения списка пользователей
        if (!$this->checkRateLimit($this->getClientIP(), 'users_list', 20, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов списка пользователей'], 429);
        }

        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';

        $currentId = (int)$_SESSION['user']['id'];
        
        try {
            $stmt = $conn->prepare('SELECT id, firstname, lastname, middlename FROM users WHERE id <> ? ORDER BY lastname, firstname');
            $stmt->bind_param('i', $currentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = [];
            
            while ($row = $result->fetch_assoc()) {
                $users[] = [
                    'id' => (int)$row['id'],
                    'firstname' => $this->sanitizeString($row['firstname']),
                    'lastname' => $this->sanitizeString($row['lastname']),
                    'middlename' => $this->sanitizeString($row['middlename'])
                ];
            }
            
            $stmt->close();
            $this->jsonResponse($users);
            
        } catch (\Exception $e) {
            $this->logSecurityEvent('users_list_error', [
                'user_id' => $currentId,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['error' => 'Ошибка получения списка пользователей'], 500);
        }
    }
    /**
     * Fetch current user's admin status (migrated from fetch_user_role.php)
     */
    public function role(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения роли пользователя
        if (!$this->checkRateLimit($this->getClientIP(), 'user_role', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов роли пользователя'], 429);
        }

        $isAdmin = isset($_SESSION['user']['is_admin']) ? (bool)$_SESSION['user']['is_admin'] : false;
        
        // Логируем запрос роли (только для админов, чтобы отслеживать потенциальные попытки эскалации привилегий)
        if ($isAdmin) {
            $this->logSecurityEvent('admin_role_check', [
                'user_id' => $_SESSION['user']['id']
            ]);
        }
        
        $this->jsonResponse(['is_admin' => $isAdmin]);
    }
} 