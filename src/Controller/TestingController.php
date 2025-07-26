<?php
namespace App\Controller;

use App\Controller\BaseController;

class TestingController extends BaseController
{
    /**
     * Create testing data record
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания записей испытаний (30 записей в час для активной работы с испытаниями)
        if (!$this->checkRateLimit($this->getClientIP(), 'testing_create', 30, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много попыток создания записей испытаний'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка базовых полей
        $serial_number    = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number      = $this->sanitizeString($_POST['case_number'] ?? '');
        $creator          = $this->sanitizeString($_POST['creator_surname'] ?? '');
        $temperature_test = $this->sanitizeString($_POST['temperature_test'] ?? '');
        $vibration_test   = $this->sanitizeString($_POST['vibration_test'] ?? '');
        $test_result      = $this->sanitizeString($_POST['test_result'] ?? '', 500);

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['success' => false, 'message' => 'Не указаны серийный номер или номер корпуса.'], 400);
        }

        // Валидация длины результата испытания
        if (strlen($test_result) > 500) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Результат испытания слишком длинный (максимум 500 символов)'
            ], 400);
        }

        // Собираем массив числовых метрик (float)
        $floats = [];
        foreach ([400, 2000] as $freq) {
            foreach (['x1','y1','z1','x2','y2','z2'] as $col) {
                $key = "noise_sko_{$col}_{$freq}";
                $val = $_POST[$key] ?? '';
                $floats[$key] = $val === '' ? null : (float)$val;
            }
        }
        foreach (['mds_transmitter','second_harmonic','third_harmonic','non_harmonic_components','phase_difference_drift'] as $base) {
            foreach ([400, 2000] as $freq) {
                for ($i = 1; $i <= 5; $i++) {
                    $key = "{$base}_{$i}_{$freq}";
                    $val = $_POST[$key] ?? '';
                    $floats[$key] = $val === '' ? null : (float)$val;
                }
            }
        }

        // Формируем список столбцов и плейсхолдеров
        $columns = ['serial_number','case_number','creator','temperature_test','vibration_test'];
        $columns = array_merge($columns, array_keys($floats));
        $columns[] = 'test_result';
        $placeholders = array_fill(0, count($columns), '?');
        $sql = 'INSERT INTO testing_data (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        // Формируем параметры и типы
        $params = [$serial_number, $case_number, $creator, $temperature_test, $vibration_test];
        $types = 'sssss';
        foreach ($floats as $val) {
            $params[] = $val;
            $types   .= 'd';
        }
        $params[] = $test_result;
        $types   .= 's';

        try {
            executeQuery($conn, $sql, $params, $types);
            
            // Логируем успешное создание записи испытаний
            $this->logSecurityEvent('testing_created', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'test_result' => $test_result,
                'metrics_count' => count($floats)
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'test_result_status' => $test_result,
                'message' => 'Данные испытаний успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            $this->logSecurityEvent('testing_create_error', [
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
     * Fetch testing history (JSON)
     */
    public function history(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения истории испытаний (30 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'testing_history', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории испытаний'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $serial_number = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number   = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['error' => 'Не указаны серийный номер или номер корпуса.'], 400);
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM testing_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC LIMIT 100",
                [$serial_number, $case_number],
                'ss'
            );
            
            // Логируем получение истории испытаний
            $this->logSecurityEvent('testing_history_accessed', [
                'user_id' => $_SESSION['user']['id'],
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'records_count' => count($data)
            ]);
            
            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->logSecurityEvent('testing_history_error', [
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

    /**
     * Update testing record (optional)
     */
    public function update(): void
    {
        // можно реализовать по аналогии с RepairController::update
        echo json_encode(['success' => false, 'message' => 'Метод обновления не реализован.']);
    }
} 