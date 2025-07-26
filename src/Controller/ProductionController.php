<?php
namespace App\Controller;

use App\Controller\BaseController;

class ProductionController extends BaseController
{
    /**
     * Create production discontinuation record
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания записей о прекращении производства (10 записей в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'production_create', 10, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много запросов на создание записей о прекращении производства'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка входных данных
        $discontinuation_date = $this->sanitizeString($_POST['discontinuation_date'] ?? '');
        $creator_surname      = $this->sanitizeString($_POST['creator_surname'] ?? '');
        $serial_number        = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number          = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($discontinuation_date) || empty($creator_surname) || empty($serial_number) || empty($case_number)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Ошибка: Не указаны все обязательные поля.'
            ], 400);
        }

        try {
            $sql = "INSERT INTO production_discontinuation
                    (discontinuation_date, creator_surname, serial_number, case_number)
                    VALUES (?, ?, ?, ?)";
            executeQuery(
                $conn,
                $sql,
                [$discontinuation_date, $creator_surname, $serial_number, $case_number],
                'ssss'
            );
            
            // Логируем успешное создание записи о прекращении производства
            $this->logSecurityEvent('production_discontinuation_created', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'discontinuation_date' => $discontinuation_date
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Данные успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('production_discontinuation_create_error', [
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
     * Fetch production discontinuation history (JSON)
     */
    public function history(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории прекращения производства (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'production_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории прекращения производства'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $serial_number = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number   = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['error' => 'Ошибка: Не указан серийный номер или номер корпуса.'], 400);
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM production_discontinuation WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC LIMIT 100",
                [$serial_number, $case_number],
                'ss'
            );
            
            // Логируем получение истории прекращения производства
            $this->logSecurityEvent('production_discontinuation_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse(['history' => $data]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('production_discontinuation_history_error', [
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