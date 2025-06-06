<?php
include 'db_connection.php';

// Set the Content-Type header
header('Content-Type: application/json');

$response = array();

// Check if serial_number and case_number were provided
if (!isset($_POST['serial_number']) || !isset($_POST['case_number'])) {
    $response['error'] = "Отсутствуют серийный номер или номер корпуса.";
    echo json_encode($response);
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit();
}

$serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
$case_number = mysqli_real_escape_string($conn, $_POST['case_number']);

// SQL query to get the most recent record
$sql = "SELECT * FROM repair_data
        WHERE serial_number = ?
        AND case_number = ?
        ORDER BY created_at DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param('ss', $serial_number, $case_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $latest_data = $result->fetch_assoc();
        echo json_encode($latest_data);
    } else {
        // If no record is found, return an empty object
        echo json_encode(new stdClass());
    }

    $stmt->close();
} else {
    $response['error'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Error preparing query for fetch_latest_repair_data: " . $conn->error);
    echo json_encode($response);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
