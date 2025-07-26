<?php
namespace App\Controller;

use App\Controller\BaseController;

class InspectionController extends BaseController
{
    /**
     * Create NDT record
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания записей контроля (15 записей в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'inspection_create', 15, 60)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много запросов на создание записей контроля'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка входных данных
        $inspection_date   = $this->sanitizeString($_POST['inspection_date'] ?? '');
        $inspection_result = $this->sanitizeString($_POST['inspection_result'] ?? '', 1000);
        $creator_surname   = $this->sanitizeString($_POST['creator_surname'] ?? '');
        $serial_number     = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number       = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($inspection_date) || empty($inspection_result) || empty($creator_surname) || empty($serial_number) || empty($case_number)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Ошибка: Не все обязательные поля были заполнены.'
            ], 400);
        }

        // Валидация длины результата контроля
        if (strlen($inspection_result) > 1000) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Результат контроля слишком длинный (максимум 1000 символов)'
            ], 400);
        }

        try {
            $sql = "INSERT INTO inspection_data (inspection_date, inspection_result, creator_surname, serial_number, case_number)"
                 . " VALUES (?, ?, ?, ?, ?)";
            executeQuery(
                $conn,
                $sql,
                [$inspection_date, $inspection_result, $creator_surname, $serial_number, $case_number],
                'sssss'
            );
            
            // Логируем успешное создание записи контроля
            $this->logSecurityEvent('inspection_created', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'inspection_date' => $inspection_date
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Данные успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('inspection_create_error', [
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
     * Fetch NDT history (JSON)
     */
    public function history(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории контроля (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'inspection_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории контроля'], 429);
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
                "SELECT * FROM inspection_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC LIMIT 100",
                [$serial_number, $case_number],
                'ss'
            );
            
            // Логируем получение истории контроля
            $this->logSecurityEvent('inspection_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->logSecurityEvent('inspection_history_error', [
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