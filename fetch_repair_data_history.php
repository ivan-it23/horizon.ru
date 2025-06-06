<?php
include 'db_connection.php';

// Set the Content-Type header to indicate that the browser should expect JSON
header('Content-Type: application/json');

$response = array(); // Array to build the JSON response

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

// Use prepared statements for a secure query
$sql = "SELECT * FROM repair_data
        WHERE serial_number = ?
        AND case_number = ?
        ORDER BY created_at DESC"; // Sort by creation date (newest first)

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param('ss', $serial_number, $case_number);
    $stmt->execute();
    $result = $stmt->get_result();

    $history_data = array();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history_data[] = $row;
        }
    }

    // Return the history data as an array
    echo json_encode($history_data);

    $stmt->close();
} else {
    $response['error'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Error preparing query for fetch_repair_data_history: " . $conn->error);
    echo json_encode($response);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
