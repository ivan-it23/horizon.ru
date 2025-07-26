<?php
namespace App\Controller;

use App\Controller\BaseController;

class RepairController extends BaseController
{
    /**
     * Create new repair record
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания записей о ремонте (10 записей в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'repair_create', 10, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много попыток создания записей о ремонте'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка входных данных
        $serial_number       = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number         = $this->sanitizeString($_POST['case_number'] ?? '');
        $repair_reason       = $this->sanitizeString($_POST['repair_reason'] ?? '', 500);
        $failure_reason      = $this->sanitizeString($_POST['failure_reason'] ?? '', 500);
        $admission_date      = $this->sanitizeString($_POST['admission_date'] ?? '');
        $release_date        = $this->sanitizeString($_POST['release_date'] ?? '');
        $actions_description = $this->sanitizeString($_POST['actions_description'] ?? '', 2000);
        $repair_creator      = $this->sanitizeString($_POST['repair_creator'] ?? '');
        $creator_id          = $_SESSION['user']['id'];

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['success' => false, 'message' => 'Ошибка: Не указан серийный номер или номер корпуса.'], 400);
        }

        // Валидация длины полей
        if (strlen($repair_reason) > 500) {
            $this->jsonResponse(['success' => false, 'message' => 'Причина ремонта слишком длинная (максимум 500 символов)'], 400);
        }
        if (strlen($failure_reason) > 500) {
            $this->jsonResponse(['success' => false, 'message' => 'Причина неисправности слишком длинная (максимум 500 символов)'], 400);
        }
        if (strlen($actions_description) > 2000) {
            $this->jsonResponse(['success' => false, 'message' => 'Описание действий слишком длинное (максимум 2000 символов)'], 400);
        }

        try {
            $sql = "INSERT INTO repair_data (serial_number, case_number, repair_reason, failure_reason, admission_date, release_date, actions_description, repair_creator, creator_id)"
                 . " VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?)";
            executeQuery(
                $conn,
                $sql,
                [$serial_number, $case_number, $repair_reason, $failure_reason, $admission_date, $release_date, $actions_description, $repair_creator, $creator_id],
                'ssssssssi'
            );
            
            // Логируем успешное создание записи о ремонте
            $this->logSecurityEvent('repair_created', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'repair_creator' => $repair_creator
            ]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Новая запись о ремонте успешно создана!']);
        } catch (\Exception $e) {
            $this->logSecurityEvent('repair_create_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ]);
            
            $this->handleError('Ошибка при создании записи: ' . $e->getMessage(), [
                'method' => 'create',
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Update existing repair record
     */
    public function update(): void
    {
        // Очищаем буфер вывода для предотвращения проблем с JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для обновления записей о ремонте (15 обновлений в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'repair_update', 15, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много попыток обновления записей о ремонте'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация ID записи
        $repair_id = $this->validateInt($_POST['id'] ?? 0, 1);
        if (!$repair_id) {
            $this->jsonResponse(['success' => false, 'message' => 'Отсутствует или некорректный ID записи.'], 400);
        }
        
        $user_id = $_SESSION['user']['id'];
        
        // Безопасная валидация и очистка входных данных
        $new_data = [
            'repair_reason'      => $this->sanitizeString($_POST['repair_reason'] ?? '', 500),
            'failure_reason'     => $this->sanitizeString($_POST['failure_reason'] ?? '', 500),
            'admission_date'     => $this->sanitizeString($_POST['admission_date'] ?? ''),
            'release_date'       => $this->sanitizeString($_POST['release_date'] ?? ''),
            'actions_description'=> $this->sanitizeString($_POST['actions_description'] ?? '', 2000)
        ];

        // Валидация длины полей
        if (strlen($new_data['repair_reason']) > 500) {
            $this->jsonResponse(['success' => false, 'message' => 'Причина ремонта слишком длинная (максимум 500 символов)'], 400);
        }
        if (strlen($new_data['failure_reason']) > 500) {
            $this->jsonResponse(['success' => false, 'message' => 'Причина неисправности слишком длинная (максимум 500 символов)'], 400);
        }
        if (strlen($new_data['actions_description']) > 2000) {
            $this->jsonResponse(['success' => false, 'message' => 'Описание действий слишком длинное (максимум 2000 символов)'], 400);
        }

        try {
            $conn->begin_transaction();
            // Get old data
            $sqlSelect = "SELECT repair_reason, failure_reason, admission_date, release_date, actions_description FROM repair_data WHERE id = ?";
            $stmt = $conn->prepare($sqlSelect);
            $stmt->bind_param('i', $repair_id);
            $stmt->execute();
            $old_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$old_data) {
                throw new \Exception('Запись с ID ' . $repair_id . ' не найдена');
            }

            // Insert history
            $sqlHist = "INSERT INTO repair_data_history (repair_data_id, repair_reason, failure_reason, admission_date, release_date, actions_description, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlHist);
            $stmt->bind_param('isssssi', $repair_id, $old_data['repair_reason'], $old_data['failure_reason'], $old_data['admission_date'], $old_data['release_date'], $old_data['actions_description'], $user_id);
            $stmt->execute();
            $stmt->close();

            // Update main record
            $sqlUpdate = "UPDATE repair_data SET repair_reason = ?, failure_reason = ?, admission_date = NULLIF(?, ''), release_date = NULLIF(?, ''), actions_description = ? WHERE id = ?";
            $stmt = $conn->prepare($sqlUpdate);
            $stmt->bind_param('sssssi', $new_data['repair_reason'], $new_data['failure_reason'], $new_data['admission_date'], $new_data['release_date'], $new_data['actions_description'], $repair_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            
            // Логируем успешное обновление записи о ремонте
            $this->logSecurityEvent('repair_updated', [
                'user_id' => $_SESSION['user']['id'],
                'repair_id' => $repair_id
            ]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Изменения успешно сохранены!']);
        } catch (\Exception $e) {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->rollback();
            }
            
            $this->logSecurityEvent('repair_update_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'repair_id' => $repair_id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Ошибка при сохранении изменений: ' . $e->getMessage()
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Fetch repair history (JSON)
     */
    public function history(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории ремонтов (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'repair_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории ремонтов'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $serial_number = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number   = $this->sanitizeString($_POST['case_number'] ?? '');
        
        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['error' => 'Отсутствуют серийный номер или номер корпуса.'], 400);
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM repair_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC LIMIT 100",
                [$serial_number, $case_number],
                'ss'
            );
            
            // Логируем получение истории ремонтов
            $this->logSecurityEvent('repair_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->logSecurityEvent('repair_history_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ]);
            
            $this->handleError('Ошибка загрузки истории: ' . $e->getMessage(), [
                'method' => 'history',
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Fetch edit history for a repair record (JSON)
     */
    public function editHistory(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории правок (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'repair_edit_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории правок'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация ID записи
        $repair_id = $this->validateInt($_GET['id'] ?? 0, 1);
        if (!$repair_id) {
            $this->jsonResponse(['error' => 'ID записи не указан или некорректен.'], 400);
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT h.*, u.lastname, u.firstname, u.middlename
                 FROM repair_data_history h
                 LEFT JOIN users u ON h.changed_by_user_id = u.id
                 WHERE h.repair_data_id = ?
                 ORDER BY h.changed_at DESC
                 LIMIT 100",
                [$repair_id],
                'i'
            );
            
            // Логируем получение истории правок
            $this->logSecurityEvent('repair_edit_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'repair_id' => $repair_id,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->logSecurityEvent('repair_edit_history_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'repair_id' => $repair_id
            ]);
            
            $this->handleError('Ошибка загрузки истории правок: ' . $e->getMessage(), [
                'method' => 'editHistory',
                'repair_id' => $repair_id
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Delete a repair record and its history (JSON)
     */
    public function delete(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для удаления записей о ремонте (5 удалений в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'repair_delete', 5, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много попыток удаления записей о ремонте'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация ID записи
        $repair_id = $this->validateInt($_POST['id'] ?? 0, 1);
        if (!$repair_id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID записи не указан или некорректен.'], 400);
        }

        try {
            // Получаем информацию о записи перед удалением для логирования
            $repairInfo = fetchResults(
                $conn,
                "SELECT serial_number, case_number FROM repair_data WHERE id = ?",
                [$repair_id],
                'i'
            );
            
            // Delete history first
            executeQuery($conn,
                'DELETE FROM repair_data_history WHERE repair_data_id = ?',
                [$repair_id], 'i'
            );
            // Delete main record
            executeQuery($conn,
                'DELETE FROM repair_data WHERE id = ?',
                [$repair_id], 'i'
            );
            
            // Логируем успешное удаление записи о ремонте
            $this->logSecurityEvent('repair_deleted', [
                'user_id' => $_SESSION['user']['id'],
                'repair_id' => $repair_id,
                'serial_number' => $repairInfo[0]['serial_number'] ?? 'unknown',
                'case_number' => $repairInfo[0]['case_number'] ?? 'unknown'
            ]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Запись и история удалены.']);
        } catch (\Exception $e) {
            $this->logSecurityEvent('repair_delete_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'repair_id' => $repair_id
            ]);
            
            $this->handleError('Ошибка при удалении: ' . $e->getMessage(), [
                'method' => 'delete',
                'repair_id' => $repair_id
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }
} 