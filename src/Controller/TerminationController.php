<?php
namespace App\Controller;

use App\Controller\BaseController;

class TerminationController extends BaseController
{
    /**
     * Create termination record
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания записей о прекращении эксплуатации (10 записей в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'termination_create', 10, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много запросов на создание записей о прекращении эксплуатации'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка входных данных
        $termination_date   = $this->sanitizeString($_POST['termination_date'] ?? '');
        $termination_reason = $this->sanitizeString($_POST['termination_reason'] ?? '', 1000);
        $creator_surname    = $this->sanitizeString($_POST['creator_surname'] ?? '');
        $serial_number      = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number        = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Ошибка: Не указан серийный номер или номер корпуса.'
            ], 400);
        }

        // Валидация длины причины прекращения эксплуатации
        if (strlen($termination_reason) > 1000) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Причина прекращения эксплуатации слишком длинная (максимум 1000 символов)'
            ], 400);
        }

        try {
            $sql = "INSERT INTO termination_data (termination_date, termination_reason, creator_surname, serial_number, case_number)"
                 . " VALUES (?, ?, ?, ?, ?)";
            executeQuery(
                $conn,
                $sql,
                [$termination_date, $termination_reason, $creator_surname, $serial_number, $case_number],
                'sssss'
            );
            
            // Логируем успешное создание записи о прекращении эксплуатации
            $this->logSecurityEvent('termination_created', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'termination_date' => $termination_date
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Данные успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('termination_create_error', [
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
     * Fetch termination history (JSON)
     */
    public function history(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории прекращения эксплуатации (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'termination_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории прекращения эксплуатации'], 429);
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
                "SELECT * FROM termination_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC LIMIT 100",
                [$serial_number, $case_number],
                'ss'
            );
            
            // Логируем получение истории прекращения эксплуатации
            $this->logSecurityEvent('termination_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->logSecurityEvent('termination_history_error', [
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