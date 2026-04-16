<?php
namespace App\Service;

/**
 * Сервис для получения структуры базы данных
 * Предоставляет контекст для LLM о схеме БД
 */
class DatabaseSchemaService
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Получает полный контекст базы данных для LLM
     */
    public function getDatabaseContextForLLM(): string
    {
        $context = "=== СТРУКТУРА БАЗЫ ДАННЫХ ===\n\n";
        
        // Основные таблицы и их поля
        $tables = [
            'device_identification' => [
                'description' => 'Основная таблица приборов',
                'fields' => [
                    'id' => 'int, PK',
                    'device_type' => "varchar - тип прибора: 'LWD', 'Картограф', 'Автономный'",
                    'serial_number' => 'varchar - серийный номер прибора',
                    'case_number' => 'varchar - номер корпуса',
                    'nominal_diameter' => 'varchar - номинальный диаметр',
                    'transmitter_count' => 'int - количество передатчиков',
                    'length_mm' => 'int - длина в мм',
                    'diameter_mm' => 'int - диаметр в мм',
                    'frequency1_khz' => 'decimal - рабочая частота 1',
                    'frequency2_khz' => 'decimal - рабочая частота 2',
                    'receiver_base1_mm' => 'int - база приёмника 1',
                    'transmitter_base1_mm' => 'int - база передатчика 1',
                    'creator_lastname' => 'varchar - фамилия создателя',
                    'record_date' => 'datetime - дата записи'
                ]
            ],
            'device_composition' => [
                'description' => 'Состав прибора (платы)',
                'fields' => [
                    'id' => 'int, PK',
                    'device_id' => 'int, FK -> device_identification.id',
                    'board_name' => 'varchar - название платы',
                    'type' => 'varchar - тип платы',
                    'version' => 'varchar - версия',
                    'serial_number' => 'varchar - серийный номер платы',
                    'creator_name' => 'varchar - создатель записи'
                ]
            ],
            'repair_data' => [
                'description' => 'Данные о ремонтах',
                'fields' => [
                    'id' => 'int, PK',
                    'serial_number' => 'varchar - серийный номер прибора',
                    'case_number' => 'varchar - номер корпуса',
                    'repair_reason' => 'text - причина ремонта',
                    'failure_reason' => 'text - причина неисправности',
                    'admission_date' => 'date - дата поступления',
                    'release_date' => 'date - дата выпуска',
                    'actions_description' => 'text - выполненные действия',
                    'repair_creator' => 'varchar - ответственный за ремонт'
                ]
            ],
            'production_discontinuation' => [
                'description' => 'Выход прибора из производства',
                'fields' => [
                    'id' => 'int, PK',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'discontinuation_date' => 'date',
                    'creator_surname' => 'varchar'
                ]
            ],
            'testing_data' => [
                'description' => 'Испытания приборов',
                'fields' => [
                    'id' => 'int, PK',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'test_date' => 'date',
                    'test_result' => 'text',
                    'creator_surname' => 'varchar'
                ]
            ],
            'inspection_data' => [
                'description' => 'Неразрушающий контроль',
                'fields' => [
                    'id' => 'int, PK',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'inspection_date' => 'date',
                    'inspection_result' => 'text',
                    'creator_surname' => 'varchar'
                ]
            ],
            'termination_data' => [
                'description' => 'Прекращение эксплуатации',
                'fields' => [
                    'id' => 'int, PK',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'termination_date' => 'date',
                    'termination_reason' => 'text',
                    'creator_surname' => 'varchar'
                ]
            ],
            'additional_data' => [
                'description' => 'Дополнительные сведения',
                'fields' => [
                    'id' => 'int, PK',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'additional_date' => 'date',
                    'additional_content' => 'text',
                    'creator_surname' => 'varchar'
                ]
            ]
        ];

        foreach ($tables as $tableName => $tableInfo) {
            $context .= "ТАБЛИЦА: {$tableName}\n";
            $context .= "Описание: {$tableInfo['description']}\n";
            $context .= "Поля:\n";
            foreach ($tableInfo['fields'] as $field => $description) {
                $context .= "  - {$field}: {$description}\n";
            }
            $context .= "\n";
        }

        $context .= "=== ПРИМЕРЫ ЗАПРОСОВ ===\n";
        $context .= "-- Количество приборов типа LWD:\n";
        $context .= "SELECT COUNT(*) FROM device_identification WHERE device_type = 'LWD';\n\n";
        $context .= "-- Последние ремонты:\n";
        $context .= "SELECT * FROM repair_data ORDER BY admission_date DESC LIMIT 5;\n\n";
        $context .= "-- Информация о приборе по серийному номеру:\n";
        $context .= "SELECT * FROM device_identification WHERE serial_number = '109';\n\n";
        $context .= "-- Все данные о ремонтах прибора:\n";
        $context .= "SELECT * FROM repair_data WHERE serial_number = '109';\n\n";

        return $context;
    }

    /**
     * Получает статистику БД для контекста
     */
    public function getDatabaseStats(): array
    {
        $stats = [];
        
        try {
            // Количество приборов
            $result = $this->conn->query("SELECT COUNT(*) as count FROM device_identification");
            $stats['total_devices'] = $result->fetch_assoc()['count'];

            // Количество по типам
            $result = $this->conn->query("SELECT device_type, COUNT(*) as count FROM device_identification GROUP BY device_type");
            $stats['by_type'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['by_type'][$row['device_type']] = $row['count'];
            }

            // Количество ремонтов
            $result = $this->conn->query("SELECT COUNT(*) as count FROM repair_data");
            $stats['total_repairs'] = $result->fetch_assoc()['count'];

            // Диапазон серийных номеров
            $result = $this->conn->query("SELECT MIN(CAST(serial_number AS UNSIGNED)) as min_serial, MAX(CAST(serial_number AS UNSIGNED)) as max_serial FROM device_identification WHERE serial_number REGEXP '^[0-9]+$'");
            $row = $result->fetch_assoc();
            $stats['serial_range'] = $row['min_serial'] . ' - ' . $row['max_serial'];

        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Выполняет SQL запрос и возвращает результат для LLM
     */
    public function executeQueryForLLM(string $sql): array
    {
        try {
            $result = $this->conn->query($sql);
            if (!$result) {
                return ['success' => false, 'error' => $this->conn->error];
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return ['success' => true, 'data' => $data, 'count' => count($data)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
