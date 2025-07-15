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
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $inspection_date   = $_POST['inspection_date']   ?? '';
        $inspection_result = $_POST['inspection_result'] ?? '';
        $creator_surname   = $_POST['creator_surname']   ?? '';
        $serial_number     = $_POST['serial_number']     ?? '';
        $case_number       = $_POST['case_number']       ?? '';

        if (empty($inspection_date) || empty($inspection_result) || empty($creator_surname) || empty($serial_number) || empty($case_number)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка: Не все обязательные поля были заполнены.'
            ]);
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
            return;
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
            echo json_encode([
                'success' => true,
                'message' => 'Данные успешно сохранены!'
            ]);
        } catch (\Exception $e) {
            error_log('InspectionController::create error: ' . $e->getMessage());
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
     * Fetch NDT history (JSON)
     */
    public function history(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $serial_number = $_POST['serial_number'] ?? '';
        $case_number   = $_POST['case_number']   ?? '';

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM inspection_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC",
                [$serial_number, $case_number],
                'ss'
            );
            echo json_encode($data);
        } catch (\Exception $e) {
            error_log('InspectionController::history error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Ошибка загрузки истории: ' . $e->getMessage()
            ]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }
} 