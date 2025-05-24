<?php
session_start();
include 'db_connection.php';

// Проверка соединения
if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Устанавливаем кодировку
mysqli_set_charset($conn, 'utf8');

// Получаем параметры
$device_id = intval($_GET['device_id'] ?? 0);
$serial_number = mysqli_real_escape_string($conn, $_GET['serial_number'] ?? '');
$case_number = mysqli_real_escape_string($conn, $_GET['case_number'] ?? '');

// Заголовки для Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=device_{$serial_number}_{$case_number}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Функция для генерации таблиц
function generateTable($data, $title) {
    if(empty($data)) return '';

    $html = "<h2>$title</h2>";
    $html .= '<table border="1"><tr>';

    // Заголовки
    foreach(array_keys($data[0]) as $header) {
        $html .= "<th>" . htmlspecialchars($header) . "</th>";
    }
    $html .= "</tr>";

    // Данные
    foreach($data as $row) {
        $html .= "<tr>";
        foreach($row as $cell) {
            $html .= "<td>" . htmlspecialchars($cell) . "</td>";
        }
        $html .= "</tr>";
    }

    $html .= "</table><br>";
    return $html;
}

try {
    // 1. Device Identification
    $query = "SELECT * FROM device_identification WHERE id = $device_id";
    $result = mysqli_query($conn, $query);
    $device = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // 2. Device Composition
    $query = "SELECT * FROM device_composition WHERE device_id = $device_id";
    $result = mysqli_query($conn, $query);
    $composition = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // 3. Device History
    $query = "SELECT * FROM device_history WHERE device_id = $device_id";
    $result = mysqli_query($conn, $query);
    $history = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $query = "SELECT * FROM device_composition_history WHERE device_id = $device_id";
    $result = mysqli_query($conn, $query);
    $composition_history = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // 4. Другие таблицы
    $tables = [
        'production_discontinuation' => "Production Discontinuation",
        'testing_data' => "Testing Data",
        'repair_data' => "Repair Data",
        'inspection_data' => "Inspection Data",
        'termination_data' => "Termination Data",
        'additional_data' => "Additional Data"
    ];

    $output = "<html><meta charset='UTF-8'><body>";

    // Основные таблицы
    $output .= generateTable($device, 'Device Identification');
    $output .= generateTable($composition, 'Device Composition');
    $output .= generateTable($history, 'Device History');
    $output .= generateTable($composition_history, 'Device Composition History');

    // Остальные таблицы
    foreach($tables as $table => $title) {
        $query = "SELECT * FROM $table
                WHERE serial_number = '$serial_number'
                AND case_number = '$case_number'";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $output .= generateTable($data, $title);
    }

    $output .= "</body></html>";
    echo $output;

} catch(Exception $e) {
    die("Ошибка генерации отчета: " . $e->getMessage());
} finally {
    mysqli_close($conn);
}
?>