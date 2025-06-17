<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serialNumber = $_POST['serial_number'] ?? '';
    $caseNumber = $_POST['case_number'] ?? '';
    $deviceType = $_POST['device_type'] ?? '';
    $fieldName = $_POST['field_name'] ?? '';

    if (empty($serialNumber) || empty($caseNumber) || empty($deviceType) || empty($fieldName)) {
        echo json_encode(['success' => false, 'message' => 'Не все параметры были предоставлены.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, file_name, created_at FROM uploaded_files WHERE serial_number = ? AND case_number = ? AND device_type = ? AND field_name = ? ORDER BY created_at DESC");
    $stmt->bind_param("ssss", $serialNumber, $caseNumber, $deviceType, $fieldName);
    $stmt->execute();
    $result = $stmt->get_result();
    $files = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];

    echo json_encode(['success' => true, 'files' => $files, 'is_admin' => $isAdmin]);
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса.']);
}
?>
