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
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для получения допусков (200 запросов в минуту для работы с формой испытаний)
        if (!$this->checkRateLimit($this->getClientIP(), 'tolerance_get', 200, 60)) {
            $this->jsonResponse(['min' => null, 'max' => null, 'error' => 'Слишком много запросов допусков'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['min' => null, 'max' => null, 'error' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Валидация JSON данных
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logSecurityEvent('tolerance_get_invalid_json', [
                'user_id' => $_SESSION['user']['id'],
                'json_error' => json_last_error_msg()
            ]);
            $this->jsonResponse(['min' => null, 'max' => null, 'error' => 'Некорректные JSON данные'], 400);
        }

        // Безопасная валидация входных данных
        $deviceType = $this->sanitizeString($data['deviceType'] ?? '');
        $parameter  = $this->sanitizeString($data['parameter'] ?? '');
        $frequency  = $this->validateInt($data['frequency'] ?? 0, 1);
        $column     = $this->sanitizeString($data['column'] ?? '');

        $tables = [
            'Автономный' => 'tolerances_autonomous',
            'LWD'        => 'tolerances_lwd',
            'Картограф'  => 'tolerances_cartographer'
        ];

        if (empty($deviceType) || empty($parameter) || !$frequency || empty($column) || !isset($tables[$deviceType])) {
            $this->jsonResponse(['min' => null, 'max' => null]);
        }

        $tableName = $tables[$deviceType];

        try {
            $tableResult = $conn->query("SHOW TABLES LIKE '$tableName'");
            if (!$tableResult || $tableResult->num_rows === 0) {
                $this->jsonResponse(['min' => null, 'max' => null]);
            }

            $stmt = $conn->prepare(
                "SELECT min_value, max_value FROM $tableName WHERE parameter = ? AND frequency = ? AND column_name = ?"
            );
            if (!$stmt) {
                $this->jsonResponse(['min' => null, 'max' => null]);
            }
            
            $stmt->bind_param('sis', $parameter, $frequency, $column);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Логируем получение допусков
                $this->logSecurityEvent('tolerance_get_accessed', [
                    'user_id' => $_SESSION['user']['id'],
                    'device_type' => $deviceType,
                    'parameter' => $parameter,
                    'frequency' => $frequency,
                    'column' => $column
                ]);
                
                $this->jsonResponse(['min' => $row['min_value'], 'max' => $row['max_value']]);
            } else {
                $this->jsonResponse(['min' => null, 'max' => null]);
            }

            $stmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('tolerance_get_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'device_type' => $deviceType,
                'parameter' => $parameter
            ]);
            
            $this->handleError('Ошибка получения допусков: ' . $e->getMessage(), [
                'method' => 'get',
                'device_type' => $deviceType
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }

    /**
     * Save tolerance values for a device type
     */
    public function save(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для сохранения допусков (20 сохранений в час для активной работы с допусками)
        if (!$this->checkRateLimit($this->getClientIP(), 'tolerance_save', 20, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много попыток сохранения допусков'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }

        require_once __DIR__ . '/../../db_connection.php';

        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        // Валидация JSON данных
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logSecurityEvent('tolerance_save_invalid_json', [
                'user_id' => $_SESSION['user']['id'],
                'json_error' => json_last_error_msg()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Некорректные JSON данные'], 400);
        }

        // Безопасная валидация входных данных
        $deviceType = $this->sanitizeString($data['deviceType'] ?? '');
        $tolerances = $data['tolerances'] ?? null;

        $tables = [
            'Автономный' => 'tolerances_autonomous',
            'LWD'        => 'tolerances_lwd',
            'Картограф'  => 'tolerances_cartographer'
        ];

        if (empty($deviceType) || !isset($tables[$deviceType]) || !is_array($tolerances)) {
            $this->jsonResponse(['success' => false, 'message' => 'Некорректные входные данные'], 400);
        }

        $tableName = $tables[$deviceType];
        
        try {
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
                    $this->jsonResponse(['success' => false, 'message' => 'Не удалось создать таблицу допусков'], 500);
                }
            }

            $conn->begin_transaction();
            if (!$conn->query("DELETE FROM $tableName")) {
                $conn->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Не удалось очистить таблицу допусков'], 500);
            }

            $stmt = $conn->prepare(
                "INSERT INTO $tableName (parameter, frequency, column_name, min_value, max_value) VALUES (?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                $conn->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Не удалось подготовить запрос'], 500);
            }

            $savedCount = 0;
            foreach ($tolerances as $parameter => $freqData) {
                foreach ($freqData as $frequency => $columns) {
                    foreach ($columns as $columnName => $values) {
                        $min = isset($values['min']) && $values['min'] !== null ? (float)$values['min'] : null;
                        $max = isset($values['max']) && $values['max'] !== null ? (float)$values['max'] : null;
                        $stmt->bind_param('sisdd', $parameter, $frequency, $columnName, $min, $max);
                        if (!$stmt->execute()) {
                            $conn->rollback();
                            $this->jsonResponse(['success' => false, 'message' => 'Не удалось сохранить допуск'], 500);
                        }
                        $savedCount++;
                    }
                }
            }

            $conn->commit();
            
            // Логируем успешное сохранение допусков
            $this->logSecurityEvent('tolerance_saved', [
                'user_id' => $_SESSION['user']['id'],
                'device_type' => $deviceType,
                'tolerances_count' => $savedCount
            ]);
            
            $this->jsonResponse(['success' => true, 'message' => "Сохранено {$savedCount} допусков"]);

            $stmt->close();
        } catch (\Exception $e) {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->rollback();
            }
            
            $this->logSecurityEvent('tolerance_save_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'device_type' => $deviceType
            ]);
            
            $this->handleError('Ошибка сохранения допусков: ' . $e->getMessage(), [
                'method' => 'save',
                'device_type' => $deviceType
            ], 500);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }
} 