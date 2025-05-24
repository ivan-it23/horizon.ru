<?php
include 'db_connection.php';

if (isset($_POST['device_id'])) {
    $deviceId = $_POST['device_id'];

    $noise_sko = $_POST['noise_sko'] ?? '';
    $mds_transmitter = $_POST['mds_transmitter'] ?? '';
    $level_harmonics = $_POST['level_harmonics'] ?? '';
    $level_nonharmonics = $_POST['level_nonharmonics'] ?? '';
    $temp_test = $_POST['temp_test'] ?? '';
    $phase_drift = $_POST['phase_drift'] ?? '';
    $vibration_data = $_POST['vibration_data'] ?? '';
    $thermal_file = $_POST['thermal_file'] ?? '';
    $metrology_file = $_POST['metrology_file'] ?? '';
    $calibration_file = $_POST['calibration_file'] ?? '';
    $serial_number = $_POST['serial_number'] ?? '';
    $case_number = $_POST['case_number'] ?? '';


    $stmt = $conn->prepare("UPDATE testing_data SET noise_sko = ?, mds_transmitter = ?, level_harmonics = ?, level_nonharmonics = ?, temp_test = ?, phase_drift = ?, vibration_data = ?, thermal_file = ?, metrology_file = ?, calibration_file = ?, serial_number = ?, case_number = ? WHERE device_id = ?");

    if (!$stmt) {
        die("Ошибка при подготовке запроса: " . $conn->error);
    }

    $stmt->bind_param("sssssssssssssi", $noise_sko, $mds_transmitter, $level_harmonics, $level_nonharmonics, $temp_test, $phase_drift, $vibration_data, $thermal_file, $metrology_file, $calibration_file, $serial_number, $case_number, $deviceId);


    if ($stmt->execute()) {
        echo "Данные успешно обновлены!";
    } else {
        echo "Ошибка при обновлении данных: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Ошибка: Недостаточно данных для обновления.";
}

$conn->close();
?>