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
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $discontinuation_date = $_POST['discontinuation_date'] ?? '';
        $creator_surname      = $_POST['creator_surname']      ?? '';
        $serial_number        = $_POST['serial_number']        ?? '';
        $case_number          = $_POST['case_number']          ?? '';

        if (empty($discontinuation_date) || empty($creator_surname) || empty($serial_number) || empty($case_number)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка: Не указаны все обязательные поля.'
            ]);
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
            return;
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
            echo json_encode([
                'success' => true,
                'message' => 'Данные успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            error_log('ProductionController::create error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка при сохранении данных: ' . $e->getMessage()
            ]);
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
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $serial_number = $_POST['serial_number'] ?? '';
        $case_number   = $_POST['case_number']   ?? '';

        if (empty($serial_number) || empty($case_number)) {
            echo json_encode(['error' => 'Ошибка: Не указан серийный номер или номер корпуса.']);
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
            return;
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM production_discontinuation WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC",
                [$serial_number, $case_number],
                'ss'
            );
            echo json_encode(['history' => $data]);
        } catch (\Exception $e) {
            error_log('ProductionController::history error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки истории: ' . $e->getMessage()]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }
} 