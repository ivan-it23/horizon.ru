<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['serial_number']) && isset($data['case_number'])) {
    $_SESSION['selected_device'] = [
        'serial_number' => $data['serial_number'],
        'case_number' => $data['case_number']
    ];
    http_response_code(200);
    echo json_encode(["status" => "success"]);
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Некорректные данные."]);
}
?>
