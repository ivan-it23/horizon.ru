<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $device_type = $_POST['device_type'];
    $nominal_diameter = $_POST['nominal_diameter'];
    $transmitter_count = $_POST['transmitter_count'];
    $serial_number = $_POST['serial_number'];
    $case_number = $_POST['case_number'];
    $length_mm = $_POST['length_mm'];
    $diameter_mm = $_POST['diameter_mm'];
    $upper_thread = $_POST['upper_thread'];
    $lower_thread = $_POST['lower_thread'];
    $frequency1_khz = $_POST['frequency1_khz'];
    $frequency2_khz = $_POST['frequency2_khz'];
    $receiver_base1_mm = $_POST['receiver_base1_mm'];
    $receiver_base2_mm = $_POST['receiver_base2_mm'];
    $receiver_base3_mm = $_POST['receiver_base3_mm'];
    $record_date = $_POST['record_date'];


    // Если переменные могут быть пустыми, нужно привести их к null, если они пустые
    $serial_number = empty($serial_number) ? null : $serial_number;
    $case_number = empty($case_number) ? null : $case_number;
    $upper_thread = empty($upper_thread) ? null : $upper_thread;
    $lower_thread = empty($lower_thread) ? null : $lower_thread;
    $frequency1_khz = empty($frequency1_khz) ? null : $frequency1_khz;
    $frequency2_khz = empty($frequency2_khz) ? null : $frequency2_khz;
    $length_mm = empty($length_mm) ? null : $length_mm;
    $diameter_mm = empty($diameter_mm) ? null : $diameter_mm;
    $receiver_base1_mm = empty($receiver_base1_mm) ? null : $receiver_base1_mm;
    $receiver_base2_mm = empty($receiver_base2_mm) ? null : $receiver_base2_mm;
    $receiver_base3_mm = empty($receiver_base3_mm) ? null : $receiver_base3_mm;
    $record_date = empty($record_date) ? null : $record_date;


    // Подготовленный запрос для вставки данных
    $stmt = $conn->prepare("INSERT INTO device_identification
              (device_type, nominal_diameter, transmitter_count, serial_number, case_number,
              length_mm, diameter_mm, upper_thread, lower_thread, frequency1_khz, frequency2_khz,
              receiver_base1_mm, receiver_base2_mm, receiver_base3_mm, record_date, creator_lastname)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");

    // Проверяем на успешность подготовки запроса
    if ($stmt === false) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }
      $creator_lastname = isset($_SESSION['user']) ?  $_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename'] : '';
    // Приводим данные к нужным типам
    $stmt->bind_param(
        "siiiiidddddddss",
        $device_type, $nominal_diameter, $transmitter_count,
        $serial_number, $case_number, $length_mm, $diameter_mm,
        $upper_thread, $lower_thread, $frequency1_khz, $frequency2_khz,
        $receiver_base1_mm, $receiver_base2_mm, $receiver_base3_mm,
        $record_date, $creator_lastname
    );

    // Выполняем запрос
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    // Закрытие подготовленного выражения
    $stmt->close();
}
?>


