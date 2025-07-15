<?php
namespace App\Service;

/**
 * Класс для работы с информацией о приборах
 * Получение данных о приборах и их составе
 */
class DeviceInfoManager
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Получает информацию о приборе по серийному номеру (краткая)
     */
    public function getDeviceInfoBySerial($serialNumber): array
    {
        try {
            // Ищем прибор в таблице device_identification по серийному номеру
            $sql = "SELECT * FROM device_identification WHERE serial_number = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->bind_param('s', $serialNumber);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return [
                    'success' => false,
                    'message' => "Прибор с серийным номером {$serialNumber} не найден в базе данных. Пожалуйста, проверьте номер и попробуйте снова."
                ];
            }

            $device = $result->fetch_assoc();
            $stmt->close();

            // Получаем информацию о составе прибора по device_id
            $sql_composition = "SELECT * FROM device_composition WHERE device_id = ?";
            $stmt_composition = $this->conn->prepare($sql_composition);
            if ($stmt_composition) {
                $stmt_composition->bind_param('i', $device['id']);
                $stmt_composition->execute();
                $composition_result = $stmt_composition->get_result();
                $composition = $composition_result->fetch_all(MYSQLI_ASSOC);
                $stmt_composition->close();
            } else {
                $composition = [];
            }

            // Получаем информацию о ремонтах по серийному номеру
            $sql_repairs = "SELECT * FROM repair_data WHERE serial_number = ? ORDER BY admission_date DESC";
            $stmt_repairs = $this->conn->prepare($sql_repairs);
            if ($stmt_repairs) {
                $stmt_repairs->bind_param('s', $serialNumber);
                $stmt_repairs->execute();
                $repairs_result = $stmt_repairs->get_result();
                $repairs = $repairs_result->fetch_all(MYSQLI_ASSOC);
                $stmt_repairs->close();
            } else {
                $repairs = [];
            }

            return [
                'success' => true,
                'device' => $device,
                'composition' => $composition,
                'repairs' => $repairs
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает информацию о приборе по номеру корпуса (краткая)
     */
    public function getDeviceInfoByCase($caseNumber): array
    {
        try {
            // Ищем прибор в таблице device_identification по номеру корпуса
            $sql = "SELECT * FROM device_identification WHERE case_number = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->bind_param('s', $caseNumber);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return [
                    'success' => false,
                    'message' => "Прибор с номером корпуса {$caseNumber} не найден в базе данных. Пожалуйста, проверьте номер и попробуйте снова."
                ];
            }

            $device = $result->fetch_assoc();
            $stmt->close();

            // Получаем информацию о составе прибора по device_id
            $sql_composition = "SELECT * FROM device_composition WHERE device_id = ?";
            $stmt_composition = $this->conn->prepare($sql_composition);
            if ($stmt_composition) {
                $stmt_composition->bind_param('i', $device['id']);
                $stmt_composition->execute();
                $composition_result = $stmt_composition->get_result();
                $composition = $composition_result->fetch_all(MYSQLI_ASSOC);
                $stmt_composition->close();
            } else {
                $composition = [];
            }

            // Получаем информацию о ремонтах по номеру корпуса
            $sql_repairs = "SELECT * FROM repair_data WHERE case_number = ? ORDER BY admission_date DESC";
            $stmt_repairs = $this->conn->prepare($sql_repairs);
            if ($stmt_repairs) {
                $stmt_repairs->bind_param('s', $caseNumber);
                $stmt_repairs->execute();
                $repairs_result = $stmt_repairs->get_result();
                $repairs = $repairs_result->fetch_all(MYSQLI_ASSOC);
                $stmt_repairs->close();
            } else {
                $repairs = [];
            }

            return [
                'success' => true,
                'device' => $device,
                'composition' => $composition,
                'repairs' => $repairs
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает полную информацию о приборе по серийному номеру или номеру корпуса
     */
    public function getFullDeviceInfo($serialNumber = null, $caseNumber = null): array
    {
        try {
            $device = null;
            $searchIdentifier = null;
            $searchField = null;

            if ($serialNumber) {
                $sql = "SELECT * FROM device_identification WHERE serial_number = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('s', $serialNumber);
                $searchIdentifier = $serialNumber;
                $searchField = 'serial_number';
            } elseif ($caseNumber) {
                $sql = "SELECT * FROM device_identification WHERE case_number = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('s', $caseNumber);
                $searchIdentifier = $caseNumber;
                $searchField = 'case_number';
            } else {
                return [
                    'success' => false,
                    'message' => 'Не указан серийный номер или номер корпуса для поиска полной информации. Пожалуйста, уточните ваш запрос.'
                ];
            }

            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return [
                    'success' => false,
                    'message' => "Прибор с идентификатором {$searchIdentifier} не найден в базе данных. Пожалуйста, проверьте номер и попробуйте снова."
                ];
            }

            $device = $result->fetch_assoc();
            $stmt->close();

            // Получаем все связанные данные
            $data = [
                'device' => $device,
                'composition' => $this->fetchRelatedData('device_composition', 'device_id', $device['id']),
                'repairs' => $this->fetchRelatedData('repair_data', 'serial_number', $device['serial_number'], 'case_number', $device['case_number'], 'admission_date DESC'),
                'production_discontinuation' => $this->fetchRelatedData('production_discontinuation', 'serial_number', $device['serial_number'], 'case_number', $device['case_number']),
                'testing_data' => $this->fetchRelatedData('testing_data', 'serial_number', $device['serial_number'], 'case_number', $device['case_number']),
                'inspection_data' => $this->fetchRelatedData('inspection_data', 'serial_number', $device['serial_number'], 'case_number', $device['case_number']),
                'termination_data' => $this->fetchRelatedData('termination_data', 'serial_number', $device['serial_number'], 'case_number', $device['case_number']),
                'additional_data' => $this->fetchRelatedData('additional_data', 'serial_number', $device['serial_number'], 'case_number', $device['case_number'])
            ];

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Вспомогательная функция для получения связанных данных
     */
    private function fetchRelatedData($tableName, $primaryKey, $primaryValue, $secondaryKey = null, $secondaryValue = null, $orderBy = null): array
    {
        $data = [];
        $sql = "SELECT * FROM {$tableName} WHERE {$primaryKey} = ?";
        $params = [$primaryValue];
        $types = 's';

        if ($secondaryKey && $secondaryValue) {
            $sql .= " OR {$secondaryKey} = ?";
            $params[] = $secondaryValue;
            $types .= 's';
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            // Dynamically bind parameters based on their types
            $bindParams = [];
            $bindParams[] = &$types;
            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);

            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        return $data;
    }
} 