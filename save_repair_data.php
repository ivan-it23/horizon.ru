<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

$response = array();

// Check if all necessary data was sent
if (!isset($_POST['repair_type'], $_POST['repair_reason'], $_POST['admission_date'], $_POST['release_date'], $_POST['actions_description'], $_POST['repair_creator'], $_POST['serial_number'], $_POST['case_number'])) {
    $response['success'] = false;
    $response['message'] = "Отсутствуют необходимые данные для сохранения.";
    echo json_encode($response);
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit();
}

// Get data from the POST request
$repair_type = $_POST['repair_type'];
$repair_reason = $_POST['repair_reason'];
$failure_reason = isset($_POST['failure_reason']) ? $_POST['failure_reason'] : null;
$admission_date = $_POST['admission_date'];
$release_date = $_POST['release_date'];
$actions_description = $_POST['actions_description'];
$repair_creator = $_POST['repair_creator'];
$serial_number = $_POST['serial_number'];
$case_number = $_POST['case_number'];

// Use prepared statements for safe data insertion
$sql = "INSERT INTO repair_data (repair_type, repair_reason, failure_reason, admission_date, release_date, actions_description, repair_creator, serial_number, case_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind parameters. 'sssssssss' means 9 string parameters.
    $stmt->bind_param('sssssssss', $repair_type, $repair_reason, $failure_reason, $admission_date, $release_date, $actions_description, $repair_creator, $serial_number, $case_number);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Данные о ремонте успешно сохранены!";
    } else {
        $response['success'] = false;
        $response['message'] = "Ошибка при выполнении запроса: " . $stmt->error;
        error_log("Error executing INSERT into repair_data: " . $stmt->error);
    }

    $stmt->close();
} else {
    $response['success'] = false;
    $response['message'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Error preparing INSERT for repair_data: " . $conn->error);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Output the JSON response
echo json_encode($response);
?>
