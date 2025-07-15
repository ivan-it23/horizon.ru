<?php
namespace App\Controller;

use App\Controller\BaseController;

class ToleranceController extends BaseController
{
    /**
     * Fetch tolerance values for a given device type, parameter, frequency, and column
     */
    public function get(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $deviceType = $data['deviceType'] ?? null;
        $parameter  = $data['parameter']  ?? null;
        $frequency  = isset($data['frequency']) ? (int)$data['frequency'] : null;
        $column     = $data['column']     ?? null;

        $tables = [
            'Автономный' => 'tolerances_autonomous',
            'LWD'        => 'tolerances_lwd',
            'Картограф'  => 'tolerances_cartographer'
        ];

        if (!$deviceType || !$parameter || !$frequency || !$column || !isset($tables[$deviceType])) {
            echo json_encode(['min' => null, 'max' => null]);
            return;
        }

        $tableName = $tables[$deviceType];

        $tableResult = $conn->query("SHOW TABLES LIKE '$tableName'");
        if (!$tableResult || $tableResult->num_rows === 0) {
            echo json_encode(['min' => null, 'max' => null]);
            return;
        }

        $stmt = $conn->prepare(
            "SELECT min_value, max_value FROM $tableName WHERE parameter = ? AND frequency = ? AND column_name = ?"
        );
        if (!$stmt) {
            echo json_encode(['min' => null, 'max' => null]);
            return;
        }
        $stmt->bind_param('sis', $parameter, $frequency, $column);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo json_encode(['min' => $row['min_value'], 'max' => $row['max_value']]);
        } else {
            echo json_encode(['min' => null, 'max' => null]);
        }

        $stmt->close();
        $conn->close();
    }

    /**
     * Save tolerance values for a device type
     */
    public function save(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        $deviceType = $data['deviceType'] ?? null;
        $tolerances = $data['tolerances']  ?? null;

        $tables = [
            'Автономный' => 'tolerances_autonomous',
            'LWD'        => 'tolerances_lwd',
            'Картограф'  => 'tolerances_cartographer'
        ];

        if (!$deviceType || !isset($tables[$deviceType]) || !is_array($tolerances)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        $tableName = $tables[$deviceType];
        $tableResult = $conn->query("SHOW TABLES LIKE '$tableName'");
        if (!$tableResult || $tableResult->num_rows === 0) {
            $createTableSQL = "
                CREATE TABLE `$tableName` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `parameter` varchar(255) NOT NULL,
                  `frequency` int(11) NOT NULL,
                  `column_name` varchar(100) NOT NULL,
                  `min_value` decimal(10,2) DEFAULT NULL,
                  `max_value` decimal(10,2) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ";
            if (!$conn->query($createTableSQL)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create table']);
                return;
            }
        }

        $conn->begin_transaction();
        if (!$conn->query("DELETE FROM $tableName")) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to clear table']);
            return;
        }

        $stmt = $conn->prepare(
            "INSERT INTO $tableName (parameter, frequency, column_name, min_value, max_value) VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
            return;
        }

        foreach ($tolerances as $parameter => $freqData) {
            foreach ($freqData as $frequency => $columns) {
                foreach ($columns as $columnName => $values) {
                    $min = isset($values['min']) && $values['min'] !== null ? (float)$values['min'] : null;
                    $max = isset($values['max']) && $values['max'] !== null ? (float)$values['max'] : null;
                    $stmt->bind_param('sisdd', $parameter, $frequency, $columnName, $min, $max);
                    if (!$stmt->execute()) {
                        $conn->rollback();
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to insert tolerance']);
                        return;
                    }
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);

        $stmt->close();
        $conn->close();
    }
} 