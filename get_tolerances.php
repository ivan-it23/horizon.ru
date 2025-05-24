<?php
header('Content-Type: application/json');
require 'db_connection.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log("Received " . print_r($data, true));

    if (!$data || !isset($data['deviceType'], $data['parameter'], $data['frequency'], $data['column'])) {
        throw new Exception("Недостаточно данных");
    }

    $deviceType = $data['deviceType'];
    $parameter = $data['parameter'];
    $frequency = (int)$data['frequency'];
    $column = $data['column'];

    $tables = [
        'Автономный' => 'tolerances_autonomous',
        'LWD' => 'tolerances_lwd',
        'Картограф' => 'tolerances_cartographer'
    ];

    if (!isset($tables[$deviceType])) {
        throw new Exception("Неверный тип прибора");
    }

    $tableName = $tables[$deviceType];

    $stmt = $conn->prepare("
        SELECT min_value, max_value
        FROM $tableName
        WHERE parameter = ?
          AND frequency = ?
          AND column_name = ?
    ");

    $stmt->bind_param("sis", $parameter, $frequency, $column);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'min' => $row['min_value'],
            'max' => $row['max_value']
        ]);
    } else {
        echo json_encode(['min' => null, 'max' => null]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>