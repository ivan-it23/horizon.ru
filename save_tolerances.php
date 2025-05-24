<?php
header('Content-Type: application/json');
require 'db_connection.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['deviceType'], $data['tolerances'])) {
        throw new Exception("Неверный формат данных");
    }

    $deviceType = $data['deviceType'];
    $tolerances = $data['tolerances'];

    $tables = [
        'Автономный' => 'tolerances_autonomous',
        'LWD' => 'tolerances_lwd',
        'Картограф' => 'tolerances_cartographer'
    ];

    if (!isset($tables[$deviceType])) {
        throw new Exception("Неверный тип прибора: $deviceType");
    }

    $tableName = $tables[$deviceType];
    $conn->begin_transaction();

    // Очистка существующих записей
    if (!$conn->query("DELETE FROM $tableName")) {
        throw new Exception("Ошибка очистки таблицы: " . $conn->error);
    }

    $stmt = $conn->prepare("INSERT INTO $tableName
        (parameter, frequency, column_name, min_value, max_value)
        VALUES (?, ?, ?, ?, ?)");

    foreach ($tolerances as $parameter => $freqData) {
        foreach ($freqData as $frequency => $columns) {
            foreach ($columns as $column => $values) {
                $min = $values['min'] !== null ? (float)$values['min'] : null;
                $max = $values['max'] !== null ? (float)$values['max'] : null;

                $stmt->bind_param(
                    "sisdd",
                    $parameter,
                    $frequency,
                    $column,
                    $min,
                    $max
                );

                if (!$stmt->execute()) {
                    throw new Exception("Ошибка вставки [$parameter, $frequency, $column]: " . $stmt->error);
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn) $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

$conn->close();
?>