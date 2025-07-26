<?php
namespace App\Controller;

use App\Controller\BaseController;

class AdditionalDataController extends BaseController
{
    /**
     * Create additional data record
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания дополнительных данных (20 записей в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'additional_data_create', 20, 60)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много запросов на создание данных'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка входных данных
        $additional_date    = $this->sanitizeString($_POST['additional_date'] ?? '');
        $additional_content = $this->sanitizeString($_POST['additional_content'] ?? '', 2000);
        $creator_surname    = $this->sanitizeString($_POST['creator_surname'] ?? '');
        $serial_number      = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number        = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Ошибка: Не указан серийный номер или номер корпуса.'
            ], 400);
        }

        // Валидация длины содержимого
        if (strlen($additional_content) > 2000) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Содержимое слишком длинное (максимум 2000 символов)'
            ], 400);
        }

        try {
            $sql = "INSERT INTO additional_data
                    (additional_date, additional_content, creator_surname, serial_number, case_number)
                    VALUES (?, ?, ?, ?, ?)";
            executeQuery(
                $conn,
                $sql,
                [$additional_date, $additional_content, $creator_surname, $serial_number, $case_number],
                'sssss'
            );
            
            // Логируем успешное создание дополнительных данных
            $this->logSecurityEvent('additional_data_created', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'content_length' => strlen($additional_content)
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Данные успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('additional_data_create_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ]);
            
            $this->handleError('Ошибка при сохранении данных: ' . $e->getMessage(), [
                'method' => 'create',
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }

    /**
     * Fetch additional data history (JSON)
     */
    public function history(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'additional_data_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $serial_number = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number   = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['error' => 'Не указан серийный номер или номер корпуса'], 400);
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM additional_data
                 WHERE serial_number = ? AND case_number = ?
                 ORDER BY created_at DESC
                 LIMIT 100",
                [$serial_number, $case_number],
                'ss'
            );
            
            // Логируем получение истории дополнительных данных
            $this->logSecurityEvent('additional_data_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->logSecurityEvent('additional_data_history_error', [
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
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }
} 