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
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        // Базовые поля
        $serial_number    = $_POST['serial_number'] ?? '';
        $case_number      = $_POST['case_number'] ?? '';
        $creator          = $_POST['creator_surname'] ?? '';
        $temperature_test = $_POST['temperature_test'] ?? null;
        $vibration_test   = $_POST['vibration_test'] ?? null;
        $test_result      = $_POST['test_result'] ?? '';

        if (empty($serial_number) || empty($case_number)) {
            echo json_encode(['success' => false, 'message' => 'Не указаны серийный номер или номер корпуса.']);
            return;
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
            echo json_encode([
                'success' => true,
                'test_result_status' => $test_result,
                'message' => 'Данные испытаний успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            error_log('TestingController::create error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении данных: ' . $e->getMessage()]);
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
        header('Content-Type: application/json; charset=utf-8');
        // Suppress PHP warnings to avoid breaking JSON
        error_reporting(0);
        ini_set('display_errors', 0);
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        $serial_number = $_POST['serial_number'] ?? '';
        $case_number   = $_POST['case_number']   ?? '';

        if (empty($serial_number) || empty($case_number)) {
            echo json_encode(['error' => 'Не указаны серийный номер или номер корпуса.']);
            return;
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM testing_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC",
                [$serial_number, $case_number],
                'ss'
            );
            echo json_encode($data);
        } catch (\Exception $e) {
            error_log('TestingController::history error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки истории: ' . $e->getMessage()]);
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